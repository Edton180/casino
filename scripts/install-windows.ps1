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
     ... -Acao Ligar | Desligar | Atualizar | Diagnostico
#>
[CmdletBinding()]
param(
  [ValidateSet('Instalar', 'Ligar', 'Desligar', 'Atualizar', 'Diagnostico')]
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
  [switch]$Sim
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch {}
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

$Versao = '9.3.0'
$Raiz = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Dados = Join-Path $env:LOCALAPPDATA 'CasinoPlatform'
$PhpDir = Join-Path $Dados 'php'
$Log = Join-Path $env:TEMP ("casino-install-{0}.log" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
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
  $largura = 74
  $sem = { param($s) ($s -replace "$Esc\[[0-9;]*m", '').Length }
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

# -------------------------------------------------------------------- fluxo
if ($Acao -eq 'Diagnostico') { Diagnostico; exit 0 }

if ($Acao -eq 'Desligar') { Banner; Desliga-Site; exit 0 }

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

  if ($Acao -eq 'Atualizar') { $script:Etapas = 4 }

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
    Etapa 'Atualizando o banco de dados'
    Executa 'Rodando as migrações' { Set-Location $using:Raiz; & $using:php artisan migrate --force; if ($LASTEXITCODE -ne 0) { throw 'migrate falhou' } }
    Executa 'Limpando o cache' { Set-Location $using:Raiz; & $using:php artisan optimize:clear }
    Etapa 'Reiniciando o site'
    if (Servidor-Ligado) { Desliga-Site }
    $p = Liga-Site $php
    Pop-Location
    Caixa 'Atualização concluída' @('', '  Código atualizado, banco migrado e cache limpo.', '')
    exit 0
  }

  Etapa 'Instalando o site'
  if ($Porta -le 0) { $Porta = Porta-Livre 8000; $Porta = [int](Pergunta 'Porta do site' $Porta) }
  $url = "http://127.0.0.1:$Porta"
  if (-not $DbUsuario) { $DbUsuario = 'root' }
  Executa 'Criando tabelas, jogos iniciais e o administrador' {
    Set-Location $using:Raiz
    & $using:php artisan system:install --no-interaction --overwrite "--url=$using:url" "--name=$using:Nome" `
      "--db-host=$using:DbHost" "--db-port=$using:DbPorta" "--db-name=$using:DbNome" "--db-user=$using:DbUsuario" "--db-pass=$using:DbSenha" `
      "--admin-email=$using:Email" "--admin-pass=$using:Senha"
    if ($LASTEXITCODE -ne 0) { throw 'system:install falhou' }
  }
  (Get-Content (Join-Path $Raiz '.env')) -replace '^APP_ENV=.*$', 'APP_ENV=local' -replace '^APP_DEBUG=.*$', 'APP_DEBUG=true' | Set-Content (Join-Path $Raiz '.env') -Encoding utf8
  & $php artisan optimize:clear | Out-Null

  Etapa 'Ligando o site'
  $p = Liga-Site $php
  Pop-Location

  $tempo = [int]((Get-Date) - $Inicio).TotalSeconds
  Write-Host ''
  Caixa ("Instalação concluída em {0}m{1:00}s" -f [math]::Floor($tempo / 60), ($tempo % 60)) @(
    '',
    ("  Site       " + (Verde "http://127.0.0.1:$p")),
    ("  Painel     " + (Verde "http://127.0.0.1:$p/admin")),
    "  Login      $Email",
    "  Senha      $Senha",
    '',
    (Cinza '  Desligar:   instalar-windows.bat  → opção Desligar'),
    (Cinza '  Ligar:      instalar-windows.bat  → opção Ligar'))
  Write-Host ''
  if (-not $SemAbrirNavegador) { Start-Process "http://127.0.0.1:$p" }
}
catch {
  Falha $_.Exception.Message
  Write-Host "Registro completo: $Log"
  Write-Host 'Veja docs\SOLUCAO-DE-PROBLEMAS.md ou rode:  scripts\install-windows.ps1 -Acao Diagnostico'
  exit 1
}
