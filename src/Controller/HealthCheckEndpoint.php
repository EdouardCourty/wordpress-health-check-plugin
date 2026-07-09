<?php

declare(strict_types=1);

namespace HealthCheck\Controller;

use HealthCheck\Config\HealthCheckSettings;
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
        string $header = HealthCheckSettings::DEFAULT_HEADER,
    ) {
        $this->healthCheckService = $healthCheckService;
        $this->secret = $secret;
        $this->header = $header;
    }

    public static function fromDefaults(HealthCheckService $healthCheckService): self
    {
        $secret = HealthCheckSettings::resolveSecret()->value;
        $header = HealthCheckSettings::resolveHeader()->value ?? HealthCheckSettings::DEFAULT_HEADER;

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
