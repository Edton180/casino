@echo off
rem ===========================================================================
rem  Casino Platform - instalacao LOCAL no Windows (XAMPP ou Laragon)
rem
rem  1. Inicie o MySQL no painel do XAMPP/Laragon.
rem  2. De dois cliques neste arquivo (no Laragon: rode pelo "Terminal" do Laragon).
rem  3. Abra http://127.0.0.1:8000  (painel: http://127.0.0.1:8000/admin)
rem     Login: admin@casino.local / admin12345
rem
rem  Requer PHP 8.1+ com as extensoes intl, zip, gd, pdo_mysql, mbstring,
rem  curl e bcmath ativas no php.ini (no XAMPP, tire o ";" da frente de
rem  extension=intl, extension=zip e extension=gd).
rem ===========================================================================
chcp 65001 >nul
setlocal EnableExtensions

cd /d "%~dp0..\extraia para public_html\public_html" || goto :fail

set "PHP_BIN=php"
where php >nul 2>nul
if errorlevel 1 if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
"%PHP_BIN%" -v >nul 2>nul
if errorlevel 1 goto :nophp

if exist storage\installed.lock goto :serve

set "DB_PASS="
set /p "DB_PASS=Senha do usuario root do MySQL (ENTER se estiver vazia, padrao do XAMPP/Laragon): "

if exist vendor\autoload.php goto :install
echo.
echo Baixando o Composer e as dependencias (alguns minutos)...
"%PHP_BIN%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
"%PHP_BIN%" composer-setup.php --quiet
del composer-setup.php
"%PHP_BIN%" composer.phar install --no-interaction --no-progress --optimize-autoloader
if errorlevel 1 goto :fail
del composer.phar

:install
echo.
echo Instalando a aplicacao...
"%PHP_BIN%" artisan system:install --no-interaction --overwrite --url=http://127.0.0.1:8000 --name="Meu Cassino" --db-host=127.0.0.1 --db-port=3306 --db-name=casino_local --db-user=root --db-pass="%DB_PASS%" --admin-email=admin@casino.local --admin-pass=admin12345
if errorlevel 1 goto :fail
echo.
echo ====================================================
echo  Instalacao concluida!
echo  Site:   http://127.0.0.1:8000
echo  Painel: http://127.0.0.1:8000/admin
echo  Login:  admin@casino.local  /  admin12345
echo ====================================================

:serve
echo.
echo Iniciando o site em http://127.0.0.1:8000  (feche esta janela para parar)
start "" http://127.0.0.1:8000
"%PHP_BIN%" artisan serve --host=127.0.0.1 --port=8000
goto :eof

:nophp
echo PHP nao encontrado. Instale o XAMPP (https://www.apachefriends.org) ou o Laragon (https://laragon.org)
echo com PHP 8.1 ou superior, ou adicione o php.exe ao PATH.
pause
exit /b 1

:fail
echo.
echo A instalacao falhou. Leia a mensagem acima (normalmente uma extensao do PHP desativada
echo ou o MySQL parado) e veja o arquivo SOLUCAO-DE-PROBLEMAS.md.
pause
exit /b 1
