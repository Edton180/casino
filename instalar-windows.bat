@echo off
rem ===========================================================================
rem  Casino Platform - assistente de instalacao para Windows
rem  De dois cliques neste arquivo. Ele abre o assistente (PowerShell) que
rem  identifica o Windows, instala o que faltar e liga o site em
rem  http://127.0.0.1:8000
rem ===========================================================================
chcp 65001 >nul
setlocal EnableExtensions
title Casino Platform - Instalador
cd /d "%~dp0"

where powershell >nul 2>nul
if errorlevel 1 (
  echo PowerShell nao encontrado. Use o Windows 10 ou 11, ou instale o WSL e rode:  bash instalar.sh
  pause
  exit /b 1
)

if not exist "%~dp0scripts\install-windows.ps1" (
  echo Baixando o instalador e o codigo do Casino Platform...
  powershell -NoProfile -ExecutionPolicy Bypass -Command "irm https://raw.githubusercontent.com/Edton180/casino/main/scripts/install-windows.ps1 | iex"
  pause
  exit /b 0
)

:menu
cls
echo.
echo   ====================================================
echo     CASINO PLATFORM - Assistente de instalacao
echo   ====================================================
echo.
echo     1^) Instalar no meu computador
echo     2^) Ligar o site
echo     3^) Desligar o site
echo     4^) Atualizar (sem perder dados, com backup antes)
echo     5^) Fazer backup (banco, configuracao e arquivos enviados)
echo     6^) Restaurar um backup
echo     7^) Desinstalar (remove esta e outras instalacoes encontradas)
echo     8^) Diagnostico (o que esta faltando?)
echo     0^) Sair
echo.
choice /c 123456780 /n /m "  Escolha uma opcao: "
if errorlevel 9 exit /b 0
if errorlevel 8 set ACAO=Diagnostico& goto run
if errorlevel 7 goto desinstalar
if errorlevel 6 set ACAO=Restaurar& goto run
if errorlevel 5 set ACAO=Backup& goto run
if errorlevel 4 set ACAO=Atualizar& goto run
if errorlevel 3 set ACAO=Desligar& goto run
if errorlevel 2 set ACAO=Ligar& goto run
if errorlevel 1 set ACAO=Instalar& goto run
goto menu

:run
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\install-windows.ps1" -Acao %ACAO% %*
echo.
pause
goto menu

:desinstalar
rem sai da pasta antes: o Windows nao apaga uma pasta que esteja aberta nesta janela
set "CASINO_PS=%~dp0scripts\install-windows.ps1"
cd /d "%USERPROFILE%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%CASINO_PS%" -Acao Desinstalar %*
echo.
pause
if not exist "%CASINO_PS%" exit /b 0
cd /d "%~dp0"
goto menu
