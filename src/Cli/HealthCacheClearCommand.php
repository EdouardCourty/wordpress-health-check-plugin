<?php

declare(strict_types=1);

namespace HealthCheck\Cli;

use HealthCheck\Service\HealthCheckService;

final class HealthCacheClearCommand
{
    /**
     * @param array<int, string>    $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        HealthCheckService::clearCache();

        \WP_CLI::success('Health check cache cleared.');
    }
}
