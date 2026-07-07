<?php

declare(strict_types=1);

namespace HealthCheck\Cli;

use HealthCheck\Dto\HealthStatus;
use HealthCheck\Service\HealthCheckService;

final class HealthCheckCommand
{
    private HealthCheckService $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * @param array<int, string>    $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $result = $this->healthCheckService->runAll();

        \WP_CLI::line('Health Check');
        \WP_CLI::line('============');
        \WP_CLI::line('');

        if (count($result['checks']) === 0) {
            \WP_CLI::warning('No health checks registered.');

            return;
        }

        $items = [];
        foreach ($result['checks'] as $name => $check) {
            $status = $check['status'] === HealthStatus::Ok ? 'OK' : 'KO';
            $error = $check['error'] ?? '';
            $items[] = [
                'Check' => $name,
                'Status' => $status,
                'Error' => $error,
            ];
        }

        \WP_CLI\Utils\format_items('table', $items, ['Check', 'Status', 'Error']);

        if ($result['status'] === HealthStatus::Ok) {
            \WP_CLI::success('All checks passed.');
        } else {
            $failedCount = count(array_filter($result['checks'], fn (array $c) => $c['status'] === HealthStatus::Ko));
            \WP_CLI::error(sprintf('%d of %d check(s) failed.', $failedCount, count($result['checks'])));
        }
    }
}
