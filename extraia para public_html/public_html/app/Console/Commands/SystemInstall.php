<?php

namespace App\Console\Commands;

use App\Install\CasinoInstaller;
use Illuminate\Console\Command;

/**
 * Instalação pelo terminal (VPS / SSH). Mesmas etapas do instalador web.
 *
 *   php artisan system:install
 *   php artisan system:install --url=https://site.com --db-name=casino --db-user=casino --db-pass=xxx \
 *       --admin-email=admin@site.com --admin-pass=xxxxxxxx --no-interaction
 */
class SystemInstall extends Command
{
    protected $signature = 'system:install
        {--url= : URL do site (ex: https://meusite.com)}
        {--name= : Nome do site}
        {--db-host=localhost : Servidor MySQL}
        {--db-port=3306 : Porta do MySQL}
        {--db-name= : Nome do banco}
        {--db-user= : Usuário do banco}
        {--db-pass= : Senha do banco}
        {--admin-name=Administrador : Nome do administrador}
        {--admin-email= : E-mail do administrador}
        {--admin-pass= : Senha do administrador (mín. 8 caracteres)}
        {--overwrite : Apaga as tabelas existentes no banco}
        {--check : Apenas verifica os requisitos}';

    protected $description = 'Instala o sistema (banco de dados, .env e administrador)';

    public function handle(): int
    {
        $installer = new CasinoInstaller(base_path());

        $requirements = $installer->requirements();
        $this->table(['', 'Requisito', 'Detalhe'], array_map(
            fn ($i) => [$i['ok'] ? '<info>✔</info>' : ($i['required'] ? '<error>✖</error>' : '<comment>!</comment>'), $i['label'], $i['detail']],
            $requirements['items']
        ));

        if ($this->option('check')) {
            return $requirements['ok'] ? self::SUCCESS : self::FAILURE;
        }

        if (!$requirements['ok']) {
            $this->error('Corrija os requisitos acima antes de instalar.');
            return self::FAILURE;
        }

        if ($installer->isInstalled() && !$this->option('overwrite')) {
            $this->error('O sistema já está instalado (storage/installed.lock). Use --overwrite para reinstalar.');
            return self::FAILURE;
        }

        $db = [
            'host'     => $this->option('db-host') ?: $this->ask('Servidor MySQL', 'localhost'),
            'port'     => (int) ($this->option('db-port') ?: 3306),
            'database' => $this->option('db-name') ?: $this->ask('Nome do banco'),
            'username' => $this->option('db-user') ?: $this->ask('Usuário do banco'),
            'password' => $this->option('db-pass') ?? $this->secret('Senha do banco') ?? '',
        ];

        $test = $installer->testDatabase($db);
        if (!$test['ok']) {
            $this->error($test['message']);
            return self::FAILURE;
        }
        $this->info($test['message']);

        if (!empty($test['tables']) && !$this->option('overwrite')
            && !$this->confirm('O banco já possui tabelas. Apagar e reinstalar?', false)) {
            return self::FAILURE;
        }

        $url = $this->option('url') ?: $this->ask('URL do site', 'http://localhost');
        $name = $this->option('name') ?: $this->ask('Nome do site', 'Casino');
        $adminEmail = $this->option('admin-email') ?: $this->ask('E-mail do administrador');
        $adminPass = $this->option('admin-pass') ?: $this->secret('Senha do administrador (mín. 8 caracteres)');

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen((string) $adminPass) < 8) {
            $this->error('Informe um e-mail válido e uma senha com pelo menos 8 caracteres.');
            return self::FAILURE;
        }

        @unlink(storage_path('installed.lock'));

        $steps = [
            'Gravando .env'               => fn () => $installer->writeEnv(['app_name' => $name, 'app_url' => $url, 'db' => $db]),
            'Criando banco de dados'      => fn () => $installer->importBase($db, true),
            'Atualizando estrutura'       => fn () => $installer->migrate(),
            'Criando administrador'       => fn () => $installer->createAdmin($this->option('admin-name') ?: 'Administrador', $adminEmail, $adminPass),
            'Finalizando'                 => fn () => $installer->finish(['app_name' => $name]),
        ];

        foreach ($steps as $label => $step) {
            $failed = null;
            $this->components->task($label, function () use ($step, $installer, $label, &$failed) {
                try {
                    $step();
                    return true;
                } catch (\Throwable $e) {
                    $installer->log('Erro (CLI): ' . $e->getMessage(), ['step' => $label]);
                    $failed = $e->getMessage();
                    return false;
                }
            });

            if ($failed !== null) {
                $this->error($failed);
                return self::FAILURE;
            }
        }

        if (!$installer->isInstalled()) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Instalação concluída!');
        $this->line('Site:  ' . rtrim($url, '/'));
        $this->line('Painel: ' . rtrim($url, '/') . '/admin  (' . $adminEmail . ')');

        return self::SUCCESS;
    }
}
