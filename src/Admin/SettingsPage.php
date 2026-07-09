<?php

declare(strict_types=1);

namespace HealthCheck\Admin;

use HealthCheck\Config\HealthCheckSettings;

final class SettingsPage
{
    public const PAGE_SLUG = 'health-check-settings';

    private const ACTION_SAVE_HEADER = 'health_check_save_header';
    private const ACTION_REGENERATE_SECRET = 'health_check_regenerate_secret';
    private const NOTICE_QUERY_ARG = 'health_check_notice';

    /** @var string[] */
    private const ALLOWED_NOTICES = ['header_saved', 'secret_regenerated'];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_filter('plugin_action_links_' . plugin_basename(HEALTH_CHECK_PLUGIN_FILE), [$this, 'addSettingsLink']);
        add_action('admin_post_' . self::ACTION_SAVE_HEADER, [$this, 'handleSaveHeader']);
        add_action('admin_post_' . self::ACTION_REGENERATE_SECRET, [$this, 'handleRegenerateSecret']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            '',
            'Health Check',
            'Health Check',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage'],
        );
    }

    /**
     * @param string[] $links
     *
     * @return string[]
     */
    public function addSettingsLink(array $links): array
    {
        $url = admin_url('admin.php?page=' . self::PAGE_SLUG);
        $link = '<a href="' . esc_url($url) . '">Réglages</a>';

        array_unshift($links, $link);

        return $links;
    }

    public function renderPage(): void
    {
        $this->assertCanManageSettings();

        $header = HealthCheckSettings::resolveHeader();
        $secret = HealthCheckSettings::resolveSecret();
        $endpointUrl = rest_url('health-check/v1/health');
        $notice = $this->resolveNotice();

        require __DIR__ . '/settings-page.php';
    }

    public function handleSaveHeader(): void
    {
        $this->assertCanManageSettings();
        check_admin_referer(self::ACTION_SAVE_HEADER);

        if (HealthCheckSettings::resolveHeader()->isLocked) {
            $this->redirectWithNotice(null);
        }

        $rawHeader = $_POST['health_check_header'] ?? '';
        HealthCheckSettings::saveHeader(is_string($rawHeader) ? $rawHeader : '');

        $this->redirectWithNotice('header_saved');
    }

    public function handleRegenerateSecret(): void
    {
        $this->assertCanManageSettings();
        check_admin_referer(self::ACTION_REGENERATE_SECRET);

        if (HealthCheckSettings::resolveSecret()->isLocked) {
            $this->redirectWithNotice(null);
        }

        HealthCheckSettings::regenerateSecret();

        $this->redirectWithNotice('secret_regenerated');
    }

    private function assertCanManageSettings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
    }

    private function redirectWithNotice(?string $notice): void
    {
        wp_safe_redirect($this->buildRedirectUrl($notice));
        exit;
    }

    private function buildRedirectUrl(?string $notice): string
    {
        $baseUrl = admin_url('admin.php?page=' . self::PAGE_SLUG);

        if ($notice === null) {
            return $baseUrl;
        }

        return (string) add_query_arg(self::NOTICE_QUERY_ARG, $notice, $baseUrl);
    }

    private function resolveNotice(): ?string
    {
        $rawNotice = $_GET[self::NOTICE_QUERY_ARG] ?? null;

        if (!is_string($rawNotice)) {
            return null;
        }

        $notice = sanitize_key($rawNotice);

        return in_array($notice, self::ALLOWED_NOTICES, true) ? $notice : null;
    }
}
