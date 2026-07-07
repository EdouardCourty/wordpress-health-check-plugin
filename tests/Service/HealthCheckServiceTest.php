<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Service;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HealthCheck\Check\HealthCheckInterface;
use HealthCheck\Dto\HealthCheckResult;
use HealthCheck\Dto\HealthStatus;
use HealthCheck\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;

final class HealthCheckServiceTest extends TestCase
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

    public function testRunAllWithNoChecks(): void
    {
        $service = new HealthCheckService(checks: [], cacheTtl: 0);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ok, $result['status']);
        self::assertSame([], $result['checks']);
    }

    public function testRunAllWithPassingCheck(): void
    {
        $check = $this->createHealthCheck('database', HealthCheckResult::ok());
        $service = new HealthCheckService(checks: [$check], cacheTtl: 0);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ok, $result['status']);
        self::assertSame(['status' => HealthStatus::Ok], $result['checks']['database']);
    }

    public function testRunAllWithFailingCheck(): void
    {
        $check = $this->createHealthCheck('redis', HealthCheckResult::ko('Connection refused'));
        $service = new HealthCheckService(checks: [$check], cacheTtl: 0);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ko, $result['status']);
        self::assertSame(HealthStatus::Ko, $result['checks']['redis']['status']);
        self::assertSame('Connection refused', $result['checks']['redis']['error'] ?? null);
    }

    public function testRunAllWithMixedChecks(): void
    {
        $checks = [
            $this->createHealthCheck('database', HealthCheckResult::ok()),
            $this->createHealthCheck('redis', HealthCheckResult::ko('Timeout')),
        ];
        $service = new HealthCheckService(checks: $checks, cacheTtl: 0);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ko, $result['status']);
        self::assertSame(HealthStatus::Ok, $result['checks']['database']['status']);
        self::assertSame(HealthStatus::Ko, $result['checks']['redis']['status']);
    }

    public function testRunAllCatchesExceptions(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('broken');
        $check->method('check')->willThrowException(new \RuntimeException('Unexpected error'));

        $service = new HealthCheckService(checks: [$check], cacheTtl: 0);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ko, $result['status']);
        self::assertSame(HealthStatus::Ko, $result['checks']['broken']['status']);
        self::assertSame('Unexpected error', $result['checks']['broken']['error'] ?? null);
    }

    public function testRunAllWithCacheEnabled(): void
    {
        Functions\when('get_transient')->justReturn(false);

        Functions\expect('set_transient')
            ->once()
            ->with('health_check_result', self::isType('array'), 300);

        $check = $this->createHealthCheck('database', HealthCheckResult::ok());
        $service = new HealthCheckService(checks: [$check], cacheTtl: 300);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ok, $result['status']);
    }

    public function testRunAllUsesCache(): void
    {
        $cachedResult = [
            'status' => HealthStatus::Ok,
            'checks' => ['database' => ['status' => HealthStatus::Ok]],
        ];

        Functions\when('get_transient')->justReturn($cachedResult);

        Functions\expect('set_transient')
            ->never();

        $check = $this->createHealthCheck('database', HealthCheckResult::ok());
        $service = new HealthCheckService(checks: [$check], cacheTtl: 300);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ok, $result['status']);
        self::assertArrayHasKey('database', $result['checks']);
    }

    public function testRunAllWithCacheDisabled(): void
    {
        $check = $this->createHealthCheck('database', HealthCheckResult::ok());
        $service = new HealthCheckService(checks: [$check], cacheTtl: 0);

        $result = $service->runAll();

        self::assertSame(HealthStatus::Ok, $result['status']);
    }

    public function testClearCache(): void
    {
        Functions\expect('delete_transient')
            ->once()
            ->with('health_check_result')
            ->andReturn(true);

        HealthCheckService::clearCache();

        $this->expectNotToPerformAssertions();
    }

    private function createHealthCheck(string $name, HealthCheckResult $result): HealthCheckInterface
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn($name);
        $check->method('check')->willReturn($result);

        return $check;
    }
}
