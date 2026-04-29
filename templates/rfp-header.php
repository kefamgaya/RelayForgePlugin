<?php
/**
 * Opens RelayForge frontend templates.
 * Default: theme header via get_header() (site masthead/footer integration).
 *
 * Filters:
 * - relayforge_use_theme_shell (bool, default true) — false loads legacy bundled HTML shell.
 * - relayforge_get_header_name (string|null) — pass a slug to prefer header-{slug}.php (e.g. 'relayforge' → header-relayforge.php).
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}

RelayForge_Theme_Compat::prepare_virtual_page(isset($rfp_title) ? (string) $rfp_title : '');
RelayForge_Theme_Compat::apply_request_filters();

if (! apply_filters('relayforge_use_theme_shell', true)) {
    require RELAYFORGE_WP_PATH . 'templates/rfp-shell-standalone-open.php';

    return;
}

$header_slug = apply_filters('relayforge_get_header_name', '');

// Loads header-{slug}.php when slug is non-empty, else header.php.
if (is_string($header_slug) && '' !== trim($header_slug)) {
    get_header(trim($header_slug));
} else {
    get_header();
}
