<?php

namespace App\Install;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Núcleo do instalador. Funciona sem o Laravel inicializado (antes de
 * existir o .env), por isso usa apenas PHP puro até a etapa de migrations.
 *
 * Usado por:
 *  - instalador web:  public/install/index.php
 *  - instalador CLI:  php artisan system:install
 *  - script de VPS:   scripts/auto-install.sh
 */
class CasinoInstaller
{
    public const MIN_PHP = '8.2.0';

    public const EXTENSIONS = [
        'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json',
        'bcmath', 'fileinfo', 'curl', 'zip', 'intl', 'gd', 'simplexml', 'dom',
    ];

    public function __construct(private string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
    }

    public function path(string $relative = ''): string
    {
        return $this->basePath . ($relative !== '' ? DIRECTORY_SEPARATOR . ltrim($relative, '/\\') : '');
    }

    /* ------------------------------------------------------------------ */
    /* Estado                                                                */
    /* ------------------------------------------------------------------ */

    public function isInstalled(): bool
    {
        return is_file($this->path('storage/installed.lock'));
    }

    /**
     * Verificação de requisitos do servidor.
     * @return array{ok: bool, items: array<int, array{label: string, ok: bool, detail: string, required: bool}>}
     */
    public function requirements(): array
    {
        $items = [];

        $items[] = [
            'label'    => 'PHP ' . self::MIN_PHP . ' ou superior',
            'ok'       => version_compare(PHP_VERSION, self::MIN_PHP, '>='),
            'detail'   => 'Versão atual: ' . PHP_VERSION,
            'required' => true,
        ];

        foreach (self::EXTENSIONS as $ext) {
            $items[] = [
                'label'    => "Extensão {$ext}",
                'ok'       => extension_loaded($ext),
                'detail'   => extension_loaded($ext) ? 'instalada' : 'ative no painel da hospedagem (Selecionar versão do PHP)',
                'required' => true,
            ];
        }

        $items[] = [
            'label'    => 'Dependências PHP (pasta vendor)',
            'ok'       => is_file($this->path('vendor/autoload.php')),
            'detail'   => is_file($this->path('vendor/autoload.php'))
                ? 'encontradas'
                : 'use o pacote "pronto para hospedagem" da página Releases ou rode "composer install --no-dev"',
            'required' => true,
        ];

        $items[] = [
            'label'    => 'Frontend compilado (public/build)',
            'ok'       => is_file($this->path('public/build/manifest.json')),
            'detail'   => is_file($this->path('public/build/manifest.json')) ? 'encontrado' : 'rode "npm ci && npm run build"',
            'required' => true,
        ];

        $items[] = [
            'label'    => 'Base de dados inicial',
            'ok'       => is_file($this->baseSqlPath()),
            'detail'   => 'database/install/base.sql',
            'required' => true,
        ];

        foreach (['storage', 'storage/app', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $full = $this->path($dir);
            if (!is_dir($full)) {
                @mkdir($full, 0775, true);
            }
            $items[] = [
                'label'    => "Pasta {$dir} gravável",
                'ok'       => is_writable($full),
                'detail'   => is_writable($full) ? 'ok' : 'ajuste a permissão para 775',
                'required' => true,
            ];
        }

        $envWritable = is_file($this->path('.env')) ? is_writable($this->path('.env')) : is_writable($this->basePath);
        $items[] = [
            'label'    => 'Arquivo .env gravável',
            'ok'       => $envWritable,
            'detail'   => $envWritable ? 'ok' : 'dê permissão de escrita na pasta do site',
            'required' => true,
        ];

        $ok = !in_array(false, array_map(fn ($i) => $i['ok'] || !$i['required'], $items), true);

        return ['ok' => $ok, 'items' => $items];
    }

    /* ------------------------------------------------------------------ */
    /* Banco de dados                                                        */
    /* ------------------------------------------------------------------ */

    public function connect(array $db): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'] ?? 'localhost',
            (int) ($db['port'] ?? 3306),
            $db['database'] ?? ''
        );

        return new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
        ]);
    }

    /**
     * @return array{ok: bool, message: string, tables?: int, version?: string}
     */
    public function testDatabase(array $db): array
    {
        try {
            try {
                $pdo = $this->connect($db);
            } catch (Throwable $e) {
                // banco inexistente: cria quando o usuário tem permissão (localhost/VPS)
                if (!str_contains($e->getMessage(), '1049') || !$this->createDatabase($db)) {
                    throw $e;
                }
                $pdo = $this->connect($db);
            }
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $tables = count($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));

            return [
                'ok'      => true,
                'message' => "Conectado ao MySQL/MariaDB {$version}." . ($tables ? " O banco já possui {$tables} tabela(s)." : ' Banco vazio.'),
                'tables'  => $tables,
                'version' => $version,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Não foi possível conectar: ' . $this->friendlyDbError($e->getMessage())];
        }
    }

    private function createDatabase(array $db): bool
    {
        $name = (string) ($db['database'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $name)) {
            return false;
        }

        try {
            $pdo = $this->connect(['database' => ''] + $db);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->log('Banco de dados criado: ' . $name);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function friendlyDbError(string $message): string
    {
        return match (true) {
            str_contains($message, '1045') => 'usuário ou senha incorretos.',
            str_contains($message, '1049') => 'o banco de dados informado não existe (crie-o no painel da hospedagem).',
            str_contains($message, '2002'), str_contains($message, 'getaddrinfo') => 'servidor do banco não encontrado (na hospedagem compartilhada normalmente é "localhost").',
            str_contains($message, '1044') => 'o usuário não tem permissão neste banco (adicione o usuário ao banco com TODOS os privilégios).',
            default => $message,
        };
    }

    public function baseSqlPath(): string
    {
        return $this->path('database/install/base.sql');
    }

    /**
     * Importa a estrutura e os dados iniciais (jogos, provedores, categorias...).
     */
    public function importBase(array $db, bool $overwrite = false): int
    {
        $pdo = $this->connect($db);
        $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        if ($existing && !$overwrite) {
            throw new RuntimeException('O banco já possui tabelas. Marque "Apagar dados existentes" ou use um banco vazio.');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($existing as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', $table) . '`');
        }

        $count = 0;
        foreach ($this->sqlStatements($this->baseSqlPath()) as $statement) {
            $pdo->exec($statement);
            $count++;
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        return $count;
    }

    /**
     * Divide um dump do mysqldump em comandos (cada comando termina em ";" no fim da linha).
     * @return \Generator<string>
     */
    private function sqlStatements(string $file): \Generator
    {
        $handle = @fopen($file, 'rb');
        if (!$handle) {
            throw new RuntimeException('Arquivo database/install/base.sql não encontrado.');
        }

        $buffer = '';
        while (($line = fgets($handle)) !== false) {
            $trim = trim($line);
            if ($buffer === '' && ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#'))) {
                continue;
            }

            $buffer .= $line;
            if (str_ends_with($trim, ';')) {
                yield $buffer;
                $buffer = '';
            }
        }
        fclose($handle);

        if (trim($buffer) !== '') {
            yield $buffer;
        }
    }

    /* ------------------------------------------------------------------ */
    /* .env                                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * @param array{app_name: string, app_url: string, db: array, environment?: string, debug?: bool} $data
     */
    public function writeEnv(array $data): void
    {
        $envPath = $this->path('.env');
        $content = is_file($envPath) ? file_get_contents($envPath) : file_get_contents($this->path('.env.example'));

        $url = rtrim($data['app_url'], '/');
        $values = [
            'APP_NAME'              => $data['app_name'],
            'APP_ENV'               => $data['environment'] ?? 'production',
            'APP_DEBUG'             => !empty($data['debug']) ? 'true' : 'false',
            'APP_URL'               => $url,
            'APP_KEY'               => 'base64:' . base64_encode(random_bytes(32)),
            'JWT_SECRET'            => bin2hex(random_bytes(32)),
            'PRIVATE_KEY'           => bin2hex(random_bytes(16)),
            'LOG_LEVEL'             => !empty($data['debug']) ? 'debug' : 'error',
            'DB_CONNECTION'         => 'mysql',
            'DB_HOST'               => $data['db']['host'],
            'DB_PORT'               => (string) ($data['db']['port'] ?? 3306),
            'DB_DATABASE'           => $data['db']['database'],
            'DB_USERNAME'           => $data['db']['username'],
            'DB_PASSWORD'           => $data['db']['password'] ?? '',
            'CACHE_DRIVER'          => 'file',
            'SESSION_DRIVER'        => 'file',
            'QUEUE_CONNECTION'      => 'sync',
            'SESSION_SECURE_COOKIE' => str_starts_with($url, 'https://') ? 'true' : 'false',
        ];

        // reinstalação: mantém as chaves já existentes para não invalidar sessões/senhas criptografadas
        foreach (['APP_KEY', 'JWT_SECRET', 'PRIVATE_KEY'] as $keep) {
            if (preg_match('/^' . $keep . '=(.+)$/m', $content, $m) && trim($m[1]) !== '' && trim($m[1]) !== 'null') {
                unset($values[$keep]);
            }
        }

        foreach ($values as $key => $value) {
            $content = $this->setEnvValue($content, $key, $value);
        }

        if (file_put_contents($envPath, $content) === false) {
            throw new RuntimeException('Não foi possível gravar o arquivo .env. Verifique as permissões.');
        }

        @chmod($envPath, 0640);
        $this->clearCachedConfig();
    }

    public function setEnvValue(string $content, string $key, string $value): string
    {
        $line = $key . '=' . $this->quoteEnv($value);
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        return preg_match($pattern, $content)
            ? preg_replace_callback($pattern, fn () => $line, $content)
            : rtrim($content) . PHP_EOL . $line . PHP_EOL;
    }

    private function quoteEnv(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_\-.:\/@+=]+$/', $value)) {
            return $value;
        }

        return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value) . '"';
    }

    /** Remove caches de configuração/rotas de um deploy anterior. */
    public function clearCachedConfig(): void
    {
        foreach (glob($this->path('bootstrap/cache/*.php')) ?: [] as $file) {
            if (!in_array(basename($file), ['packages.php', 'services.php'], true)) {
                @unlink($file);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Etapas que usam o Laravel                                             */
    /* ------------------------------------------------------------------ */

    /** Inicializa o Laravel (depois que o .env existe). */
    public function laravel(): \Illuminate\Contracts\Console\Kernel
    {
        static $kernel = null;
        if ($kernel) {
            return $kernel;
        }

        require_once $this->path('vendor/autoload.php');
        $app = require $this->path('bootstrap/app.php');
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        return $kernel;
    }

    public function migrate(): string
    {
        $kernel = $this->laravel();
        $code = $kernel->call('migrate', ['--force' => true]);
        $output = $kernel->output();

        if ($code !== 0) {
            throw new RuntimeException('Falha nas migrations: ' . $output);
        }

        return $output;
    }

    public function createAdmin(string $name, string $email, string $password): void
    {
        $this->laravel();

        $user = \App\Models\User::where('email', $email)->first();
        $attributes = [
            'name'     => $name,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role_id'  => 0,
            'status'   => 'active',
        ];

        if ($user) {
            $user->forceFill($attributes)->save();
        } else {
            $user = \App\Models\User::forceCreate($attributes + [
                'email'             => $email,
                'email_verified_at' => now(),
                'inviter_code'      => strtoupper(\Illuminate\Support\Str::random(10)),
                'language'          => 'pt_BR',
            ]);
        }

        // o painel /admin exige o papel "admin" (spatie/laravel-permission)
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'afiliado', 'guard_name' => 'web']);
        $user->assignRole($role);

        if (!\App\Models\Wallet::where('user_id', $user->id)->exists()) {
            $setting = \App\Models\Setting::first();
            \App\Models\Wallet::create([
                'user_id'  => $user->id,
                'currency' => $setting->currency_code ?? 'BRL',
                'symbol'   => $setting->prefix ?? 'R$',
                'active'   => 1,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Link do storage, caches e trava do instalador.
     * @return string[] avisos
     */
    public function finish(array $settings = []): array
    {
        $warnings = [];
        $kernel = $this->laravel();

        // o disco "public" grava direto em public/storage (config/filesystems.php),
        // então não é preciso symlink — funciona em qualquer hospedagem
        $publicStorage = $this->path('public/storage');
        if (is_link($publicStorage)) {
            @unlink($publicStorage);
        }
        if (!is_dir($publicStorage) && !@mkdir($publicStorage, 0775, true)) {
            $warnings[] = 'Crie a pasta public/storage com permissão 775 para os uploads do painel.';
        }

        // logos e banners padrão (podem ser trocados no painel)
        $defaults = $this->path('database/install/defaults');
        @mkdir($publicStorage . '/defaults', 0775, true);
        foreach (glob($defaults . '/*') ?: [] as $file) {
            $dest = $publicStorage . '/defaults/' . basename($file);
            if (!is_file($dest)) {
                @copy($file, $dest);
            }
        }

        if (!empty($settings)) {
            $setting = \App\Models\Setting::first();
            if ($setting) {
                $setting->forceFill(array_filter([
                    'software_name' => $settings['app_name'] ?? null,
                ]))->save();
            }
        }

        $kernel->call('optimize:clear');
        \Illuminate\Support\Facades\Cache::flush();

        file_put_contents($this->path('storage/installed.lock'), date('c'));

        return $warnings;
    }

    /* ------------------------------------------------------------------ */
    /* Log                                                                   */
    /* ------------------------------------------------------------------ */

    public function log(string $message, array $context = []): void
    {
        $context = array_diff_key($context, array_flip(['password', 'db_password', 'admin_password']));
        @file_put_contents(
            $this->path('storage/logs/installer.log'),
            sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $message, $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''),
            FILE_APPEND
        );
    }
}
