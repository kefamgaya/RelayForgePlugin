<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Theme_Compat
{
    private static bool $body_class_filter_added = false;
    private static bool $document_title_filter_added = false;
    private static bool $request_filters_added = false;
    private static bool $emergency_guard_started = false;
    private static ?WP_Post $virtual_post = null;
    private static string $current_title = '';

    public static function maybe_start_emergency_guard(): void
    {
        if (self::$emergency_guard_started || is_admin() || ! self::is_relayforge_uri()) {
            return;
        }

        self::$emergency_guard_started = true;

        // Deprecations emitted by third-party theme frameworks can break cache/header
        // handling before RelayForge templates run. Keep this scoped to our routes.
        error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        if (function_exists('ini_set')) {
            ini_set('display_errors', '0');
        }

        self::apply_request_filters();
        self::isolate_incompatible_callbacks();
    }

    public static function prepare_virtual_page(string $title = ''): void
    {
        global $wp_query, $post;

        self::$current_title = sanitize_text_field(wp_strip_all_tags($title));
        self::$virtual_post = self::virtual_post(self::$current_title);
        $post = self::$virtual_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

        if ($wp_query instanceof WP_Query) {
            $wp_query->is_404 = false;
            $wp_query->is_singular = true;
            $wp_query->is_page = true;
            $wp_query->is_archive = false;
            $wp_query->is_home = false;
            $wp_query->is_search = false;
            $wp_query->is_tax = false;
            $wp_query->is_category = false;
            $wp_query->is_tag = false;
            $wp_query->post = $post;
            $wp_query->posts = [$post];
            $wp_query->post_count = 1;
            $wp_query->found_posts = 1;
            $wp_query->max_num_pages = 1;
            $wp_query->queried_object = $post;
            $wp_query->queried_object_id = 0;
        }

        if (! self::$body_class_filter_added) {
            add_filter('body_class', [self::class, 'body_classes']);
            self::$body_class_filter_added = true;
        }

        if (! self::$document_title_filter_added) {
            add_filter('document_title_parts', [self::class, 'document_title_parts'], 20);
            self::$document_title_filter_added = true;
        }
    }

    public static function apply_request_filters(): void
    {
        if (self::$request_filters_added) {
            return;
        }

        add_filter('is_active_sidebar', '__return_false', PHP_INT_MAX);
        add_filter('sidebars_widgets', '__return_empty_array', PHP_INT_MAX);
        add_filter('wpseo_breadcrumb_output', '__return_empty_string', PHP_INT_MAX);
        add_filter('rank_math/frontend/breadcrumb/html', '__return_empty_string', PHP_INT_MAX);
        add_filter('bcn_display', '__return_false', PHP_INT_MAX);
        add_filter('bcn_display_list', '__return_false', PHP_INT_MAX);
        add_filter('woocommerce_get_breadcrumb', '__return_empty_array', PHP_INT_MAX);

        self::$request_filters_added = true;
    }

    public static function isolate_head_callbacks(): void
    {
        self::isolate_incompatible_callbacks(['wp_head', 'wp_body_open', 'wp_footer']);
    }

    public static function isolate_incompatible_callbacks($hooks = null): void
    {
        /**
         * When false (default): theme enqueue and markup hooks stay registered so get_header()/get_footer()
         * look like the rest of the site. Set to true only if an active theme breaks RelayForge routes.
         *
         * @since 0.2.1
         */
        if (! apply_filters('relayforge_isolate_theme_hooks', false)) {
            return;
        }

        if (! self::$emergency_guard_started && ! self::is_relayforge_uri()) {
            return;
        }

        global $wp_filter;

        $hooks = is_array($hooks) ? $hooks : array_keys((array) $wp_filter);

        foreach ($hooks as $hook) {
            if (empty($wp_filter[$hook]) || ! is_object($wp_filter[$hook]) || empty($wp_filter[$hook]->callbacks)) {
                continue;
            }

            foreach ($wp_filter[$hook]->callbacks as $priority => &$callbacks) {
                foreach ($callbacks as $callback_id => $callback) {
                    $file = self::callback_file($callback['function'] ?? null);
                    if ($file && self::should_remove_callback_file($file)) {
                        unset($callbacks[$callback_id]);
                    }
                }

                if (empty($callbacks)) {
                    unset($wp_filter[$hook]->callbacks[$priority]);
                }
            }
        }

        unset($callbacks, $callback, $callback_id);
    }

    public static function is_relayforge_uri(): bool
    {
        $path = wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $path = '/' . trim((string) $path, '/') . '/';

        if ('//' === $path) {
            return false;
        }

        $patterns = [
            '#^/relayforge-demo/(tour|packages|destination)/?$#',
            '#^/packages/?$#',
            '#^/tours/[^/]+/?$#',
            '#^/destinations/[^/]+/?$#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    public static function body_classes(array $classes): array
    {
        $classes[] = 'rfp-page';
        $classes[] = 'rfp-canvas-page';
        $classes[] = 'rfp-full-bleed-page';

        return array_values(array_unique($classes));
    }

    public static function document_title_parts(array $parts): array
    {
        if ('' !== self::$current_title) {
            $parts['title'] = self::$current_title;
        }

        return $parts;
    }

    private static function virtual_post(string $title): WP_Post
    {
        if (self::$virtual_post instanceof WP_Post && self::$virtual_post->post_title === $title) {
            return self::$virtual_post;
        }

        $post = new WP_Post((object) [
            'ID' => 0,
            'post_author' => 0,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
            'post_content' => '',
            'post_title' => $title ?: __('RelayForge', 'relayforge-wordpress'),
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => 'relayforge',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => home_url('/relayforge/'),
            'menu_order' => 0,
            'post_type' => 'page',
            'post_mime_type' => '',
            'comment_count' => 0,
            'filter' => 'raw',
        ]);

        wp_cache_add(0, $post, 'posts');

        return $post;
    }

    private static function callback_file($callback): string
    {
        try {
            if ($callback instanceof Closure) {
                $reflection = new ReflectionFunction($callback);
            } elseif (is_string($callback) && function_exists($callback)) {
                $reflection = new ReflectionFunction($callback);
            } elseif (is_array($callback) && isset($callback[0], $callback[1])) {
                $reflection = new ReflectionMethod($callback[0], (string) $callback[1]);
            } elseif (is_object($callback) && method_exists($callback, '__invoke')) {
                $reflection = new ReflectionMethod($callback, '__invoke');
            } else {
                return '';
            }
        } catch (Throwable $e) {
            return '';
        }

        return (string) $reflection->getFileName();
    }

    private static function should_remove_callback_file(string $file): bool
    {
        $file = strtolower(wp_normalize_path($file));
        $theme_paths = array_filter([
            get_template_directory(),
            get_stylesheet_directory(),
        ]);

        foreach ($theme_paths as $theme_path) {
            $theme_path = trailingslashit(strtolower(wp_normalize_path($theme_path)));
            if (0 === strpos($file, $theme_path)) {
                return true;
            }
        }

        $blocked_fragments = (array) apply_filters('relayforge_incompatible_head_callback_path_fragments', [
            'templaza-framework',
        ]);

        foreach ($blocked_fragments as $fragment) {
            $fragment = strtolower(wp_normalize_path((string) $fragment));
            if ('' !== $fragment && false !== strpos($file, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
