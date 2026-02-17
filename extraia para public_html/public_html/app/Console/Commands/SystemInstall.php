<?php

namespace App\Console\Commands;

use App\Install\EnvironmentDetector;
use App\Install\InstallerService;
use Illuminate\Console\Command;

class SystemInstall extends Command
{
    protected $signature = 'system:install';

    protected $description = 'Instalação inteligente multi-ambiente do sistema';

    public function handle(): int
    {
        require_once base_path('install/helpers.php');
        require_once base_path('install/EnvironmentDetector.php');
        require_once base_path('install/InstallerService.php');

        $detector = new EnvironmentDetector();
        $detected = $detector->detect();

        $this->info('Ambiente detectado: ' . $detected['hosting']);
        $this->line('SO: ' . $detected['os']);
        $this->line('Domínio: ' . $detected['domain']);
        $this->line('HTTPS: ' . ($detected['https'] ? 'sim' : 'não'));

        $payload = [
            'app_name' => $this->ask('APP_NAME', 'Casino Platform'),
            'app_env' => $this->choice('APP_ENV', ['local', 'production', 'staging'], $detected['production'] ? 'production' : 'local'),
            'app_debug' => $this->choice('APP_DEBUG', ['true', 'false'], $detected['production'] ? 'false' : 'true'),
            'app_url' => $this->ask('APP_URL', $detected['app_url']),
            'timezone' => $this->ask('APP_TIMEZONE', 'America/Sao_Paulo'),
            'db_host' => $this->ask('DB_HOST', $detected['default_db_host']),
            'db_port' => $this->ask('DB_PORT', '3306'),
            'db_database' => $this->ask('DB_DATABASE'),
            'db_username' => $this->ask('DB_USERNAME'),
            'db_password' => $this->secret('DB_PASSWORD') ?: '',
            'admin_name' => $this->ask('Nome do Super Admin'),
            'admin_email' => $this->ask('Email do Super Admin'),
            'admin_password' => $this->secret('Senha do Super Admin'),
        ];

        $service = new InstallerService();
        $dbStatus = $service->testDatabaseConnection([
            'host' => $payload['db_host'],
            'port' => $payload['db_port'],
            'database' => $payload['db_database'],
            'username' => $payload['db_username'],
            'password' => $payload['db_password'],
        ]);

        if (! $dbStatus['ok']) {
            $this->error($dbStatus['message']);
            return self::FAILURE;
        }

        $result = $service->run($payload, $detected);

        if (! $result['ok']) {
            $this->error($result['message']);
            return self::FAILURE;
        }

        $this->info('Instalação concluída! Login admin: ' . $result['user_email']);
        return self::SUCCESS;
    }
}
