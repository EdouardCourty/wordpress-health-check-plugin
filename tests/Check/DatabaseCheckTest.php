<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Check;

use HealthCheck\Check\DatabaseCheck;
use PHPUnit\Framework\TestCase;

final class DatabaseCheckTest extends TestCase
{
    public function testGetName(): void
    {
        $check = new DatabaseCheck();

        self::assertSame('database', $check->getName());
    }

    public function testCheckReturnsOkWhenQuerySucceeds(): void
    {
        $this->registerWpdb();

        $check = new DatabaseCheck();
        $result = $check->check();

        self::assertTrue($result->success);
    }

    public function testCheckReturnsKoWhenQueryFails(): void
    {
        $this->registerWpdb(expectedResult: false);

        $check = new DatabaseCheck();
        $result = $check->check();

        self::assertFalse($result->success);
        self::assertNotNull($result->error);
    }

    public function testCheckReturnsKoWhenLastErrorIsSet(): void
    {
        $this->registerWpdb(lastError: 'Table not found');

        $check = new DatabaseCheck();
        $result = $check->check();

        self::assertFalse($result->success);
        self::assertSame('Table not found', $result->error);
    }

    private function registerWpdb(mixed $expectedResult = true, string $lastError = ''): void
    {
        global $wpdb;

        $wpdb = new class($expectedResult, $lastError) {
            public string $last_error;

            public function __construct(
                private readonly mixed $expectedResult,
                string $lastError,
            ) {
                $this->last_error = $lastError;
            }

            public function get_results(string $query): mixed
            {
                if ($this->expectedResult === false) {
                    throw new \RuntimeException('Connection failed');
                }

                return $this->expectedResult;
            }
        };
    }
}
