<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Diagnostics
{
    private RelayForge_Settings $settings;
    private RelayForge_Api_Client $api_client;

    public function __construct(RelayForge_Settings $settings, RelayForge_Api_Client $api_client)
    {
        $this->settings = $settings;
        $this->api_client = $api_client;
    }

    public function register_menu(): void
    {
        add_submenu_page(
            RelayForge_Settings::OPTION_KEY,
            __('RelayForge Diagnostics', 'relayforge-wordpress'),
            __('RelayForge Diagnostics', 'relayforge-wordpress'),
            'manage_options',
            'relayforge-wp-diagnostics',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $connection = $this->api_client->test_connection();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('RelayForge Diagnostics', 'relayforge-wordpress'); ?></h1>
            <table class="widefat striped" style="max-width: 900px; margin-top: 20px;">
                <tbody>
                    <tr><th><?php esc_html_e('Plugin version', 'relayforge-wordpress'); ?></th><td><?php echo esc_html(RELAYFORGE_WP_VERSION); ?></td></tr>
                    <tr><th><?php esc_html_e('WordPress version', 'relayforge-wordpress'); ?></th><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
                    <tr><th><?php esc_html_e('RelayForge base URL', 'relayforge-wordpress'); ?></th><td><?php echo esc_html((string) $this->settings->get('base_url', '')); ?></td></tr>
                    <tr><th><?php esc_html_e('Tenant slug', 'relayforge-wordpress'); ?></th><td><?php echo esc_html((string) $this->settings->get('tenant_slug', '')); ?></td></tr>
                    <tr><th><?php esc_html_e('Cache TTL', 'relayforge-wordpress'); ?></th><td><?php echo esc_html((string) $this->settings->get('cache_ttl', 900)); ?></td></tr>
                    <tr><th><?php esc_html_e('Connection test', 'relayforge-wordpress'); ?></th><td><?php echo esc_html((string) ($connection['message'] ?? '')); ?></td></tr>
                    <tr><th><?php esc_html_e('HTTP status', 'relayforge-wordpress'); ?></th><td><?php echo esc_html((string) ($connection['status'] ?? 0)); ?></td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}
