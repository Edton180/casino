<?php

namespace App\Support\System;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Desinstalação da aplicação: apaga as tabelas do banco e tudo o que a instalação gerou
 * (.env, uploads, logs, caches, trava do instalador). O código (pastas app/, vendor/...) fica,
 * para o assistente ou você apagar a pasta inteira depois.
 *
 * Banco e usuário do MySQL em si só podem ser apagados com uma conta administradora:
 * isso fica a cargo do comando system:uninstall (--drop-database) e dos assistentes.
 */
class Uninstaller
{
    /** Usuários do MySQL que nunca são apagados. */
    public const PROTECTED_DB_USERS = ['root', 'mysql', 'mariadb.sys', 'debian-sys-maint', 'admin', 'phpmyadmin'];

    /**
     * O que será removido (para mostrar antes de confirmar).
     *
     * @return array{path: string, database: ?string, host: ?string, db_user: ?string, tables: ?int, foreign: list<string>, uploads: array{files: int, bytes: int}, version: ?string}
     */
    public function plan(): array
    {
        $connection = config('database.connections.' . config('database.default'));
        $tables = null;
        $foreign = [];
        try {
            ['ours' => $ours, 'foreign' => $foreign] = $this->classifyTables();
            $tables = count($ours);
        } catch (\Throwable) {
            // banco inacessível: só os arquivos serão removidos
        }

        return [
            'path'     => base_path(),
            'database' => $connection['database'] ?? null,
            'host'     => $connection['host'] ?? null,
            'db_user'  => $connection['username'] ?? null,
            'tables'   => $tables,
            'foreign'  => $foreign,
            'uploads'  => $this->measure(public_path('storage')),
            'version'  => AppVersion::installedVersion() ?? AppVersion::code(),
        ];
    }

    /**
     * Nomes das tabelas que pertencem ao cassino (base.sql + migrações). Só essas são apagadas:
     * se o mesmo banco tiver tabelas de outro sistema, elas ficam intactas.
     *
     * @return list<string> em minúsculas
     */
    public function applicationTables(): array
    {
        $names = ['migrations'];
        $base = database_path('install/base.sql');
        if (is_file($base) && preg_match_all('/CREATE TABLE `([^`]+)`/i', (string) file_get_contents($base), $m)) {
            $names = array_merge($names, $m[1]);
        }
        foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
            if (preg_match_all('/Schema::create\(\s*[\'"]([^\'"]+)[\'"]/', (string) file_get_contents($file), $m)) {
                $names = array_merge($names, $m[1]);
            }
        }

        return array_values(array_unique(array_map('strtolower', $names)));
    }

    /**
     * Tabelas do banco atual separadas entre as do cassino e as de outros sistemas.
     *
     * @return array{ours: list<string>, foreign: list<string>}
     */
    public function classifyTables(): array
    {
        $known = array_flip($this->applicationTables());
        $ours = [];
        $foreign = [];
        foreach (DB::connection()->getPdo()->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name]) {
            isset($known[strtolower((string) $name)]) ? $ours[] = (string) $name : $foreign[] = (string) $name;
        }

        return ['ours' => $ours, 'foreign' => $foreign];
    }

    /**
     * Apaga as tabelas do cassino.
     *
     * @return array{dropped: int, kept: list<string>}
     */
    public function dropTables(): array
    {
        ['ours' => $ours, 'foreign' => $foreign] = $this->classifyTables();
        $pdo = DB::connection()->getPdo();
        $types = [];
        foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
            $types[(string) $name] = strtoupper((string) $type);
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $count = 0;
        try {
            foreach ($ours as $name) {
                $quoted = '`' . str_replace('`', '``', $name) . '`';
                $pdo->exec((($types[$name] ?? '') === 'VIEW' ? 'DROP VIEW IF EXISTS ' : 'DROP TABLE IF EXISTS ') . $quoted);
                $count++;
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
        DB::purge();

        return ['dropped' => $count, 'kept' => $foreign];
    }

    /**
     * Comandos SQL (para uma conta administradora) que apagam o banco e o usuário do site.
     * O usuário só é apagado se não for root/sistema.
     *
     * @return list<string>
     */
    public function dropDatabaseStatements(string $database, ?string $user): array
    {
        if (!preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $database) || in_array(strtolower($database), ['mysql', 'information_schema', 'performance_schema', 'sys'], true)) {
            throw new \InvalidArgumentException("Nome de banco que não pode ser apagado: {$database}");
        }

        $sql = ["DROP DATABASE IF EXISTS `{$database}`"];
        if ($user !== null && $user !== '' && $this->canDropUser($user)) {
            foreach (['localhost', '127.0.0.1', '%'] as $host) {
                $sql[] = "DROP USER IF EXISTS '{$user}'@'{$host}'";
            }
        }
        $sql[] = 'FLUSH PRIVILEGES';

        return $sql;
    }

    public function canDropUser(string $user): bool
    {
        return preg_match('/^[A-Za-z0-9_\-.]{1,32}$/', $user) === 1
            && !in_array(strtolower($user), self::PROTECTED_DB_USERS, true);
    }

    /**
     * Apaga os arquivos gerados pela instalação. O .env é o último (os outros passos ainda precisam dele).
     *
     * @return list<string> o que foi removido (caminhos relativos)
     */
    public function removeFiles(bool $keepUploads = false): array
    {
        $removed = [];

        $this->stopLocalServer();

        $clean = [
            'storage/logs'                 => true,
            'storage/framework/cache/data' => true,
            'storage/framework/sessions'   => true,
            'storage/framework/views'      => true,
            'storage/framework/testing'    => true,
            'storage/app'                  => true,
        ];
        foreach ($clean as $relative => $keepGitignore) {
            if ($this->emptyDirectory(base_path($relative), $keepGitignore)) {
                $removed[] = $relative . '/*';
            }
        }

        foreach (glob(base_path('bootstrap/cache/*.php')) ?: [] as $file) {
            @unlink($file);
        }
        $removed[] = 'bootstrap/cache/*.php';

        if (!$keepUploads && file_exists(public_path('storage'))) {
            if (is_link(public_path('storage'))) {
                @unlink(public_path('storage'));
            } else {
                $this->deleteTree(public_path('storage'));
            }
            $removed[] = 'public/storage (uploads)';
        }

        foreach (['storage/framework/down', 'storage/framework/maintenance.php', 'storage/install.token', 'storage/local-server.pid', 'storage/installed.lock', '.env.antes-da-restauracao', '.env'] as $relative) {
            if (is_file(base_path($relative)) && @unlink(base_path($relative))) {
                $removed[] = $relative;
            }
        }

        return $removed;
    }

    /** Desliga o servidor local (php -S) desta pasta, se o assistente o tiver ligado. */
    public function stopLocalServer(): bool
    {
        $pidFile = storage_path('local-server.pid');
        if (!is_file($pidFile) || !function_exists('posix_kill')) {
            return false;
        }
        $pid = (int) trim((string) file_get_contents($pidFile));
        if ($pid <= 1 || $pid === getmypid()) {
            return false;
        }

        return @posix_kill($pid, 15);
    }

    /** @return array{files: int, bytes: int} */
    public function measure(string $directory): array
    {
        $files = 0;
        $bytes = 0;
        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files++;
                    $bytes += $file->getSize();
                }
            }
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    private function emptyDirectory(string $directory, bool $keepGitignore): bool
    {
        if (!is_dir($directory)) {
            return false;
        }
        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isDot() || ($keepGitignore && $item->getFilename() === '.gitignore')) {
                continue;
            }
            if ($item->isDir() && !$item->isLink()) {
                // mantém subpastas versionadas (ex.: storage/app/public com .gitignore), só esvazia
                if (is_file($item->getPathname() . '/.gitignore')) {
                    $this->emptyDirectory($item->getPathname(), true);
                } else {
                    $this->deleteTree($item->getPathname());
                }
            } else {
                @unlink($item->getPathname());
            }
        }

        return true;
    }

    private function deleteTree(string $path): void
    {
        $base = realpath(base_path());
        $real = realpath($path);
        // trava de segurança: nunca apaga nada fora da pasta do site
        if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }
}
