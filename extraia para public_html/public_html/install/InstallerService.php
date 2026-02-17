<?php

namespace App\Install;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Throwable;

class InstallerService
{
    public function testDatabaseConnection(array $db): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['database']);
            $pdo = new \PDO($dsn, $db['username'], $db['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return ['ok' => true, 'message' => 'Conexão com banco realizada com sucesso.'];
        } catch (Throwable $e) {
            installer_log('Erro ao testar banco', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Falha na conexão: ' . $e->getMessage()];
        }
    }

    public function run(array $payload, array $detected): array
    {
        try {
            $this->writeEnv($payload, $detected);
            $this->runArtisanPipeline($detected);
            $user = $this->createSuperAdmin($payload);
            $this->markInstalled();

            installer_log('Instalação concluída', ['env' => $detected['hosting'], 'domain' => $detected['domain']]);

            return [
                'ok' => true,
                'message' => 'Instalação finalizada com sucesso.',
                'user_email' => $user->email,
            ];
        } catch (Throwable $e) {
            installer_log('Erro no fluxo de instalação', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function writeEnv(array $payload, array $detected): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $updates = [
            'APP_NAME' => '"' . $payload['app_name'] . '"',
            'APP_ENV' => $payload['app_env'],
            'APP_DEBUG' => $payload['app_debug'],
            'APP_URL' => $payload['app_url'],
            'APP_TIMEZONE' => $payload['timezone'],
            'DB_HOST' => $payload['db_host'],
            'DB_PORT' => $payload['db_port'],
            'DB_DATABASE' => $payload['db_database'],
            'DB_USERNAME' => $payload['db_username'],
            'DB_PASSWORD' => '"' . addslashes($payload['db_password']) . '"',
        ];

        $content = File::get($envPath);

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= PHP_EOL . "{$key}={$value}";
            }
        }

        File::put($envPath, $content);
        installer_log('Arquivo .env atualizado', ['app_url' => $payload['app_url'], 'hosting' => $detected['hosting']]);
    }

    private function runArtisanPipeline(array $detected): void
    {
        $commands = [
            'key:generate --force',
            'migrate --force',
            'storage:link',
            'optimize:clear',
            'config:cache',
            'route:cache',
            'view:cache',
        ];

        foreach ($commands as $command) {
            Artisan::call($command);
            installer_log('Artisan executado', ['command' => $command, 'output' => Artisan::output()]);
        }

        if ($detected['hosting'] !== 'cpanel') {
            $this->runNodeBuild();
        } else {
            installer_log('npm build ignorado no cPanel');
        }

        if ($detected['hosting'] === 'ubuntu-vps') {
            @chmod(storage_path(), 0775);
            @chmod(base_path('bootstrap/cache'), 0775);
            installer_log('Permissões ajustadas para Ubuntu VPS');
        }
    }

    private function runNodeBuild(): void
    {
        if (! $this->commandExists('npm')) {
            installer_log('npm indisponível, build ignorado');
            return;
        }

        $commands = ['npm install', 'npm run build'];
        foreach ($commands as $command) {
            @exec($command . ' 2>&1', $output, $code);
            installer_log('Comando node executado', ['command' => $command, 'exit' => $code, 'output' => implode("\n", $output)]);
        }
    }

    private function createSuperAdmin(array $payload): User
    {
        $user = User::firstOrCreate(
            ['email' => $payload['admin_email']],
            [
                'name' => $payload['admin_name'],
                'password' => Hash::make($payload['admin_password']),
                'email_verified_at' => now(),
                'is_admin' => 1,
            ]
        );

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        installer_log('Super admin criado/atualizado', ['email' => $user->email]);

        return $user;
    }

    private function markInstalled(): void
    {
        File::put(storage_path('installed.lock'), now()->toDateTimeString());
    }

    private function commandExists(string $command): bool
    {
        $checker = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'command -v';
        @exec("{$checker} {$command}", $output, $code);

        return $code === 0;
    }
}
