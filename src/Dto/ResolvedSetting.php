<?php

declare(strict_types=1);

namespace HealthCheck\Dto;

final class ResolvedSetting
{
    public function __construct(
        public readonly ?string $value,
        public readonly bool $isLocked,
    ) {
    }
}
