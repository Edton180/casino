<?php
/**
 * Instalador web — acesse https://seu-dominio/install/
 *
 * Funciona sem o Laravel inicializado (antes de existir o .env) e é
 * bloqueado automaticamente depois da instalação (storage/installed.lock).
 */

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);
require $basePath . '/app/Install/CasinoInstaller.php';

use App\Install\CasinoInstaller;

@set_time_limit(300);
@ini_set('memory_limit', '512M');
@ini_set('display_errors', '0');

$installer = new CasinoInstaller($basePath);
$tokenFile = $installer->path('storage/install.token');

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function detected_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php'))), '/');
    $dir = preg_replace('#/public$#', '', $dir);

    return ($https ? 'https://' : 'http://') . $host . $dir;
}

/* -------------------------------------------------------------------------- */
/* API (AJAX)                                                                  */
/* -------------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($installer->isInstalled()) {
        json_out(['ok' => false, 'message' => 'O sistema já está instalado. Para reinstalar, apague storage/installed.lock.'], 423);
    }

    // aceita apenas chamadas da própria página
    $origin = parse_url($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
    if ($origin && $origin !== parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
        json_out(['ok' => false, 'message' => 'Origem inválida.'], 403);
    }

    $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $action = (string) ($input['action'] ?? '');
    $db = [
        'host'     => trim((string) ($input['db']['host'] ?? 'localhost')),
        'port'     => (int) ($input['db']['port'] ?? 3306),
        'database' => trim((string) ($input['db']['database'] ?? '')),
        'username' => trim((string) ($input['db']['username'] ?? '')),
        'password' => (string) ($input['db']['password'] ?? ''),
    ];

    // depois da etapa "env", as próximas etapas exigem o token desta instalação
    $requireToken = function () use ($input, $tokenFile) {
        $token = is_file($tokenFile) ? trim((string) file_get_contents($tokenFile)) : '';
        if ($token === '' || !hash_equals($token, (string) ($input['token'] ?? ''))) {
            json_out(['ok' => false, 'message' => 'Sessão de instalação inválida. Recarregue a página e comece novamente.'], 403);
        }
    };

    try {
        switch ($action) {
            case 'requirements':
                json_out(['ok' => true] + $installer->requirements());

            case 'test_db':
                json_out($installer->testDatabase($db));

            case 'env':
                if (!$installer->requirements()['ok']) {
                    json_out(['ok' => false, 'message' => 'Corrija os requisitos pendentes antes de instalar.'], 422);
                }
                $test = $installer->testDatabase($db);
                if (!$test['ok']) {
                    json_out($test, 422);
                }
                if (!empty($test['tables']) && empty($input['overwrite'])) {
                    json_out(['ok' => false, 'message' => 'O banco já possui tabelas. Use um banco vazio ou marque "Apagar dados existentes".'], 422);
                }

                $appUrl = filter_var((string) ($input['app_url'] ?? ''), FILTER_VALIDATE_URL) ?: detected_url();
                $installer->writeEnv([
                    'app_name' => trim((string) ($input['app_name'] ?? 'Casino')) ?: 'Casino',
                    'app_url'  => $appUrl,
                    'db'       => $db,
                ]);

                $token = bin2hex(random_bytes(24));
                file_put_contents($tokenFile, $token);
                $installer->log('Arquivo .env gravado', ['url' => $appUrl]);
                json_out(['ok' => true, 'message' => 'Configuração gravada.', 'token' => $token]);

            case 'import':
                $requireToken();
                $count = $installer->importBase($db, !empty($input['overwrite']));
                $installer->log('Base importada', ['statements' => $count]);
                json_out(['ok' => true, 'message' => "Banco de dados criado ({$count} comandos)."]);

            case 'migrate':
                $requireToken();
                $installer->migrate();
                $installer->log('Migrations executadas');
                json_out(['ok' => true, 'message' => 'Estrutura atualizada.']);

            case 'admin':
                $requireToken();
                $email = filter_var((string) ($input['admin_email'] ?? ''), FILTER_VALIDATE_EMAIL);
                $password = (string) ($input['admin_password'] ?? '');
                if (!$email || strlen($password) < 8) {
                    json_out(['ok' => false, 'message' => 'Informe um e-mail válido e uma senha com pelo menos 8 caracteres.'], 422);
                }
                $installer->createAdmin(trim((string) ($input['admin_name'] ?? 'Administrador')) ?: 'Administrador', $email, $password);
                $installer->log('Administrador criado', ['email' => $email]);
                json_out(['ok' => true, 'message' => 'Administrador criado.']);

            case 'finish':
                $requireToken();
                $warnings = $installer->finish(['app_name' => (string) ($input['app_name'] ?? '')]);
                @unlink($tokenFile);
                $installer->log('Instalação concluída', ['warnings' => $warnings]);
                json_out(['ok' => true, 'message' => 'Instalação concluída!', 'warnings' => $warnings]);
        }

        json_out(['ok' => false, 'message' => 'Ação inválida.'], 400);
    } catch (Throwable $e) {
        $installer->log('Erro: ' . $e->getMessage(), ['action' => $action, 'file' => $e->getFile() . ':' . $e->getLine()]);
        json_out(['ok' => false, 'message' => $e->getMessage()], 500);
    }
}

$installed = $installer->isInstalled();
$suggestedUrl = detected_url();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Instalação — Casino Platform</title>
<style>
  :root { --bg:#0b1220; --card:#121b2d; --line:#24324a; --muted:#93a4c3; --text:#eef3ff; --accent:#22c55e; --danger:#ef4444; --warn:#f59e0b; --blue:#3b82f6; }
  * { box-sizing: border-box; }
  body { margin:0; font-family: system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif; background: radial-gradient(1200px 600px at 10% -10%, #16325b 0, transparent 60%), var(--bg); color: var(--text); min-height:100vh; }
  .wrap { max-width: 860px; margin: 0 auto; padding: 32px 16px 60px; }
  header { display:flex; align-items:center; gap:14px; margin-bottom: 24px; }
  .logo { width:46px; height:46px; border-radius:12px; background: linear-gradient(135deg,#22c55e,#16a34a); display:flex; align-items:center; justify-content:center; font-size:24px; }
  h1 { font-size: 22px; margin:0; } header p { margin: 2px 0 0; color: var(--muted); font-size: 14px; }
  .steps { display:flex; gap:8px; margin-bottom: 18px; flex-wrap: wrap; }
  .steps span { flex:1; min-width: 110px; text-align:center; padding:8px; border-radius: 999px; background: var(--card); border:1px solid var(--line); color: var(--muted); font-size: 13px; font-weight:600; }
  .steps span.on { color:#04130a; background: var(--accent); border-color: var(--accent); }
  .steps span.done { color: var(--accent); border-color: #1d5a35; }
  .card { background: var(--card); border:1px solid var(--line); border-radius: 16px; padding: 24px; }
  h2 { margin: 0 0 6px; font-size: 19px; } .lead { color: var(--muted); margin: 0 0 18px; line-height: 1.5; }
  label { display:block; font-size: 13px; color: var(--muted); font-weight:600; margin: 12px 0 6px; }
  input[type=text], input[type=password], input[type=email], input[type=number], input[type=url] { width:100%; padding: 12px; border-radius: 10px; border:1px solid var(--line); background:#0b1322; color: var(--text); font-size: 15px; outline: none; }
  input:focus { border-color: var(--blue); }
  .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 0 14px; } @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
  .actions { display:flex; gap:10px; justify-content: space-between; margin-top: 22px; flex-wrap: wrap; }
  button { border:0; border-radius: 10px; padding: 12px 20px; font-weight: 800; font-size: 15px; cursor:pointer; }
  .primary { background: var(--accent); color:#04130a; } .ghost { background: transparent; color: var(--text); border:1px solid var(--line); }
  button:disabled { opacity:.5; cursor:not-allowed; }
  .req { list-style:none; padding:0; margin:0; display:grid; grid-template-columns: 1fr 1fr; gap: 6px 16px; } @media (max-width: 640px) { .req { grid-template-columns: 1fr; } }
  .req li { display:flex; gap:8px; font-size: 14px; padding: 6px 0; border-bottom: 1px dashed #1c2940; } .req small { color: var(--muted); display:block; }
  .ok { color: var(--accent); } .bad { color: var(--danger); } .opt { color: var(--warn); }
  .msg { padding: 12px 14px; border-radius: 10px; margin-top: 14px; font-size: 14px; line-height: 1.5; }
  .msg.err { background:#3b1219; border:1px solid #7f1d1d; } .msg.good { background:#0f2e1c; border:1px solid #166534; } .msg.info { background:#10223f; border:1px solid #1e3a8a; }
  .check { display:flex; gap:10px; align-items:flex-start; margin-top: 14px; font-size: 14px; color: var(--text); font-weight: 500; }
  .check input { margin-top: 3px; width:18px; height:18px; }
  .progress li { display:flex; align-items:center; gap:10px; padding: 10px 0; border-bottom: 1px solid #1c2940; list-style:none; } .progress { padding:0; margin:0; }
  .dot { width:22px; height:22px; border-radius:50%; border:2px solid var(--line); display:inline-flex; align-items:center; justify-content:center; font-size:12px; flex:none; }
  .dot.run { border-color: var(--blue); border-top-color: transparent; animation: spin .8s linear infinite; } .dot.done { background: var(--accent); border-color: var(--accent); color:#04130a; } .dot.fail { background: var(--danger); border-color: var(--danger); }
  @keyframes spin { to { transform: rotate(360deg); } }
  .legal { background:#0b1322; border:1px solid var(--line); border-radius: 10px; padding: 14px; font-size: 13px; color: var(--muted); line-height: 1.6; max-height: 210px; overflow:auto; }
  .hint { font-size: 12px; color: var(--muted); margin-top: 6px; }
  .hidden { display:none !important; }
  a { color: #7cc4ff; }
  code { background:#0b1322; padding: 2px 6px; border-radius: 6px; }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="logo">🎰</div>
    <div><h1>Instalação da plataforma</h1><p>Assistente para VPS e hospedagem compartilhada (cPanel, Hostinger, HostGator...)</p></div>
  </header>

<?php if ($installed): ?>
  <div class="card">
    <h2>✅ O sistema já está instalado</h2>
    <p class="lead">Por segurança o instalador fica bloqueado. Para reinstalar, apague o arquivo <code>storage/installed.lock</code> pelo gerenciador de arquivos.</p>
    <div class="actions"><a href="../"><button class="primary">Abrir o site</button></a><a href="../admin"><button class="ghost">Painel administrativo</button></a></div>
  </div>
<?php else: ?>
  <?php if (isset($_GET['desinstalado'])): ?>
  <div class="card" style="margin-bottom:16px;border-color:#2f855a">
    <h2>✅ Sistema desinstalado</h2>
    <p class="lead">As tabelas do cassino, a configuração (<code>.env</code>), os arquivos enviados, logs e caches foram apagados.
      <?php $bkp = (string) ($_GET['backup'] ?? ''); ?>
      O backup final<?= preg_match('/^casino-backup-[0-9]{8}-[0-9]{6}\.zip$/', $bkp) ? ' (<code>' . htmlspecialchars($bkp, ENT_QUOTES) . '</code>)' : '' ?> ficou numa pasta <code>casino-backups</code> fora do site (normalmente ao lado da pasta do site).</p>
    <p class="lead">Para remover tudo, apague a pasta do site pelo gerenciador de arquivos da hospedagem e, se quiser, o banco e o usuário MySQL pelo painel (cPanel → Bancos de dados MySQL).
      Para usar de novo, siga a instalação abaixo e depois restaure o backup em <b>Painel → Sistema</b>.</p>
  </div>
  <?php endif; ?>
  <div class="steps" id="steps">
    <span data-step="0">1. Início</span><span data-step="1">2. Requisitos</span><span data-step="2">3. Banco</span><span data-step="3">4. Site e admin</span><span data-step="4">5. Instalar</span>
  </div>

  <!-- 0: boas-vindas -->
  <section class="card step" data-step="0">
    <h2>Bem-vindo 👋</h2>
    <p class="lead">Este assistente vai verificar o servidor, criar o banco de dados, configurar o arquivo <code>.env</code> e criar o seu usuário administrador. Leva cerca de 1 minuto.</p>
    <p class="lead">Antes de começar, tenha em mãos os dados do banco MySQL criado no painel da hospedagem (nome do banco, usuário e senha).</p>
    <div class="legal">
      <b>Aviso legal.</b> Operar jogos de azar e apostas online exige licença na maior parte dos países (no Brasil, autorização da Secretaria de Prêmios e Apostas do Ministério da Fazenda — Lei 14.790/2023).
      Você é o único responsável por obter as licenças necessárias, cumprir as regras de jogo responsável, prevenção à lavagem de dinheiro (KYC/PLD) e proteção de dados (LGPD),
      e por usar apenas jogos e provedores que você tenha direito de distribuir. Os gateways de pagamento também exigem conta aprovada para esse segmento.
    </div>
    <label class="check"><input type="checkbox" id="accept"> Li e concordo com o aviso acima.</label>
    <div class="actions"><span></span><button class="primary" id="startBtn" disabled>Começar</button></div>
  </section>

  <!-- 1: requisitos -->
  <section class="card step hidden" data-step="1">
    <h2>Requisitos do servidor</h2>
    <p class="lead">Itens em vermelho precisam ser corrigidos no painel da hospedagem (normalmente em <i>Selecionar versão do PHP → Extensões</i>).</p>
    <ul class="req" id="reqList"><li>Verificando...</li></ul>
    <div id="reqMsg"></div>
    <div class="actions"><button class="ghost" data-back>Voltar</button><div><button class="ghost" id="reqRetry">Verificar novamente</button> <button class="primary" id="reqNext" disabled>Continuar</button></div></div>
  </section>

  <!-- 2: banco -->
  <section class="card step hidden" data-step="2">
    <h2>Banco de dados MySQL</h2>
    <p class="lead">Crie um banco e um usuário no painel (cPanel → <i>Bancos de dados MySQL</i>), adicione o usuário ao banco com <b>todos os privilégios</b> e informe os dados abaixo.</p>
    <div class="grid">
      <div><label>Servidor (host)</label><input type="text" id="dbHost" value="localhost"><div class="hint">Na hospedagem compartilhada quase sempre é <code>localhost</code>.</div></div>
      <div><label>Porta</label><input type="number" id="dbPort" value="3306"></div>
      <div><label>Nome do banco</label><input type="text" id="dbName" placeholder="ex: usuario_casino"></div>
      <div><label>Usuário do banco</label><input type="text" id="dbUser" placeholder="ex: usuario_casino"></div>
      <div><label>Senha do banco</label><input type="password" id="dbPass"></div>
    </div>
    <label class="check hidden" id="overwriteBox"><input type="checkbox" id="overwrite"> <span><b>Apagar dados existentes</b> — o banco já tem tabelas; elas serão removidas.</span></label>
    <div id="dbMsg"></div>
    <div class="actions"><button class="ghost" data-back>Voltar</button><div><button class="ghost" id="dbTest">Testar conexão</button> <button class="primary" id="dbNext" disabled>Continuar</button></div></div>
  </section>

  <!-- 3: site e admin -->
  <section class="card step hidden" data-step="3">
    <h2>Site e administrador</h2>
    <div class="grid">
      <div><label>Nome do site</label><input type="text" id="appName" value="Casino"></div>
      <div><label>Endereço do site (URL)</label><input type="url" id="appUrl" value="<?= e($suggestedUrl) ?>"><div class="hint">Use <code>https://</code> se o SSL já estiver ativo.</div></div>
      <div><label>Seu nome</label><input type="text" id="adminName" value="Administrador"></div>
      <div><label>E-mail de acesso ao painel</label><input type="email" id="adminEmail" placeholder="voce@seudominio.com"></div>
      <div><label>Senha (mín. 8 caracteres)</label><input type="password" id="adminPass"></div>
      <div><label>Confirme a senha</label><input type="password" id="adminPass2"></div>
    </div>
    <div id="adminMsg"></div>
    <div class="actions"><button class="ghost" data-back>Voltar</button><button class="primary" id="installBtn">Instalar agora</button></div>
  </section>

  <!-- 4: progresso -->
  <section class="card step hidden" data-step="4">
    <h2 id="runTitle">Instalando...</h2>
    <p class="lead" id="runLead">Não feche esta página.</p>
    <ul class="progress" id="progress"></ul>
    <div id="runMsg"></div>
    <div class="actions hidden" id="doneActions"><a id="siteLink" href="../"><button class="primary">Abrir o site</button></a><a id="adminLink" href="../admin"><button class="ghost">Entrar no painel /admin</button></a></div>
    <div class="actions hidden" id="retryActions"><button class="ghost" data-back>Voltar</button><button class="primary" id="retryBtn">Tentar novamente</button></div>
  </section>
<?php endif; ?>
</div>

<script>
(function () {
  if (!document.getElementById('steps')) return;
  var current = 0, token = null, tablesFound = 0;
  var $ = function (id) { return document.getElementById(id); };

  function show(step) {
    current = step;
    document.querySelectorAll('section.step').forEach(function (s) { s.classList.toggle('hidden', Number(s.dataset.step) !== step); });
    document.querySelectorAll('#steps span').forEach(function (s) {
      var n = Number(s.dataset.step);
      s.className = n === step ? 'on' : (n < step ? 'done' : '');
    });
    window.scrollTo(0, 0);
  }

  function msg(el, text, type) { $(el).innerHTML = text ? '<div class="msg ' + type + '">' + text + '</div>' : ''; }

  function call(action, extra) {
    var body = Object.assign({ action: action, token: token, db: db(), overwrite: $('overwrite').checked }, extra || {});
    return fetch(location.pathname, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      .then(function (r) {
        return r.text().then(function (t) {
          var data;
          try { data = JSON.parse(t); } catch (e) { throw new Error('Resposta inválida do servidor (HTTP ' + r.status + '). Veja storage/logs/installer.log.'); }
          if (!r.ok || data.ok === false) throw new Error(data.message || ('Erro HTTP ' + r.status));
          return data;
        });
      });
  }

  function db() {
    return { host: $('dbHost').value.trim(), port: Number($('dbPort').value) || 3306, database: $('dbName').value.trim(), username: $('dbUser').value.trim(), password: $('dbPass').value };
  }

  document.querySelectorAll('[data-back]').forEach(function (b) { b.onclick = function () { show(Math.max(0, current - 1)); }; });

  // 0
  $('accept').onchange = function () { $('startBtn').disabled = !this.checked; };
  $('startBtn').onclick = function () { show(1); checkReq(); };

  // 1
  function checkReq() {
    $('reqList').innerHTML = '<li>Verificando...</li>'; $('reqNext').disabled = true;
    call('requirements').then(function (data) {
      $('reqList').innerHTML = data.items.map(function (i) {
        var cls = i.ok ? 'ok' : (i.required ? 'bad' : 'opt');
        return '<li><span class="' + cls + '">' + (i.ok ? '✔' : (i.required ? '✖' : '!')) + '</span><span>' + i.label + '<small>' + i.detail + '</small></span></li>';
      }).join('');
      $('reqNext').disabled = !data.ok;
      msg('reqMsg', data.ok ? 'Tudo certo com o servidor.' : 'Corrija os itens em vermelho e clique em "Verificar novamente".', data.ok ? 'good' : 'err');
    }).catch(function (e) { msg('reqMsg', e.message, 'err'); });
  }
  $('reqRetry').onclick = checkReq;
  $('reqNext').onclick = function () { show(2); };

  // 2
  ['dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass'].forEach(function (id) { $(id).oninput = function () { $('dbNext').disabled = true; }; });
  $('dbTest').onclick = function () {
    var btn = this; btn.disabled = true; msg('dbMsg', 'Testando...', 'info');
    call('test_db').then(function (data) {
      tablesFound = data.tables || 0;
      $('overwriteBox').classList.toggle('hidden', !tablesFound);
      msg('dbMsg', data.message, data.ok ? 'good' : 'err');
      $('dbNext').disabled = !data.ok;
    }).catch(function (e) { msg('dbMsg', e.message, 'err'); }).finally(function () { btn.disabled = false; });
  };
  $('dbNext').onclick = function () {
    if (tablesFound && !$('overwrite').checked) { msg('dbMsg', 'O banco já possui tabelas. Marque "Apagar dados existentes" ou use outro banco.', 'err'); return; }
    show(3);
  };

  // 3/4
  var STEPS = [
    ['env', 'Gravando configuração (.env)'],
    ['import', 'Criando banco de dados e jogos'],
    ['migrate', 'Atualizando estrutura'],
    ['admin', 'Criando administrador'],
    ['finish', 'Finalizando']
  ];

  function runInstall() {
    var payload = {
      app_name: $('appName').value.trim(), app_url: $('appUrl').value.trim(),
      admin_name: $('adminName').value.trim(), admin_email: $('adminEmail').value.trim(), admin_password: $('adminPass').value
    };
    show(4);
    $('doneActions').classList.add('hidden'); $('retryActions').classList.add('hidden'); msg('runMsg', '');
    $('runTitle').textContent = 'Instalando...'; $('runLead').textContent = 'Não feche esta página.';
    $('progress').innerHTML = STEPS.map(function (s, i) { return '<li id="p' + i + '"><span class="dot"></span>' + s[1] + '</li>'; }).join('');

    var i = 0;
    (function next() {
      if (i >= STEPS.length) {
        $('runTitle').textContent = '🎉 Instalação concluída!';
        $('runLead').innerHTML = 'Acesse o painel em <code>' + payload.app_url.replace(/\/$/, '') + '/admin</code> com o e-mail <b>' + payload.admin_email + '</b>.';
        $('siteLink').href = payload.app_url; $('adminLink').href = payload.app_url.replace(/\/$/, '') + '/admin';
        $('doneActions').classList.remove('hidden');
        return;
      }
      var dot = document.querySelector('#p' + i + ' .dot'); dot.className = 'dot run';
      call(STEPS[i][0], payload).then(function (data) {
        if (data.token) token = data.token;
        if (data.warnings && data.warnings.length) msg('runMsg', data.warnings.join('<br>'), 'info');
        dot.className = 'dot done'; dot.textContent = '✔'; i++; next();
      }).catch(function (e) {
        dot.className = 'dot fail'; dot.textContent = '✖';
        $('runTitle').textContent = 'A instalação parou';
        $('runLead').textContent = 'Corrija o problema abaixo e tente novamente.';
        msg('runMsg', e.message, 'err');
        $('retryActions').classList.remove('hidden');
      });
    })();
  }

  $('installBtn').onclick = function () {
    var email = $('adminEmail').value.trim(), pass = $('adminPass').value;
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return msg('adminMsg', 'Informe um e-mail válido.', 'err');
    if (pass.length < 8) return msg('adminMsg', 'A senha precisa ter pelo menos 8 caracteres.', 'err');
    if (pass !== $('adminPass2').value) return msg('adminMsg', 'As senhas não conferem.', 'err');
    if (!/^https?:\/\//.test($('appUrl').value.trim())) return msg('adminMsg', 'Informe a URL completa, ex: https://seudominio.com', 'err');
    msg('adminMsg', '');
    token = null;
    runInstall();
  };
  $('retryBtn').onclick = function () { token = null; runInstall(); };

  show(0);
})();
</script>
</body>
</html>
