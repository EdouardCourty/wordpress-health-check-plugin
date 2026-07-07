<?php

declare(strict_types=1);

namespace HealthCheck\Controller;

use HealthCheck\Dto\HealthStatus;
use HealthCheck\Service\HealthCheckService;

final class HealthCheckEndpoint
{
    private HealthCheckService $healthCheckService;
    private ?string $secret;
    private string $header;

    public function __construct(
        HealthCheckService $healthCheckService,
        ?string $secret = null,
        string $header = 'Authorization',
    ) {
        $this->healthCheckService = $healthCheckService;
        $this->secret = $secret;
        $this->header = $header;
    }

    public static function fromDefaults(HealthCheckService $healthCheckService): self
    {
        $secret = defined('HEALTH_CHECK_SECRET') ? (string) HEALTH_CHECK_SECRET : null;
        $header = defined('HEALTH_CHECK_HEADER') ? (string) HEALTH_CHECK_HEADER : 'Authorization';

        if (empty($secret)) {
            $secret = null;
        }

        return new self($healthCheckService, $secret, $header);
    }

    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $result = $this->healthCheckService->runAll();
        $statusCode = $result['status'] === HealthStatus::Ok ? 200 : 503;

        if ($this->isAuthorized($request)) {
            $body = $result;
        } else {
            $body = ['status' => $result['status']->value];
        }

        return new \WP_REST_Response($body, $statusCode);
    }

    private function isAuthorized(\WP_REST_Request $request): bool
    {
        if ($this->secret === null) {
            return false;
        }

        $authorization = $request->get_header($this->header);

        if ($authorization === null) {
            return false;
        }

        return hash_equals($this->secret, $authorization);
    }
}
