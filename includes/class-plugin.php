<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once RELAYFORGE_WP_PATH . 'includes/pricing.php';
require_once RELAYFORGE_WP_PATH . 'includes/icons.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-typography.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-theme-styles.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-settings.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-cache.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-api-client.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-theme-compat.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-shortcodes.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-router.php';
require_once RELAYFORGE_WP_PATH . 'includes/class-diagnostics.php';

class RelayForge_Plugin
{
    private static ?RelayForge_Plugin $instance = null;

    public RelayForge_Settings $settings;
    public RelayForge_Cache $cache;
    public RelayForge_Api_Client $api_client;
    public RelayForge_Shortcodes $shortcodes;
    public RelayForge_Router $router;
    public RelayForge_Diagnostics $diagnostics;

    public static function instance(): RelayForge_Plugin
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->settings = new RelayForge_Settings();
        $this->cache = new RelayForge_Cache();
        $this->api_client = new RelayForge_Api_Client($this->settings, $this->cache);
        $this->shortcodes = new RelayForge_Shortcodes($this->api_client, $this->settings);
        $this->router = new RelayForge_Router($this->api_client, $this->settings);
        $this->diagnostics = new RelayForge_Diagnostics($this->settings, $this->api_client);

        RelayForge_Theme_Compat::maybe_start_emergency_guard();
        add_action('plugins_loaded', ['RelayForge_Theme_Compat', 'maybe_start_emergency_guard'], PHP_INT_MAX);
        add_action('init', ['RelayForge_Theme_Compat', 'maybe_start_emergency_guard'], 0);
        add_action('init', ['RelayForge_Theme_Compat', 'isolate_incompatible_callbacks'], PHP_INT_MAX, 0);
        add_action('wp_loaded', ['RelayForge_Theme_Compat', 'isolate_incompatible_callbacks'], PHP_INT_MAX, 0);
        add_action('template_redirect', ['RelayForge_Theme_Compat', 'isolate_incompatible_callbacks'], 0, 0);
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_head', [$this, 'output_custom_style_vars'], 20);
        add_action('admin_init', [$this, 'maybe_flush_rewrite_rules']);
    }

    public function maybe_flush_rewrite_rules(): void
    {
        $rules = (array) get_option('rewrite_rules', []);
        if (empty($rules['^relayforge-demo/tour/?$']) || empty($rules['^tours/([^/]+)/?$'])) {
            self::register_rewrite_rules();
            flush_rewrite_rules(false);
        }
    }

    public function output_custom_style_vars(): void
    {
        $accent  = sanitize_hex_color((string) $this->settings->get('cards_color_accent', ''));
        $surface = sanitize_hex_color((string) $this->settings->get('cards_color_surface', ''));
        $text    = sanitize_hex_color((string) $this->settings->get('cards_color_text', ''));
        $theme_accent = $accent ?: RelayForge_Theme_Styles::accent_color();
        $theme_surface = $surface ?: RelayForge_Theme_Styles::surface_color();
        $theme_text = $text ?: RelayForge_Theme_Styles::text_color();
        $theme_body_font = RelayForge_Theme_Styles::body_font();
        $theme_heading_font = RelayForge_Theme_Styles::heading_font();

        $vars = [];
        if ($theme_accent) {
            $vars[] = '--rfp-accent:' . $theme_accent;
            $vars[] = '--rf-theme-link:' . $theme_accent;
            $vars[] = '--rfp-hero-overlay:linear-gradient(180deg,color-mix(in srgb,' . $theme_accent . ' 56%,transparent),color-mix(in srgb,' . $theme_accent . ' 78%,transparent))';
        }
        if ($theme_surface) {
            $vars[] = '--rfp-surface:' . $theme_surface;
        }
        if ($theme_text) {
            $vars[] = '--rfp-text:' . $theme_text;
            $vars[] = '--rf-theme-text:' . $theme_text;
        }
        if ($theme_body_font) {
            $vars[] = '--rfp-font-family:' . $theme_body_font;
        }
        if ($theme_heading_font) {
            $vars[] = '--rfp-font-title:' . $theme_heading_font;
        }

        $typography = RelayForge_Typography::build_frontend_style_block($this->settings->all());

        if (! empty($vars)) {
            echo '<style id="relayforge-custom-vars">.rfp,.rfp-detail,body.rfp-page{' . implode(';', $vars) . ';}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        if ('' !== trim($typography)) {
            echo '<style id="relayforge-typography">' . trim($typography) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('relayforge-wordpress', false, dirname(plugin_basename(RELAYFORGE_WP_FILE)) . '/languages');
    }

    public function register_assets(): void
    {
        wp_register_style(
            'relayforge-wordpress',
            RELAYFORGE_WP_URL . 'assets/css/relayforge.css',
            [],
            RELAYFORGE_WP_VERSION
        );

        wp_register_script(
            'relayforge-wordpress',
            RELAYFORGE_WP_URL . 'assets/js/relayforge.js',
            [],
            RELAYFORGE_WP_VERSION,
            true
        );
    }

    public static function activate(): void
    {
        self::register_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public static function register_rewrite_rules(): void
    {
        add_rewrite_tag('%relayforge_view%', '([^&]+)');
        add_rewrite_tag('%relayforge_slug%', '([^&]+)');
        add_rewrite_rule('^packages/?$', 'index.php?relayforge_view=packages', 'top');
        add_rewrite_rule('^relayforge-demo/packages/?$', 'index.php?relayforge_view=demo_packages', 'top');
        add_rewrite_rule('^relayforge-demo/tour/?$', 'index.php?relayforge_view=demo_tour', 'top');
        add_rewrite_rule('^relayforge-demo/destination/?$', 'index.php?relayforge_view=demo_destination', 'top');
        add_rewrite_rule('^destinations/([^/]+)/?$', 'index.php?relayforge_view=destination&relayforge_slug=$matches[1]', 'top');
        add_rewrite_rule('^tours/([^/]+)/?$', 'index.php?relayforge_view=tour&relayforge_slug=$matches[1]', 'top');
    }
}
