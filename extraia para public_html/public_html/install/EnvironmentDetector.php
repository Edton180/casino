<?php

namespace App\Install;

class EnvironmentDetector
{
    public function detect(): array
    {
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $https = $this->isHttps();
        $os = $this->detectOperatingSystem();
        $hosting = $this->detectHostingEnvironment($os);

        return [
            'os' => $os,
            'hosting' => $hosting,
            'php_version' => PHP_VERSION,
            'https' => $https,
            'domain' => $host,
            'app_url' => ($https ? 'https://' : 'http://') . $host,
            'default_db_host' => $hosting === 'cpanel' ? 'localhost' : '127.0.0.1',
            'production' => ! in_array($host, ['127.0.0.1', 'localhost'], true),
            'required_extensions' => $this->requiredExtensionsStatus(),
            'permissions' => $this->permissionsStatus(),
        ];
    }

    public function detectOperatingSystem(): string
    {
        return str_starts_with(strtolower(PHP_OS), 'win') || DIRECTORY_SEPARATOR === '\\'
            ? 'windows'
            : 'linux';
    }

    public function detectHostingEnvironment(string $os): string
    {
        if ($os === 'windows') {
            return 'windows-localhost';
        }

        $isCpanel = isset($_SERVER['cpanel'])
            || str_contains(base_path(), 'public_html')
            || $this->hasHomeUserStructure();

        if ($isCpanel) {
            return 'cpanel';
        }

        if (is_file('/etc/os-release')) {
            return 'ubuntu-vps';
        }

        return 'linux-generic';
    }

    public function isHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return ($_SERVER['SERVER_PORT'] ?? null) == 443;
    }

    private function hasHomeUserStructure(): bool
    {
        $segments = explode(DIRECTORY_SEPARATOR, base_path());

        return in_array('home', $segments, true);
    }

    private function requiredExtensionsStatus(): array
    {
        $required = ['curl', 'intl', 'libxml', 'simplexml', 'zip', 'mbstring', 'openssl', 'pdo_mysql', 'fileinfo', 'gd', 'bcmath'];
        $result = [];

        foreach ($required as $extension) {
            $result[$extension] = extension_loaded($extension);
        }

        return $result;
    }

    private function permissionsStatus(): array
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap_cache' => base_path('bootstrap/cache'),
        ];

        $status = [];

        foreach ($paths as $key => $path) {
            $status[$key] = is_writable($path);
        }

        return $status;
    }
}
