<?php

declare(strict_types=1);

namespace HealthCheck\Check;

use HealthCheck\Dto\HealthCheckResult;

final class DatabaseCheck implements HealthCheckInterface
{
    public function getName(): string
    {
        return 'database';
    }

    public function check(): HealthCheckResult
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        try {
            $wpdb->get_results('SELECT 1');

            if (!empty($wpdb->last_error)) {
                return HealthCheckResult::ko((string) $wpdb->last_error);
            }

            return HealthCheckResult::ok();
        } catch (\Throwable $e) {
            return HealthCheckResult::ko($e->getMessage());
        }
    }
}
