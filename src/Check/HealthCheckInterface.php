<?php

declare(strict_types=1);

namespace HealthCheck\Check;

use HealthCheck\Dto\HealthCheckResult;

interface HealthCheckInterface
{
    public function getName(): string;

    public function check(): HealthCheckResult;
}
