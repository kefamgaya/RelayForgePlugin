<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Shortcodes
{
    private RelayForge_Api_Client $api_client;
    private RelayForge_Settings $settings;
    private bool $custom_css_enqueued = false;
    private bool $custom_js_enqueued = false;

    public function __construct(RelayForge_Api_Client $api_client, RelayForge_Settings $settings)
    {
        $this->api_client = $api_client;
        $this->settings = $settings;

        add_action('init', [$this, 'register']);
        add_action('admin_post_nopriv_relayforge_submit_form', [$this, 'handle_form_submit']);
        add_action('admin_post_relayforge_submit_form', [$this, 'handle_form_submit']);
    }

    public function register(): void
    {
        add_shortcode('relayforge_destinations', [$this, 'render_destinations']);
        add_shortcode('relayforge_tours', [$this, 'render_tours']);
        add_shortcode('relayforge_availability', [$this, 'render_availability']);
        add_shortcode('relayforge_reviews', [$this, 'render_reviews']);
        add_shortcode('relayforge_form', [$this, 'render_form']);
        add_shortcode('relayforge_forms', [$this, 'render_forms']);
    }

    public function render_destinations(array $atts = []): string
    {
        wp_enqueue_style('relayforge-wordpress');
        $this->enqueue_custom_cards_assets();

        $atts = shortcode_atts([
            'limit' => 6,
            'columns' => 3,
            'cta_text' => 'View destination',
            'template' => '',
            'custom_html' => '',
            'custom_css_class' => '',
            'style_pack' => '',
        ], $atts, 'relayforge_destinations');

        $response = $this->api_client->get_destinations([
            'limit' => max(1, min(100, absint($atts['limit']))),
            'offset' => 0,
        ]);

        $destinations = $response['data']['destinations'] ?? [];
        $template_mode = sanitize_key((string) ($atts['template'] ?: $this->settings->get('destinations_card_template', 'default')));
        $custom_template = '';
        if (! empty($atts['custom_html'])) {
            $custom_template = wp_kses_post((string) $atts['custom_html']);
            $template_mode = 'custom';
        } else {
            $custom_template = (string) $this->settings->get('destinations_card_custom_html', '');
        }

        $style_pack = sanitize_key((string) $atts['style_pack']);
        if ('' !== $style_pack) {
            $pack = $this->get_template_library_pack($style_pack);
            if (! empty($pack['destinations_html'])) {
                $custom_template = (string) $pack['destinations_html'];
                $template_mode = 'custom';
            }
            if (! empty($pack['css'])) {
                wp_add_inline_style('relayforge-wordpress', (string) $pack['css']);
            }
        }

        $custom_grid_class = sanitize_text_field((string) $atts['custom_css_class']);

        return $this->render_template('destinations-grid.php', [
            'columns' => max(1, min(4, absint($atts['columns']))),
            'cta_text' => sanitize_text_field($atts['cta_text']),
            'destinations' => is_array($destinations) ? $destinations : [],
            'error' => $response['ok'] ? '' : (string) ($response['error'] ?? ''),
            'grid_class' => $this->combined_css_classes([
                (string) $this->settings->get('cards_grid_class', ''),
                $custom_grid_class,
            ]),
            'card_class' => (string) $this->settings->get('cards_card_class', ''),
            'button_class' => (string) $this->settings->get('cards_button_class', ''),
            'image_fallback' => (string) $this->settings->get('cards_image_fallback', ''),
            'image_loading' => $this->image_loading_value(),
            'accessibility_mode' => (string) $this->settings->get('cards_accessibility_mode', 'assist'),
            'template_version' => (string) $this->settings->get('cards_template_version', ''),
            'render_destination_card' => function (array $destination) use ($atts, $template_mode, $custom_template): string {
                return $this->render_destination_card(
                    $destination,
                    sanitize_text_field($atts['cta_text']),
                    $template_mode,
                    $custom_template
                );
            },
        ]);
    }

    public function render_tours(array $atts = []): string
    {
        wp_enqueue_style('relayforge-wordpress');
        $this->enqueue_custom_cards_assets();

        $atts = shortcode_atts([
            'limit' => 6,
            'columns' => 3,
            'show_price' => '',
            'cta_text' => 'View tour',
            'template' => '',
            'custom_html' => '',
            'custom_css_class' => '',
            'style_pack' => '',
        ], $atts, 'relayforge_tours');

        $response = $this->api_client->get_tours([
            'limit' => max(1, min(100, absint($atts['limit']))),
            'offset' => 0,
        ]);

        $tours = $response['data']['tours'] ?? [];
        $template_mode = sanitize_key((string) ($atts['template'] ?: $this->settings->get('tours_card_template', 'default')));
        $custom_template = '';
        if (! empty($atts['custom_html'])) {
            $custom_template = wp_kses_post((string) $atts['custom_html']);
            $template_mode = 'custom';
        } else {
            $custom_template = (string) $this->settings->get('tours_card_custom_html', '');
        }

        $style_pack = sanitize_key((string) $atts['style_pack']);
        if ('' !== $style_pack) {
            $pack = $this->get_template_library_pack($style_pack);
            if (! empty($pack['tours_html'])) {
                $custom_template = (string) $pack['tours_html'];
                $template_mode = 'custom';
            }
            if (! empty($pack['css'])) {
                wp_add_inline_style('relayforge-wordpress', (string) $pack['css']);
            }
        }

        $custom_grid_class = sanitize_text_field((string) $atts['custom_css_class']);

        return $this->render_template('tours-grid.php', [
            'cta_text' => sanitize_text_field($atts['cta_text']),
            'columns' => max(1, min(4, absint($atts['columns']))),
            'error' => $response['ok'] ? '' : (string) ($response['error'] ?? ''),
            'show_price' => $this->should_show_price((string) $atts['show_price']),
            'tours' => is_array($tours) ? $tours : [],
            'grid_class' => $this->combined_css_classes([
                (string) $this->settings->get('cards_grid_class', ''),
                $custom_grid_class,
            ]),
            'card_class' => (string) $this->settings->get('cards_card_class', ''),
            'button_class' => (string) $this->settings->get('cards_button_class', ''),
            'image_fallback' => (string) $this->settings->get('cards_image_fallback', ''),
            'image_loading' => $this->image_loading_value(),
            'accessibility_mode' => (string) $this->settings->get('cards_accessibility_mode', 'assist'),
            'template_version' => (string) $this->settings->get('cards_template_version', ''),
            'render_tour_card' => function (array $tour) use ($atts, $template_mode, $custom_template): string {
                return $this->render_tour_card(
                    $tour,
                    sanitize_text_field($atts['cta_text']),
                    $this->should_show_price((string) $atts['show_price']),
                    $template_mode,
                    $custom_template
                );
            },
        ]);
    }

    /**
     * Tour card markup matching relayforge_tours / Card layouts (saved template + CSS variables).
     *
     * @param array<string, mixed> $tour Tour payload from the API or query vars.
     * @param array<string, string|bool> $args Optional: cta_text, show_price ('yes'|'no'|'') like the shortcode, custom overrides are not merged (uses plugin settings).
     */
    public function render_saved_tour_card_markup(array $tour, array $args = []): string
    {
        wp_enqueue_style('relayforge-wordpress');
        $this->enqueue_custom_cards_assets();

        $cta_text = sanitize_text_field((string) ($args['cta_text'] ?? __('View tour', 'relayforge-wordpress')));
        $show_price_arg = isset($args['show_price']) ? (string) $args['show_price'] : '';
        $show_price_flag = $this->should_show_price($show_price_arg);

        $template_mode = sanitize_key((string) $this->settings->get('tours_card_template', 'default'));
        $custom_template = (string) $this->settings->get('tours_card_custom_html', '');

        return $this->render_tour_card($tour, $cta_text, $show_price_flag, $template_mode, $custom_template);
    }

    /**
     * Sanitized CSS classes from settings for tour/destination grids (matches shortcode grids).
     */
    public function get_cards_grid_combined_classes(): string
    {
        return $this->combined_css_classes([(string) $this->settings->get('cards_grid_class', '')]);
    }

    public function render_reviews(array $atts = []): string
    {
        wp_enqueue_style('relayforge-wordpress');

        $atts = shortcode_atts([
            'tour_id' => '',
            'limit' => 6,
        ], $atts, 'relayforge_reviews');

        $query = [
            'limit' => max(1, min(100, absint($atts['limit']))),
            'offset' => 0,
        ];

        if (! empty($atts['tour_id'])) {
            $query['tourId'] = sanitize_text_field($atts['tour_id']);
        }

        $response = $this->api_client->get_reviews($query);
        $reviews = $response['data']['reviews'] ?? [];

        return $this->render_template('reviews-list.php', [
            'error' => $response['ok'] ? '' : (string) ($response['error'] ?? ''),
            'reviews' => is_array($reviews) ? $reviews : [],
        ]);
    }

    public function render_availability(array $atts = []): string
    {
        wp_enqueue_style('relayforge-wordpress');

        $atts = shortcode_atts([
            'tour_id' => '',
            'from' => '',
            'to' => '',
            'limit' => 6,
        ], $atts, 'relayforge_availability');

        $query = [
            'limit' => max(1, min(100, absint($atts['limit']))),
            'offset' => 0,
        ];

        if (! empty($atts['tour_id'])) {
            $query['tourId'] = sanitize_text_field($atts['tour_id']);
        }
        if (! empty($atts['from'])) {
            $query['from'] = sanitize_text_field($atts['from']);
        }
        if (! empty($atts['to'])) {
            $query['to'] = sanitize_text_field($atts['to']);
        }

        $response = $this->api_client->get_availability($query);
        $availability = $response['data']['availability'] ?? [];

        return $this->render_template('availability-list.php', [
            'availability' => is_array($availability) ? $availability : [],
            'error' => $response['ok'] ? '' : (string) ($response['error'] ?? ''),
        ]);
    }

    public function render_form(array $atts = []): string
    {
        wp_enqueue_style('relayforge-wordpress');
        wp_enqueue_script('relayforge-wordpress');

        $atts = shortcode_atts([
            'id' => '',
            'slug' => '',
            'title' => '',
            'button_text' => '',
            'mode' => 'native',
            'height' => 720,
        ], $atts, 'relayforge_form');

        $slug = sanitize_title($atts['slug']);
        $form_id = sanitize_text_field((string) $atts['id']);
        $mode = sanitize_key((string) $atts['mode']);
        $mode = in_array($mode, ['native', 'iframe'], true) ? $mode : 'native';
        $result = isset($_GET['rf_form']) ? sanitize_key((string) $_GET['rf_form']) : '';
        $message = isset($_GET['rf_msg']) ? sanitize_text_field(wp_unslash((string) $_GET['rf_msg'])) : '';
        $form = [];
        $error = '';

        if ('' !== $slug || '' !== $form_id) {
            $lookup = $this->find_form($slug, $form_id);
            $form = $lookup['form'];
            $error = $lookup['error'];
        }

        $resolved_slug = (string) ($form['slug'] ?? $slug);
        $resolved_id = (string) ($form['formId'] ?? ($form['id'] ?? $form_id));
        $embed = is_array($form['embed'] ?? null) ? $form['embed'] : [];
        $embed_url = (string) ($embed['url'] ?? '');
        if ('' === $embed_url && '' !== $resolved_slug) {
            $embed_url = $this->api_client->form_embed_url($resolved_slug);
        }

        $default_title = (string) ($form['title'] ?? __('Send an inquiry', 'relayforge-wordpress'));
        $button_text = '' !== trim((string) $atts['button_text'])
            ? sanitize_text_field((string) $atts['button_text'])
            : sanitize_text_field((string) ($form['submitLabel'] ?? __('Submit', 'relayforge-wordpress')));

        return $this->render_template('form-embed.php', [
            'button_text' => $button_text,
            'embed_url' => $embed_url,
            'error' => $error,
            'fields' => is_array($form['fields'] ?? null) ? $form['fields'] : [],
            'form' => $form,
            'form_id' => $resolved_id,
            'height' => max(360, min(1400, absint($atts['height']))),
            'mode' => $mode,
            'message' => $message,
            'nonce' => wp_create_nonce('relayforge_submit_form_' . ($resolved_id ?: $resolved_slug)),
            'redirect_url' => esc_url_raw((is_ssl() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/')),
            'result' => $result,
            'slug' => $resolved_slug,
            'title' => '' !== trim((string) $atts['title']) ? sanitize_text_field((string) $atts['title']) : sanitize_text_field($default_title),
        ]);
    }

    public function render_forms(array $atts = []): string
    {
        $atts = shortcode_atts([
            'limit' => 20,
            'mode' => 'list',
            'slug' => '',
            'id' => '',
        ], $atts, 'relayforge_forms');

        if ('' !== trim((string) $atts['slug']) || '' !== trim((string) $atts['id'])) {
            return $this->render_form($atts);
        }

        wp_enqueue_style('relayforge-wordpress');
        $response = $this->api_client->get_forms([
            'limit' => max(1, min(100, absint($atts['limit']))),
            'offset' => 0,
        ]);

        return $this->render_template('forms-list.php', [
            'error' => $response['ok'] ? '' : (string) ($response['error'] ?? ''),
            'forms' => is_array($response['data']['forms'] ?? null) ? $response['data']['forms'] : [],
            'mode' => sanitize_key((string) $atts['mode']),
        ]);
    }

    public function handle_form_submit(): void
    {
        $slug = sanitize_title((string) ($_POST['relayforge_form_slug'] ?? ''));
        $form_id = sanitize_text_field((string) ($_POST['relayforge_form_id'] ?? ''));
        $redirect = esc_url_raw((string) ($_POST['relayforge_redirect'] ?? home_url('/')));
        $nonce = (string) ($_POST['relayforge_nonce'] ?? '');
        $nonce_key = $form_id ?: $slug;

        if (! $nonce_key || ! wp_verify_nonce($nonce, 'relayforge_submit_form_' . $nonce_key)) {
            wp_safe_redirect(add_query_arg('rf_form', 'invalid', $redirect));
            exit;
        }

        $response = [];
        $posted_fields = isset($_POST['rfp_fields']) && is_array($_POST['rfp_fields']) ? wp_unslash($_POST['rfp_fields']) : [];
        foreach ($posted_fields as $field_id => $raw_value) {
            $field_id = sanitize_key((string) $field_id);
            if ('' === $field_id) {
                continue;
            }
            if (is_array($raw_value)) {
                $response[$field_id] = array_values(array_filter(array_map('sanitize_text_field', array_map('strval', $raw_value)), static function ($value) {
                    return '' !== trim($value);
                }));
            } else {
                $response[$field_id] = sanitize_textarea_field((string) $raw_value);
            }
        }

        $payload = ['response' => $response];
        if ('' !== $form_id) {
            $payload['formId'] = $form_id;
        } else {
            $payload['formSlug'] = $slug;
        }

        $result = $this->api_client->submit_form($payload);

        $args = ['rf_form' => ! empty($result['ok']) ? 'success' : 'error'];
        if (empty($result['ok']) && ! empty($result['error'])) {
            $args['rf_msg'] = sanitize_text_field((string) $result['error']);
        }
        wp_safe_redirect(add_query_arg($args, $redirect));
        exit;
    }

    /**
     * @return array{form: array<string, mixed>, error: string}
     */
    private function find_form(string $slug, string $form_id): array
    {
        $response = $this->api_client->get_forms(['limit' => 100, 'offset' => 0]);
        if (empty($response['ok'])) {
            return ['form' => [], 'error' => (string) ($response['error'] ?? __('Failed to load RelayForge forms.', 'relayforge-wordpress'))];
        }

        $forms = is_array($response['data']['forms'] ?? null) ? $response['data']['forms'] : [];
        foreach ($forms as $form) {
            if (! is_array($form)) {
                continue;
            }
            $candidate_slug = sanitize_title((string) ($form['slug'] ?? ''));
            $candidate_id = (string) ($form['formId'] ?? ($form['id'] ?? ''));
            if (('' !== $slug && $candidate_slug === $slug) || ('' !== $form_id && $candidate_id === $form_id)) {
                return ['form' => $form, 'error' => ''];
            }
        }

        return ['form' => [], 'error' => __('RelayForge form not found. Check the form slug or ID.', 'relayforge-wordpress')];
    }

    private function render_template(string $template, array $data = []): string
    {
        $template_path = locate_template('relayforge/' . $template);
        if (! $template_path) {
            $template_path = RELAYFORGE_WP_PATH . 'templates/' . $template;
        }

        if (! file_exists($template_path)) {
            return '';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $template_path;

        return (string) ob_get_clean();
    }

    private function enqueue_custom_cards_assets(): void
    {
        if (! $this->custom_css_enqueued) {
            $css = trim((string) $this->settings->get('custom_cards_css', ''));
            $css_tablet = trim((string) $this->settings->get('custom_cards_css_tablet', ''));
            $css_mobile = trim((string) $this->settings->get('custom_cards_css_mobile', ''));

            $variables = $this->build_cards_variable_css();
            if ('' !== $variables) {
                $css = $variables . "\n" . $css;
            }

            if ('' !== $css_tablet) {
                $css .= "\n@media (max-width: 960px) {\n" . $css_tablet . "\n}\n";
            }

            if ('' !== $css_mobile) {
                $css .= "\n@media (max-width: 640px) {\n" . $css_mobile . "\n}\n";
            }

            if ('' !== trim($css)) {
                wp_add_inline_style('relayforge-wordpress', $css);
            }

            $this->custom_css_enqueued = true;
        }

        if ($this->custom_js_enqueued) {
            return;
        }

        wp_enqueue_script('relayforge-wordpress');

        $script = $this->analytics_script();
        $custom_js = trim((string) $this->settings->get('custom_cards_js', ''));
        if ('' !== $custom_js) {
            $script .= "\n" . $custom_js;
        }

        if ('' !== trim($script)) {
            wp_add_inline_script('relayforge-wordpress', $script, 'after');
        }

        $this->custom_js_enqueued = true;
    }

    private function should_show_price(string $shortcode_value = ''): bool
    {
        $shortcode_value = strtolower(trim($shortcode_value));
        if (in_array($shortcode_value, ['yes', 'true', '1'], true)) {
            return true;
        }

        if (in_array($shortcode_value, ['no', 'false', '0'], true)) {
            return false;
        }

        return 'no' !== (string) $this->settings->get('cards_show_price', 'yes');
    }

    private function render_tour_card(array $tour, string $cta_text, bool $show_price, string $template_mode, string $custom_template): string
    {
        $title = (string) ($tour['title'] ?? 'Tour');
        $location = (string) ($tour['location'] ?? '');
        $price = ($show_price && isset($tour['price'])) ? (float) $tour['price'] : null;
        $image_url = $this->api_client->tour_cover_image_url($tour);
        if ('' === $image_url) {
            $image_url = (string) $this->settings->get('cards_image_fallback', '');
        }

        $tour_slug = relayforge_content_slug($tour, 'tour');
        $link = '' !== $tour_slug ? home_url('/tours/' . $tour_slug) : '';
        $duration = (string) ($tour['duration'] ?? ($tour['durationDays'] ?? ''));
        $rating = isset($tour['rating']) ? (string) $tour['rating'] : (isset($tour['avgRating']) ? (string) $tour['avgRating'] : '');
        $reviews_count = isset($tour['reviewsCount']) ? (string) $tour['reviewsCount'] : '';
        $currency = (string) ($tour['currency'] ?? '');
        $from_price = isset($tour['fromPrice']) ? number_format_i18n((float) $tour['fromPrice'], 2) : ((null !== $price) ? number_format_i18n($price, 2) : '');
        $badge = (string) ($tour['badge'] ?? ($tour['tag'] ?? ''));

        $show_currency = 'yes' === (string) $this->settings->get('cards_show_currency', 'no');
        $currency_prefix = ($show_currency && '' !== $currency) ? $currency . ' ' : '';

        $card_class = $this->combined_css_classes([(string) $this->settings->get('cards_card_class', '')]);
        $button_class = $this->combined_css_classes([(string) $this->settings->get('cards_button_class', '')]);
        $tracking_attrs = $this->tracking_attributes('tour', (string) ($tour['id'] ?? ($tour['slug'] ?? $title)), $template_mode);

        $tokens = [
            '{{title}}' => esc_html($title),
            '{{location}}' => esc_html($location),
            '{{image_url}}' => esc_url($image_url),
            '{{price}}' => (null !== $price) ? esc_html($currency_prefix . number_format_i18n($price, 2)) : '',
            '{{link}}' => esc_url($link),
            '{{cta_text}}' => esc_html($cta_text),
            '{{duration}}' => esc_html($duration),
            '{{rating}}' => esc_html($rating),
            '{{reviews_count}}' => esc_html($reviews_count),
            '{{currency}}' => esc_html($currency),
            '{{from_price}}' => esc_html($currency_prefix . $from_price),
            '{{badge}}' => esc_html($badge),
            '{{card_class}}' => esc_attr($card_class),
            '{{button_class}}' => esc_attr($button_class),
            '{{tracking_attrs}}' => $tracking_attrs,
        ];

        $lazy = esc_attr($this->image_loading_value());
        $preset_templates = [
            'default'   => '<article class="rfp-user-card rfp-user-card--default {{card_class}}">{{#if image_url}}<img class="rfp-user-card__image" src="{{image_url}}" alt="{{title}}" loading="' . $lazy . '" />{{/if}}<div class="rfp-user-card__body"><h3>{{title}}</h3>{{#if location}}<p class="rfp-user-card__meta">{{location}}</p>{{/if}}{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'minimal'   => '<article class="rfp-user-card rfp-user-card--minimal {{card_class}}"><h3>{{title}}</h3><p>{{location}}</p>{{#if duration}}<p class="rfp-user-card__meta">{{duration}}</p>{{/if}}{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</article>',
            'split'     => '<article class="rfp-user-card rfp-user-card--split {{card_class}}">{{#if image_url}}<img class="rfp-user-card__image" src="{{image_url}}" alt="{{title}}" loading="' . $lazy . '" />{{/if}}<div class="rfp-user-card__body"><h3>{{title}}</h3><p>{{location}}</p>{{#if rating}}<p class="rfp-user-card__meta">&#9733; {{rating}} ({{reviews_count}})</p>{{/if}}{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'overlay'   => '<article class="rfp-user-card rfp-user-card--overlay {{card_class}}">{{#if image_url}}<img class="rfp-user-card__image" src="{{image_url}}" alt="{{title}}" loading="' . $lazy . '" />{{/if}}<div class="rfp-user-card__overlay"><h3>{{title}}</h3><p>{{location}}</p>{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'magazine'  => '<article class="rfp-user-card rfp-user-card--magazine {{card_class}}">{{#if image_url}}<img class="rfp-user-card__image" src="{{image_url}}" alt="{{title}}" loading="' . $lazy . '" />{{/if}}<div class="rfp-user-card__body">{{#if badge}}<span class="rfp-user-card__badge">{{badge}}</span>{{/if}}<h3>{{title}}</h3><p>{{location}}</p>{{#if duration}}<p class="rfp-user-card__meta">{{duration}}</p>{{/if}}{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'luxury'    => '<article class="rfp-user-card rfp-user-card--luxury {{card_class}}"><div class="rfp-user-card__body"><p class="rfp-user-card__eyebrow">{{location}}</p><h3>{{title}}</h3><p class="rfp-user-card__price">{{from_price}}</p>{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'adventure' => '<article class="rfp-user-card rfp-user-card--adventure {{card_class}}">{{#if image_url}}<img class="rfp-user-card__image" src="{{image_url}}" alt="{{title}}" loading="' . $lazy . '" />{{/if}}<div class="rfp-user-card__body"><h3>{{title}}</h3><p>{{location}}</p><div class="rfp-user-card__chips">{{#if duration}}<span>{{duration}}</span>{{/if}}{{#if badge}}<span>{{badge}}</span>{{/if}}</div>{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'compact'   => '<article class="rfp-user-card rfp-user-card--compact {{card_class}}"><h3>{{title}}</h3><p>{{location}}</p>{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</article>',
            'feature'   => '<article class="rfp-user-card rfp-user-card--feature {{card_class}}">{{#if image_url}}<img class="rfp-user-card__image" src="{{image_url}}" alt="{{title}}" loading="' . $lazy . '" />{{/if}}<div class="rfp-user-card__body"><h3>{{title}}</h3><p>{{location}}</p>{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}{{#if rating}}<p class="rfp-user-card__meta">&#9733; {{rating}} ({{reviews_count}})</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
        ];

        return $this->render_custom_or_preset_template($template_mode, $custom_template, $preset_templates, $tokens);
    }

    private function render_destination_card(array $destination, string $cta_text, string $template_mode, string $custom_template): string
    {
        $name = (string) ($destination['name'] ?? 'Destination');
        $description = (string) ($destination['shortDescription'] ?? '');
        $image_url = (string) ($destination['imageUrl'] ?? ($destination['coverImageUrl'] ?? ($destination['image'] ?? ($destination['thumbnailUrl'] ?? ($destination['thumbnail'] ?? '')))));
        if ('' === $image_url) {
            $image_url = (string) $this->settings->get('cards_image_fallback', '');
        }

        $dest_slug = relayforge_content_slug($destination, 'destination');
        $link = '' !== $dest_slug ? home_url('/destinations/' . $dest_slug) : '';
        $country = (string) ($destination['country'] ?? '');
        $tour_count = isset($destination['tourCount']) ? (string) $destination['tourCount'] : (isset($destination['toursCount']) ? (string) $destination['toursCount'] : '');
        $best_time = (string) ($destination['bestTime'] ?? ($destination['bestTimeToVisit'] ?? ''));

        $card_class = $this->combined_css_classes([(string) $this->settings->get('cards_card_class', '')]);
        $button_class = $this->combined_css_classes([(string) $this->settings->get('cards_button_class', '')]);
        $tracking_attrs = $this->tracking_attributes('destination', (string) ($destination['id'] ?? ($destination['slug'] ?? $name)), $template_mode);

        $tokens = [
            '{{name}}' => esc_html($name),
            '{{description}}' => esc_html($description),
            '{{image_url}}' => esc_url($image_url),
            '{{link}}' => esc_url($link),
            '{{cta_text}}' => esc_html($cta_text),
            '{{country}}' => esc_html($country),
            '{{tour_count}}' => esc_html($tour_count),
            '{{best_time}}' => esc_html($best_time),
            '{{card_class}}' => esc_attr($card_class),
            '{{button_class}}' => esc_attr($button_class),
            '{{tracking_attrs}}' => $tracking_attrs,
        ];

        $lazy = esc_attr($this->image_loading_value());
        $preset_templates = [
            'default'   => '<article class="rfp-user-card rfp-user-card--default {{card_class}}"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{name}}" loading="' . $lazy . '" /><div class="rfp-user-card__body"><h3>{{name}}</h3>{{#if description}}<p>{{description}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'minimal'   => '<article class="rfp-user-card rfp-user-card--minimal {{card_class}}"><h3>{{name}}</h3><p>{{description}}</p>{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</article>',
            'split'     => '<article class="rfp-user-card rfp-user-card--split {{card_class}}"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{name}}" loading="' . $lazy . '" /><div class="rfp-user-card__body"><h3>{{name}}</h3><p>{{description}}</p>{{#if country}}<p class="rfp-user-card__meta">{{country}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'overlay'   => '<article class="rfp-user-card rfp-user-card--overlay {{card_class}}"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{name}}" loading="' . $lazy . '" /><div class="rfp-user-card__overlay"><h3>{{name}}</h3><p>{{description}}</p>{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'magazine'  => '<article class="rfp-user-card rfp-user-card--magazine {{card_class}}"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{name}}" loading="' . $lazy . '" /><div class="rfp-user-card__body"><h3>{{name}}</h3><p>{{description}}</p>{{#if country}}<p class="rfp-user-card__meta">{{country}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'luxury'    => '<article class="rfp-user-card rfp-user-card--luxury {{card_class}}"><div class="rfp-user-card__body"><p class="rfp-user-card__eyebrow">{{country}}</p><h3>{{name}}</h3>{{#if best_time}}<p>{{best_time}}</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'adventure' => '<article class="rfp-user-card rfp-user-card--adventure {{card_class}}"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{name}}" loading="' . $lazy . '" /><div class="rfp-user-card__body"><h3>{{name}}</h3><p>{{description}}</p><div class="rfp-user-card__chips">{{#if tour_count}}<span>{{tour_count}} tours</span>{{/if}}{{#if best_time}}<span>{{best_time}}</span>{{/if}}</div>{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
            'compact'   => '<article class="rfp-user-card rfp-user-card--compact {{card_class}}"><h3>{{name}}</h3><p>{{country}}</p>{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</article>',
            'feature'   => '<article class="rfp-user-card rfp-user-card--feature {{card_class}}"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{name}}" loading="' . $lazy . '" /><div class="rfp-user-card__body"><h3>{{name}}</h3><p>{{description}}</p>{{#if tour_count}}<p class="rfp-user-card__meta">{{tour_count}} tours</p>{{/if}}{{#if link}}<a class="rfp-button {{button_class}}" href="{{link}}" {{tracking_attrs}}>{{cta_text}}</a>{{/if}}</div></article>',
        ];

        return $this->render_custom_or_preset_template($template_mode, $custom_template, $preset_templates, $tokens);
    }

    private function render_custom_or_preset_template(string $template_mode, string $custom_template, array $preset_templates, array $tokens): string
    {
        $template_mode = sanitize_key($template_mode);

        if ('custom' === $template_mode) {
            $template = trim($custom_template);
            if ('' === $template) {
                return '';
            }

            $template = $this->apply_template_conditionals($template, $tokens);
            return wp_kses_post(strtr($template, $tokens));
        }

        if (! isset($preset_templates[$template_mode])) {
            return '';
        }

        $template = $this->apply_template_conditionals((string) $preset_templates[$template_mode], $tokens);

        return wp_kses_post(strtr($template, $tokens));
    }

    private function apply_template_conditionals(string $template, array $tokens): string
    {
        return (string) preg_replace_callback('/\{\{#if\s+([a-zA-Z0-9_]+)\}\}(.*?)\{\{\/if\}\}/s', function ($matches) use ($tokens) {
            $token_key = '{{' . $matches[1] . '}}';
            $value = (string) ($tokens[$token_key] ?? '');

            return '' !== trim(wp_strip_all_tags($value)) ? (string) $matches[2] : '';
        }, $template);
    }

    private function build_cards_variable_css(): string
    {
        $radius = max(0, (int) $this->settings->get('cards_radius', 16));
        $padding = max(8, (int) $this->settings->get('cards_spacing_padding', 20));
        $title_size = max(12, (int) $this->settings->get('cards_typography_title_size', 18));
        $ratio = (string) $this->settings->get('cards_image_aspect_ratio', '16 / 10');
        $position = (string) $this->settings->get('cards_image_object_position', 'center center');

        $css = ':root{--rf-card-radius:' . $radius . 'px;--rf-card-padding:' . $padding . 'px;--rf-card-title-size:' . $title_size . 'px;--rf-card-image-ratio:' . esc_html($ratio) . ';--rf-card-image-position:' . esc_html($position) . ';}';

        if ('yes' === (string) $this->settings->get('cards_theme_sync', 'no')) {
            $link = sanitize_hex_color((string) get_theme_mod('link_color', ''));
            $text = sanitize_hex_color((string) get_theme_mod('text_color', ''));
            $css .= ':root{';
            if ($link) {
                $css .= '--rf-theme-link:' . $link . ';';
            }
            if ($text) {
                $css .= '--rf-theme-text:' . $text . ';';
            }
            $css .= '}';
        }

        return $css;
    }

    private function analytics_script(): string
    {
        if ('yes' !== (string) $this->settings->get('cards_analytics', 'yes')) {
            return '';
        }

        return '(function(){document.addEventListener("click",function(e){var el=e.target&&e.target.closest?e.target.closest("[data-rf-track=card-cta]"):null;if(!el){return;}var payload={type:el.getAttribute("data-rf-card-type")||"",id:el.getAttribute("data-rf-card-id")||"",template:el.getAttribute("data-rf-template")||"",href:el.getAttribute("href")||""};window.dispatchEvent(new CustomEvent("relayforge:card-click",{detail:payload}));window.dataLayer=window.dataLayer||[];window.dataLayer.push({event:"relayforge_card_click",relayforge:payload});});})();';
    }

    private function image_loading_value(): string
    {
        return 'no' === (string) $this->settings->get('cards_image_lazyload', 'yes') ? 'eager' : 'lazy';
    }

    private function tracking_attributes(string $type, string $id, string $template_mode): string
    {
        $attrs = [
            'data-rf-track' => 'card-cta',
            'data-rf-card-type' => sanitize_key($type),
            'data-rf-card-id' => sanitize_text_field($id),
            'data-rf-template' => sanitize_key($template_mode),
        ];

        $output = [];
        foreach ($attrs as $key => $value) {
            $output[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
        }

        return implode(' ', $output);
    }

    private function combined_css_classes(array $classes): string
    {
        $clean = [];
        foreach ($classes as $class) {
            $parts = preg_split('/\s+/', trim((string) $class)) ?: [];
            foreach ($parts as $part) {
                $safe = sanitize_html_class((string) $part);
                if ('' !== $safe) {
                    $clean[] = $safe;
                }
            }
        }

        return implode(' ', array_unique($clean));
    }

    private function get_template_library_pack(string $pack_key): array
    {
        if ('' === $pack_key) {
            return [];
        }

        $library = $this->settings->get('cards_template_library', []);
        if (! is_array($library)) {
            return [];
        }

        $pack = $library[$pack_key] ?? null;

        return is_array($pack) ? $pack : [];
    }
}
