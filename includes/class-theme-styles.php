<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Theme_Styles
{
    public static function accent_color(): string
    {
        return self::first_css_value([
            self::global_style(['elements', 'link', 'color', 'text']),
            self::global_style(['elements', 'button', 'color', 'background']),
            self::palette_color(['primary', 'accent', 'secondary', 'foreground', 'contrast']),
            self::theme_mod_color(['link_color', 'accent_color', 'primary_color', 'theme_color', 'color_primary', 'button_color']),
            'var(--wp--preset--color--primary, var(--wp--preset--color--accent, currentColor))',
        ]);
    }

    public static function text_color(): string
    {
        return self::first_css_value([
            self::global_style(['color', 'text']),
            self::palette_color(['contrast', 'foreground', 'text']),
            self::theme_mod_color(['text_color', 'body_text_color', 'content_color']),
            'var(--wp--preset--color--contrast, currentColor)',
        ]);
    }

    public static function surface_color(): string
    {
        return self::first_css_value([
            self::global_style(['color', 'background']),
            self::palette_color(['base', 'background', 'surface']),
            'var(--wp--preset--color--base, Canvas)',
        ]);
    }

    public static function body_font(): string
    {
        return self::first_css_value([
            self::global_style(['typography', 'fontFamily']),
            self::font_preset(['body', 'primary', 'base', 'system-ui', 'sans']),
        ]);
    }

    public static function heading_font(): string
    {
        return self::first_css_value([
            self::global_style(['elements', 'heading', 'typography', 'fontFamily']),
            self::global_style(['elements', 'h1', 'typography', 'fontFamily']),
            self::global_style(['elements', 'h2', 'typography', 'fontFamily']),
            self::font_preset(['heading', 'headings', 'primary', 'body']),
        ]);
    }

    private static function global_style(array $path): string
    {
        if (! function_exists('wp_get_global_styles')) {
            return '';
        }

        try {
            $value = wp_get_global_styles($path, ['transforms' => ['resolve-variables']]);
        } catch (Throwable $e) {
            return '';
        }

        return is_scalar($value) ? self::clean_css_value((string) $value) : '';
    }

    private static function palette_color(array $slugs): string
    {
        $palette = self::theme_palette();
        foreach ($slugs as $slug) {
            if (! empty($palette[$slug])) {
                return self::clean_css_value($palette[$slug]);
            }
        }

        return '';
    }

    private static function theme_palette(): array
    {
        static $palette = null;
        if (null !== $palette) {
            return $palette;
        }

        $palette = [];
        if (function_exists('wp_get_global_settings')) {
            try {
                $settings = wp_get_global_settings(['color', 'palette']);
                self::collect_palette_colors($settings, $palette);
            } catch (Throwable $e) {
                $palette = [];
            }
        }

        return $palette;
    }

    private static function collect_palette_colors($node, array &$palette): void
    {
        if (! is_array($node)) {
            return;
        }

        if (isset($node['slug'], $node['color'])) {
            $slug = sanitize_key((string) $node['slug']);
            $color = self::clean_css_value((string) $node['color']);
            if ('' !== $slug && '' !== $color) {
                $palette[$slug] = $color;
            }
            return;
        }

        foreach ($node as $child) {
            self::collect_palette_colors($child, $palette);
        }
    }

    private static function theme_mod_color(array $keys): string
    {
        foreach ($keys as $key) {
            $value = self::normalize_color((string) get_theme_mod($key, ''));
            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }

    private static function font_preset(array $slugs): string
    {
        if (! class_exists('RelayForge_Typography')) {
            return '';
        }

        $fonts = RelayForge_Typography::get_wordpress_font_preset_list();
        foreach ($slugs as $slug) {
            $slug = sanitize_key($slug);
            if (! empty($fonts[$slug]['css'])) {
                return self::clean_css_value((string) $fonts[$slug]['css']);
            }
        }

        return '';
    }

    private static function first_css_value(array $values): string
    {
        foreach ($values as $value) {
            $value = self::clean_css_value((string) $value);
            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }

    private static function normalize_color(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            return '';
        }
        if (preg_match('/^[0-9a-fA-F]{3,8}$/', $value)) {
            $value = '#' . $value;
        }

        return self::clean_css_value($value);
    }

    private static function clean_css_value(string $value): string
    {
        $value = trim(wp_strip_all_tags($value));
        $value = str_replace(["\r", "\n"], '', $value);
        if (preg_match('/^var:preset\|([a-z-]+)\|([^\s|]+)$/', $value, $matches)) {
            $type = sanitize_key((string) $matches[1]);
            $slug = sanitize_key((string) $matches[2]);
            $value = ('' !== $type && '' !== $slug) ? 'var(--wp--preset--' . $type . '--' . $slug . ')' : '';
        }
        if ('' === $value || preg_match('/[;{}<>]/', $value)) {
            return '';
        }

        return $value;
    }
}
