<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Cli;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HealthCheck\Cli\HealthCacheClearCommand;
use PHPUnit\Framework\TestCase;

final class HealthCacheClearCommandTest extends TestCase
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

    public function testClearCache(): void
    {
        Functions\expect('delete_transient')
            ->once()
            ->with('health_check_result')
            ->andReturn(true);

        $command = new HealthCacheClearCommand();
        $command([], []);

        $this->expectNotToPerformAssertions();
    }
}
