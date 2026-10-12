<?php

namespace App\Support\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Backups completos num único .zip:
 *   database.sql            banco inteiro (gerado em PHP: não depende do mysqldump, funciona em hospedagem)
 *   env/.env                configuração do site
 *   files/public-storage/   arquivos enviados pelo painel (logos, banners...) — public/storage
 *   files/storage-app/      demais arquivos da aplicação — storage/app (sem os próprios backups)
 *   manifest.json           versão, data, banco, contagens
 *
 * Usado por: php artisan system:backup | system:restore | system:update | system:uninstall,
 * pela página Sistema do painel e pelos assistentes (instalar.sh / instalar-windows.bat).
 */
class BackupManager
{
    public const PREFIX = 'casino-backup-';

    public const FORMAT = 'casino-backup-v1';

    /** Chaves do .env que acompanham os dados (sessões, tokens e senhas criptografadas dependem delas). */
    public const KEY_VARIABLES = ['APP_KEY', 'JWT_SECRET', 'PRIVATE_KEY'];

    private const INSERT_ROWS = 500;

    private const INSERT_BYTES = 512 * 1024;

    public function defaultDirectory(): string
    {
        return storage_path('app/backups');
    }

    /* ------------------------------------------------------------------ */
    /* Criar                                                               */
    /* ------------------------------------------------------------------ */

    /**
     * @param  callable(string): void|null  $progress
     * @return array{path: string, name: string, size: int, tables: int, rows: int, files: int, seconds: float}
     */
    public function create(?string $directory = null, bool $withFiles = true, ?callable $progress = null): array
    {
        @set_time_limit(0);
        $progress ??= static fn (string $m) => null;
        $started = microtime(true);

        $directory = rtrim($directory ?: $this->defaultDirectory(), '/\\');
        if (!is_dir($directory) && !@mkdir($directory, 0750, true)) {
            throw new RuntimeException("Não consegui criar a pasta de backup: {$directory}");
        }
        if (!is_writable($directory)) {
            throw new RuntimeException("Sem permissão para gravar em {$directory}");
        }
        $this->protectDirectory($directory);

        $name = self::PREFIX . date('Ymd-His') . '.zip';
        $zipPath = $directory . DIRECTORY_SEPARATOR . $name;
        $sqlPath = storage_path('framework/backup-' . bin2hex(random_bytes(6)) . '.sql');

        try {
            $progress('Copiando o banco de dados');
            [$tables, $rows] = $this->dumpDatabase($sqlPath);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Não consegui criar {$zipPath}");
            }
            $zip->addFile($sqlPath, 'database.sql');
            if (is_file(base_path('.env'))) {
                $zip->addFile(base_path('.env'), 'env/.env');
            }

            $files = 0;
            if ($withFiles) {
                $progress('Copiando os arquivos enviados');
                $files += $this->addDirectory($zip, public_path('storage'), 'files/public-storage');
                $files += $this->addDirectory($zip, storage_path('app'), 'files/storage-app', [$this->defaultDirectory()]);
            }

            $zip->addFromString('manifest.json', json_encode([
                'format'     => self::FORMAT,
                'created_at' => date('c'),
                'version'    => AppVersion::code(),
                'app_name'   => config('app.name'),
                'app_url'    => config('app.url'),
                'database'   => DB::connection()->getDatabaseName(),
                'tables'     => $tables,
                'rows'       => $rows,
                'files'      => $files,
                'php'        => PHP_VERSION,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $progress('Compactando');
            if (!$zip->close()) {
                throw new RuntimeException("Não consegui gravar {$zipPath} (espaço em disco?)");
            }
        } catch (\Throwable $e) {
            @unlink($zipPath);
            throw $e;
        } finally {
            @unlink($sqlPath);
        }

        @chmod($zipPath, 0600);   // contém o .env (senhas): só o dono lê

        return [
            'path'    => $zipPath,
            'name'    => $name,
            'size'    => (int) filesize($zipPath),
            'tables'  => $tables,
            'rows'    => $rows,
            'files'   => $files,
            'seconds' => round(microtime(true) - $started, 1),
        ];
    }

    /**
     * Gera o SQL do banco: uma instrução por linha (as quebras de linha dos valores ficam escapadas),
     * horários em UTC e AUTO_INCREMENT preservado.
     *
     * @return array{0: int, 1: int} tabelas, linhas
     */
    private function dumpDatabase(string $file): array
    {
        $pdo = DB::connection()->getPdo();
        $out = fopen($file, 'wb');
        if (!$out) {
            throw new RuntimeException("Não consegui criar o arquivo temporário {$file}");
        }

        $previousZone = (string) $pdo->query('SELECT @@session.time_zone')->fetchColumn();
        $pdo->exec("SET time_zone = '+00:00'");

        fwrite($out, '-- Casino Platform ' . AppVersion::code() . ' — backup do banco `' . DB::connection()->getDatabaseName() . '` em ' . date('c') . "\n");
        fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET time_zone='+00:00';\n");

        $tableCount = 0;
        $rowCount = 0;

        try {
            foreach ($this->tables($pdo, 'BASE TABLE') as $table) {
                $quoted = $this->quoteIdentifier($table);
                $create = (string) $pdo->query("SHOW CREATE TABLE {$quoted}")->fetch(PDO::FETCH_NUM)[1];
                fwrite($out, "DROP TABLE IF EXISTS {$quoted};\n");
                fwrite($out, $this->oneLine($create) . ";\n");
                $tableCount++;

                $columns = $pdo->query("SHOW COLUMNS FROM {$quoted}")->fetchAll(PDO::FETCH_ASSOC);
                $binary = [];
                foreach ($columns as $i => $column) {
                    if (preg_match('/blob|binary|bit|geometry|point|polygon|linestring/i', (string) $column['Type'])) {
                        $binary[$i] = true;
                    }
                }
                $columnList = implode(',', array_map(fn ($c) => $this->quoteIdentifier($c['Field']), $columns));

                $pdo->setAttribute(self::bufferedQueryAttribute(), false);
                try {
                    $statement = $pdo->query("SELECT * FROM {$quoted}");
                    $batch = [];
                    $bytes = 0;
                    while (($row = $statement->fetch(PDO::FETCH_NUM)) !== false) {
                        $values = [];
                        foreach ($row as $i => $value) {
                            $values[] = $this->sqlValue($value, isset($binary[$i]));
                        }
                        $tuple = '(' . implode(',', $values) . ')';
                        $batch[] = $tuple;
                        $bytes += strlen($tuple);
                        $rowCount++;
                        if (count($batch) >= self::INSERT_ROWS || $bytes >= self::INSERT_BYTES) {
                            fwrite($out, "INSERT INTO {$quoted} ({$columnList}) VALUES " . implode(',', $batch) . ";\n");
                            $batch = [];
                            $bytes = 0;
                        }
                    }
                    if ($batch) {
                        fwrite($out, "INSERT INTO {$quoted} ({$columnList}) VALUES " . implode(',', $batch) . ";\n");
                    }
                    $statement->closeCursor();
                } finally {
                    $pdo->setAttribute(self::bufferedQueryAttribute(), true);
                }
            }

            foreach ($this->tables($pdo, 'VIEW') as $view) {
                $quoted = $this->quoteIdentifier($view);
                $create = (string) $pdo->query("SHOW CREATE VIEW {$quoted}")->fetch(PDO::FETCH_NUM)[1];
                // o DEFINER (usuário@host) pode não existir no servidor de destino
                $create = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $create);
                fwrite($out, "DROP VIEW IF EXISTS {$quoted};\n");
                fwrite($out, $this->oneLine($create) . ";\n");
            }

            fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n");
        } finally {
            fclose($out);
            $pdo->exec('SET time_zone = ' . $pdo->quote($previousZone !== '' ? $previousZone : 'SYSTEM'));
        }

        return [$tableCount, $rowCount];
    }

    /** PHP 8.5+: PDO::MYSQL_ATTR_* ficou obsoleto; o equivalente é Pdo\Mysql::ATTR_*. */
    private static function bufferedQueryAttribute(): int
    {
        return PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
    }

    /** @return list<string> */
    private function tables(PDO $pdo, string $type): array
    {
        $rows = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);

        return array_values(array_map(fn ($r) => (string) $r[0], array_filter($rows, fn ($r) => strtoupper((string) $r[1]) === $type)));
    }

    private function quoteIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private function oneLine(string $sql): string
    {
        return str_replace(["\r\n", "\n", "\r"], ' ', $sql);
    }

    private function sqlValue(mixed $value, bool $binary): string
    {
        if ($value === null) {
            return 'NULL';
        }
        $value = (string) $value;
        if ($binary) {
            return $value === '' ? "''" : '0x' . bin2hex($value);
        }

        // mesmo escape do mysql_real_escape_string (modo padrão, sem NO_BACKSLASH_ESCAPES)
        return "'" . strtr($value, [
            '\\'   => '\\\\',
            "\0"   => '\\0',
            "\n"   => '\\n',
            "\r"   => '\\r',
            "'"    => "\\'",
            '"'    => '\\"',
            "\x1a" => '\\Z',
        ]) . "'";
    }

    private function addDirectory(ZipArchive $zip, string $directory, string $prefix, array $exclude = []): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $exclude = array_map(fn ($p) => rtrim($p, '/\\') . DIRECTORY_SEPARATOR, $exclude);
        $base = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $path = $file->getPathname();
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }
            foreach ($exclude as $skip) {
                if (str_starts_with($path, $skip)) {
                    continue 2;
                }
            }
            $relative = str_replace('\\', '/', substr($path, strlen($base)));
            $zip->addFile($path, $prefix . '/' . $relative);
            $count++;
        }

        return $count;
    }

    /** Impede o acesso pela web à pasta de backups (caso ela fique dentro do site). */
    private function protectDirectory(string $directory): void
    {
        if (!str_starts_with(realpath($directory) ?: $directory, base_path())) {
            return;
        }
        if (!is_file($directory . '/.htaccess')) {
            @file_put_contents($directory . '/.htaccess', "Require all denied\nDeny from all\n");
        }
        if (!is_file($directory . '/index.html')) {
            @file_put_contents($directory . '/index.html', '');
        }
    }

    /* ------------------------------------------------------------------ */
    /* Listar / apagar / rodízio                                           */
    /* ------------------------------------------------------------------ */

    /** @return list<array{name: string, path: string, size: int, created_at: int, manifest: array}> */
    public function list(?string $directory = null): array
    {
        $directory = rtrim($directory ?: $this->defaultDirectory(), '/\\');
        $items = [];
        foreach (glob($directory . DIRECTORY_SEPARATOR . self::PREFIX . '*.zip') ?: [] as $path) {
            $items[] = [
                'name'       => basename($path),
                'path'       => $path,
                'size'       => (int) filesize($path),
                'created_at' => (int) filemtime($path),
                'manifest'   => $this->manifest($path),
            ];
        }
        usort($items, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $items;
    }

    public function manifest(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }
        $raw = $zip->getFromName('manifest.json');
        $zip->close();

        return is_string($raw) ? (json_decode($raw, true) ?: []) : [];
    }

    /** Caminho de um backup da pasta padrão a partir do nome (sem permitir sair da pasta). */
    public function find(string $name): ?string
    {
        $name = basename($name);
        if (!preg_match('/^' . preg_quote(self::PREFIX, '/') . '[0-9]{8}-[0-9]{6}\.zip$/', $name)) {
            return null;
        }
        $path = $this->defaultDirectory() . DIRECTORY_SEPARATOR . $name;

        return is_file($path) ? $path : null;
    }

    public function delete(string $name): bool
    {
        $path = $this->find($name);

        return $path !== null && @unlink($path);
    }

    /** Mantém só os $keep backups mais novos da pasta padrão. */
    public function rotate(int $keep = 10): int
    {
        $removed = 0;
        foreach (array_slice($this->list(), max(1, $keep)) as $old) {
            if (@unlink($old['path'])) {
                $removed++;
            }
        }

        return $removed;
    }

    /* ------------------------------------------------------------------ */
    /* Restaurar                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Restaura banco e arquivos de um backup e depois aplica as migrações mais novas que o backup.
     *
     * @param  array{files?: bool, keys?: bool, full_env?: bool}  $options
     * @param  callable(string): void|null  $progress
     * @return array{tables: int, statements: int, files: int, env: string, manifest: array}
     */
    public function restore(string $path, array $options = [], ?callable $progress = null): array
    {
        @set_time_limit(0);
        $progress ??= static fn (string $m) => null;
        $options += ['files' => true, 'keys' => true, 'full_env' => false];

        $zip = new ZipArchive();
        if (!is_file($path) || $zip->open($path) !== true) {
            throw new RuntimeException("Não consegui abrir o backup {$path}");
        }

        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true) ?: [];
            if (($manifest['format'] ?? null) !== self::FORMAT || $zip->locateName('database.sql') === false) {
                throw new RuntimeException('Este arquivo não é um backup do Casino Platform (manifest.json/database.sql ausentes).');
            }

            $sqlPath = storage_path('framework/restore-' . bin2hex(random_bytes(6)) . '.sql');
            if (!@copy('zip://' . $path . '#database.sql', $sqlPath)) {
                $raw = $zip->getFromName('database.sql');
                if ($raw === false || file_put_contents($sqlPath, $raw) === false) {
                    throw new RuntimeException('Não consegui extrair database.sql do backup.');
                }
            }

            try {
                $progress('Restaurando o banco de dados');
                [$tables, $statements] = $this->importDatabase($sqlPath);
            } finally {
                @unlink($sqlPath);
            }

            $files = 0;
            if ($options['files']) {
                $progress('Restaurando os arquivos enviados');
                $files += $this->extractPrefix($zip, 'files/public-storage/', public_path('storage'));
                $files += $this->extractPrefix($zip, 'files/storage-app/', storage_path('app'));
            }

            $env = 'mantido';
            $backupEnv = $zip->getFromName('env/.env');
            if (is_string($backupEnv) && ($options['full_env'] || $options['keys'])) {
                $env = $this->restoreEnv($backupEnv, (bool) $options['full_env']);
            }
        } finally {
            $zip->close();
        }

        $progress('Atualizando a estrutura do banco');
        $code = Artisan::call('migrate', ['--force' => true]);
        if ($code !== 0) {
            throw new RuntimeException('O backup foi restaurado, mas as migrações falharam: ' . trim(Artisan::output()));
        }
        Artisan::call('optimize:clear');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return ['tables' => $tables, 'statements' => $statements, 'files' => $files, 'env' => $env, 'manifest' => $manifest];
    }

    /** @return array{0: int, 1: int} tabelas, instruções */
    private function importDatabase(string $file): array
    {
        $pdo = DB::connection()->getPdo();
        $previousZone = (string) $pdo->query('SELECT @@session.time_zone')->fetchColumn();

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        // remove o que existe hoje (inclusive tabelas criadas por versões mais novas)
        foreach ($this->tables($pdo, 'VIEW') as $view) {
            $pdo->exec('DROP VIEW IF EXISTS ' . $this->quoteIdentifier($view));
        }
        foreach ($this->tables($pdo, 'BASE TABLE') as $table) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $this->quoteIdentifier($table));
        }

        $handle = fopen($file, 'rb');
        $tables = 0;
        $statements = 0;
        try {
            while (($line = fgets($handle)) !== false) {
                $sql = rtrim($line, "\r\n");
                if ($sql === '' || str_starts_with($sql, '--')) {
                    continue;
                }
                $pdo->exec($sql);
                $statements++;
                if (str_starts_with($sql, 'CREATE TABLE')) {
                    $tables++;
                }
            }
        } finally {
            fclose($handle);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET time_zone = ' . $pdo->quote($previousZone !== '' ? $previousZone : 'SYSTEM'));
        }

        return [$tables, $statements];
    }

    private function extractPrefix(ZipArchive $zip, string $prefix, string $target): int
    {
        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (!str_starts_with($name, $prefix) || str_ends_with($name, '/')) {
                continue;
            }
            $relative = substr($name, strlen($prefix));
            // nada de caminhos absolutos ou "..": o backup não pode escrever fora da pasta de destino
            if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, '/') || preg_match('/^[A-Za-z]:/', $relative)) {
                continue;
            }
            $dest = rtrim($target, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (!is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0775, true);
            }
            $stream = $zip->getStream($name);
            if (!$stream) {
                continue;
            }
            $out = @fopen($dest, 'wb');
            if ($out) {
                stream_copy_to_stream($stream, $out);
                fclose($out);
                $count++;
            }
            fclose($stream);
        }

        return $count;
    }

    /**
     * Sem $full: copia só as chaves (APP_KEY, JWT_SECRET, PRIVATE_KEY) do backup para o .env atual.
     * Com $full: usa o .env do backup inteiro, mas mantém a conexão com o banco atual (DB_*).
     */
    private function restoreEnv(string $backupEnv, bool $full): string
    {
        $envPath = base_path('.env');
        $current = is_file($envPath) ? (string) file_get_contents($envPath) : '';
        $line = static function (string $content, string $key): ?string {
            return preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content, $m) ? $m[0] : null;
        };
        $replace = static function (string $content, string $key, string $newLine): string {
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            return preg_match($pattern, $content)
                ? preg_replace_callback($pattern, fn () => $newLine, $content)
                : rtrim($content) . PHP_EOL . $newLine . PHP_EOL;
        };

        if ($full) {
            $result = $backupEnv;
            foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
                if (($keep = $line($current, $key)) !== null) {
                    $result = $replace($result, $key, $keep);
                }
            }
            $what = '.env do backup (conexão com o banco atual mantida)';
        } else {
            $result = $current;
            foreach (self::KEY_VARIABLES as $key) {
                if (($fromBackup = $line($backupEnv, $key)) !== null) {
                    $result = $replace($result, $key, $fromBackup);
                }
            }
            $what = 'chaves do backup (APP_KEY, JWT_SECRET, PRIVATE_KEY)';
        }

        if ($result !== $current) {
            if (is_file($envPath)) {
                @copy($envPath, $envPath . '.antes-da-restauracao');
            }
            file_put_contents($envPath, $result);

            return $what;
        }

        return 'sem mudanças';
    }
}
