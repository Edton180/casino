<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Inteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/install/assets/install.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">🚀 Instalador Inteligente Casino</h1>
        <div class="text-sm text-slate-300">Status: <?= $installed ? 'Instalado' : 'Pendente' ?></div>
    </div>

    <?php if (session('installer_error')): ?>
        <div class="mb-4 p-3 rounded bg-red-500/20 border border-red-500"><?= e(session('installer_error')) ?></div>
    <?php endif; ?>

    <div class="bg-slate-900 rounded-xl p-6 border border-slate-700 space-y-6">
        <div>
            <div class="h-2 rounded bg-slate-700 overflow-hidden">
                <div class="h-full w-full bg-emerald-500"></div>
            </div>
            <p class="text-xs text-slate-400 mt-2">Wizard SaaS multi-ambiente</p>
        </div>

        <section class="grid md:grid-cols-2 gap-4">
            <div class="p-4 rounded bg-slate-800">
                <h2 class="font-semibold mb-3">1) Diagnóstico Inteligente</h2>
                <ul class="text-sm space-y-1">
                    <li>SO: <strong><?= e($detected['os']) ?></strong></li>
                    <li>Hospedagem: <strong><?= e($detected['hosting']) ?></strong></li>
                    <li>PHP: <strong><?= e($detected['php_version']) ?></strong></li>
                    <li>Domínio: <strong><?= e($detected['domain']) ?></strong></li>
                    <li>HTTPS: <strong><?= installer_bool_label($detected['https']) ?></strong></li>
                    <li>APP_URL sugerida: <strong><?= e($detected['app_url']) ?></strong></li>
                </ul>
            </div>
            <div class="p-4 rounded bg-slate-800">
                <h2 class="font-semibold mb-3">Extensões & Permissões</h2>
                <ul class="text-sm space-y-1">
                    <?php foreach ($detected['required_extensions'] as $ext => $status): ?>
                        <li><?= e($ext) ?>: <?= installer_bool_label($status) ?></li>
                    <?php endforeach; ?>
                    <li class="pt-2">storage: <?= installer_bool_label($detected['permissions']['storage']) ?></li>
                    <li>bootstrap/cache: <?= installer_bool_label($detected['permissions']['bootstrap_cache']) ?></li>
                </ul>
            </div>
        </section>

        <section class="p-4 rounded bg-slate-800">
            <h2 class="font-semibold mb-3">2) Banco de Dados (Teste de Conexão)</h2>
            <div class="grid md:grid-cols-5 gap-2">
                <input id="t_host" class="inp" placeholder="DB_HOST" value="<?= e($detected['default_db_host']) ?>">
                <input id="t_port" class="inp" placeholder="DB_PORT" value="3306">
                <input id="t_database" class="inp" placeholder="DB_DATABASE">
                <input id="t_username" class="inp" placeholder="DB_USERNAME">
                <input id="t_password" type="password" class="inp" placeholder="DB_PASSWORD">
            </div>
            <button id="btnTest" class="mt-3 px-4 py-2 rounded bg-blue-600">Testar Conexão</button>
            <div id="dbResult" class="text-sm mt-2"></div>
        </section>

        <section class="p-4 rounded bg-slate-800">
            <h2 class="font-semibold mb-3">3,4,5) Configuração + Super Admin + Execução</h2>
            <form method="POST" action="/install/run" class="grid md:grid-cols-2 gap-3">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input class="inp" name="app_name" placeholder="APP_NAME" value="Casino Platform">
                <input class="inp" name="app_url" placeholder="APP_URL" value="<?= e($detected['app_url']) ?>">

                <select class="inp" name="app_env">
                    <option value="production" <?= $detected['production'] ? 'selected' : '' ?>>production</option>
                    <option value="local" <?= ! $detected['production'] ? 'selected' : '' ?>>local</option>
                    <option value="staging">staging</option>
                </select>

                <select class="inp" name="app_debug">
                    <option value="false" <?= $detected['production'] ? 'selected' : '' ?>>false</option>
                    <option value="true" <?= ! $detected['production'] ? 'selected' : '' ?>>true</option>
                </select>

                <input class="inp" name="timezone" value="America/Sao_Paulo" placeholder="Timezone">
                <input class="inp" name="db_host" value="<?= e($detected['default_db_host']) ?>" placeholder="DB_HOST">
                <input class="inp" name="db_port" value="3306" placeholder="DB_PORT">
                <input class="inp" name="db_database" placeholder="DB_DATABASE">
                <input class="inp" name="db_username" placeholder="DB_USERNAME">
                <input class="inp" type="password" name="db_password" placeholder="DB_PASSWORD">
                <input class="inp" name="admin_name" placeholder="Nome do Super Admin">
                <input class="inp" name="admin_email" placeholder="Email do Super Admin">
                <input class="inp md:col-span-2" type="password" name="admin_password" placeholder="Senha do Super Admin">
                <button class="md:col-span-2 mt-2 px-4 py-3 rounded bg-emerald-600 font-semibold">Instalar Agora</button>
            </form>
            <div class="mt-2 text-xs text-slate-400">Após concluir, o instalador será bloqueado via <code>storage/installed.lock</code>.</div>
        </section>

        <div class="text-xs text-slate-400">Pronto para futura validação de licença, auto update GitHub e modo SaaS multi-tenant.</div>
    </div>
</div>
<script>
const btn = document.getElementById('btnTest');
btn?.addEventListener('click', async () => {
  const body = {
    host: document.getElementById('t_host').value,
    port: document.getElementById('t_port').value,
    database: document.getElementById('t_database').value,
    username: document.getElementById('t_username').value,
    password: document.getElementById('t_password').value,
  };

  const target = document.getElementById('dbResult');
  target.innerHTML = '<span class="loader"></span> Testando...';

  const response = await fetch('/install/test-db', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '<?= csrf_token() ?>'
    },
    body: JSON.stringify(body)
  });

  const data = await response.json();
  target.className = 'text-sm mt-2 ' + (data.ok ? 'text-emerald-400' : 'text-red-400');
  target.innerText = data.message;
});
</script>
</body>
</html>
