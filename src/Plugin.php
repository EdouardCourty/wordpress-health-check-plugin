<?php

declare(strict_types=1);

namespace HealthCheck;

use HealthCheck\Check\DatabaseCheck;
use HealthCheck\Check\HealthCheckInterface;
use HealthCheck\Cli\HealthCacheClearCommand;
use HealthCheck\Cli\HealthCheckCommand;
use HealthCheck\Controller\HealthCheckEndpoint;
use HealthCheck\Service\HealthCheckService;

final class Plugin
{
    private HealthCheckService $healthCheckService;

    public function __construct()
    {
        $checks = $this->collectChecks();
        $this->healthCheckService = new HealthCheckService($checks);
    }

    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerRoute']);
        add_action('init', [$this, 'registerCliCommands']);
    }

    public function registerRoute(): void
    {
        $endpoint = HealthCheckEndpoint::fromDefaults($this->healthCheckService);

        register_rest_route('health-check/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$endpoint, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function registerCliCommands(): void
    {
        if (!defined('WP_CLI') || !constant('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('health check', new HealthCheckCommand($this->healthCheckService));
        \WP_CLI::add_command('health cache clear', new HealthCacheClearCommand());
    }

    /**
     * @return HealthCheckInterface[]
     */
    private function collectChecks(): array
    {
        $checks = [];
        $checks[] = new DatabaseCheck();

        /** @var HealthCheckInterface[] $checks */
        $checks = apply_filters('health_check_checks', $checks);

        return array_filter($checks, fn (mixed $check): bool => $check instanceof HealthCheckInterface);
    }
}
