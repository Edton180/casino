<?php

use App\Install\InstallerController;
use Illuminate\Support\Facades\Route;

require_once base_path('install/helpers.php');
require_once base_path('install/EnvironmentDetector.php');
require_once base_path('install/InstallerService.php');
require_once base_path('install/InstallerController.php');

Route::get('/install', [InstallerController::class, 'show'])->name('install.show');
Route::post('/install/test-db', [InstallerController::class, 'testDatabase'])->name('install.test-db');
Route::post('/install/run', [InstallerController::class, 'run'])->name('install.run');
Route::get('/install/assets/{asset}', function (string $asset) {
    $path = base_path('install/assets/' . basename($asset));

    abort_unless(is_file($path), 404);

    $mime = str_ends_with($asset, '.css') ? 'text/css' : 'application/octet-stream';

    return response(file_get_contents($path), 200)->header('Content-Type', $mime);
})->where('asset', '.*')->name('install.asset');
