<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Dto;

use HealthCheck\Dto\HealthStatus;
use PHPUnit\Framework\TestCase;

final class HealthStatusTest extends TestCase
{
    public function testOkValue(): void
    {
        self::assertSame('ok', HealthStatus::Ok->value);
    }

    public function testKoValue(): void
    {
        self::assertSame('ko', HealthStatus::Ko->value);
    }

    public function testBackedEnum(): void
    {
        self::assertSame(HealthStatus::Ok, HealthStatus::from('ok'));
        self::assertSame(HealthStatus::Ko, HealthStatus::from('ko'));
    }
}
