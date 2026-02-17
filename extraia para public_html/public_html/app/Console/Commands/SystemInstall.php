<?php

namespace App\Console\Commands;

use App\Install\EnvironmentDetector;
use App\Install\InstallerService;
use Illuminate\Console\Command;

class SystemInstall extends Command
{
    protected $signature = 'system:install
        {--mode= : vps|cpanel|localhost|docker|diagnose|help}
        {--domain= : Domínio da aplicação}
        {--email= : E-mail do super admin}
        {--ssl= : sim|nao}
        {--db-connection=mysql : mysql|pgsql}
        {--db-host= : Host do banco}
        {--db-port=3306 : Porta do banco}
        {--db-name= : Nome do banco}
        {--db-user= : Usuário do banco}
        {--db-pass= : Senha do banco}
        {--admin-name= : Nome do super admin}
        {--admin-pass= : Senha do super admin}';

    protected $description = 'Instalação inteligente multi-ambiente do sistema';

    public function handle(): int
    {
        require_once base_path('install/helpers.php');
        require_once base_path('install/EnvironmentDetector.php');
        require_once base_path('install/InstallerService.php');

        $detector = new EnvironmentDetector();
        $service = new InstallerService();
        $detected = $detector->detect();

        $mode = strtolower((string) ($this->option('mode') ?: ''));
        if ($mode === 'help') {
            $this->line('Modos: vps, cpanel, localhost, docker, diagnose, help');
            $this->line('Exemplo: php artisan system:install --mode=vps --domain=casino.site --ssl=sim --email=admin@casino.site');

            return self::SUCCESS;
        }

        if ($mode === 'diagnose') {
            $requirements = $service->validateRequirements($detected);
            $this->table(['Item', 'Status'], [
                ['Hosting', $detected['hosting']],
                ['PHP', $detected['php_version']],
                ['Domínio', $detected['domain']],
                ['HTTPS', $detected['https'] ? 'sim' : 'não'],
                ['Requisitos', $requirements['ok'] ? 'ok' : 'pendências'],
            ]);

            return $requirements['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $mode = $mode ?: $this->choice('Modo de instalação', ['vps', 'cpanel', 'localhost', 'docker'], 'vps');
        $sslDefault = $detected['https'] ? 'sim' : 'nao';
        $ssl = strtolower((string) ($this->option('ssl') ?: $this->choice('SSL habilitado?', ['sim', 'nao'], $sslDefault)));

        $domain = (string) ($this->option('domain') ?: $this->ask('Domínio da aplicação', $detected['domain']));
        $adminEmail = (string) ($this->option('email') ?: $this->ask('Email do Super Admin'));

        $defaultEnv = in_array($mode, ['localhost', 'docker'], true) ? 'local' : 'production';
        $payload = [
            'install_mode' => $mode,
            'domain' => $domain,
            'ssl_enabled' => $ssl === 'sim',
            'app_name' => $this->ask('APP_NAME', 'Casino Platform'),
            'app_env' => $this->choice('APP_ENV', ['local', 'production', 'staging'], $defaultEnv),
            'app_debug' => $this->choice('APP_DEBUG', ['true', 'false'], $defaultEnv === 'production' ? 'false' : 'true'),
            'app_url' => $this->ask('APP_URL', ($ssl === 'sim' ? 'https://' : 'http://') . $domain),
            'timezone' => $this->ask('APP_TIMEZONE', 'America/Sao_Paulo'),
            'db_connection' => (string) $this->option('db-connection'),
            'db_host' => (string) ($this->option('db-host') ?: $this->ask('DB_HOST', $detected['default_db_host'])),
            'db_port' => (string) ($this->option('db-port') ?: $this->ask('DB_PORT', '3306')),
            'db_database' => (string) ($this->option('db-name') ?: $this->ask('DB_DATABASE')),
            'db_username' => (string) ($this->option('db-user') ?: $this->ask('DB_USERNAME')),
            'db_password' => (string) ($this->option('db-pass') ?: ($this->secret('DB_PASSWORD') ?: '')),
            'admin_name' => (string) ($this->option('admin-name') ?: $this->ask('Nome do Super Admin', 'Administrator')),
            'admin_email' => $adminEmail,
            'admin_password' => (string) ($this->option('admin-pass') ?: $this->secret('Senha do Super Admin')),
        ];

        $dbStatus = $service->testDatabaseConnection([
            'driver' => $payload['db_connection'],
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
