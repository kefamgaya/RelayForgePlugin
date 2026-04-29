<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Theme font presets (theme.json / block editor) plus @font-face for uploaded files.
 */
class RelayForge_Typography
{
    /**
     * @return array<string, array{label: string, css: string}> slug keyed
     */
    public static function get_wordpress_font_preset_list(): array
    {
        static $memo = null;

        if (null !== $memo) {
            return $memo;
        }

        $items = [];

        if (class_exists('\WP_Theme_JSON_Resolver')) {
            try {
                $theme_json = \WP_Theme_JSON_Resolver::get_merged_data();
                $data       = [];
                if (method_exists($theme_json, 'get_data')) {
                    $data = (array) $theme_json->get_data();
                }
                if (! empty($data['settings']['typography']['fontFamilies'])) {
                    self::collect_font_family_nodes($data['settings']['typography']['fontFamilies'], $items);
                }
            } catch (\Throwable $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
            }
        }

        if (\function_exists('wp_get_global_settings')) {
            $global = wp_get_global_settings();
            $fams   = $global['typography']['fontFamilies'] ?? [];
            if (is_array($fams)) {
                self::collect_font_family_nodes($fams, $items);
            }
        }

        if (empty($items)) {
            $items['system-ui'] = [
                'label' => __('System UI (recommended fallback)', 'relayforge-wordpress'),
                'css'   => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
            ];
            $items['serif'] = [
                'label' => __('Serif fallback', 'relayforge-wordpress'),
                'css'   => 'Georgia, "Times New Roman", Times, serif',
            ];
            $items['sans'] = [
                'label' => __('Sans-serif fallback', 'relayforge-wordpress'),
                'css'   => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            ];
        }

        $memo = $items;

        return $memo;
    }

    /**
     * @param mixed                                            $tree
     * @param array<string, array{label: string, css: string}> $into
     */
    private static function collect_font_family_nodes($tree, array &$into): void
    {
        if (\is_scalar($tree) || null === $tree) {
            return;
        }

        if (! is_array($tree)) {
            return;
        }

        foreach ($tree as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item['slug'], $item['fontFamily'])) {
                self::inject_presets_list($item, $into);
                continue;
            }
            self::collect_font_family_nodes($item, $into);
        }

        unset($into['']);
    }

    /**
     * @param array<string, mixed>                           $preset
     * @param array<string, array{label: string, css: string}> $into
     */
    private static function inject_presets_list(array $preset, array &$into): void
    {
        $slug = strtolower(sanitize_key(str_replace('.', '-', (string) ($preset['slug'] ?? ''))));
        if ('' === $slug) {
            return;
        }

        $raw_family = trim((string) ($preset['fontFamily'] ?? ''));
        $name       = isset($preset['name']) ? (string) $preset['name'] : '';

        self::inject_one_family($slug, $raw_family, $name, $into);
    }

    /**
     * @param array<string, array{label: string, css: string}> $into
     */
    private static function inject_one_family(string $slug, string $raw_family, string $name, array &$into): void
    {
        $label = trim((string) $name) ?: $slug;
        $css   = '';

        if (preg_match('/^var:preset\|font-family\|([^\s|]+)/', $raw_family, $m)) {
            $preset_slug = strtolower(sanitize_key(str_replace('.', '-', $m[1])));
            $css           = '' !== $preset_slug ? 'var(--wp--preset--font-family--' . $preset_slug . ')' : '';
        } elseif (false !== strpos($raw_family, 'var(--wp--preset--font-family--')) {
            $css = trim($raw_family);
        } else {
            $css = trim($raw_family);
        }

        if ('' === $css) {
            $css = 'var(--wp--preset--font-family--' . $slug . ')';
        }

        $into[$slug] = [
            'label' => $label,
            'css'   => $css,
        ];
    }

    /**
     * @param array<string, mixed> $settings Option array
     */
    public static function resolve_role(array $settings, string $role): array
    {
        $prefix = 'heading' === $role ? 'cards_font_heading_' : 'cards_font_body_';
        $raw    = trim((string) ($settings[$prefix . 'mode'] ?? ''));

        if ('' === $raw) {
            $legacy_key = 'heading' === $role ? 'cards_font_title' : 'cards_font_family';
            $legacy = self::sanitize_font_stack_only((string) ($settings[$legacy_key] ?? ''));
            if ('' !== $legacy) {
                return [
                    'family' => $legacy,
                    'faces'  => '',
                ];
            }

            return [
                'family' => '',
                'faces'  => '',
            ];
        }

        $mode = sanitize_key($raw);
        if (! in_array($mode, ['inherit', 'preset', 'custom', 'upload'], true)) {
            $mode = 'inherit';
        }

        if ('inherit' === $mode) {
            return [
                'family' => '',
                'faces'  => '',
            ];
        }

        if ('preset' === $mode) {
            $slug = strtolower(sanitize_key(str_replace('.', '-', (string) ($settings[$prefix . 'preset'] ?? ''))));
            $list = self::get_wordpress_font_preset_list();
            if ('' !== $slug && isset($list[$slug]['css'])) {
                return [
                    'family' => $list[$slug]['css'],
                    'faces'  => '',
                ];
            }

            return [
                'family' => '',
                'faces'  => '',
            ];
        }

        if ('custom' === $mode) {
            $stack = (string) ($settings[$prefix . 'custom'] ?? '');

            return [
                'family' => self::sanitize_font_stack_only($stack),
                'faces'  => '',
            ];
        }

        $id = absint($settings[$prefix . 'upload_id'] ?? 0);

        if ($id < 1) {
            return [
                'family' => '',
                'faces'  => '',
            ];
        }

        $face_name = self::sanitize_face_name((string) ($settings[$prefix . 'face'] ?? ''));
        $faces     = self::build_font_face_css($id, $face_name, $role);

        if ('' === $faces['family'] || '' === $faces['css']) {
            return [
                'family' => '',
                'faces'  => '',
            ];
        }

        return [
            'family' => $faces['family'],
            'faces'  => $faces['css'],
        ];
    }

    /**
     * @return array{family: string, css: string}
     */
    private static function build_font_face_css(int $attachment_id, string $face_name, string $role): array
    {
        $path = get_attached_file($attachment_id);
        if (! $path || ! is_readable($path)) {
            return ['family' => '', 'css' => ''];
        }

        $url = wp_get_attachment_url($attachment_id);
        if (! $url) {
            return ['family' => '', 'css' => ''];
        }

        $ft  = wp_check_filetype($path);
        $ext = strtolower((string) ($ft['ext'] ?? ''));
        $allowed = [
            'woff2' => 'woff2',
            'woff'  => 'woff',
            'ttf'   => 'truetype',
            'otf'   => 'opentype',
        ];
        if ('' === $ext || ! isset($allowed[$ext])) {
            return ['family' => '', 'css' => ''];
        }

        $format = $allowed[$ext];
        $safe   = '' !== $face_name ? $face_name : ('rfp_' . $role . '_' . $attachment_id);
        $family = '"' . preg_replace('/[^a-zA-Z0-9 \-_]/', '', $safe) . '"';
        if ('""' === $family) {
            $family = '"RelayForge ' . $attachment_id . '"';
        }

        $esc_url = esc_url_raw($url);
        $css = '@font-face{font-family:' . $family . ';font-style:normal;font-weight:100 900;font-display:swap;src:url("' . $esc_url . '") format("' . $format . '");}';

        return [
            'family' => $family . ',sans-serif',
            'css'    => $css,
        ];
    }

    public static function sanitize_font_stack_only(string $value): string
    {
        $value = wp_strip_all_tags($value);
        $value = str_replace(["\r", "\n"], '', $value);

        return trim(preg_replace('/[;{}<>]/', '', $value));
    }

    public static function sanitize_face_name(string $value): string
    {
        $value = wp_strip_all_tags($value);

        return trim(preg_replace('/[^a-zA-Z0-9 \-_]/', '', $value));
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function build_frontend_style_block(array $settings): string
    {
        $h = self::resolve_role($settings, 'heading');
        $b = self::resolve_role($settings, 'body');

        $faces = trim($h['faces'] . "\n" . $b['faces']);
        $vars = [];

        if ('' !== $h['family']) {
            $vars[] = '--rfp-font-title:' . $h['family'];
        }
        if ('' !== $b['family']) {
            $vars[] = '--rfp-font-family:' . $b['family'];
        }

        if ('' === $faces && empty($vars)) {
            return '';
        }

        $out = '';
        if ('' !== $faces) {
            $out .= $faces . "\n";
        }
        if ([] !== $vars) {
            // Scope card/body typography to branded areas — not booking forms (those follow theme/site fonts).
            $out .= '.rfp.rfp-typography-cards,.rfp-detail .rfp-typography-cards{' . implode(';', $vars) . ';}';
        }

        return $out;
    }
}
