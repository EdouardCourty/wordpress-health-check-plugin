<?php

declare(strict_types=1);

namespace HealthCheck\Config;

use HealthCheck\Dto\ResolvedSetting;

final class HealthCheckSettings
{
    public const OPTION_SECRET = 'health_check_secret';
    public const OPTION_HEADER = 'health_check_header';
    public const DEFAULT_HEADER = 'Authorization';

    public static function resolveHeader(): ResolvedSetting
    {
        return self::resolve('HEALTH_CHECK_HEADER', self::OPTION_HEADER, self::DEFAULT_HEADER);
    }

    public static function resolveSecret(): ResolvedSetting
    {
        return self::resolve('HEALTH_CHECK_SECRET', self::OPTION_SECRET, null);
    }

    public static function saveHeader(string $rawHeader): void
    {
        update_option(self::OPTION_HEADER, self::sanitizeHeaderName($rawHeader), true);
    }

    public static function sanitizeHeaderName(string $raw): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9-]/', '', $raw) ?? '';

        if ($sanitized === '' || !preg_match('/^[A-Za-z]/', $sanitized)) {
            return self::DEFAULT_HEADER;
        }

        return $sanitized;
    }

    public static function regenerateSecret(): string
    {
        $secret = self::generateSecret();
        update_option(self::OPTION_SECRET, $secret, true);

        return $secret;
    }

    private static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    private static function resolve(string $constantName, string $optionName, ?string $default): ResolvedSetting
    {
        $constant = self::readConstant($constantName);

        if ($constant !== null) {
            return new ResolvedSetting($constant, true);
        }

        $option = get_option($optionName);

        if (is_string($option) && $option !== '') {
            return new ResolvedSetting($option, false);
        }

        return new ResolvedSetting($default, false);
    }

    /**
     * Reads via constant() rather than the bare token so PHPStan can't resolve
     * this to a fixed literal from phpstan-bootstrap.php — that would make the
     * null/empty-string check below always-true or always-false in analysis,
     * even though the real value is only known at runtime.
     */
    private static function readConstant(string $name): ?string
    {
        if (!defined($name)) {
            return null;
        }

        $value = constant($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
