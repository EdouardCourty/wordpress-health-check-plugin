<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Cli;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HealthCheck\Check\HealthCheckInterface;
use HealthCheck\Cli\HealthCheckCommand;
use HealthCheck\Dto\HealthCheckResult;
use HealthCheck\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;

final class HealthCheckCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('WP_CLI\\Utils\\format_items')->justReturn(null);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testAllChecksPassed(): void
    {
        $service = new HealthCheckService(
            checks: [$this->createHealthCheck('database', HealthCheckResult::ok())],
            cacheTtl: 0,
        );

        $command = new HealthCheckCommand($service);
        $command([], []);

        $this->expectNotToPerformAssertions();
    }

    public function testSomeChecksFailed(): void
    {
        $service = new HealthCheckService(
            checks: [
                $this->createHealthCheck('database', HealthCheckResult::ok()),
                $this->createHealthCheck('redis', HealthCheckResult::ko('Connection refused')),
            ],
            cacheTtl: 0,
        );

        $command = new HealthCheckCommand($service);
        $command([], []);

        $this->expectNotToPerformAssertions();
    }

    public function testNoChecksRegistered(): void
    {
        $service = new HealthCheckService(checks: [], cacheTtl: 0);

        $command = new HealthCheckCommand($service);
        $command([], []);

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
