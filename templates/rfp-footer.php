<?php
/**
 * Closes RelayForge frontend templates. Pairs with rfp-header.php.
 *
 * Filters:
 * - relayforge_use_theme_shell (bool, default true) — see rfp-header.php.
 * - relayforge_get_footer_name (string|null) — slug for footer-{slug}.php.
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! apply_filters('relayforge_use_theme_shell', true)) {
    require RELAYFORGE_WP_PATH . 'templates/rfp-shell-standalone-close.php';

    return;
}

$footer_slug = apply_filters('relayforge_get_footer_name', '');

if (is_string($footer_slug) && '' !== trim($footer_slug)) {
    get_footer(trim($footer_slug));
} else {
    get_footer();
}
