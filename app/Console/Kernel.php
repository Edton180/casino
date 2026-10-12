<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Libera o cashback acumulado para resgate (antes era o script público updatecash.php)
        $schedule->call(function () {
            \Illuminate\Support\Facades\DB::table('wallets')->where('cashback', '>', 0)->update(['cashbackliquido' => 1]);
        })->dailyAt('00:05')->name('wallet-release-cashback')->withoutOverlapping();

        // Backup diário do banco e do .env (storage/app/backups; ficam os 10 mais novos).
        // Os arquivos enviados entram nos backups feitos pelo painel (Sistema) e antes de cada atualização.
        $schedule->command('system:backup --no-files --keep=10')
            ->dailyAt('03:20')->name('daily-database-backup')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
