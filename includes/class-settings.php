<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Settings
{
    public const OPTION_KEY = 'relayforge_wp_settings';

    private array $field_definitions = [];

    public static function card_template_options(): array
    {
        return [
            'default' => 'Default',
            'minimal' => 'Minimal',
            'split' => 'Split',
            'overlay' => 'Overlay',
            'magazine' => 'Magazine',
            'luxury' => 'Luxury',
            'adventure' => 'Adventure',
            'compact' => 'Compact',
            'feature' => 'Feature',
            'custom' => 'Custom HTML',
        ];
    }

    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if (false === strpos($hook, self::OPTION_KEY)) {
            return;
        }

        wp_enqueue_style(
            'relayforge-wordpress',
            RELAYFORGE_WP_URL . 'assets/css/relayforge.css',
            [],
            RELAYFORGE_WP_VERSION
        );

        wp_enqueue_style(
            'relayforge-wordpress-admin',
            RELAYFORGE_WP_URL . 'assets/css/relayforge-admin.css',
            ['relayforge-wordpress'],
            RELAYFORGE_WP_VERSION
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'relayforge-wordpress-admin',
            RELAYFORGE_WP_URL . 'assets/js/relayforge-admin.js',
            ['jquery'],
            RELAYFORGE_WP_VERSION,
            true
        );

        $preset_css = [];
        if (class_exists('RelayForge_Typography')) {
            foreach (RelayForge_Typography::get_wordpress_font_preset_list() as $slug => $meta) {
                $preset_css[(string) $slug] = (string) ($meta['css'] ?? '');
            }
        }
        wp_localize_script(
            'relayforge-wordpress-admin',
            'relayforgeFontPresetCss',
            $preset_css
        );
        wp_localize_script(
            'relayforge-wordpress-admin',
            'relayforgeAdminStrings',
            [
                'copied'       => __('Copied', 'relayforge-wordpress'),
                'copyFailed'   => __('Could not copy. Select the text manually.', 'relayforge-wordpress'),
            ]
        );
    }

    /**
     * Load a PHP partial from includes/admin/views/ (instance $this available).
     *
     * @param array<string, mixed> $context Variables to extract into the partial (parent scope is not inherited by include).
     */
    private function admin_view(string $file, array $context = []): void
    {
        $path = RELAYFORGE_WP_PATH . 'includes/admin/views/' . ltrim($file, '/');
        if (! is_readable($path)) {
            return;
        }

        if ([] !== $context) {
            extract($context, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        }

        include $path;
    }

    public function register_settings(): void
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => $this->defaults(),
        ]);

        add_settings_section(
            'relayforge_wp_main',
            __('Connection', 'relayforge-wordpress'),
            function () {
                echo '<p>' . esc_html__('These fields link WordPress to your RelayForge account. You only need them once per site.', 'relayforge-wordpress') . '</p>';
            },
            self::OPTION_KEY
        );

        add_settings_section(
            'relayforge_wp_cards',
            __('Tour and destination cards (optional HTML)', 'relayforge-wordpress'),
            [$this, 'render_cards_section'],
            self::OPTION_KEY
        );

        $this->field_definitions = $this->fields();

        foreach ($this->field_definitions as $key => $field) {
            add_settings_field(
                $key,
                $field['label'],
                [$this, 'render_field'],
                self::OPTION_KEY,
                $field['section'],
                ['key' => $key]
            );
        }
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('RelayForge', 'relayforge-wordpress'),
            __('RelayForge', 'relayforge-wordpress'),
            'manage_options',
            self::OPTION_KEY,
            '__return_null',
            RELAYFORGE_WP_URL . 'assets/brand/Relayforge-favicon.svg',
            58
        );

        add_submenu_page(
            self::OPTION_KEY,
            __('RelayForge — Get started', 'relayforge-wordpress'),
            __('Get started', 'relayforge-wordpress'),
            'manage_options',
            self::OPTION_KEY,
            [$this, 'render_overview_page']
        );

        add_submenu_page(
            self::OPTION_KEY,
            __('Tours & Destinations — RelayForge', 'relayforge-wordpress'),
            __('Tours & Destinations', 'relayforge-wordpress'),
            'manage_options',
            self::OPTION_KEY . '_content',
            [$this, 'render_content_page']
        );

        add_submenu_page(
            self::OPTION_KEY,
            __('RelayForge Settings', 'relayforge-wordpress'),
            __('Settings', 'relayforge-wordpress'),
            'manage_options',
            self::OPTION_KEY . '_settings',
            [$this, 'render_page']
        );

        add_submenu_page(
            self::OPTION_KEY,
            __('Shortcodes — RelayForge', 'relayforge-wordpress'),
            __('Shortcodes', 'relayforge-wordpress'),
            'manage_options',
            self::OPTION_KEY . '_shortcodes',
            [$this, 'render_shortcodes_page']
        );
    }

    public function render_field(array $args): void
    {
        $key = $args['key'];
        $field = $this->field_definitions[$key] ?? $this->fields()[$key] ?? null;
        if (! is_array($field)) {
            return;
        }

        $settings = $this->all();
        $value = $settings[$key] ?? '';
        $name = self::OPTION_KEY . '[' . $key . ']';
        $type = $field['type'] ?? 'text';

        if ('select' === $type) {
            echo '<select class="regular-text" name="' . esc_attr($name) . '">';
            foreach (($field['options'] ?? []) as $option_key => $option_label) {
                printf(
                    '<option value="%1$s" %2$s>%3$s</option>',
                    esc_attr((string) $option_key),
                    selected((string) $value, (string) $option_key, false),
                    esc_html((string) $option_label)
                );
            }
            echo '</select>';
        } elseif ('textarea' === $type) {
            $rows = isset($field['rows']) ? max(3, absint($field['rows'])) : 8;
            printf(
                '<textarea class="large-text code" rows="%1$s" name="%2$s" placeholder="%3$s">%4$s</textarea>',
                esc_attr((string) $rows),
                esc_attr($name),
                esc_attr((string) ($field['placeholder'] ?? '')),
                esc_textarea((string) $value)
            );
        } else {
            printf(
                '<input class="regular-text" type="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" />',
                esc_attr((string) $type),
                esc_attr($name),
                esc_attr((string) $value),
                esc_attr((string) ($field['placeholder'] ?? ''))
            );
        }

        if (! empty($field['description'])) {
            echo '<p class="description">' . esc_html((string) $field['description']) . '</p>';
        }
    }

    public function render_cards_section(): void
    {
        $tour_tokens = '{{title}}, {{location}}, {{image_url}}, {{price}}, {{link}}, {{cta_text}}';
        $tour_tokens .= ', {{duration}}, {{rating}}, {{reviews_count}}, {{currency}}, {{from_price}}, {{badge}}, {{card_class}}, {{button_class}}, {{tracking_attrs}}';
        $destination_tokens = '{{name}}, {{description}}, {{image_url}}, {{link}}, {{cta_text}}, {{country}}, {{tour_count}}, {{best_time}}, {{card_class}}, {{button_class}}, {{tracking_attrs}}';

        echo '<div class="relayforge-help-card">';
        echo '<h3>' . esc_html__('Need custom HTML?', 'relayforge-wordpress') . '</h3>';
        echo '<p>' . esc_html__('Most users should start with the visual templates and Design section. Use custom HTML/CSS only when you need a fully custom card structure.', 'relayforge-wordpress') . '</p>';
        echo '<details><summary>' . esc_html__('Show placeholders and shortcode examples', 'relayforge-wordpress') . '</summary>';
        echo '<p><strong>' . esc_html__('Tours:', 'relayforge-wordpress') . '</strong> <code>' . esc_html($tour_tokens) . '</code></p>';
        echo '<p><strong>' . esc_html__('Destinations:', 'relayforge-wordpress') . '</strong> <code>' . esc_html($destination_tokens) . '</code></p>';
        echo '<p><code>[relayforge_tours template="luxury" custom_css_class="my-grid"]</code></p>';
        echo '<p><code>{{#if price}}&lt;p&gt;{{price}}&lt;/p&gt;{{/if}}</code></p>';
        echo '</details>';
        echo '</div>';
    }

    private function render_template_gallery(): void
    {
        $settings = $this->all();
        $tours_tpl = (string) ($settings['tours_card_template'] ?? 'default');
        $dest_tpl  = (string) ($settings['destinations_card_template'] ?? 'default');

        $templates = [
            'default'   => ['Default',   'Image on top, title, price and button below'],
            'minimal'   => ['Minimal',   'Text only — clean and fast'],
            'split'     => ['Split',     'Image on the left, content on the right'],
            'overlay'   => ['Overlay',   'Text appears over a full-bleed image'],
            'magazine'  => ['Magazine',  'Bold editorial look with a category badge'],
            'luxury'    => ['Luxury',    'No image — elegant text-focused layout'],
            'adventure' => ['Adventure', 'Image with duration and category tags'],
            'compact'   => ['Compact',   'Minimal list-style for dense grids'],
            'feature'   => ['Feature',   'Large hero image with a full-width card'],
        ];

        $name_map = array_map(fn($t) => $t[0], $templates);

        printf(
            '<input type="hidden" id="relayforge_tours_card_template" name="%s" value="%s" />',
            esc_attr(self::OPTION_KEY . '[tours_card_template]'),
            esc_attr($tours_tpl)
        );
        printf(
            '<input type="hidden" id="relayforge_destinations_card_template" name="%s" value="%s" />',
            esc_attr(self::OPTION_KEY . '[destinations_card_template]'),
            esc_attr($dest_tpl)
        );

        echo '<p class="relayforge-gallery-selection">';
        echo '<span>' . esc_html__('Tour cards:', 'relayforge-wordpress') . ' <strong id="relayforge-tours-selection-label">' . esc_html($name_map[$tours_tpl] ?? ucfirst($tours_tpl)) . '</strong></span>';
        echo ' &nbsp;·&nbsp; ';
        echo '<span>' . esc_html__('Destination cards:', 'relayforge-wordpress') . ' <strong id="relayforge-destinations-selection-label">' . esc_html($name_map[$dest_tpl] ?? ucfirst($dest_tpl)) . '</strong></span>';
        echo '</p>';

        echo '<div class="relayforge-template-gallery">';
        foreach ($templates as $key => $template) {
            echo '<article class="relayforge-template-option" data-template-option="' . esc_attr($key) . '">';
            echo '<div class="relayforge-template-option__preview-shell">';
            echo '<div class="relayforge-template-option__preview rfp" data-template-preview="' . esc_attr($key) . '"></div>';
            echo '<p class="relayforge-template-option__selected-note" hidden></p>';
            echo '</div>';
            echo '<h3>' . esc_html($template[0]) . '</h3>';
            echo '<p>' . esc_html($template[1]) . '</p>';
            echo '<div class="relayforge-template-option__actions">';
            echo '<button type="button" data-template-target="tours" data-template-value="' . esc_attr($key) . '">' . esc_html__('Use for tour cards', 'relayforge-wordpress') . '</button>';
            echo '<button type="button" data-template-target="destinations" data-template-value="' . esc_attr($key) . '">' . esc_html__('Use for destinations', 'relayforge-wordpress') . '</button>';
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
    }

    private function render_live_preview(): void
    {
        echo '<div class="relayforge-preview-toolbar">';
        echo '<button type="button" class="is-active" data-preview-device="desktop">' . esc_html__('Desktop', 'relayforge-wordpress') . '</button>';
        echo '<button type="button" data-preview-device="mobile">' . esc_html__('Mobile', 'relayforge-wordpress') . '</button>';
        echo '<a class="relayforge-preview-link" href="' . esc_url(home_url('/packages/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open packages page', 'relayforge-wordpress') . '</a>';
        echo '<a class="relayforge-preview-link" href="' . esc_url(home_url('/relayforge-demo/packages/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open demo packages', 'relayforge-wordpress') . '</a>';
        echo '<a class="relayforge-preview-link" href="' . esc_url(home_url('/relayforge-demo/tour/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open demo tour page', 'relayforge-wordpress') . '</a>';
        echo '<a class="relayforge-preview-link" href="' . esc_url(home_url('/relayforge-demo/destination/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open demo destination page', 'relayforge-wordpress') . '</a>';
        echo '</div>';
        echo '<div class="relayforge-live-preview" data-preview-frame="desktop">';
        echo '<style id="relayforge-preview-custom-css"></style>';
        echo '<section><h3>' . esc_html__('Tours', 'relayforge-wordpress') . '</h3><div class="rfp rfp-typography-cards rfp-tours-grid columns-2" id="relayforge-tour-preview"></div></section>';
        echo '<section><h3>' . esc_html__('Destinations', 'relayforge-wordpress') . '</h3><div class="rfp rfp-typography-cards rfp-tours-grid columns-2" id="relayforge-destination-preview"></div></section>';
        echo '<section><h3>' . esc_html__('Tour detail page', 'relayforge-wordpress') . '</h3><div id="relayforge-tour-detail-preview"></div></section>';
        echo '<section><h3>' . esc_html__('Destination detail page', 'relayforge-wordpress') . '</h3><div id="relayforge-destination-detail-preview"></div></section>';
        echo '</div>';
        echo '<p class="relayforge-admin__hint">' . esc_html__('Preview updates instantly as you change template, radius, spacing, image settings, or custom CSS/HTML.', 'relayforge-wordpress') . '</p>';
    }

    public function render_overview_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $this->admin_view('page-overview.php');
    }

    public function render_shortcodes_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $this->admin_view('page-shortcodes.php');
    }

    public function render_content_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $this->admin_view('page-content.php');
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = $this->all();
        $fields = $this->fields();
        $tabs = $this->tabs();
        $errors = get_settings_errors(self::OPTION_KEY);
        $saved = isset($_GET['settings-updated']) && 'true' === sanitize_key((string) $_GET['settings-updated']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_new = empty(trim((string) ($settings['base_url'] ?? '')));
        $library_json = wp_json_encode((array) ($settings['cards_template_library'] ?? []), JSON_PRETTY_PRINT);
        if (! is_string($library_json)) {
            $library_json = '{}';
        }

        $this->admin_view(
            'page-settings.php',
            compact('settings', 'fields', 'tabs', 'errors', 'saved', 'is_new', 'library_json')
        );
    }

    private function render_custom_field(string $key, array $field, array $settings): void
    {
        $label = (string) ($field['label'] ?? $key);
        $type = (string) ($field['type'] ?? 'text');

        $value = $settings[$key] ?? '';
        $name = self::OPTION_KEY . '[' . $key . ']';

        $switch = $this->switch_field_config($key, $field, (string) $value);
        if (null !== $switch) {
            echo '<div class="relayforge-admin__switch-row">';
            echo '<div><label for="' . esc_attr('relayforge_' . $key) . '">' . esc_html($label) . '</label>';
            if (! empty($field['description'])) {
                echo '<small>' . esc_html((string) $field['description']) . '</small>';
            }
            echo '</div>';
            printf(
                '<input type="hidden" name="%1$s" value="%2$s" /><label class="relayforge-switch" aria-label="%3$s"><input id="%4$s" type="checkbox" name="%1$s" value="%5$s" %6$s /><span></span></label>',
                esc_attr($name),
                esc_attr($switch['off']),
                esc_attr($label),
                esc_attr('relayforge_' . $key),
                esc_attr($switch['on']),
                checked((string) $value, $switch['on'], false)
            );
            echo '</div>';

            return;
        }

        if ('relayforge_typography' === $type) {
            $slot = isset($field['typography_role']) ? (string) $field['typography_role'] : 'body';
            $this->render_typography_combo($slot, $settings, $field);

            return;
        }

        echo '<label for="' . esc_attr('relayforge_' . $key) . '">' . esc_html($label) . '</label>';

        if ('color' === $type) {
            $swatch = $value ?: '#aaaaaa';
            printf(
                '<div class="rfp-color-field"><input type="color" class="rfp-color-swatch" value="%1$s" tabindex="-1" aria-hidden="true" /><input id="%2$s" type="text" name="%3$s" value="%4$s" placeholder="%5$s" /><button type="button" class="rfp-color-clear">Clear</button></div>',
                esc_attr($swatch),
                esc_attr('relayforge_' . $key),
                esc_attr($name),
                esc_attr((string) $value),
                esc_attr('#000000')
            );
        } elseif ('select' === $type) {
            echo '<select id="' . esc_attr('relayforge_' . $key) . '" name="' . esc_attr($name) . '">';
            foreach ((array) ($field['options'] ?? []) as $option_key => $option_label) {
                printf(
                    '<option value="%1$s" %2$s>%3$s</option>',
                    esc_attr((string) $option_key),
                    selected((string) $value, (string) $option_key, false),
                    esc_html((string) $option_label)
                );
            }
            echo '</select>';
        } elseif ('textarea' === $type) {
            $rows = isset($field['rows']) ? max(3, absint($field['rows'])) : 8;
            printf(
                '<textarea id="%1$s" rows="%2$s" name="%3$s" placeholder="%4$s">%5$s</textarea>',
                esc_attr('relayforge_' . $key),
                esc_attr((string) $rows),
                esc_attr($name),
                esc_attr((string) ($field['placeholder'] ?? '')),
                esc_textarea((string) $value)
            );
        } else {
            printf(
                '<input id="%1$s" type="%2$s" name="%3$s" value="%4$s" placeholder="%5$s" />',
                esc_attr('relayforge_' . $key),
                esc_attr($type),
                esc_attr($name),
                esc_attr((string) $value),
                esc_attr((string) ($field['placeholder'] ?? ''))
            );
        }

        if (! empty($field['description'])) {
            echo '<small>' . esc_html((string) $field['description']) . '</small>';
        }

        if (! empty($field['technical_note'])) {
            printf(
                '<details class="relayforge-admin__technical"><summary>%s</summary><p>%s</p></details>',
                esc_html__('Technical details', 'relayforge-wordpress'),
                esc_html((string) $field['technical_note'])
            );
        }
    }

    /**
     * @return array{on: string, off: string}|null
     */
    private function switch_field_config(string $key, array $field, string $value): ?array
    {
        if ('select' !== (string) ($field['type'] ?? '')) {
            return null;
        }

        $options = array_map('strval', array_keys((array) ($field['options'] ?? [])));
        sort($options);

        if (['no', 'yes'] === $options) {
            return ['on' => 'yes', 'off' => 'no'];
        }

        if ('cards_accessibility_mode' === $key && in_array($value, ['assist', 'off'], true)) {
            return ['on' => 'assist', 'off' => 'off'];
        }

        return null;
    }

    private function render_typography_combo(string $slot, array $settings, array $field): void
    {
        if (! class_exists('RelayForge_Typography')) {
            echo '<p>' . esc_html__('Typography helpers not loaded. Please reload the page.', 'relayforge-wordpress') . '</p>';

            return;
        }

        $pfx = 'heading' === $slot ? 'cards_font_heading_' : 'cards_font_body_';
        $opt = self::OPTION_KEY;

        $mode = (string) ($settings[$pfx . 'mode'] ?? '');
        if ('' === trim($mode)) {
            $mode = 'inherit';
        }

        if ('inherit' === $mode && '' === trim((string) ($settings[$pfx . 'custom'] ?? ''))) {
            $legacy_fallback = 'heading' === $slot
                ? (string) ($settings['cards_font_title'] ?? '')
                : (string) ($settings['cards_font_family'] ?? '');
            if ('' !== $this->sanitize_font_stack($legacy_fallback)) {
                $mode = 'custom';
                $settings[$pfx . 'custom'] = $legacy_fallback;
            }
        }

        $presets    = RelayForge_Typography::get_wordpress_font_preset_list();
        $preset_val = strtolower(sanitize_key(str_replace('.', '-', (string) ($settings[$pfx . 'preset'] ?? ''))));
        $slug_id    = 'rfp-' . $slot . '-preset';

        echo '<fieldset class="rfp-font-fieldset relayforge-admin__field relayforge-admin__field--full" data-rfp-font-slot="' . esc_attr($slot) . '">';
        echo '<legend><strong>' . esc_html((string) ($field['label'] ?? __('Font', 'relayforge-wordpress'))) . '</strong></legend>';

        echo '<p class="relayforge-admin__hint">' . esc_html__('Pick a WordPress/theme preset (when available), type a CSS font stack, or upload a font file. Theme default leaves RelayForge typography to your active theme.', 'relayforge-wordpress') . '</p>';

        echo '<div class="rfp-font-mode-row"><label for="rfp-' . esc_attr($slot) . '-mode">' . esc_html__('Source', 'relayforge-wordpress') . '</label> ';
        echo '<select id="rfp-' . esc_attr($slot) . '-mode" name="' . esc_attr($opt . '[' . $pfx . 'mode]') . '" class="rfp-font-mode" data-rfp-font-scope="' . esc_attr($slot) . '">';
        $modes = [
            'inherit' => __('Theme default (inherit)', 'relayforge-wordpress'),
            'preset' => __('Theme / WP font presets', 'relayforge-wordpress'),
            'custom' => __('Custom CSS font stack', 'relayforge-wordpress'),
            'upload' => __('Upload font (.woff2, .woff, .ttf, .otf)', 'relayforge-wordpress'),
        ];
        foreach ($modes as $k => $lbl) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr((string) $k),
                selected($mode, (string) $k, false),
                esc_html((string) $lbl)
            );
        }
        echo '</select></div>';

        $show_preset = 'preset' === $mode ? '' : ' hidden';
        $show_custom = 'custom' === $mode ? '' : ' hidden';
        $show_upload = 'upload' === $mode ? '' : ' hidden';

        echo '<div class="rfp-font-sub rfp-font-sub--preset' . $show_preset . '">';
        echo '<label for="' . esc_attr($slug_id) . '">' . esc_html__('Preset', 'relayforge-wordpress') . '</label> ';
        echo '<select id="' . esc_attr($slug_id) . '" name="' . esc_attr($opt . '[' . $pfx . 'preset]') . '">';
        echo '<option value="">' . esc_html__('— Choose a font —', 'relayforge-wordpress') . '</option>';
        foreach ($presets as $slug => $meta) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr((string) $slug),
                selected($preset_val, (string) $slug, false),
                esc_html((string) ($meta['label'] ?? $slug))
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('These are read from your block theme where possible. Classic themes still get safe system fallbacks.', 'relayforge-wordpress') . '</p>';
        echo '</div>';

        echo '<div class="rfp-font-sub rfp-font-sub--custom' . $show_custom . '">';
        echo '<label for="rfp-' . esc_attr($slot) . '-custom">' . esc_html__('CSS font-family stack', 'relayforge-wordpress') . '</label>';
        printf(
            '<textarea id="rfp-%1$s-custom" class="large-text code" rows="2" name="%2$s" placeholder="%3$s">%4$s</textarea>',
            esc_attr($slot),
            esc_attr($opt . '[' . $pfx . 'custom]'),
            esc_attr('"Inter", system-ui, sans-serif'),
            esc_textarea((string) ($settings[$pfx . 'custom'] ?? ''))
        );
        echo '<p class="description">' . esc_html__('Example: "Cormorant Garamond", Georgia, serif — include quotes around names that contain spaces.', 'relayforge-wordpress') . '</p>';
        echo '</div>';

        echo '<div class="rfp-font-sub rfp-font-sub--upload' . $show_upload . '">';
        $uid   = absint($settings[$pfx . 'upload_id'] ?? 0);
        $fname = isset($settings[$pfx . 'face']) ? (string) $settings[$pfx . 'face'] : '';
        printf(
            '<p><button type="button" class="button rfp-upload-font" data-slot="%1$s" data-target-upload="rfp-%1$s-upload-id">%2$s</button>',
            esc_attr($slot),
            esc_html__('Choose font file…', 'relayforge-wordpress')
        );
        if ($uid > 0) {
            $url = wp_get_attachment_url($uid);
            if ($url) {
                echo ' <span class="description"><a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View uploaded file', 'relayforge-wordpress') . '</a></span>';
            }
        }
        echo '</p>';
        printf(
            '<input type="hidden" id="rfp-%1$s-upload-id" name="%2$s" value="%3$d" />',
            esc_attr($slot),
            esc_attr($opt . '[' . $pfx . 'upload_id]'),
            $uid
        );
        echo '<label for="rfp-' . esc_attr($slot) . '-face">' . esc_html__('Name in CSS (letters, spaces, hyphen)', 'relayforge-wordpress') . '</label> ';
        printf(
            '<input id="rfp-%1$s-face" type="text" class="regular-text" name="%2$s" value="%3$s" maxlength="120" />',
            esc_attr($slot),
            esc_attr($opt . '[' . $pfx . 'face]'),
            esc_attr($fname)
        );
        echo '<p class="description">' . esc_html__('The font is embedded with @font-face on RelayForge templates. Prefer WOFF2. Use Clear in Media Library then save here to detach.', 'relayforge-wordpress') . '</p>';
        echo '</div>';

        if (! empty($field['description'])) {
            echo '<p class="description">' . esc_html((string) $field['description']) . '</p>';
        }

        echo '</fieldset>';
    }

    private function tabs(): array
    {
        return [
            [
                'id' => 'connection',
                'label' => __('Connection', 'relayforge-wordpress'),
                'icon' => 'admin-network',
                'nav_hint' => __('Keys & caching', 'relayforge-wordpress'),
                'title' => __('RelayForge connection', 'relayforge-wordpress'),
                'description' => __('Paste your secret key so WordPress can read your listings. The RelayForge service URL is managed by the plugin.', 'relayforge-wordpress'),
                'fields' => ['api_key', 'tenant_slug', 'cache_ttl'],
            ],
            [
                'id' => 'cards',
                'label' => __('Card layouts', 'relayforge-wordpress'),
                'icon' => 'screenoptions',
                'nav_hint' => __('Pick styles', 'relayforge-wordpress'),
                'title' => __('Tour and destination cards', 'relayforge-wordpress'),
                'description' => __('Pick a preset for tour and destination grids. Changes show in the live preview below — save when you are happy.', 'relayforge-wordpress'),
                'fields' => [],
            ],
            [
                'id' => 'styling',
                'label' => __('Look & feel', 'relayforge-wordpress'),
                'icon' => 'art',
                'nav_hint' => __('Fonts & colors', 'relayforge-wordpress'),
                'title' => __('Colors, images, and type', 'relayforge-wordpress'),
                'description' => __('Optional tweaks to fonts, accents, prices, and image shape. Leave fields blank to follow your WordPress theme.', 'relayforge-wordpress'),
                'fields' => [
                    'cards_color_accent',
                    'cards_color_surface',
                    'cards_color_text',
                    'cards_typography_heading',
                    'cards_typography_body',
                    'cards_image_fallback',
                    'cards_image_aspect_ratio',
                    'cards_image_object_position',
                    'cards_image_lazyload',
                    'cards_show_price',
                    'cards_show_currency',
                    'cards_typography_title_size',
                    'cards_spacing_padding',
                    'cards_radius',
                ],
            ],
            [
                'id' => 'code',
                'label' => __('Custom code', 'relayforge-wordpress'),
                'icon' => 'editor-code',
                'nav_hint' => __('Advanced', 'relayforge-wordpress'),
                'title' => __('Custom HTML, CSS, imports', 'relayforge-wordpress'),
                'description' => __('Optional: custom card markup and CSS, or importing a developer template pack.', 'relayforge-wordpress'),
                'fields' => [
                    'tours_card_custom_html',
                    'destinations_card_custom_html',
                    'custom_cards_css',
                    'custom_cards_css_tablet',
                    'custom_cards_css_mobile',
                    'cards_grid_class',
                    'cards_card_class',
                    'cards_button_class',
                    'template_library_json',
                ],
            ],
            [
                'id' => 'advanced',
                'label' => __('Site integration', 'relayforge-wordpress'),
                'icon' => 'admin-plugins',
                'nav_hint' => __('Tracking & extras', 'relayforge-wordpress'),
                'title' => __('Tracking, accessibility & scripts', 'relayforge-wordpress'),
                'description' => __('Analytics helpers, clearer labels for visitors using assistive tech, optional theme sync, or small scripts.', 'relayforge-wordpress'),
                'fields' => [
                    'cards_accessibility_mode',
                    'cards_analytics',
                    'cards_theme_sync',
                    'custom_cards_js',
                ],
            ],
            [
                'id' => 'diagnostics',
                'label' => __('Site health', 'relayforge-wordpress'),
                'icon' => 'heart',
                'nav_hint' => __('Status', 'relayforge-wordpress'),
                'title' => __('Status & connectivity', 'relayforge-wordpress'),
                'description' => __('Check that RelayForge responds, permalinks are correct, and which version you are running.', 'relayforge-wordpress'),
                'fields' => [],
            ],
        ];
    }

    private function render_diagnostics_panel(): void
    {
        $connection = [
            'message' => __('Diagnostics unavailable.', 'relayforge-wordpress'),
            'status' => 0,
        ];

        if (class_exists('RelayForge_Plugin')) {
            $plugin = RelayForge_Plugin::instance();
            if (isset($plugin->api_client)) {
                $connection = (array) $plugin->api_client->test_connection();
            }
        }

        $rewrite_rules     = (array) get_option('rewrite_rules', []);
        $rules_ok          = ! empty($rewrite_rules['^relayforge-demo/tour/?$']);
        $permalink_ok      = '' !== (string) get_option('permalink_structure', '');
        $http_status       = (int) ($connection['status'] ?? 0);
        $base_url          = (string) $this->get('base_url', '');
        $tenant_slug       = (string) $this->get('tenant_slug', '');
        $cache_ttl         = (int) $this->get('cache_ttl', 900);

        $connected = $http_status >= 200 && $http_status < 300;
        $conn_label = $connected
            ? '<span style="color:#065f46;font-weight:700;">&#10003; Connected</span>'
            : ($base_url
                ? '<span style="color:#b91c1c;font-weight:700;">&#10007; Not connected — check your API URL and key</span>'
                : '<span style="color:#92400e;">No API URL set yet</span>');

        $ttl_labels = [
            0    => 'Always fresh (no cache)',
            300  => 'Every 5 minutes',
            900  => 'Every 15 minutes',
            1800 => 'Every 30 minutes',
            3600 => 'Every hour',
        ];
        $ttl_label = $ttl_labels[$cache_ttl] ?? ($cache_ttl . ' seconds');

        echo '<h3 style="margin:0 0 14px;">' . esc_html__('System Status', 'relayforge-wordpress') . '</h3>';
        echo '<table class="widefat striped" style="max-width:100%;">';
        echo '<tbody>';
        echo '<tr><th>' . esc_html__('RelayForge connection', 'relayforge-wordpress') . '</th><td>' . $conn_label . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<tr><th>' . esc_html__('API URL', 'relayforge-wordpress') . '</th><td>' . ($base_url ? esc_html($base_url) : '<em style="color:#92400e;">' . esc_html__('Not set — open the Connection tab', 'relayforge-wordpress') . '</em>') . '</td></tr>';
        echo '<tr><th>' . esc_html__('Account name', 'relayforge-wordpress') . '</th><td>' . ($tenant_slug ? esc_html($tenant_slug) : '<em>' . esc_html__('Not set', 'relayforge-wordpress') . '</em>') . '</td></tr>';
        echo '<tr><th>' . esc_html__('Data refresh', 'relayforge-wordpress') . '</th><td>' . esc_html($ttl_label) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Tour page URLs', 'relayforge-wordpress') . '</th><td>';
        if (! $permalink_ok) {
            echo '<span style="color:#b91c1c;font-weight:700;">&#10007; Not working — go to <a href="' . esc_url(admin_url('options-permalink.php')) . '">Settings › Permalinks</a> and click Save</span>';
        } elseif (! $rules_ok) {
            echo '<span style="color:#92400e;">&#9888; Reload this page to register tour URLs</span>';
        } else {
            echo '<span style="color:#065f46;font-weight:700;">&#10003; Working</span>';
        }
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Plugin version', 'relayforge-wordpress') . '</th><td>' . esc_html(RELAYFORGE_WP_VERSION) . '</td></tr>';
        echo '<tr><th>' . esc_html__('WordPress version', 'relayforge-wordpress') . '</th><td>' . esc_html(get_bloginfo('version')) . '</td></tr>';
        echo '</tbody>';
        echo '</table>';

        echo '<p style="margin-top:14px;">';
        echo '<a class="button" href="' . esc_url(home_url('/relayforge-demo/tour/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open demo tour page', 'relayforge-wordpress') . '</a> ';
        echo '<a class="button" href="' . esc_url(home_url('/relayforge-demo/destination/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open demo destination', 'relayforge-wordpress') . '</a> ';
        echo '<a class="button" href="' . esc_url(home_url('/relayforge-demo/packages/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open demo packages', 'relayforge-wordpress') . '</a>';
        echo '</p>';
    }

    /**
     * @param mixed $input Value from settings form.
     */
    private function sanitize_typography_mode($input): string
    {
        $value = strtolower(trim((string) $input));

        if ('' === $value) {
            return 'inherit';
        }

        return in_array($value, ['inherit', 'preset', 'custom', 'upload'], true) ? $value : 'inherit';
    }

    /**
     * Sanitize a CSS font-family stack for storage (no markup; strip CSS-breakout chars).
     */
    private function sanitize_font_stack(string $value): string
    {
        $value = wp_strip_all_tags($value);
        $value = str_replace(["\r", "\n"], '', $value);

        return trim(preg_replace('/[;{}<>]/', '', $value));
    }

    public function sanitize(array $input): array
    {
        $tours_template = sanitize_key((string) ($input['tours_card_template'] ?? 'default'));
        $destinations_template = sanitize_key((string) ($input['destinations_card_template'] ?? 'default'));
        $allowed_templates = array_keys(self::card_template_options());
        $existing = (array) get_option(self::OPTION_KEY, []);

        $library = isset($existing['cards_template_library']) && is_array($existing['cards_template_library'])
            ? $existing['cards_template_library']
            : [];

        $library_input = trim((string) ($input['template_library_json'] ?? ''));
        if ('' !== $library_input) {
            $decoded = json_decode($library_input, true);
            if (is_array($decoded)) {
                $library = $this->normalize_template_library($decoded);
            } else {
                add_settings_error(self::OPTION_KEY, 'rf_template_library_json', __('Template library JSON is invalid. Existing library was kept.', 'relayforge-wordpress'));
            }
        }

        $legacy_heading = $this->sanitize_font_stack((string) ($existing['cards_font_title'] ?? ''));
        $legacy_body    = $this->sanitize_font_stack((string) ($existing['cards_font_family'] ?? ''));
        $stored_raw     = get_option(self::OPTION_KEY);
        $stored_flat    = \is_array($stored_raw) ? $stored_raw : [];

        if ($legacy_heading !== '' && ! array_key_exists('cards_font_heading_mode', $stored_flat)) {
            $sub_h = strtolower(trim((string) ($input['cards_font_heading_mode'] ?? 'inherit')));
            if ('' === ($input['cards_font_heading_mode'] ?? '') || 'inherit' === $sub_h) {
                $input['cards_font_heading_mode']   = 'custom';
                $input['cards_font_heading_custom'] = $legacy_heading;
            }
        }

        if ($legacy_body !== '' && ! array_key_exists('cards_font_body_mode', $stored_flat)) {
            $sub_b = strtolower(trim((string) ($input['cards_font_body_mode'] ?? 'inherit')));
            if ('' === ($input['cards_font_body_mode'] ?? '') || 'inherit' === $sub_b) {
                $input['cards_font_body_mode']   = 'custom';
                $input['cards_font_body_custom'] = $legacy_body;
            }
        }

        $next = [
            'base_url' => (function ($v, $old) {
                $v = esc_url_raw(rtrim((string) $v, '/'));
                if ('' === $v) {
                    return !empty($old) ? $old : 'https://relay.forgelabspro.com';
                }
                return $v;
            })($input['base_url'] ?? '', $existing['base_url'] ?? ''),
            'api_key' => sanitize_text_field((string) ($input['api_key'] ?? '')),
            'tenant_slug' => (function ($v, $old) {
                $v = sanitize_title((string) $v);
                return ('' === $v && !empty($old)) ? $old : $v;
            })($input['tenant_slug'] ?? '', $existing['tenant_slug'] ?? ''),
            'cache_ttl' => in_array(absint($input['cache_ttl'] ?? 900), [0, 300, 900, 1800, 3600], true) ? absint($input['cache_ttl'] ?? 900) : 900,
            'tours_card_template' => in_array($tours_template, $allowed_templates, true) ? $tours_template : 'default',
            'destinations_card_template' => in_array($destinations_template, $allowed_templates, true) ? $destinations_template : 'default',
            'tours_card_custom_html' => wp_kses_post((string) ($input['tours_card_custom_html'] ?? '')),
            'destinations_card_custom_html' => wp_kses_post((string) ($input['destinations_card_custom_html'] ?? '')),
            'custom_cards_css' => sanitize_textarea_field((string) ($input['custom_cards_css'] ?? '')),
            'custom_cards_css_tablet' => sanitize_textarea_field((string) ($input['custom_cards_css_tablet'] ?? '')),
            'custom_cards_css_mobile' => sanitize_textarea_field((string) ($input['custom_cards_css_mobile'] ?? '')),
            'custom_cards_js' => sanitize_textarea_field((string) ($input['custom_cards_js'] ?? '')),
            'cards_grid_class' => sanitize_text_field((string) ($input['cards_grid_class'] ?? '')),
            'cards_card_class' => sanitize_text_field((string) ($input['cards_card_class'] ?? '')),
            'cards_button_class' => sanitize_text_field((string) ($input['cards_button_class'] ?? '')),
            'cards_accessibility_mode' => sanitize_key((string) ($input['cards_accessibility_mode'] ?? 'assist')),
            'cards_image_fallback' => esc_url_raw((string) ($input['cards_image_fallback'] ?? '')),
            'cards_image_aspect_ratio' => (function ($v) {
                $allowed = ['16 / 10', '16 / 9', '4 / 3', '1 / 1', '4 / 5'];
                $v = sanitize_text_field((string) $v);
                return in_array($v, $allowed, true) ? $v : '16 / 10';
            })($input['cards_image_aspect_ratio'] ?? '16 / 10'),
            'cards_image_object_position' => (function ($v) {
                $allowed = ['center center', 'center top', 'center bottom', 'left center', 'right center'];
                $v = sanitize_text_field((string) $v);
                return in_array($v, $allowed, true) ? $v : 'center center';
            })($input['cards_image_object_position'] ?? 'center center'),
            'cards_image_lazyload' => ('no' === sanitize_key((string) ($input['cards_image_lazyload'] ?? 'yes'))) ? 'no' : 'yes',
            'cards_typography_title_size' => max(12, min(80, absint($input['cards_typography_title_size'] ?? 18))),
            'cards_spacing_padding' => max(8, min(80, absint($input['cards_spacing_padding'] ?? 20))),
            'cards_radius' => max(0, min(80, absint($input['cards_radius'] ?? 16))),
            'cards_theme_sync' => ('yes' === sanitize_key((string) ($input['cards_theme_sync'] ?? 'no'))) ? 'yes' : 'no',
            'cards_analytics' => ('no' === sanitize_key((string) ($input['cards_analytics'] ?? 'yes'))) ? 'no' : 'yes',
            'cards_show_price' => ('no' === sanitize_key((string) ($input['cards_show_price'] ?? 'yes'))) ? 'no' : 'yes',
            'cards_show_currency' => ('yes' === sanitize_key((string) ($input['cards_show_currency'] ?? 'no'))) ? 'yes' : 'no',
            'cards_color_accent' => (string) (sanitize_hex_color((string) ($input['cards_color_accent'] ?? '')) ?? ''),
            'cards_color_surface' => (string) (sanitize_hex_color((string) ($input['cards_color_surface'] ?? '')) ?? ''),
            'cards_color_text' => (string) (sanitize_hex_color((string) ($input['cards_color_text'] ?? '')) ?? ''),
            'cards_font_heading_mode' => $this->sanitize_typography_mode((string) ($input['cards_font_heading_mode'] ?? ($existing['cards_font_heading_mode'] ?? 'inherit'))),
            'cards_font_heading_preset' => strtolower(sanitize_key(str_replace('.', '-', (string) ($input['cards_font_heading_preset'] ?? '')))),
            'cards_font_heading_custom' => $this->sanitize_font_stack((string) ($input['cards_font_heading_custom'] ?? '')),
            'cards_font_heading_upload_id' => max(0, absint($input['cards_font_heading_upload_id'] ?? 0)),
            'cards_font_heading_face' => class_exists('RelayForge_Typography') ? RelayForge_Typography::sanitize_face_name((string) ($input['cards_font_heading_face'] ?? '')) : sanitize_text_field((string) ($input['cards_font_heading_face'] ?? '')),
            'cards_font_body_mode' => $this->sanitize_typography_mode((string) ($input['cards_font_body_mode'] ?? ($existing['cards_font_body_mode'] ?? 'inherit'))),
            'cards_font_body_preset' => strtolower(sanitize_key(str_replace('.', '-', (string) ($input['cards_font_body_preset'] ?? '')))),
            'cards_font_body_custom' => $this->sanitize_font_stack((string) ($input['cards_font_body_custom'] ?? '')),
            'cards_font_body_upload_id' => max(0, absint($input['cards_font_body_upload_id'] ?? 0)),
            'cards_font_body_face' => class_exists('RelayForge_Typography') ? RelayForge_Typography::sanitize_face_name((string) ($input['cards_font_body_face'] ?? '')) : sanitize_text_field((string) ($input['cards_font_body_face'] ?? '')),
            'cards_template_library' => $library,
            'template_library_json' => '',
        ];

        if ('custom' === $next['tours_card_template']) {
            $this->validate_custom_template((string) $next['tours_card_custom_html'], ['{{title}}', '{{link}}'], 'rf_tours_custom_html');
        }

        if ('custom' === $next['destinations_card_template']) {
            $this->validate_custom_template((string) $next['destinations_card_custom_html'], ['{{name}}', '{{link}}'], 'rf_destinations_custom_html');
        }

        $version_seed = wp_json_encode([
            $next['tours_card_template'],
            $next['destinations_card_template'],
            $next['tours_card_custom_html'],
            $next['destinations_card_custom_html'],
            $next['custom_cards_css'],
            $next['custom_cards_css_tablet'],
            $next['custom_cards_css_mobile'],
            $next['cards_template_library'],
        ]);

        $next['cards_template_version'] = is_string($version_seed) ? md5($version_seed) : md5((string) time());

        $merged_resolve = wp_parse_args($next, $existing);

        $next['cards_font_title'] = class_exists('RelayForge_Typography')
            ? (string) (RelayForge_Typography::resolve_role($merged_resolve, 'heading')['family'] ?? '')
            : $this->sanitize_font_stack((string) ($merged_resolve['cards_font_title'] ?? ''));
        $next['cards_font_family'] = class_exists('RelayForge_Typography')
            ? (string) (RelayForge_Typography::resolve_role($merged_resolve, 'body')['family'] ?? '')
            : $this->sanitize_font_stack((string) ($merged_resolve['cards_font_family'] ?? ''));

        return $next;
    }

    public function all(): array
    {
        return wp_parse_args((array) get_option(self::OPTION_KEY, []), $this->defaults());
    }

    public function get(string $key, $default = null)
    {
        $constants = [
            'base_url' => 'RELAYFORGE_API_URL',
            'api_key' => 'RELAYFORGE_API_KEY',
            'tenant_slug' => 'RELAYFORGE_TENANT_SLUG',
        ];

        if (isset($constants[$key]) && defined($constants[$key])) {
            return constant($constants[$key]);
        }

        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    private function defaults(): array
    {
        return [
            'base_url' => 'https://relay.forgelabspro.com',
            'api_key' => '',
            'tenant_slug' => '',
            'cache_ttl' => 900,
            'tours_card_template' => 'default',
            'destinations_card_template' => 'default',
            'tours_card_custom_html' => '',
            'destinations_card_custom_html' => '',
            'custom_cards_css' => '',
            'custom_cards_css_tablet' => '',
            'custom_cards_css_mobile' => '',
            'custom_cards_js' => '',
            'cards_grid_class' => '',
            'cards_card_class' => '',
            'cards_button_class' => '',
            'cards_accessibility_mode' => 'assist',
            'cards_image_fallback' => '',
            'cards_image_aspect_ratio' => '16 / 10',
            'cards_image_object_position' => 'center center',
            'cards_image_lazyload' => 'yes',
            'cards_typography_title_size' => 18,
            'cards_spacing_padding' => 20,
            'cards_radius' => 16,
            'cards_theme_sync' => 'no',
            'cards_analytics' => 'yes',
            'cards_show_price' => 'yes',
            'cards_show_currency' => 'no',
            'cards_color_accent' => '',
            'cards_color_surface' => '',
            'cards_color_text' => '',
            'cards_font_family' => '',
            'cards_font_title' => '',
            'cards_font_heading_mode' => '',
            'cards_font_heading_preset' => '',
            'cards_font_heading_custom' => '',
            'cards_font_heading_upload_id' => 0,
            'cards_font_heading_face' => '',
            'cards_font_body_mode' => '',
            'cards_font_body_preset' => '',
            'cards_font_body_custom' => '',
            'cards_font_body_upload_id' => 0,
            'cards_font_body_face' => '',
            'cards_template_library' => [],
            'template_library_json' => '',
            'cards_template_version' => md5('default'),
        ];
    }

    private function fields(): array
    {
        return [
            'base_url' => [
                'label' => __('RelayForge web address', 'relayforge-wordpress'),
                'section' => 'relayforge_wp_main',
                'type' => 'text',
                'placeholder' => 'https://relay.forgelabspro.com',
                'description' => __('Normally leave this as your default RelayForge URL. Must start with https://.', 'relayforge-wordpress'),
                'technical_note' => __('Technical: “base_url” API option — the host WordPress calls for listings and booking.', 'relayforge-wordpress'),
            ],
            'api_key' => [
                'label' => __('Secret connection key', 'relayforge-wordpress'),
                'section' => 'relayforge_wp_main',
                'type' => 'password',
                'description' => __('From your RelayForge account. Paste it exactly — it is password-protected.', 'relayforge-wordpress'),
                'technical_note' => __('Technical: often labeled “API key” in integrations.', 'relayforge-wordpress'),
            ],
            'tenant_slug' => [
                'label' => __('Account short name (optional)', 'relayforge-wordpress'),
                'section' => 'relayforge_wp_main',
                'type' => 'text',
                'description' => __('Rarely needed. RelayForge Support can tell you if your site expects a value.', 'relayforge-wordpress'),
                'technical_note' => __('Technical: “tenant slug” identifies your RelayForge tenant in some setups.', 'relayforge-wordpress'),
            ],
            'cache_ttl' => [
                'label' => 'Data Refresh Interval',
                'section' => 'relayforge_wp_main',
                'type' => 'select',
                'options' => [
                    0    => 'Always fresh (no cache — slower)',
                    300  => 'Every 5 minutes',
                    900  => 'Every 15 minutes (recommended)',
                    1800 => 'Every 30 minutes',
                    3600 => 'Every hour',
                ],
                'description' => 'How often WordPress fetches fresh tours and destinations from RelayForge. Longer intervals mean faster pages.',
            ],
            'tours_card_template' => [
                'label' => 'Tours Card Template',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => self::card_template_options(),
                'description' => 'Select the card style used by [relayforge_tours].',
            ],
            'destinations_card_template' => [
                'label' => 'Destinations Card Template',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => self::card_template_options(),
                'description' => 'Select the card style used by [relayforge_destinations].',
            ],
            'tours_card_custom_html' => [
                'label' => 'Tours Custom HTML',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 8,
                'placeholder' => '<article class="rfp-user-card"><img src="{{image_url}}" alt="{{title}}" /><h3>{{title}}</h3><p>{{location}}</p><p>{{price}}</p><a href="{{link}}">{{cta_text}}</a></article>',
                'description' => 'Used when Tours Card Template is set to Custom HTML.',
            ],
            'destinations_card_custom_html' => [
                'label' => 'Destinations Custom HTML',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 8,
                'placeholder' => '<article class="rfp-user-card"><img src="{{image_url}}" alt="{{name}}" /><h3>{{name}}</h3><p>{{description}}</p><a href="{{link}}">{{cta_text}}</a></article>',
                'description' => 'Used when Destinations Card Template is set to Custom HTML.',
            ],
            'custom_cards_css' => [
                'label' => 'Custom Cards CSS',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 10,
                'placeholder' => '.rfp-user-card { border-radius: 18px; overflow: hidden; }',
                'description' => 'Applied to both tours and destinations card grids.',
            ],
            'custom_cards_css_tablet' => [
                'label' => 'Custom Cards CSS (Tablet)',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 6,
                'placeholder' => '.rfp-user-card { grid-template-columns: 1fr; }',
                'description' => 'Wrapped in @media (max-width: 960px).',
            ],
            'custom_cards_css_mobile' => [
                'label' => 'Custom Cards CSS (Mobile)',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 6,
                'placeholder' => '.rfp-user-card { border-radius: 12px; }',
                'description' => 'Wrapped in @media (max-width: 640px).',
            ],
            'custom_cards_js' => [
                'label' => 'Custom Cards JS',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 6,
                'placeholder' => 'window.addEventListener("relayforge:card-click", function(e){ console.log(e.detail); });',
                'description' => 'Optional script loaded on pages using tours or destinations shortcodes.',
            ],
            'cards_grid_class' => [
                'label' => 'Extra Grid CSS Class',
                'section' => 'relayforge_wp_cards',
                'type' => 'text',
                'placeholder' => 'my-grid',
                'description' => 'For developers: an extra CSS class added to the tour/destination grid wrapper. Leave blank if you don\'t need it.',
            ],
            'cards_card_class' => [
                'label' => 'Extra Card CSS Class',
                'section' => 'relayforge_wp_cards',
                'type' => 'text',
                'placeholder' => 'my-card',
                'description' => 'For developers: an extra CSS class added to each card. Also available as {{card_class}} in custom HTML templates.',
            ],
            'cards_button_class' => [
                'label' => 'Extra Button CSS Class',
                'section' => 'relayforge_wp_cards',
                'type' => 'text',
                'placeholder' => 'my-button',
                'description' => 'For developers: an extra CSS class added to card buttons. Also available as {{button_class}} in custom HTML templates.',
            ],
            'cards_accessibility_mode' => [
                'label' => 'Screen Reader Support',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'assist' => 'On — improve support for screen readers (recommended)',
                    'off'    => 'Off',
                ],
                'description' => 'Adds descriptive labels to card images and buttons so visitors using screen readers get a better experience.',
            ],
            'cards_image_fallback' => [
                'label' => 'Fallback Image',
                'section' => 'relayforge_wp_cards',
                'type' => 'text',
                'placeholder' => 'https://example.com/default-image.jpg',
                'description' => 'Image shown on a card when RelayForge has no photo for that tour or destination.',
            ],
            'cards_image_aspect_ratio' => [
                'label' => 'Image Shape',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    '16 / 10' => 'Wide landscape (16:10)',
                    '16 / 9'  => 'Widescreen (16:9)',
                    '4 / 3'   => 'Classic photo (4:3)',
                    '1 / 1'   => 'Square (1:1)',
                    '4 / 5'   => 'Portrait (4:5)',
                ],
                'description' => 'The shape of images shown on tour and destination cards.',
            ],
            'cards_image_object_position' => [
                'label' => 'Image Focus Point',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'center center' => 'Center (default)',
                    'center top'    => 'Top',
                    'center bottom' => 'Bottom',
                    'left center'   => 'Left',
                    'right center'  => 'Right',
                ],
                'description' => 'Which part of the image stays visible when it is cropped to fit the card.',
            ],
            'cards_image_lazyload' => [
                'label' => 'Lazy Load Images',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'yes' => 'Yes — load images as visitors scroll (recommended)',
                    'no'  => 'No — load all images immediately',
                ],
                'description' => 'Lazy loading speeds up your page by only loading images when they are about to appear on screen.',
            ],
            'cards_show_price' => [
                'label' => 'Show Tour Prices',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'yes' => 'Yes — show prices on cards and detail pages',
                    'no'  => 'No — hide all prices',
                ],
                'description' => 'You can still override this per page using the shortcode.',
            ],
            'cards_show_currency' => [
                'label' => 'Show Currency Code',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'no'  => 'No — show price only (e.g. 1,200)',
                    'yes' => 'Yes — show currency code (e.g. USD 1,200)',
                ],
                'description' => 'When on, the 3-letter currency code (e.g. USD, EUR) appears before prices on cards and tour pages.',
            ],
            'cards_typography_title_size' => [
                'label' => 'Card Title Size (px)',
                'section' => 'relayforge_wp_cards',
                'type' => 'number',
                'description' => 'How large the tour or destination name appears on each card. Default is 18.',
            ],
            'cards_spacing_padding' => [
                'label' => 'Card Inner Spacing (px)',
                'section' => 'relayforge_wp_cards',
                'type' => 'number',
                'description' => 'Space between the card edge and its content. Default is 20.',
            ],
            'cards_radius' => [
                'label' => 'Card Corner Rounding (px)',
                'section' => 'relayforge_wp_cards',
                'type' => 'number',
                'description' => 'How rounded the card corners are. 0 = sharp corners, 24+ = very rounded. Default is 16.',
            ],
            'cards_theme_sync' => [
                'label' => 'Theme Color Sync',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'no'  => 'Auto (follows your theme — recommended)',
                    'yes' => 'Force sync (for older themes that do not set color variables)',
                ],
                'description' => 'Controls how card colors follow your active WordPress theme. Leave on Auto unless cards show the wrong colors.',
            ],
            'cards_analytics' => [
                'label' => 'Click Tracking',
                'section' => 'relayforge_wp_cards',
                'type' => 'select',
                'options' => [
                    'yes' => 'On — track card clicks in analytics tools',
                    'no'  => 'Off',
                ],
                'description' => 'When on, card clicks are reported to Google Tag Manager and other analytics tools connected to your site.',
            ],
            'cards_color_accent' => [
                'label' => 'Accent color',
                'section' => 'relayforge_wp_cards',
                'type' => 'color',
                'description' => 'Buttons, links, progress dots, hero overlay, eyebrow labels, and highlights. Leave blank to use your theme’s primary color.',
            ],
            'cards_color_surface' => [
                'label' => 'Panel & card background',
                'section' => 'relayforge_wp_cards',
                'type' => 'color',
                'description' => 'Card and sidebar surfaces. Leave blank for your theme background.',
            ],
            'cards_color_text' => [
                'label' => 'Primary text color',
                'section' => 'relayforge_wp_cards',
                'type' => 'color',
                'description' => 'Main text on RelayForge pages. Leave blank for your theme’s body text color.',
            ],
            'cards_typography_heading' => [
                'label' => 'Titles & headings font',
                'section' => 'relayforge_wp_cards',
                'type' => 'relayforge_typography',
                'typography_role' => 'heading',
                'description' => 'Used for page titles, section headings, card titles, and booking step questions.',
            ],
            'cards_typography_body' => [
                'label' => 'Body & UI font',
                'section' => 'relayforge_wp_cards',
                'type' => 'relayforge_typography',
                'typography_role' => 'body',
                'description' => 'Used for descriptions, forms, labels, buttons, and prices.',
            ],
            'template_library_json' => [
                'label' => 'Import Shared Template Pack',
                'section' => 'relayforge_wp_cards',
                'type' => 'textarea',
                'rows' => 8,
                'placeholder' => '{"my-pack":{"tours_html":"<article>{{title}}</article>","destinations_html":"<article>{{name}}</article>","css":".rfp-user-card{...}"}}',
                'description' => 'Paste a template pack code provided by a developer or the RelayForge team, then save. Leave blank if you don\'t have one.',
            ],
        ];
    }

    private function validate_custom_template(string $template, array $required_tokens, string $error_code): void
    {
        foreach ($required_tokens as $token) {
            if (false === strpos($template, $token)) {
                add_settings_error(
                    self::OPTION_KEY,
                    $error_code . '_' . md5($token),
                    sprintf(__('Custom template should include %s.', 'relayforge-wordpress'), $token)
                );
            }
        }

        preg_match_all('/\{\{#if\s+[a-zA-Z0-9_]+\}\}/', $template, $if_open);
        preg_match_all('/\{\{\/if\}\}/', $template, $if_close);
        if (count($if_open[0]) !== count($if_close[0])) {
            add_settings_error(self::OPTION_KEY, $error_code . '_if_mismatch', __('Conditional blocks look unbalanced in custom template.', 'relayforge-wordpress'));
        }
    }

    private function normalize_template_library(array $library): array
    {
        $normalized = [];
        foreach ($library as $name => $pack) {
            if (! is_array($pack)) {
                continue;
            }

            $key = sanitize_key((string) $name);
            if ('' === $key) {
                continue;
            }

            $normalized[$key] = [
                'tours_html' => wp_kses_post((string) ($pack['tours_html'] ?? '')),
                'destinations_html' => wp_kses_post((string) ($pack['destinations_html'] ?? '')),
                'css' => sanitize_textarea_field((string) ($pack['css'] ?? '')),
            ];
        }

        return $normalized;
    }
}
