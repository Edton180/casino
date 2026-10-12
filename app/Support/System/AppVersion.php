<?php

namespace App\Support\System;

/**
 * Versão do código (arquivo VERSION) e dados da instalação (storage/installed.lock).
 */
class AppVersion
{
    public static function code(): string
    {
        $file = base_path('VERSION');
        $version = is_file($file) ? trim((string) file_get_contents($file)) : '';

        return $version !== '' ? $version : 'dev';
    }

    /** @return array{installed_at?: string, updated_at?: string, version?: string} */
    public static function installed(): array
    {
        $file = storage_path('installed.lock');
        if (!is_file($file)) {
            return [];
        }

        $raw = trim((string) file_get_contents($file));
        $data = json_decode($raw, true);

        // formato antigo: o arquivo tinha só a data da instalação
        return is_array($data) ? $data : ['installed_at' => $raw];
    }

    public static function installedVersion(): ?string
    {
        return self::installed()['version'] ?? null;
    }

    /** Grava a versão atual do código como a versão instalada. */
    public static function stamp(array $extra = []): void
    {
        $data = array_merge(self::installed(), $extra, ['version' => self::code()]);
        file_put_contents(
            storage_path('installed.lock'),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    }
}
