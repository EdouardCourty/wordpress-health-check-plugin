<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Dto;

use HealthCheck\Dto\ResolvedSetting;
use PHPUnit\Framework\TestCase;

final class ResolvedSettingTest extends TestCase
{
    public function testUnlockedValue(): void
    {
        $setting = new ResolvedSetting('my-value', false);

        self::assertSame('my-value', $setting->value);
        self::assertFalse($setting->isLocked);
    }

    public function testLockedValue(): void
    {
        $setting = new ResolvedSetting('constant-value', true);

        self::assertSame('constant-value', $setting->value);
        self::assertTrue($setting->isLocked);
    }

    public function testNullValue(): void
    {
        $setting = new ResolvedSetting(null, false);

        self::assertNull($setting->value);
        self::assertFalse($setting->isLocked);
    }
}
