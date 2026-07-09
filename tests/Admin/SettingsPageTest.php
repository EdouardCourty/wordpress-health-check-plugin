<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HealthCheck\Admin\SettingsPage;
use PHPUnit\Framework\TestCase;

final class SettingsPageTest extends TestCase
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

    public function testAddSettingsLinkPrependsSettingsLink(): void
    {
        Functions\when('admin_url')->alias(fn (string $path): string => 'https://example.test/wp-admin/' . $path);
        Functions\when('esc_url')->returnArg();

        $settingsPage = new SettingsPage();
        $links = $settingsPage->addSettingsLink(['deactivate' => '<a>Deactivate</a>']);

        self::assertSame(
            '<a href="https://example.test/wp-admin/admin.php?page=health-check-settings">Réglages</a>',
            $links[0],
        );
        self::assertSame('<a>Deactivate</a>', $links['deactivate']);
    }
}
