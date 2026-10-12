<#
.SYNOPSIS
  Casino Platform — assistente de instalação para Windows (computador local).

.DESCRIPTION
  Identifica o Windows, procura PHP / MySQL / Composer (inclusive o do XAMPP e do Laragon),
  instala o que faltar sem precisar de administrador (PHP e Composer em %LOCALAPPDATA%\CasinoPlatform;
  MariaDB via winget, se disponível), instala o site e abre no navegador.

  Uso (dê dois cliques em instalar-windows.bat, ou no PowerShell):
     powershell -ExecutionPolicy Bypass -File scripts\install-windows.ps1
     ... -Porta 8000 -Nome "Meu Cassino" -Email admin@casino.local -Senha admin12345
     ... -Acao Ligar | Desligar | Atualizar | Backup | Restaurar | Desinstalar | Diagnostico

  Atualizar   baixa a versão nova (git), faz backup e atualiza o banco sem perder dados
  Backup      banco, .env e arquivos enviados num .zip em %USERPROFILE%\casino-backups
  Restaurar   volta um backup (-Arquivo caminho.zip, ou escolhe na lista)
  Desinstalar remove esta e outras instalações encontradas (banco, pasta, PHP/Composer baixados);
              -Tudo apaga sem perguntar, -ManterBanco não apaga o banco, -SemBackup pula o backup final
#>
[CmdletBinding()]
param(
  [ValidateSet('Instalar', 'Ligar', 'Desligar', 'Atualizar', 'Backup', 'Restaurar', 'Desinstalar', 'Diagnostico')]
  [string]$Acao = 'Instalar',
  [int]$Porta = 0,
  [string]$Nome = 'Meu Cassino',
  [string]$Email = 'admin@casino.local',
  [string]$Senha = 'admin12345',
  [string]$DbHost = '127.0.0.1',
  [int]$DbPorta = 3306,
  [string]$DbNome = 'casino_local',
  [string]$DbUsuario = '',
  [string]$DbSenha = '',
  [switch]$SemAbrirNavegador,
  [switch]$Sim,
  [string]$Arquivo = '',
  [switch]$Tudo,
  [switch]$ManterBanco,
  [switch]$SemBackup
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch {}
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

$Versao = '9.4.0'

# ---- Executado sem o projeto ao lado (ex.: irm .../install-windows.ps1 | iex): baixa o código e continua ----
$Aqui = $PSScriptRoot
if (-not $Aqui -or -not (Test-Path (Join-Path $Aqui '..\artisan'))) {
  $Destino = Join-Path $env:USERPROFILE 'casino'
  Write-Host ''
  Write-Host '  Casino Platform - baixando o código para' $Destino -ForegroundColor Cyan
  if (-not (Test-Path (Join-Path $Destino 'artisan'))) {
    if (Get-Command git -ErrorAction SilentlyContinue) {
      & git clone --depth 1 https://github.com/Edton180/casino.git $Destino
      if ($LASTEXITCODE -ne 0) { throw 'Não consegui baixar o código com o git. Confira a internet.' }
    } else {
      $zip = Join-Path $env:TEMP 'casino-main.zip'
      $tmp = Join-Path $env:TEMP ('casino-extract-' + [guid]::NewGuid().ToString('N'))
      Write-Host '  Baixando o ZIP do repositório (pode demorar, são ~400 MB)...'
      Invoke-WebRequest -UseBasicParsing 'https://codeload.github.com/Edton180/casino/zip/refs/heads/main' -OutFile $zip
      Expand-Archive -Path $zip -DestinationPath $tmp -Force
      Move-Item (Join-Path $tmp 'casino-main') $Destino
      Remove-Item $zip, $tmp -Recurse -Force -ErrorAction SilentlyContinue
    }
  }
  $filho = Join-Path $Destino 'scripts\install-windows.ps1'
  & $filho @PSBoundParameters
  return
}

$Raiz = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$ArqVersao = Join-Path $Raiz 'VERSION'
if (Test-Path $ArqVersao) { $Versao = ((Get-Content $ArqVersao -TotalCount 1) -as [string]).Trim() }
$Dados = Join-Path $env:LOCALAPPDATA 'CasinoPlatform'
$PhpDir = Join-Path $Dados 'php'
$Log = Join-Path $env:TEMP ("casino-install-{0}.log" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
$PastaBackups = Join-Path $env:USERPROFILE 'casino-backups'
$Registro = Join-Path $Dados 'instalacoes.json'   # o que o assistente instalou (para desinstalar sem deixar rastros)
New-Item -ItemType Directory -Force -Path $Dados | Out-Null

# ----------------------------------------------------------------- interface
$Esc = [char]27
$UsaCor = $true
try { $null = $Host.UI.RawUI.WindowSize } catch { $UsaCor = $false }
function C($codigo, $texto) { if ($UsaCor) { "$Esc[$codigo" + "m$texto$Esc[0m" } else { $texto } }
function Verde($t) { C '38;5;41' $t }
function Vermelho($t) { C '38;5;203' $t }
function Amarelo($t) { C '38;5;220' $t }
function Cinza($t) { C '38;5;244' $t }
function Azul($t) { C '38;5;39' $t }
function Negrito($t) { C '1' $t }

$script:Etapa = 0
$script:Etapas = 5
$Inicio = Get-Date

function Banner {
  Write-Host ''
  $cores = '46', '47', '48', '49', '44'
  $linhas = @(
    '   ____          _               ____  _       _    __',
    '  / ___|__ _ ___(_)_ __   ___   |  _ \| | __ _| |_ / _| ___  _ __ _ __ ___',
    " | |   / _`` / __| | '_ \ / _ \  | |_) | |/ _`` | __| |_ / _ \| '__| '_ `` _ \",
    ' | |__| (_| \__ \ | | | | (_) | |  __/| | (_| | |_|  _| (_) | |  | | | | | |',
    '  \____\__,_|___/_|_| |_|\___/  |_|   |_|\__,_|\__|_|  \___/|_|  |_| |_| |_|')
  for ($i = 0; $i -lt $linhas.Count; $i++) { Write-Host (C ("38;5;" + $cores[$i]) $linhas[$i]) }
  Write-Host (Cinza "  Assistente de instalação para Windows v$Versao")
  Write-Host ''
}

function Etapa($titulo) {
  $script:Etapa++
  $feito = [int]([math]::Floor($script:Etapa * 20 / $script:Etapas))
  $barra = (Verde ('█' * $feito)) + (Cinza ('░' * (20 - $feito)))
  Write-Host ''
  Write-Host ("$barra " + (Cinza "$($script:Etapa)/$($script:Etapas)") + '  ' + (Negrito $titulo))
}
function Ok($t) { Write-Host ("  " + (Verde '✔') + " $t") }
function Aviso($t) { Write-Host ("  " + (Amarelo '!') + " $t") }
function Falha($t) { Write-Host ''; Write-Host ((Vermelho '✖ ') + $t) -ForegroundColor Red }
function Linha($estado, $rotulo, $valor) {
  $icone = switch ($estado) { 'ok' { Verde '✔' } 'aviso' { Amarelo '!' } 'erro' { Vermelho '✖' } default { Cinza '●' } }
  Write-Host ("  $icone {0,-24} {1}" -f $rotulo, $valor)
}
function Caixa($titulo, $linhasCaixa) {
  $sem = { param($s) ($s -replace "$Esc\[[0-9;]*m", '').Length }
  # alarga para a maior linha (até a largura da janela)
  $largura = 74
  foreach ($l in $linhasCaixa) { $largura = [math]::Max($largura, (& $sem $l) + 4) }
  try { $janela = $Host.UI.RawUI.WindowSize.Width; if ($janela -gt 20) { $largura = [math]::Min($largura, $janela - 1) } } catch {}
  $largura = [math]::Max($largura, 40)
  $topo = '╭─ ' + $titulo + ' ' + ('─' * [math]::Max(0, $largura - 5 - $titulo.Length)) + '╮'
  Write-Host (Cinza $topo)
  foreach ($l in $linhasCaixa) {
    $pad = [math]::Max(0, $largura - 4 - (& $sem $l))
    Write-Host ((Cinza '│') + ' ' + $l + (' ' * $pad) + ' ' + (Cinza '│'))
  }
  Write-Host (Cinza ('╰' + ('─' * ($largura - 2)) + '╯'))
}
function Pergunta($texto, $padrao) {
  if ($Sim) { return $padrao }
  $r = Read-Host ("  ? $texto [$padrao]")
  if ([string]::IsNullOrWhiteSpace($r)) { $padrao } else { $r }
}
function Confirma($texto, $padraoSim = $true) {
  if ($Sim) { return $padraoSim }
  $d = if ($padraoSim) { 'S/n' } else { 's/N' }
  $r = Read-Host "  ? $texto [$d]"
  if ([string]::IsNullOrWhiteSpace($r)) { return $padraoSim }
  return ($r -match '^(s|y)')
}

# Executa um comando mostrando animação; a saída completa vai para o log.
function Executa($descricao, [scriptblock]$bloco) {
  $quadros = '⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'
  $job = Start-Job -ScriptBlock $bloco
  $t0 = Get-Date; $i = 0
  while ($job.State -eq 'Running') {
    $seg = [int]((Get-Date) - $t0).TotalSeconds
    Write-Host -NoNewline ("`r  " + (Verde $quadros[$i % $quadros.Count]) + " $descricao " + (Cinza "${seg}s") + '   ')
    $i++; Start-Sleep -Milliseconds 120
  }
  $saida = Receive-Job $job -ErrorAction SilentlyContinue 2>&1
  $saida | Out-File -Append -Encoding utf8 $Log
  # composer/git escrevem avisos no stderr: só conta como falha se o bloco lançar exceção (throw)
  $erro = $job.State -eq 'Failed'
  if ($erro) {
    $motivo = @($job.ChildJobs[0].JobStateInfo.Reason | Where-Object { $_ } | ForEach-Object { $_.Message })
    ($motivo -join "`n") | Out-File -Append -Encoding utf8 $Log
  }
  Remove-Job $job -Force
  Write-Host -NoNewline "`r$(' ' * 100)`r"
  if ($erro) {
    Write-Host ("  " + (Vermelho '✖') + " $descricao")
    Write-Host ''; Write-Host (Vermelho 'Falhou. Últimas linhas do registro:')
    Get-Content $Log -Tail 12 | ForEach-Object { Write-Host ("    " + (Cinza $_)) }
    Write-Host "Registro completo: $Log"
    throw "Falha em: $descricao"
  }
  Ok $descricao
}

# --------------------------------------------------------------- ambiente
function Encontra-Php {
  $candidatos = @()
  $cmd = Get-Command php -ErrorAction SilentlyContinue
  if ($cmd) { $candidatos += $cmd.Source }
  $candidatos += (Join-Path $PhpDir 'php.exe'), 'C:\xampp\php\php.exe', 'C:\laragon\bin\php\php.exe'
  $candidatos += Get-ChildItem 'C:\laragon\bin\php\*\php.exe' -ErrorAction SilentlyContinue | Sort-Object FullName -Descending | ForEach-Object FullName
  foreach ($c in $candidatos) {
    if ($c -and (Test-Path $c)) {
      try {
        $v = & $c -r 'echo PHP_VERSION_ID;' 2>$null
        if ([int]$v -ge 80200) { return $c }
      } catch {}
    }
  }
  return $null
}

$Extensoes = 'pdo_mysql', 'mbstring', 'xml', 'curl', 'zip', 'intl', 'gd', 'bcmath', 'fileinfo', 'openssl', 'tokenizer', 'ctype', 'dom'
function Extensoes-Faltando($php) {
  $mods = (& $php -m 2>$null | ForEach-Object { $_.ToLower() })
  return @($Extensoes | Where-Object { $mods -notcontains $_ })
}

function Instala-Php {
  Executa 'Baixando o PHP 8.3 (windows.php.net)' {
    $idx = Invoke-RestMethod 'https://windows.php.net/downloads/releases/releases.json'
    $rel = $idx.'8.3'
    $chave = $rel.PSObject.Properties.Name | Where-Object { $_ -match '^nts-Win32-vs16-x64$|^nts-Win32-vs17-x64$' } | Select-Object -First 1
    if (-not $chave) { throw 'Não encontrei o PHP 8.3 NTS x64 em windows.php.net' }
    $arq = $rel.$chave.zip.path
    $zip = Join-Path $env:TEMP $arq
    Invoke-WebRequest "https://windows.php.net/downloads/releases/$arq" -OutFile $zip
    if (Test-Path $using:PhpDir) { Remove-Item -Recurse -Force $using:PhpDir }
    Expand-Archive -Path $zip -DestinationPath $using:PhpDir -Force
  }
  # php.ini com as extensões necessárias
  $ini = Join-Path $PhpDir 'php.ini'
  Copy-Item (Join-Path $PhpDir 'php.ini-development') $ini -Force
  $conteudo = Get-Content $ini -Raw
  $conteudo = $conteudo -replace ';\s*extension_dir\s*=\s*"ext"', 'extension_dir = "ext"'
  foreach ($e in 'pdo_mysql', 'mbstring', 'curl', 'zip', 'intl', 'gd', 'bcmath', 'fileinfo', 'openssl') {
    $conteudo = $conteudo -replace "(?m)^;\s*extension=$e\s*$", "extension=$e"
  }
  $conteudo = $conteudo -replace '(?m)^;\s*curl\.cainfo\s*=.*$', ''
  Set-Content $ini $conteudo -Encoding ascii
  Registra 'PhpBaixado' $true
  Ok "PHP instalado em $PhpDir"
}

function Ativa-Extensoes($php) {
  $ini = & $php -r 'echo php_ini_loaded_file();' 2>$null
  if (-not $ini -or -not (Test-Path $ini)) { return }
  $conteudo = Get-Content $ini -Raw
  foreach ($e in (Extensoes-Faltando $php)) {
    $conteudo = $conteudo -replace "(?m)^;\s*extension=$e\s*$", "extension=$e"
  }
  Set-Content $ini $conteudo -Encoding ascii
}

function Banco-Responde {
  try { $c = New-Object Net.Sockets.TcpClient; $c.Connect($DbHost, $DbPorta); $c.Close(); return $true } catch { return $false }
}
function Testa-Login-Banco($php, $usuario, $senha) {   # 'ok' | 'acesso' | 'outro: mensagem'
  # script em arquivo + variáveis de ambiente: o PowerShell 5.1 estraga aspas e argumentos vazios de programas externos
  $arq = Join-Path $env:TEMP ('casino-testa-banco-' + [guid]::NewGuid().ToString('N') + '.php')
  Set-Content -Path $arq -Encoding ascii -Value @'
<?php
try {
    new PDO('mysql:host=' . getenv('CASINO_DB_HOST') . ';port=' . getenv('CASINO_DB_PORT'), (string) getenv('CASINO_DB_USER'), (string) getenv('CASINO_DB_PASS'));
    echo 'ok';
} catch (Throwable $e) {
    $m = $e->getMessage();
    echo (strpos($m, '[1045]') !== false || strpos($m, '[1698]') !== false) ? 'acesso' : 'outro: ' . $m;
}
'@
  $env:CASINO_DB_HOST = $DbHost; $env:CASINO_DB_PORT = "$DbPorta"; $env:CASINO_DB_USER = $usuario; $env:CASINO_DB_PASS = $senha
  try { $r = & $php $arq 2>$null }
  finally {
    Remove-Item Env:CASINO_DB_PASS, Env:CASINO_DB_USER -ErrorAction SilentlyContinue
    Remove-Item $arq -ErrorAction SilentlyContinue
  }
  return (($r | Out-String).Trim())
}

function Acha-MysqlExe {
  foreach ($p in 'C:\xampp\mysql\bin\mysql.exe', 'C:\laragon\bin\mysql\*\bin\mysql.exe', 'C:\Program Files\MariaDB*\bin\mysql.exe') {
    $f = Get-ChildItem $p -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($f) { return $f.FullName }
  }
  $cmd = Get-Command mysql -ErrorAction SilentlyContinue
  if ($cmd) { return $cmd.Source }
  return $null
}

function Instala-Composer($php) {
  $dest = Join-Path $Dados 'composer.phar'
  if (-not (Test-Path $dest)) {
    Executa 'Baixando o Composer' {
      $ok = $false
      foreach ($u in 'https://getcomposer.org/download/latest-stable/composer.phar', 'https://github.com/composer/composer/releases/latest/download/composer.phar') {
        try { Invoke-WebRequest $u -OutFile $using:dest; if ((Get-Item $using:dest).Length -gt 1MB) { $ok = $true; break } } catch { "Falha em $u : $($_.Exception.Message)" }
      }
      if (-not $ok) { throw 'Não consegui baixar o Composer (verifique a internet ou o antivírus/firewall).' }
    }
    Registra 'ComposerBaixado' $true
  } else { Ok 'Composer já baixado' }
  return $dest
}

function Porta-Livre($inicial) {
  $p = $inicial
  while ($true) {
    $ocupada = $false
    try { $c = New-Object Net.Sockets.TcpClient; $c.Connect('127.0.0.1', $p); $c.Close(); $ocupada = $true } catch {}
    if (-not $ocupada) { return $p }
    $p++
  }
}

function Caminho-Pid { Join-Path $Raiz 'storage\local-server.pid' }
function Servidor-Ligado {
  $pf = Caminho-Pid
  if (-not (Test-Path $pf)) { return $false }
  $id = [int](Get-Content $pf)
  return [bool](Get-Process -Id $id -ErrorAction SilentlyContinue)
}
function Desliga-Site {
  if (Servidor-Ligado) { Stop-Process -Id ([int](Get-Content (Caminho-Pid))) -Force; Remove-Item (Caminho-Pid) -Force; Ok 'Site desligado.' }
  else { Aviso 'O site local não está ligado.' }
}
function Liga-Site($php) {
  $envFile = Join-Path $Raiz '.env'
  if (-not (Test-Path $envFile)) { throw 'Instalação incompleta (sem .env). Rode a instalação primeiro.' }
  $url = (Select-String -Path $envFile -Pattern '^APP_URL=(.*)$').Matches[0].Groups[1].Value
  $p = if ($url -match ':(\d+)/?$') { [int]$Matches[1] } else { 8000 }
  if (Servidor-Ligado) { Ok "O site já está ligado (PID $(Get-Content (Caminho-Pid)))"; return $p }
  $livre = Porta-Livre $p
  if ($livre -ne $p) { Aviso "A porta $p está ocupada; usando $livre."; $p = $livre
    (Get-Content $envFile) -replace '^APP_URL=.*$', "APP_URL=http://127.0.0.1:$p" | Set-Content $envFile -Encoding utf8 }
  $roteador = Join-Path $Raiz 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
  $publico = Join-Path $Raiz 'public'
  $logSrv = Join-Path $Raiz 'storage\logs\local-server.log'
  $proc = Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$p", "`"$roteador`"") -WorkingDirectory $publico `
    -WindowStyle Hidden -PassThru -RedirectStandardError $logSrv -RedirectStandardOutput (Join-Path $Raiz 'storage\logs\local-server.out.log')
  Set-Content (Caminho-Pid) $proc.Id
  $ok = $false
  for ($i = 0; $i -lt 20; $i++) {
    try { $r = Invoke-WebRequest "http://127.0.0.1:$p/" -UseBasicParsing -TimeoutSec 5; if ($r.StatusCode -lt 400) { $ok = $true; break } } catch { Start-Sleep -Milliseconds 700 }
  }
  if (-not $ok) { throw "O servidor subiu mas o site não respondeu. Veja $logSrv" }
  Ok "Site ligado e respondendo em http://127.0.0.1:$p"
  return $p
}

function Diagnostico {
  Banner
  $so = Get-CimInstance Win32_OperatingSystem
  $php = Encontra-Php
  Caixa 'Diagnóstico do ambiente' @(
    ("{0,-12} {1}" -f 'Sistema', "$($so.Caption)  ·  $($env:PROCESSOR_ARCHITECTURE)"),
    ("{0,-12} {1}" -f 'Usuário', $env:USERNAME))
  Write-Host ''
  if ($php) {
    $f = Extensoes-Faltando $php
    if ($f.Count -eq 0) { Linha 'ok' 'PHP' "$(& $php -r 'echo PHP_VERSION;') ($php)" } else { Linha 'aviso' 'PHP' "faltam extensões: $($f -join ', ')" }
  } else { Linha 'aviso' 'PHP 8.2+' 'não encontrado (o assistente instala)' }
  if (Banco-Responde) { Linha 'ok' 'MySQL/MariaDB' "respondendo em ${DbHost}:$DbPorta" } else { Linha 'aviso' 'MySQL/MariaDB' 'não está respondendo (inicie o MySQL do XAMPP/Laragon ou deixe o assistente instalar)' }
  Linha ($(if (Get-Command winget -ErrorAction SilentlyContinue) { 'ok' } else { 'info' })) 'winget' $(if (Get-Command winget -ErrorAction SilentlyContinue) { 'disponível' } else { 'não disponível' })
  Linha ($(if (Test-Path (Join-Path $Raiz '.env')) { 'ok' } else { 'aviso' })) 'Instalação' $(if (Test-Path (Join-Path $Raiz '.env')) { "encontrada em $Raiz" } else { 'ainda não instalado' })
  Linha ($(if (Servidor-Ligado) { 'ok' } else { 'info' })) 'Site local' $(if (Servidor-Ligado) { 'ligado' } else { 'desligado' })
  Write-Host ''
}

# ------------------------------------------- registro, backup, restauração e desinstalação
function Le-Registro {
  $r = $null
  if (Test-Path $Registro) { try { $r = Get-Content $Registro -Raw | ConvertFrom-Json } catch { $r = $null } }
  if (-not $r) { $r = [pscustomobject]@{} }
  foreach ($k in 'Instalacoes', 'PhpBaixado', 'ComposerBaixado', 'MariaDbWinget') {
    if ($r.PSObject.Properties.Name -notcontains $k) {
      $r | Add-Member -NotePropertyName $k -NotePropertyValue $(if ($k -eq 'Instalacoes') { @() } else { $false })
    }
  }
  $r.Instalacoes = @($r.Instalacoes | Where-Object { $_ -and $_.Pasta })
  return $r
}
function Grava-Registro($r) { ConvertTo-Json -InputObject $r -Depth 4 | Set-Content -Path $Registro -Encoding utf8 }
function Registra($chave, $valor) { $r = Le-Registro; $r.$chave = $valor; Grava-Registro $r }
function Registra-Instalacao($pasta, $banco) {
  $r = Le-Registro
  $r.Instalacoes = @(@($r.Instalacoes | Where-Object { $_.Pasta -ne $pasta }) + [pscustomobject]@{ Pasta = $pasta; Banco = $banco; Data = (Get-Date).ToString('s'); Versao = $Versao })
  Grava-Registro $r
}
function Tira-Do-Registro($pasta) { $r = Le-Registro; $r.Instalacoes = @($r.Instalacoes | Where-Object { $_.Pasta -ne $pasta }); Grava-Registro $r }

function Valor-Env($pasta, $chave) {
  $f = Join-Path $pasta '.env'
  if (-not (Test-Path $f)) { return '' }
  $m = Select-String -Path $f -Pattern ('^' + [regex]::Escape($chave) + '=(.*)$') | Select-Object -Last 1
  if (-not $m) { return '' }
  return $m.Matches[0].Groups[1].Value.Trim().Trim('"').Trim("'")
}
function Artisan-Tem($php, $pasta, $comando) {
  if (-not $php -or -not (Test-Path (Join-Path $pasta 'vendor\autoload.php'))) { return $false }
  Push-Location $pasta
  try { $lista = @(& $php artisan list --raw 2>$null) } catch { $lista = @() } finally { Pop-Location }
  return [bool]($lista | Where-Object { $_ -match ('^' + [regex]::Escape($comando) + '\s') })
}
function Versao-Instalada($pasta) {
  $lock = Join-Path $pasta 'storage\installed.lock'
  if (-not (Test-Path $lock)) { return '' }
  try { return [string](Get-Content $lock -Raw | ConvertFrom-Json).version } catch { return '' }
}
function Faz-Backup($php) {
  New-Item -ItemType Directory -Force -Path $PastaBackups | Out-Null
  Executa 'Backup do banco, do .env e dos arquivos enviados' {
    Set-Location $using:Raiz
    & $using:php artisan system:backup "--output=$using:PastaBackups"
    if ($LASTEXITCODE -ne 0) { throw 'system:backup falhou' }
  }
  $ultimo = Get-ChildItem $PastaBackups -Filter 'casino-backup-*.zip' -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
  if ($ultimo) { return $ultimo.FullName }
  return ''
}
function Lista-Backups {
  $pastas = @($PastaBackups, (Join-Path $Raiz 'storage\app\backups') | Where-Object { Test-Path $_ })
  if ($pastas.Count -eq 0) { return ,@() }
  return ,@(Get-ChildItem $pastas -Filter 'casino-backup-*.zip' -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 8)
}
function Restaura($php) {
  $arq = $Arquivo
  if (-not $arq) {
    $lista = Lista-Backups
    if ($lista.Count -eq 0) { throw "Nenhum backup encontrado em $PastaBackups. Informe o arquivo: -Arquivo C:\caminho\casino-backup.zip" }
    Write-Host ''
    for ($i = 0; $i -lt $lista.Count; $i++) {
      Write-Host ("   {0}) {1}  {2}" -f ($i + 1), $lista[$i].Name, (Cinza ('{0:N1} MB  ·  {1:dd/MM/yyyy HH:mm}' -f ($lista[$i].Length / 1MB), $lista[$i].LastWriteTime)))
    }
    Write-Host ''
    $n = 0
    if (-not [int]::TryParse([string](Pergunta 'Qual backup restaurar? (1 = o mais novo)' 1), [ref]$n) -or $n -lt 1 -or $n -gt $lista.Count) { throw 'Opção inválida.' }
    $arq = $lista[$n - 1].FullName
  }
  if (-not (Test-Path $arq)) { throw "Backup não encontrado: $arq" }
  $arq = (Resolve-Path $arq).Path
  Ok "Backup: $arq"
  Aviso 'Os dados atuais do banco serão substituídos pelos do backup (antes, faço um backup do estado atual).'
  if (-not $Sim -and -not (Confirma 'Restaurar agora?' $false)) { throw 'Cancelado.' }
  Executa 'Restaurando banco e arquivos (e aplicando migrações novas)' {
    Set-Location $using:Raiz
    & $using:php artisan system:restore $using:arq --force
    if ($LASTEXITCODE -ne 0) { throw 'system:restore falhou' }
  }
  return $arq
}

function E-Casino($p) { return [bool]($p -and (Test-Path (Join-Path $p 'artisan')) -and (Test-Path (Join-Path $p 'app\Install\CasinoInstaller.php'))) }
function Acha-Instalacoes {
  $achadas = New-Object System.Collections.Generic.List[string]
  $candidatos = @($Raiz) + @((Le-Registro).Instalacoes | ForEach-Object { $_.Pasta })
  $bases = @($env:USERPROFILE, (Join-Path $env:USERPROFILE 'Downloads'), (Join-Path $env:USERPROFILE 'Desktop'), (Join-Path $env:USERPROFILE 'Documents'),
    'C:\xampp\htdocs', 'C:\laragon\www', 'C:\wamp64\www')
  foreach ($base in $bases) {
    if (-not $base -or -not (Test-Path $base)) { continue }
    foreach ($d in @(Get-ChildItem $base -Directory -ErrorAction SilentlyContinue | Where-Object { $_.Name -like 'casino*' })) {
      $candidatos += $d.FullName
      # ZIP extraído dentro de outra pasta (ex.: casino-main\casino-main)
      $candidatos += @(Get-ChildItem $d.FullName -Directory -ErrorAction SilentlyContinue | Where-Object { $_.Name -like 'casino*' } | ForEach-Object { $_.FullName })
    }
  }
  foreach ($c in $candidatos) {
    if (E-Casino $c) {
      $r = (Resolve-Path $c).Path.TrimEnd('\', '/')
      if (-not $achadas.Contains($r)) { $achadas.Add($r) }
    }
  }
  return ,$achadas.ToArray()
}
function Estado-Instalacao($p) {
  if (Test-Path (Join-Path $p 'storage\installed.lock')) { return 'instalado' }
  if ((Test-Path (Join-Path $p '.env')) -or (Test-Path (Join-Path $p 'vendor'))) { return 'parcial' }
  return 'só o código'
}
function Para-Servidor($pasta) {
  $pf = Join-Path $pasta 'storage\local-server.pid'
  if (-not (Test-Path $pf)) { return }
  $id = 0
  if ([int]::TryParse([string](Get-Content $pf -TotalCount 1), [ref]$id) -and $id -gt 0 -and (Get-Process -Id $id -ErrorAction SilentlyContinue)) {
    Stop-Process -Id $id -Force -ErrorAction SilentlyContinue
    Ok 'Servidor local desligado'
  }
  Remove-Item $pf -Force -ErrorAction SilentlyContinue
}
function Apaga-Pasta($pasta) {
  # esta pasta pode estar em uso (este assistente, a janela do instalador): o que sobrar é apagado logo depois
  Set-Location $env:USERPROFILE
  [Environment]::CurrentDirectory = $env:USERPROFILE
  try {
    Remove-Item -LiteralPath $pasta -Recurse -Force -ErrorAction Stop
    Ok 'Pasta apagada'
    # ZIP extraído dentro de outra pasta (casino-main\casino-main): a de fora ficou vazia
    $mae = Split-Path $pasta -Parent
    if ($mae -and (Split-Path $mae -Leaf) -like 'casino*' -and (Test-Path $mae) -and -not (Get-ChildItem -LiteralPath $mae -Force -ErrorAction SilentlyContinue)) {
      Remove-Item -LiteralPath $mae -Force -ErrorAction SilentlyContinue
    }
  } catch {
    if ($env:OS -eq 'Windows_NT') {
      $cmd = 'for /l %i in (1,1,30) do @(if exist "' + $pasta + '" (rmdir /s /q "' + $pasta + '" 2>nul & ping -n 3 127.0.0.1 >nul))'
      Start-Process -FilePath 'cmd.exe' -ArgumentList '/c', $cmd -WindowStyle Hidden -WorkingDirectory $env:TEMP | Out-Null
      Ok 'Pasta será apagada assim que esta janela fechar'
    } else {
      Aviso "Não consegui apagar $pasta ($($_.Exception.Message)). Apague-a manualmente."
    }
  }
}
function Desinstala-Uma($pasta, $php) {
  Write-Host ''
  Write-Host ('  ' + (Negrito $pasta))
  Para-Servidor $pasta
  $banco = Valor-Env $pasta 'DB_DATABASE'
  $temEnv = Test-Path (Join-Path $pasta '.env')
  if ($temEnv -and (Artisan-Tem $php $pasta 'system:uninstall')) {
    $argumentos = @('artisan', 'system:uninstall', '--force')
    if ($SemBackup) { $argumentos += '--no-backup' } else { New-Item -ItemType Directory -Force -Path $PastaBackups | Out-Null; $argumentos += "--backup-dir=$PastaBackups" }
    if ($ManterBanco) { $argumentos += '--keep-database' } else { $argumentos += '--drop-database' }
    # no Windows o site costuma usar o root do MySQL (XAMPP/Laragon): a mesma conta apaga o banco
    $usuarioBanco = Valor-Env $pasta 'DB_USERNAME'
    if ($usuarioBanco -eq 'root') { $env:CASINO_DB_ROOT_USER = 'root'; $env:CASINO_DB_ROOT_PASS = (Valor-Env $pasta 'DB_PASSWORD') }
    try {
      Executa 'Backup final e remoção (banco, .env, arquivos enviados, logs e caches)' {
        Set-Location $using:pasta
        $a = $using:argumentos
        & $using:php @a
        if ($LASTEXITCODE -ne 0) { throw 'system:uninstall falhou' }
      }
    } finally {
      Remove-Item Env:CASINO_DB_ROOT_USER, Env:CASINO_DB_ROOT_PASS -ErrorAction SilentlyContinue
    }
  } elseif ($temEnv) {
    foreach ($f in '.env', 'storage\installed.lock', 'storage\install.token', 'storage\local-server.pid') { Remove-Item (Join-Path $pasta $f) -Force -ErrorAction SilentlyContinue }
    Remove-Item (Join-Path $pasta 'public\storage') -Recurse -Force -ErrorAction SilentlyContinue
    Ok 'Arquivos de configuração removidos'
    if ($banco -and -not $ManterBanco) { Aviso "Esta cópia não tem as dependências instaladas: apague o banco '$banco' pelo HeidiSQL/phpMyAdmin, se quiser." }
  }
  if ($Tudo -or (Confirma "Apagar a pasta $pasta inteira?" $true)) { Apaga-Pasta $pasta }
  elseif (Test-Path (Join-Path $pasta 'vendor')) {
    Remove-Item (Join-Path $pasta 'vendor') -Recurse -Force -ErrorAction SilentlyContinue
    Ok "Dependências (vendor) removidas; o código ficou em $pasta"
  }
  Tira-Do-Registro $pasta
}
function Remove-Programas {
  # só o que o PRÓPRIO assistente baixou (PHP e Composer em %LOCALAPPDATA%\CasinoPlatform, cache do Composer, MariaDB do winget)
  $reg = Le-Registro
  if ($reg.Instalacoes.Count -gt 0) { Aviso 'Ainda há instalações registradas: PHP e Composer do assistente foram mantidos.'; return }
  $baixados = @(Get-ChildItem $Dados -Force -ErrorAction SilentlyContinue | Where-Object { $_.Name -ne 'instalacoes.json' })
  if ($baixados.Count -gt 0) {
    $mb = [math]::Round(((Get-ChildItem $Dados -Recurse -File -Force -ErrorAction SilentlyContinue | Measure-Object Length -Sum).Sum) / 1MB)
    if ($Tudo -or (Confirma "Apagar o PHP e o Composer que o assistente baixou ($Dados, $mb MB)?" $true)) {
      Remove-Item -LiteralPath $Dados -Recurse -Force -ErrorAction SilentlyContinue
      Ok 'PHP e Composer do assistente apagados'
      $script:RegistroApagado = $true
    }
  }
  $cache = @((Join-Path $env:LOCALAPPDATA 'Composer'), (Join-Path $env:APPDATA 'Composer')) | Where-Object { $_ -and (Test-Path $_) }
  if ($cache.Count -gt 0 -and ($Tudo -or (Confirma 'Apagar o cache do Composer? (outros projetos PHP só baixam de novo o que precisarem)' $true))) {
    $cache | ForEach-Object { Remove-Item -LiteralPath $_ -Recurse -Force -ErrorAction SilentlyContinue }
    Ok 'Cache do Composer apagado'
  }
  if ($reg.MariaDbWinget -and (Get-Command winget -ErrorAction SilentlyContinue)) {
    if (Confirma 'Desinstalar também o MariaDB que o assistente instalou? (apaga TODOS os bancos dele)' $false) {
      Executa 'Desinstalando o MariaDB' { winget uninstall --id MariaDB.Server -e --silent --accept-source-agreements }
    } else { Aviso 'MariaDB mantido. Para remover depois: Configurações → Aplicativos → MariaDB.' }
  }
}

# -------------------------------------------------------------------- fluxo
# instalação existente: usa o servidor de banco do .env (a não ser que outro tenha sido informado)
if (Test-Path (Join-Path $Raiz '.env')) {
  if (-not $PSBoundParameters.ContainsKey('DbHost')) { $h = Valor-Env $Raiz 'DB_HOST'; if ($h) { $DbHost = $h } }
  if (-not $PSBoundParameters.ContainsKey('DbPorta')) { $pt = Valor-Env $Raiz 'DB_PORT'; if ($pt -match '^\d+$') { $DbPorta = [int]$pt } }
}

if ($Acao -eq 'Diagnostico') { Diagnostico; exit 0 }

if ($Acao -eq 'Desligar') { Banner; Desliga-Site; exit 0 }

if ($Acao -in 'Backup', 'Restaurar') {
  try {
    Banner
    $php = Encontra-Php
    if (-not (Test-Path (Join-Path $Raiz '.env'))) { throw "Não há instalação em $Raiz (sem .env)." }
    if (-not (Artisan-Tem $php $Raiz 'system:backup')) { throw 'Esta instalação é anterior à 9.4 ou está incompleta: rode antes a opção Atualizar.' }
    $script:Etapas = 1
    if ($Acao -eq 'Backup') {
      Etapa "Backup de $Raiz"
      $b = Faz-Backup $php
      Write-Host ''
      Caixa 'Backup concluído' @('', "  Arquivo   $(Verde $b)", '', (Cinza '  Restaurar: instalar-windows.bat → Restaurar backup'), (Cinza '  Guarde uma cópia fora deste computador (pendrive, nuvem...).'), '')
    } else {
      Etapa 'Restaurando um backup'
      $ligado = Servidor-Ligado
      $b = Restaura $php
      if ($ligado) { Push-Location $Raiz; Desliga-Site; $null = Liga-Site $php; Pop-Location }
      Write-Host ''
      Caixa 'Backup restaurado' @('', "  $b", '')
    }
    exit 0
  } catch {
    Falha $_.Exception.Message
    Write-Host "Registro completo: $Log"
    exit 1
  }
}

if ($Acao -eq 'Desinstalar') {
  try {
    Banner
    $php = Encontra-Php
    $script:Etapas = 3
    Etapa 'Procurando instalações'
    $todas = Acha-Instalacoes
    if ($todas.Count -eq 0) { Ok 'Nenhuma instalação do Casino Platform encontrada.'; Remove-Programas; exit 0 }
    Write-Host ''
    for ($i = 0; $i -lt $todas.Count; $i++) {
      $extra = ''
      $b = Valor-Env $todas[$i] 'DB_DATABASE'; if ($b) { $extra += "  ·  banco $b" }
      if ($todas[$i] -eq $Raiz) { $extra += '  ·  (esta pasta)' }
      Write-Host ("   {0}) {1}  {2}{3}" -f ($i + 1), $todas[$i], (Cinza (Estado-Instalacao $todas[$i])), (Cinza $extra))
    }
    Write-Host ''
    $escolhidas = @()
    if ($Sim -or $todas.Count -eq 1) { $escolhidas = $todas }
    else {
      $r = (Read-Host '  ? Quais remover? Números separados por espaço, T = todas, ENTER = cancelar').Trim().ToUpper()
      if (-not $r) { throw 'Cancelado.' }
      if ($r -match 'T') { $escolhidas = $todas }
      else {
        foreach ($n in ($r -split '[\s,]+' | Where-Object { $_ })) {
          $k = 0
          if (-not [int]::TryParse($n, [ref]$k) -or $k -lt 1 -or $k -gt $todas.Count) { throw "Opção inválida: $n" }
          $escolhidas += $todas[$k - 1]
        }
      }
    }

    Etapa 'O que será feito'
    $linhas = @($escolhidas | ForEach-Object { '  ' + (Vermelho '✖') + " $_" })
    $linhas += ''
    $linhas += '  Para cada uma: ' + $(if ($SemBackup) { 'sem backup final' } else { 'backup final' }) + ', desligar o site, apagar o banco'
    $linhas += '  ' + $(if ($ManterBanco) { '(mantido: -ManterBanco)' } else { 'e o usuário do MySQL do site' }) + ', .env, uploads, logs e a pasta.'
    if (-not $SemBackup) { $linhas += "  Backups ficam em $PastaBackups" }
    Caixa 'Desinstalar' $linhas
    if (-not $Sim) {
      $conf = Read-Host '  ? Para confirmar, digite DESINSTALAR'
      if ($conf.Trim().ToUpper() -ne 'DESINSTALAR') { throw 'Cancelado.' }
    }

    Etapa 'Removendo'
    foreach ($p in $escolhidas) { Desinstala-Uma $p $php }
    Remove-Programas
    $ultimo = Get-ChildItem $PastaBackups -Filter 'casino-backup-*.zip' -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    Write-Host ''
    $resumo = @('', "  Removidas: $($escolhidas.Count) instalação(ões).")
    if ($ultimo -and -not $SemBackup) {
      $resumo += "  Último backup: $(Verde $ultimo.FullName)"
      $resumo += (Cinza '  Os backups ficam guardados de propósito; apague a pasta se não precisar mais.')
    }
    $resumo += ''
    Caixa 'Desinstalação concluída' $resumo
    exit 0
  } catch {
    Falha $_.Exception.Message
    Write-Host "Registro completo: $Log"
    exit 1
  }
}

try {
  Banner
  $so = Get-CimInstance Win32_OperatingSystem
  $php = Encontra-Php

  if ($Acao -eq 'Ligar') {
    if (-not $php) { throw 'PHP 8.2+ não encontrado. Rode a instalação primeiro.' }
    Push-Location $Raiz; $p = Liga-Site $php; Pop-Location
    if (-not $SemAbrirNavegador) { Start-Process "http://127.0.0.1:$p" }
    exit 0
  }

  if ($Acao -eq 'Atualizar') { $script:Etapas = 6 }

  Etapa 'Diagnóstico do ambiente'
  Caixa 'Seu computador' @(
    ("{0,-12} {1}" -f 'Sistema', "$($so.Caption)  ·  $($env:PROCESSOR_ARCHITECTURE)"),
    ("{0,-12} {1}" -f 'Pasta', $Raiz))
  Write-Host ''
  if ($php) {
    $falta = Extensoes-Faltando $php
    if ($falta.Count -eq 0) { Linha 'ok' 'PHP' "$(& $php -r 'echo PHP_VERSION;')" } else { Linha 'aviso' 'PHP' "faltam extensões: $($falta -join ', ') (serão ativadas)" }
  } else { Linha 'aviso' 'PHP 8.2+' 'não encontrado (será baixado)' }
  $temBanco = Banco-Responde
  if ($DbUsuario) { Linha 'info' 'MySQL/MariaDB' "usando o informado ($DbUsuario@$DbHost)" }
  elseif ($temBanco) { Linha 'ok' 'MySQL/MariaDB' "respondendo em ${DbHost}:$DbPorta" }
  else { Linha 'aviso' 'MySQL/MariaDB' 'não está respondendo' }
  Write-Host ''

  Etapa 'Instalando o que falta'
  if (-not $php) {
    if (-not (Confirma 'Baixar e instalar o PHP 8.3 para este usuário (sem administrador)?')) { throw 'Instalação cancelada.' }
    Instala-Php; $php = Encontra-Php
  } else {
    if ((Extensoes-Faltando $php).Count -gt 0) { Ativa-Extensoes $php }
  }
  if (-not $php) { throw 'Não consegui preparar o PHP.' }
  $falta = Extensoes-Faltando $php
  if ($falta.Count -gt 0) { throw "O PHP em $php ainda não tem as extensões: $($falta -join ', '). Ative-as no php.ini." }
  Ok "PHP $(& $php -r 'echo PHP_VERSION;') com todas as extensões"

  if (-not $DbUsuario -and -not $temBanco -and $Acao -ne 'Atualizar') {
    $achou = Acha-MysqlExe
    if ($achou) {
      Aviso "Encontrei o MySQL em $achou, mas ele não está ligado. Inicie o MySQL pelo painel do XAMPP/Laragon e rode de novo."
      throw 'Banco de dados desligado.'
    }
    if (Get-Command winget -ErrorAction SilentlyContinue) {
      if (Confirma 'Instalar o MariaDB com o winget (pede permissão de administrador)?') {
        Executa 'Instalando o MariaDB' { winget install --id MariaDB.Server -e --silent --accept-package-agreements --accept-source-agreements }
        Registra 'MariaDbWinget' $true
        Aviso 'MariaDB instalado. Na primeira vez, abra "HeidiSQL/MariaDB" e defina a senha do root, ou rode o assistente com -DbUsuario root -DbSenha SUA_SENHA.'
        Start-Sleep -Seconds 5
        $temBanco = Banco-Responde
      }
    }
    if (-not $temBanco) {
      Falha 'Não encontrei um MySQL/MariaDB ligado.'
      Write-Host '  Opções: instale o XAMPP (https://www.apachefriends.org) ou o Laragon (https://laragon.org),'
      Write-Host '  inicie o MySQL e rode este assistente de novo.'
      exit 1
    }
  }
  $composer = Instala-Composer $php

  $backupAtualizacao = ''
  $versaoAntes = Versao-Instalada $Raiz
  if ($Acao -eq 'Atualizar') {
    if (-not (Test-Path (Join-Path $Raiz '.env'))) { throw 'Não há instalação nesta pasta para atualizar. Use a opção Instalar.' }
    Etapa 'Backup e versão nova do código'
    if ($SemBackup) { Aviso 'Backup pulado (-SemBackup).' }
    elseif (Artisan-Tem $php $Raiz 'system:backup') { $backupAtualizacao = Faz-Backup $php }
    if (Test-Path (Join-Path $Raiz '.git')) {
      if (Get-Command git -ErrorAction SilentlyContinue) {
        Executa 'Baixando a versão nova (git pull)' {
          Set-Location $using:Raiz
          & git pull --ff-only
          if ($LASTEXITCODE -ne 0) { throw 'git pull falhou (há arquivos alterados à mão nesta pasta?)' }
        }
      } else { Aviso 'O git não está instalado: atualizando com o código que já está na pasta.' }
    } else {
      Aviso 'Esta pasta não é uma cópia git. Para pegar a versão nova, baixe o ZIP do projeto e extraia por cima desta pasta (o ZIP não traz o .env nem public\storage), depois rode Atualizar.'
    }
  }

  Etapa 'Preparando o site'
  Push-Location $Raiz
  $env:COMPOSER_ALLOW_SUPERUSER = '1'
  Executa 'Instalando as dependências do PHP (pode levar alguns minutos)' {
    Set-Location $using:Raiz
    & $using:php $using:composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
    if ($LASTEXITCODE -ne 0) { throw 'composer falhou' }
  }
  if (-not (Test-Path (Join-Path $Raiz 'public\build\manifest.json'))) { throw 'O frontend não está compilado nesta cópia (public\build). Baixe o pacote completo ou rode npm ci && npm run build.' }

  if ($Acao -eq 'Atualizar') {
    # instalações anteriores à 9.4 não tinham o comando de backup: faz agora, com o código novo
    if (-not $backupAtualizacao -and -not $SemBackup -and (Artisan-Tem $php $Raiz 'system:backup')) { $backupAtualizacao = Faz-Backup $php }
    Etapa 'Atualizando o banco de dados'
    try {
      if (Artisan-Tem $php $Raiz 'system:update') {
        Executa 'Migrações, arquivos padrão, caches e versão' {
          Set-Location $using:Raiz
          & $using:php artisan system:update --force --no-backup
          if ($LASTEXITCODE -ne 0) { throw 'system:update falhou' }
        }
      } else {
        Executa 'Rodando as migrações' { Set-Location $using:Raiz; & $using:php artisan migrate --force; if ($LASTEXITCODE -ne 0) { throw 'migrate falhou' } }
        Executa 'Limpando o cache' { Set-Location $using:Raiz; & $using:php artisan optimize:clear }
      }
    } catch {
      if ($backupAtualizacao) { Write-Host ("  Para voltar ao estado anterior: instalar-windows.bat → Restaurar backup  (" + $backupAtualizacao + ')') }
      throw
    }
    Etapa 'Reiniciando o site'
    if (Servidor-Ligado) { Desliga-Site }
    $p = Liga-Site $php
    Pop-Location
    $depois = Versao-Instalada $Raiz
    $linhas = @('', ('  Versão: ' + $(if ($versaoAntes) { $versaoAntes } else { 'anterior' }) + ' → ' + (Verde $(if ($depois) { $depois } else { $Versao }))),
      '  Banco atualizado e caches limpos; dados, .env e arquivos enviados mantidos.')
    if ($backupAtualizacao) { $linhas += (Cinza "  Backup feito antes: $backupAtualizacao") }
    $linhas += ''
    Caixa 'Atualização concluída' $linhas
    exit 0
  }

  Etapa 'Instalando o site'
  if ($Porta -le 0) { $Porta = Porta-Livre 8000; $Porta = [int](Pergunta 'Porta do site' $Porta) }
  $url = "http://127.0.0.1:$Porta"
  if (-not $DbUsuario) { $DbUsuario = 'root' }
  if ($DbUsuario -eq 'root') {
    # XAMPP/Laragon: root sem senha. MariaDB instalado à parte costuma ter senha: pergunta (até 3 vezes)
    $login = Testa-Login-Banco $php 'root' $DbSenha
    $tentativa = 0
    while ($login -eq 'acesso' -and -not $Sim -and $tentativa -lt 3) {
      $tentativa++
      Aviso $(if ($tentativa -eq 1) { 'O usuário root do MySQL tem senha.' } else { 'Senha incorreta.' })
      $seg = Read-Host '  ? Senha do usuário root do MySQL' -AsSecureString
      $DbSenha = (New-Object System.Net.NetworkCredential('', $seg)).Password
      $login = Testa-Login-Banco $php 'root' $DbSenha
    }
    if ($login -eq 'acesso') {
      Falha 'Não consegui entrar no MySQL como root (senha incorreta).'
      Write-Host '  Rode de novo informando a senha:  powershell -ExecutionPolicy Bypass -File scripts\install-windows.ps1 -DbUsuario root -DbSenha SUA_SENHA'
      Write-Host '  Ou crie um usuário para o site no HeidiSQL/phpMyAdmin e use -DbUsuario/-DbSenha.'
      exit 1
    }
    if ($login -ne 'ok') { throw "Não consegui conectar ao MySQL em ${DbHost}:${DbPorta}. $login" }
    Ok 'Acesso ao MySQL conferido'
  }
  Executa 'Criando tabelas, jogos iniciais e o administrador' {
    Set-Location $using:Raiz
    & $using:php artisan system:install --no-interaction --overwrite "--url=$using:url" "--name=$using:Nome" `
      "--db-host=$using:DbHost" "--db-port=$using:DbPorta" "--db-name=$using:DbNome" "--db-user=$using:DbUsuario" "--db-pass=$using:DbSenha" `
      "--admin-email=$using:Email" "--admin-pass=$using:Senha"
    if ($LASTEXITCODE -ne 0) { throw 'system:install falhou' }
  }
  Executa 'Conferindo o login do administrador' {
    Set-Location $using:Raiz
    & $using:php artisan system:admin "--email=$using:Email" "--password=$using:Senha" --check
    if ($LASTEXITCODE -ne 0) {
      & $using:php artisan system:admin "--email=$using:Email" "--password=$using:Senha"
      if ($LASTEXITCODE -ne 0) { throw 'O login do administrador não conferiu' }
    }
  }
  (Get-Content (Join-Path $Raiz '.env')) -replace '^APP_ENV=.*$', 'APP_ENV=local' -replace '^APP_DEBUG=.*$', 'APP_DEBUG=true' | Set-Content (Join-Path $Raiz '.env') -Encoding utf8
  & $php artisan optimize:clear | Out-Null

  Etapa 'Ligando o site'
  $p = Liga-Site $php
  Pop-Location
  Registra-Instalacao $Raiz $DbNome

  $tempo = [int]((Get-Date) - $Inicio).TotalSeconds
  Write-Host ''
  Caixa ("Instalação concluída em {0}m{1:00}s" -f [math]::Floor($tempo / 60), ($tempo % 60)) @(
    '',
    ("  Site       " + (Verde "http://127.0.0.1:$p")),
    ("  Painel     " + (Verde "http://127.0.0.1:$p/admin")),
    "  Login      $Email",
    "  Senha      $Senha",
    '',
    (Cinza '  instalar-windows.bat: Ligar, Desligar, Atualizar, Backup, Restaurar e Desinstalar'))
  Write-Host ''
  if (-not $SemAbrirNavegador) { Start-Process "http://127.0.0.1:$p" }
}
catch {
  Falha $_.Exception.Message
  Write-Host "Registro completo: $Log"
  Write-Host 'Veja docs\SOLUCAO-DE-PROBLEMAS.md ou rode:  scripts\install-windows.ps1 -Acao Diagnostico'
  exit 1
}
