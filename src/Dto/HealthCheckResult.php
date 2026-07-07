<?php

declare(strict_types=1);

namespace HealthCheck\Dto;

final class HealthCheckResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {
    }

    public static function ok(): self
    {
        return new self(success: true);
    }

    public static function ko(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
