<?php

declare(strict_types=1);

if (!class_exists('WP_CLI')) {
    class WP_CLI
    {
        /** @var list<array{0: string, 1: string}> */
        public static array $messages = [];

        public static function line(string $message): void
        {
            self::$messages[] = ['line', $message];
        }

        public static function success(string $message): void
        {
            self::$messages[] = ['success', $message];
        }

        public static function error(string $message): void
        {
            self::$messages[] = ['error', $message];
        }

        public static function warning(string $message): void
        {
            self::$messages[] = ['warning', $message];
        }

        public static function add_command(string $name, callable $callable): void
        {
        }
    }
}
