<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Controller;

use Brain\Monkey;
use HealthCheck\Check\HealthCheckInterface;
use HealthCheck\Controller\HealthCheckEndpoint;
use HealthCheck\Dto\HealthCheckResult;
use HealthCheck\Dto\HealthStatus;
use HealthCheck\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;

final class HealthCheckEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testReturnsOkStatusWithoutAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $endpoint = new HealthCheckEndpoint($service, secret: null);
        $response = $endpoint->handle(new \WP_REST_Request('GET'));

        self::assertSame(200, $response->get_status());
        self::assertSame(['status' => 'ok'], $response->get_data());
    }

    public function testReturnsKoStatusWithoutAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('redis', HealthCheckResult::ko('down')),
        ]);

        $endpoint = new HealthCheckEndpoint($service, secret: null);
        $response = $endpoint->handle(new \WP_REST_Request('GET'));

        self::assertSame(503, $response->get_status());
        self::assertSame(['status' => 'ko'], $response->get_data());
    }

    public function testReturnsDetailedResponseWithValidAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $endpoint = new HealthCheckEndpoint($service, secret: 'my-secret');
        $request = new \WP_REST_Request('GET');
        $request->set_header('Authorization', 'my-secret');

        $response = $endpoint->handle($request);

        self::assertSame(200, $response->get_status());

        /** @var array{status: HealthStatus, checks: array<string, array{status: HealthStatus}>} $data */
        $data = $response->get_data();
        self::assertSame(HealthStatus::Ok, $data['status']);
        self::assertArrayHasKey('checks', $data);
        self::assertSame(['status' => HealthStatus::Ok], $data['checks']['database']);
    }

    public function testReturnsSimpleResponseWithInvalidAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $endpoint = new HealthCheckEndpoint($service, secret: 'my-secret');
        $request = new \WP_REST_Request('GET');
        $request->set_header('Authorization', 'wrong-secret');

        $response = $endpoint->handle($request);

        self::assertSame(200, $response->get_status());
        self::assertSame(['status' => 'ok'], $response->get_data());
    }

    public function testReturnsSimpleResponseWithNoAuthHeader(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $endpoint = new HealthCheckEndpoint($service, secret: 'my-secret');
        $response = $endpoint->handle(new \WP_REST_Request('GET'));

        self::assertSame(200, $response->get_status());
        self::assertSame(['status' => 'ok'], $response->get_data());
    }

    public function testCustomHeaderName(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $endpoint = new HealthCheckEndpoint($service, secret: 'my-secret', header: 'X-Health-Token');
        $request = new \WP_REST_Request('GET');
        $request->set_header('X-Health-Token', 'my-secret');

        $response = $endpoint->handle($request);

        /** @var array<string, mixed> $data */
        $data = $response->get_data();
        self::assertArrayHasKey('checks', $data);
    }

    /**
     * @param HealthCheckInterface[] $checks
     */
    private function buildService(array $checks): HealthCheckService
    {
        return new HealthCheckService(checks: $checks, cacheTtl: 0);
    }

    private function createHealthCheck(string $name, HealthCheckResult $result): HealthCheckInterface
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn($name);
        $check->method('check')->willReturn($result);

        return $check;
    }
}
