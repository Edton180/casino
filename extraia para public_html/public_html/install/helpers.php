<?php

if (! function_exists('installer_log')) {
    function installer_log(string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        $logPath = storage_path('logs/installer.log');
        @file_put_contents($logPath, $line, FILE_APPEND);
    }
}

if (! function_exists('installer_bool_label')) {
    function installer_bool_label(bool $value): string
    {
        return $value ? '✅ Sim' : '❌ Não';
    }
}
