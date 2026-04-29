<?php
/**
 * Admin: Overview / Get started (RelayForge_Settings $this in scope).
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}

$api_key     = (string) $this->get('api_key', '');
$connected   = false;
$tour_count  = 0;
$dest_count  = 0;
$conn_msg    = '';
$settings_pg = admin_url('admin.php?page=' . $this::OPTION_KEY . '_settings');
$content_pg  = admin_url('admin.php?page=' . $this::OPTION_KEY . '_content');
$shortcodes_pg = admin_url('admin.php?page=' . $this::OPTION_KEY . '_shortcodes');

if ($api_key && class_exists('RelayForge_Plugin')) {
    $plugin   = RelayForge_Plugin::instance();
    $test     = (array) $plugin->api_client->test_connection();
    $status   = (int) ($test['status'] ?? 0);
    $connected = $status >= 200 && $status < 300;

    if ($connected) {
        $t_res = $plugin->api_client->get_tours(['limit' => 1, 'offset' => 0]);
        $d_res = $plugin->api_client->get_destinations(['limit' => 1, 'offset' => 0]);
        $tour_count = (int) ($t_res['data']['total'] ?? count((array) ($t_res['data']['tours'] ?? [])));
        $dest_count = (int) ($d_res['data']['total'] ?? count((array) ($d_res['data']['destinations'] ?? [])));
        $conn_msg   = '&#10003; ' . __('Connected to RelayForge', 'relayforge-wordpress');
    } else {
        $conn_msg = '&#10007; ' . __('Connection failed — check your secret key', 'relayforge-wordpress');
    }
} elseif (! $api_key) {
    $conn_msg = __('No secret key set yet', 'relayforge-wordpress');
}

$permalink_ok  = '' !== (string) get_option('permalink_structure', '');
$rewrite_rules = (array) get_option('rewrite_rules', []);
$rules_ok      = ! empty($rewrite_rules['^relayforge-demo/tour/?$']);

$short_tours  = '[relayforge_tours limit="6" columns="3"]';
$short_dests  = '[relayforge_destinations limit="6" columns="3"]';

$step1_done = '' !== $api_key && $connected;
?>
<div class="wrap relayforge-admin rf-admin-shell">
    <div class="relayforge-admin__hero">
        <div class="relayforge-admin__brand-lockup">
            <img class="relayforge-admin__brand-logo" src="<?php echo esc_url(RELAYFORGE_WP_URL . 'assets/brand/relayforge-vertical-logo.svg'); ?>" alt="<?php esc_attr_e('RelayForge', 'relayforge-wordpress'); ?>" />
            <h1><?php esc_html_e('RelayForge', 'relayforge-wordpress'); ?></h1>
            <p class="relayforge-admin__sub"><?php esc_html_e('Your tours, destinations and booking — all in WordPress.', 'relayforge-wordpress'); ?></p>
        </div>
        <span class="relayforge-admin__version">v<?php echo esc_html(RELAYFORGE_WP_VERSION); ?></span>
    </div>

    <div class="rf-status-strip">
        <span class="rf-status-dot <?php echo $connected ? 'rf-status-dot--ok' : ($api_key ? 'rf-status-dot--err' : 'rf-status-dot--warn'); ?>"></span>
        <span><?php echo esc_html($conn_msg ?: __('Add your secret key to connect', 'relayforge-wordpress')); ?></span>
        <?php if (! $api_key) : ?>
            <span class="rf-status-strip__actions"><a href="<?php echo esc_url($settings_pg); ?>"><?php esc_html_e('Connection settings →', 'relayforge-wordpress'); ?></a></span>
        <?php endif; ?>
    </div>

    <div class="rf-overview-grid">
        <div class="rf-stat-card">
            <div class="rf-stat-card__label"><?php esc_html_e('Tours', 'relayforge-wordpress'); ?></div>
            <div class="rf-stat-card__value"><?php echo $connected ? esc_html((string) $tour_count) : '—'; ?></div>
            <div class="rf-stat-card__label"><?php esc_html_e('in your RelayForge account', 'relayforge-wordpress'); ?></div>
        </div>
        <div class="rf-stat-card">
            <div class="rf-stat-card__label"><?php esc_html_e('Destinations', 'relayforge-wordpress'); ?></div>
            <div class="rf-stat-card__value"><?php echo $connected ? esc_html((string) $dest_count) : '—'; ?></div>
            <div class="rf-stat-card__label"><?php esc_html_e('in your RelayForge account', 'relayforge-wordpress'); ?></div>
        </div>
        <div class="rf-stat-card">
            <div class="rf-stat-card__label"><?php esc_html_e('Pretty links', 'relayforge-wordpress'); ?></div>
            <div class="rf-stat-card__value" style="font-size:26px;"><?php echo $permalink_ok && $rules_ok ? '✓' : '⚠'; ?></div>
            <div class="rf-stat-card__label">
                <?php if (! $permalink_ok) : ?>
                    <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>"><?php esc_html_e('Turn on permalinks', 'relayforge-wordpress'); ?></a>
                <?php elseif (! $rules_ok) : ?>
                    <?php esc_html_e('Reload this page to finish setup', 'relayforge-wordpress'); ?>
                <?php else : ?>
                    <?php esc_html_e('Tour and destination URLs ready', 'relayforge-wordpress'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="rf-get-started" aria-labelledby="rf-get-started-heading">
        <h2 id="rf-get-started-heading"><?php esc_html_e('Get started', 'relayforge-wordpress'); ?></h2>
        <p class="relayforge-admin__sub"><?php esc_html_e('Follow these steps once. You can change looks and options anytime under Settings.', 'relayforge-wordpress'); ?></p>
        <ol class="rf-checklist">
            <li class="rf-checklist__item<?php echo $step1_done ? ' rf-checklist__item--done' : ''; ?>">
                <span class="rf-checklist__num" aria-hidden="true"><?php echo $step1_done ? '✓' : '1'; ?></span>
                <div class="rf-checklist__body">
                    <h3><?php esc_html_e('Connect WordPress to RelayForge', 'relayforge-wordpress'); ?></h3>
                    <p><?php esc_html_e('Paste the secret key from your RelayForge account so this site can load your tours and destinations.', 'relayforge-wordpress'); ?></p>
                    <div class="rf-checklist__actions">
                        <a href="<?php echo esc_url($settings_pg); ?>"><?php esc_html_e('Open Connection', 'relayforge-wordpress'); ?></a>
                    </div>
                </div>
            </li>
            <li class="rf-checklist__item">
                <span class="rf-checklist__num" aria-hidden="true">2</span>
                <div class="rf-checklist__body">
                    <h3><?php esc_html_e('Pick how cards look (optional)', 'relayforge-wordpress'); ?></h3>
                    <p><?php esc_html_e('Choose a template and colors, or keep the defaults and skip this for now.', 'relayforge-wordpress'); ?></p>
                    <div class="rf-checklist__actions">
                        <a href="<?php echo esc_url($settings_pg); ?>"><?php esc_html_e('Card layouts & design', 'relayforge-wordpress'); ?></a>
                    </div>
                </div>
            </li>
            <li class="rf-checklist__item">
                <span class="rf-checklist__num" aria-hidden="true">3</span>
                <div class="rf-checklist__body">
                    <h3><?php esc_html_e('Add tours or destinations to a page', 'relayforge-wordpress'); ?></h3>
                    <p><?php esc_html_e('Create or edit a page, add a Shortcode block, and paste one of the lines below.', 'relayforge-wordpress'); ?></p>
                    <p class="relayforge-admin__hint"><?php esc_html_e('Tip: In the block editor, insert a “Shortcode” block, then paste.', 'relayforge-wordpress'); ?></p>
                    <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($short_tours); ?>">
                        <code><?php echo esc_html($short_tours); ?></code>
                        <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
                    </div>
                    <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($short_dests); ?>">
                        <code><?php echo esc_html($short_dests); ?></code>
                        <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
                    </div>
                    <div class="rf-checklist__actions">
                        <a href="<?php echo esc_url($shortcodes_pg); ?>"><?php esc_html_e('All shortcodes and options →', 'relayforge-wordpress'); ?></a>
                    </div>
                </div>
            </li>
        </ol>
    </section>

    <div class="rf-overview-links">
        <a class="rf-overview-link" href="<?php echo esc_url($settings_pg); ?>">
            <strong><?php esc_html_e('Settings', 'relayforge-wordpress'); ?></strong>
            <span><?php esc_html_e('Connection, card style, colors, fonts, and caching', 'relayforge-wordpress'); ?></span>
        </a>
        <a class="rf-overview-link" href="<?php echo esc_url($content_pg); ?>">
            <strong><?php esc_html_e('Tours & destinations', 'relayforge-wordpress'); ?></strong>
            <span><?php esc_html_e('See what is available from RelayForge (read-only)', 'relayforge-wordpress'); ?></span>
        </a>
        <a class="rf-overview-link" href="<?php echo esc_url($shortcodes_pg); ?>">
            <strong><?php esc_html_e('Shortcode reference', 'relayforge-wordpress'); ?></strong>
            <span><?php esc_html_e('Copy snippets and optional attributes', 'relayforge-wordpress'); ?></span>
        </a>
        <a class="rf-overview-link" href="<?php echo esc_url(home_url('/relayforge-demo/tour/')); ?>" target="_blank" rel="noopener noreferrer">
            <strong><?php esc_html_e('Demo tour page', 'relayforge-wordpress'); ?></strong>
            <span><?php esc_html_e('Preview how a tour page looks on your site', 'relayforge-wordpress'); ?></span>
        </a>
    </div>
</div>
