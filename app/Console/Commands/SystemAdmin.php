<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Cria ou redefine o administrador e CONFERE que o login funciona (mesma checagem do formulário).
 *
 *   php artisan system:admin --email=admin@site.com --password=MinhaSenha123
 *   php artisan system:admin --email=admin@site.com --password=MinhaSenha123 --check   (só confere)
 */
class SystemAdmin extends Command
{
    protected $signature = 'system:admin
        {--email= : E-mail (login do painel)}
        {--password= : Senha (mín. 8 caracteres)}
        {--name=Administrador : Nome}
        {--check : Apenas confere se o login funciona, sem alterar nada}';

    protected $description = 'Cria/redefine o administrador do painel e confere o login';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('E-mail do administrador'));
        $password = (string) ($this->option('password') ?: $this->secret('Senha (mín. 8 caracteres)'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $this->error('Informe um e-mail válido e uma senha com pelo menos 8 caracteres.');
            return self::FAILURE;
        }

        $conn = config('database.connections.' . config('database.default'));
        $this->line("Banco: {$conn['database']} em {$conn['host']}:{$conn['port']}  ·  PHP " . PHP_VERSION);

        if (!$this->option('check')) {
            $user = User::where('email', $email)->first();
            $attrs = ['name' => $this->option('name'), 'password' => $password, 'role_id' => 0, 'status' => 'active'];
            if ($user) {
                $user->forceFill($attrs)->save();
            } else {
                $user = User::forceCreate($attrs + [
                    'email'             => $email,
                    'email_verified_at' => now(),
                    'inviter_code'      => strtoupper(Str::random(10)),
                    'language'          => 'pt_BR',
                ]);
            }
            $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'afiliado', 'guard_name' => 'web']);
            $user->syncRoles([$role]);
            if (!Wallet::where('user_id', $user->id)->exists()) {
                $setting = \App\Models\Setting::first();
                Wallet::create(['user_id' => $user->id, 'currency' => $setting->currency_code ?? 'BRL', 'symbol' => $setting->prefix ?? 'R$', 'active' => 1]);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info("Administrador {$email} salvo.");
        }

        $ok = Auth::guard('web')->validate(['email' => $email, 'password' => $password]);
        $row = DB::table('users')->where('email', $email)->first();
        $isAdmin = $row ? User::find($row->id)?->hasRole('admin') : false;

        if ($ok && $isAdmin) {
            $this->info('✔ Login conferido: e-mail e senha funcionam e o usuário tem acesso ao painel.');
            return self::SUCCESS;
        }

        $this->error('✖ O login NÃO está funcionando.');
        $this->line('  usuário existe: ' . ($row ? 'sim' : 'NÃO') . '  ·  senha confere: ' . ($ok ? 'sim' : 'NÃO') . '  ·  papel admin: ' . ($isAdmin ? 'sim' : 'NÃO'));
        if ($row) {
            $this->line('  hash: ' . substr((string) $row->password, 0, 7) . '… (' . strlen((string) $row->password) . ' caracteres)');
        }
        return self::FAILURE;
    }
}
