<?php

namespace App\Support;

/**
 * Altera chaves do arquivo .env preservando o restante do conteúdo.
 */
class EnvFile
{
    public static function set(array $values, ?string $path = null): bool
    {
        $path ??= base_path('.env');
        if (!is_file($path) || !is_writable($path)) {
            return false;
        }

        $content = (string) file_get_contents($path);

        foreach ($values as $key => $value) {
            $key = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string) $key));
            if ($key === '') {
                continue;
            }

            $line = $key . '=' . self::format($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            $content = preg_match($pattern, $content)
                ? preg_replace_callback($pattern, fn () => $line, $content, 1)
                : rtrim($content, "\n") . "\n" . $line . "\n";
        }

        return file_put_contents($path, $content, LOCK_EX) !== false;
    }

    private static function format(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = str_replace(["\r", "\n"], '', (string) $value);

        // aspas quando há espaços ou caracteres especiais
        if ($value !== '' && preg_match('/[\s#"\'\\\\$=]/', $value)) {
            return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value) . '"';
        }

        return $value;
    }
}
