<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

/*
|--------------------------------------------------------------------------
| Instalação
|--------------------------------------------------------------------------
|
| Enquanto o sistema não foi instalado, qualquer acesso vai para o
| instalador web (public/install), que funciona sem .env e sem Laravel.
|
*/

if (! file_exists(__DIR__.'/../storage/installed.lock') || ! file_exists(__DIR__.'/../vendor/autoload.php')) {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    $base = preg_replace('#/public$#', '', $base);

    if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
        http_response_code(503);
        echo '<!doctype html><meta charset="utf-8"><title>Instalação incompleta</title>'
            .'<body style="font-family:system-ui;background:#0b1220;color:#eef3ff;padding:40px;line-height:1.6">'
            .'<h2>Faltam as dependências do sistema (pasta <code>vendor</code>)</h2>'
            .'<p>Envie o pacote <b>casino-pronto-para-hospedagem.zip</b> da página <i>Releases</i> do GitHub '
            .'(ele já contém a pasta vendor) ou rode <code>composer install --no-dev</code> pelo terminal/SSH.</p>'
            .'<p>Depois acesse <a style="color:#7cc4ff" href="'.$base.'/install/">'.$base.'/install/</a>.</p></body>';
        exit;
    }

    header('Location: '.$base.'/install/', true, 302);
    exit;
}

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
