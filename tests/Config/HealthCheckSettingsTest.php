<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Config;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HealthCheck\Config\HealthCheckSettings;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class HealthCheckSettingsTest extends TestCase
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

    public function testResolveHeaderFallsBackToDefault(): void
    {
        Functions\when('get_option')->justReturn(false);

        $setting = HealthCheckSettings::resolveHeader();

        self::assertSame('Authorization', $setting->value);
        self::assertFalse($setting->isLocked);
    }

    public function testResolveHeaderUsesOption(): void
    {
        Functions\when('get_option')->justReturn('X-Health-Token');

        $setting = HealthCheckSettings::resolveHeader();

        self::assertSame('X-Health-Token', $setting->value);
        self::assertFalse($setting->isLocked);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testResolveHeaderIsLockedByConstant(): void
    {
        define('HEALTH_CHECK_HEADER', 'X-Constant-Header');

        $setting = HealthCheckSettings::resolveHeader();

        self::assertSame('X-Constant-Header', $setting->value);
        self::assertTrue($setting->isLocked);
    }

    public function testResolveSecretReturnsNullByDefault(): void
    {
        Functions\when('get_option')->justReturn(false);

        $setting = HealthCheckSettings::resolveSecret();

        self::assertNull($setting->value);
        self::assertFalse($setting->isLocked);
    }

    public function testResolveSecretUsesOption(): void
    {
        Functions\when('get_option')->justReturn('stored-secret');

        $setting = HealthCheckSettings::resolveSecret();

        self::assertSame('stored-secret', $setting->value);
        self::assertFalse($setting->isLocked);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testResolveSecretIsLockedByConstant(): void
    {
        define('HEALTH_CHECK_SECRET', 'constant-secret');

        $setting = HealthCheckSettings::resolveSecret();

        self::assertSame('constant-secret', $setting->value);
        self::assertTrue($setting->isLocked);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testEmptyConstantIsTreatedAsNotDefined(): void
    {
        define('HEALTH_CHECK_SECRET', '');
        Functions\when('get_option')->justReturn(false);

        $setting = HealthCheckSettings::resolveSecret();

        self::assertNull($setting->value);
        self::assertFalse($setting->isLocked);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstantSetToZeroIsStillTreatedAsDefined(): void
    {
        define('HEALTH_CHECK_SECRET', '0');

        $setting = HealthCheckSettings::resolveSecret();

        self::assertSame('0', $setting->value);
        self::assertTrue($setting->isLocked);
    }

    public function testSaveHeaderPersistsSanitizedValue(): void
    {
        Functions\expect('update_option')
            ->once()
            ->with(HealthCheckSettings::OPTION_HEADER, 'X-Health-Token', true);

        HealthCheckSettings::saveHeader('X-Health-Token');

        $this->expectNotToPerformAssertions();
    }

    public function testSanitizeHeaderNameKeepsValidToken(): void
    {
        self::assertSame('X-Health-Token', HealthCheckSettings::sanitizeHeaderName('X-Health-Token'));
    }

    public function testSanitizeHeaderNameStripsInvalidCharacters(): void
    {
        self::assertSame('XHealthToken', HealthCheckSettings::sanitizeHeaderName('X Health: Token!'));
    }

    public function testSanitizeHeaderNameFallsBackWhenEmpty(): void
    {
        self::assertSame('Authorization', HealthCheckSettings::sanitizeHeaderName('   '));
    }

    public function testSanitizeHeaderNameFallsBackWhenNotStartingWithLetter(): void
    {
        self::assertSame('Authorization', HealthCheckSettings::sanitizeHeaderName('-123'));
    }

    public function testRegenerateSecretReturnsHexToken(): void
    {
        Functions\expect('update_option')
            ->once()
            ->with(HealthCheckSettings::OPTION_SECRET, self::isType('string'), true);

        $secret = HealthCheckSettings::regenerateSecret();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $secret);
    }
}
