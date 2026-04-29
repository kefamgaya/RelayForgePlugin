<?php
/**
 * Plugin Name: RelayForge WordPress
 * Plugin URI: https://relayforge.app
 * Description: Connect WordPress websites to RelayForge for tours, reviews, forms, destinations, and related tourism content.
 * Version: 0.2.1
 * Author: RelayForge
 * Author URI: https://relayforge.app
 * Text Domain: relayforge-wordpress
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if (! defined('ABSPATH')) {
    exit;
}

define('RELAYFORGE_WP_VERSION', '0.2.1');
define('RELAYFORGE_WP_FILE', __FILE__);
define('RELAYFORGE_WP_PATH', plugin_dir_path(__FILE__));
define('RELAYFORGE_WP_URL', plugin_dir_url(__FILE__));

require_once RELAYFORGE_WP_PATH . 'includes/class-plugin.php';

register_activation_hook(RELAYFORGE_WP_FILE, ['RelayForge_Plugin', 'activate']);
register_deactivation_hook(RELAYFORGE_WP_FILE, ['RelayForge_Plugin', 'deactivate']);

function relayforge_wordpress()
{
    return RelayForge_Plugin::instance();
}

function relayforge_content_slug(array $item, string $type = 'tour'): string
{
    $name_key = 'destination' === $type ? 'name' : 'title';
    $values = [
        $item['slug'] ?? '',
        $item['urlSlug'] ?? '',
        $item['handle'] ?? '',
        $item['key'] ?? '',
        $item[$name_key] ?? '',
        $item['id'] ?? '',
        $item['legacyId'] ?? '',
    ];

    foreach ($values as $value) {
        $slug = sanitize_title((string) $value);
        if ('' !== $slug) {
            return $slug;
        }
    }

    return '';
}

/**
 * Resolve a RelayForge image ref the same way as the RelayForge web app catalog (bare id, /api/... path, or absolute URL).
 */
function relayforge_resolve_asset_url(string $raw): string
{
    if (! class_exists('RelayForge_Plugin')) {
        return trim($raw);
    }

    return RelayForge_Plugin::instance()->api_client->resolve_asset_url($raw);
}

/**
 * Best image for tour cards: gallery first, then cover fields, then first itinerary day image (GET /api/v1/tours parity).
 */
function relayforge_tour_cover_image_url(array $tour): string
{
    if (! class_exists('RelayForge_Plugin')) {
        return '';
    }

    return RelayForge_Plugin::instance()->api_client->tour_cover_image_url($tour);
}

relayforge_wordpress();
