<?php

namespace App\Libraries;

class ApiLogger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    private static function write(
        string $level,
        string $message,
        array $context = []
    ): void {
        $logData = [
            'timestamp' => date('c'),
            'level'     => $level,
            'message'   => $message,
            'context'  => $context,
        ];

        log_message(
            $level,
            json_encode(
                $logData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }
}