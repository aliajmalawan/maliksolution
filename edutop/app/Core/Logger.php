<?php

namespace App\Core;

class Logger
{
    private static function storagePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs';
    }

    private static function write(string $file, string $level, string $message, array $context = []): void
    {
        $dir = self::storagePath();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
            PHP_EOL
        );

        file_put_contents($dir . '/' . $file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error.log', 'error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('error.log', 'warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('app.log', 'info', $message, $context);
    }

    public static function activity(string $message, array $context = []): void
    {
        self::write('activity.log', 'activity', $message, $context);
    }
}
