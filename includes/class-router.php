<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Router
{
    private RelayForge_Api_Client $api_client;
    private RelayForge_Settings $settings;

    public function __construct(RelayForge_Api_Client $api_client, RelayForge_Settings $settings)
    {
        $this->api_client = $api_client;
        $this->settings = $settings;

        add_action('init', ['RelayForge_Plugin', 'register_rewrite_rules']);
        add_action('wp', [$this, 'maybe_prepare_virtual_page'], 1);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter('template_include', [$this, 'template_include']);
        add_action('admin_post_nopriv_relayforge_submit_booking', [$this, 'handle_booking_submit']);
        add_action('admin_post_relayforge_submit_booking', [$this, 'handle_booking_submit']);
        add_action('admin_post_nopriv_relayforge_media_view', [$this, 'handle_media_view']);
        add_action('admin_post_relayforge_media_view', [$this, 'handle_media_view']);
    }

    public function maybe_prepare_virtual_page(): void
    {
        $view = (string) get_query_var('relayforge_view');
        if (! $view) {
            return;
        }

        RelayForge_Theme_Compat::prepare_virtual_page($this->fallback_title_for_view($view));
        RelayForge_Theme_Compat::apply_request_filters();
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = 'relayforge_view';
        $vars[] = 'relayforge_slug';

        return $vars;
    }

    public function template_include(string $template): string
    {
        $view = get_query_var('relayforge_view');
        $slug = get_query_var('relayforge_slug');

        if (! $view) {
            return $template;
        }

        RelayForge_Theme_Compat::prepare_virtual_page($this->fallback_title_for_view((string) $view));
        RelayForge_Theme_Compat::apply_request_filters();

        wp_enqueue_style('relayforge-wordpress');
        wp_enqueue_script('relayforge-wordpress');

        if ('demo_tour' === $view) {
            set_query_var('relayforge_tour', $this->demo_tour());
            set_query_var('relayforge_related_tours', $this->demo_related_tours());
            set_query_var('relayforge_api_client', null);
            set_query_var('relayforge_settings', $this->settings);
            set_query_var('relayforge_is_demo', true);
            RelayForge_Theme_Compat::prepare_virtual_page(__('Demo Coastal Escape Tour', 'relayforge-wordpress'));
            return $this->locate_template('single-tour.php');
        }

        if ('packages' === $view) {
            wp_enqueue_script('relayforge-wordpress');
            $page = isset($_GET['rfp_page']) ? max(1, absint(wp_unslash((string) $_GET['rfp_page']))) : 1;
            $per_page = (int) apply_filters('relayforge_packages_per_page', 12);
            $per_page = max(1, min(48, $per_page));
            $response = $this->api_client->get_tours([
                'limit' => $per_page,
                'offset' => ($page - 1) * $per_page,
            ]);
            $total = max(0, (int) ($response['data']['total'] ?? 0));
            set_query_var('relayforge_packages', is_array($response['data']['tours'] ?? null) ? $response['data']['tours'] : []);
            set_query_var('relayforge_packages_page', $page);
            set_query_var('relayforge_packages_per_page', $per_page);
            set_query_var('relayforge_packages_total', $total);
            set_query_var('relayforge_settings', $this->settings);
            set_query_var('relayforge_packages_error', $response['ok'] ? '' : (string) ($response['error'] ?? ''));
            RelayForge_Theme_Compat::prepare_virtual_page(__('Tours & Packages', 'relayforge-wordpress'));
            return $this->locate_template('packages-page.php');
        }

        if ('demo_packages' === $view) {
            wp_enqueue_script('relayforge-wordpress');
            set_query_var('relayforge_packages', $this->demo_packages());
            set_query_var('relayforge_settings', $this->settings);
            set_query_var('relayforge_packages_error', '');
            set_query_var('relayforge_is_demo', true);
            RelayForge_Theme_Compat::prepare_virtual_page(__('Tours & Packages', 'relayforge-wordpress'));
            return $this->locate_template('packages-page.php');
        }

        if ('demo_destination' === $view) {
            set_query_var('relayforge_destination', $this->demo_destination());
            set_query_var('relayforge_related_tours', $this->demo_related_tours());
            set_query_var('relayforge_api_client', null);
            set_query_var('relayforge_settings', $this->settings);
            set_query_var('relayforge_is_demo', true);
            RelayForge_Theme_Compat::prepare_virtual_page(__('Demo Island Destination', 'relayforge-wordpress'));
            return $this->locate_template('single-destination.php');
        }

        if (! $slug) {
            return $template;
        }

        if ('destination' === $view) {
            $response = $this->api_client->get_destination_by_slug((string) $slug);
            if (! $response['ok']) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return $template;
            }

            set_query_var('relayforge_destination', $response['data']['destination'] ?? []);
            set_query_var('relayforge_api_client', $this->api_client);
            set_query_var('relayforge_settings', $this->settings);
            RelayForge_Theme_Compat::prepare_virtual_page((string) (($response['data']['destination']['name'] ?? '') ?: __('Destination', 'relayforge-wordpress')));
            return $this->locate_template('single-destination.php');
        }

        if ('tour' === $view) {
            $response = $this->api_client->get_tour_by_slug((string) $slug);
            if (! $response['ok']) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return $template;
            }

            $tour = $response['data']['tour'] ?? [];
            set_query_var('relayforge_tour', $tour);
            set_query_var('relayforge_related_tours', $this->api_client->get_related_tours($tour));
            set_query_var('relayforge_api_client', $this->api_client);
            set_query_var('relayforge_settings', $this->settings);
            RelayForge_Theme_Compat::prepare_virtual_page((string) (($tour['title'] ?? '') ?: __('Tour', 'relayforge-wordpress')));
            return $this->locate_template('single-tour.php');
        }

        return $template;
    }

    private function locate_template(string $template): string
    {
        $theme_template = locate_template('relayforge/' . $template);

        return $theme_template ?: RELAYFORGE_WP_PATH . 'templates/' . $template;
    }

    private function fallback_title_for_view(string $view): string
    {
        switch ($view) {
            case 'packages':
            case 'demo_packages':
                return __('Tours & Packages', 'relayforge-wordpress');
            case 'destination':
            case 'demo_destination':
                return __('Destination', 'relayforge-wordpress');
            case 'tour':
            case 'demo_tour':
                return __('Tour', 'relayforge-wordpress');
            default:
                return __('RelayForge', 'relayforge-wordpress');
        }
    }

    public function handle_booking_submit(): void
    {
        $redirect = esc_url_raw((string) ($_POST['relayforge_redirect'] ?? home_url('/')));
        $tour_id = sanitize_text_field((string) ($_POST['tour_id'] ?? ''));
        $nonce = (string) ($_POST['relayforge_booking_nonce'] ?? '');

        if (! $tour_id || ! wp_verify_nonce($nonce, 'relayforge_submit_booking_' . $tour_id)) {
            wp_safe_redirect(add_query_arg('rf_booking', 'invalid', $redirect));
            exit;
        }

        $notes = sanitize_textarea_field((string) ($_POST['message'] ?? ''));

        $adults_submit = isset($_POST['rfp_adults']) ? absint(wp_unslash((string) $_POST['rfp_adults'])) : 0;
        $children_submit = isset($_POST['rfp_children']) ? absint(wp_unslash((string) $_POST['rfp_children'])) : 0;
        if ($adults_submit + $children_submit < 1) {
            $adults_submit = max(1, absint(wp_unslash((string) ($_POST['number_of_people'] ?? 1))));
            $children_submit = 0;
        }

        $guests_total = max(1, $adults_submit + $children_submit);

        $tour_row = $this->api_client->get_tour_by_id($tour_id);
        if (! empty($tour_row['ok']) && ! empty($tour_row['data']['tour'])) {
            $bounds = relayforge_booking_party_constraints((array) $tour_row['data']['tour']);
            if ($guests_total < $bounds['min'] || ($bounds['max'] !== null && $guests_total > $bounds['max'])) {
                wp_safe_redirect(add_query_arg('rf_booking', 'party', $redirect));
                exit;
            }
        }

        $party_suffix = sprintf(
            /* translators: 1: number of adults, 2: number of children */
            __('Party: %1$d adult(s), %2$d child(ren).', 'relayforge-wordpress'),
            max(1, $adults_submit),
            $children_submit
        );
        $message_combined = '' === trim($notes) ? $party_suffix : $notes . "\n\n" . $party_suffix;

        $payload = [
            'tourId' => $tour_id,
            'customerName' => sanitize_text_field((string) ($_POST['customer_name'] ?? '')),
            'customerEmail' => sanitize_email((string) ($_POST['customer_email'] ?? '')),
            'customerPhone' => sanitize_text_field((string) ($_POST['customer_phone'] ?? '')),
            'preferredDate' => sanitize_text_field((string) ($_POST['preferred_date'] ?? '')),
            'numberOfPeople' => $guests_total,
            'message' => $message_combined,
        ];

        $payload = array_filter($payload, static function ($value) {
            return '' !== $value && null !== $value;
        });

        $result = $this->api_client->submit_booking($payload);

        wp_safe_redirect(add_query_arg('rf_booking', ! empty($result['ok']) ? 'success' : 'error', $redirect));
        exit;
    }

    public function handle_media_view(): void
    {
        $file_id = isset($_GET['file_id']) ? rawurldecode(sanitize_text_field(wp_unslash((string) $_GET['file_id']))) : '';
        if ('' === $file_id) {
            status_header(404);
            exit;
        }

        $response = $this->api_client->get_media_response($file_id);
        if (is_wp_error($response)) {
            status_header(404);
            exit;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            status_header(404);
            exit;
        }

        $content_type = wp_remote_retrieve_header($response, 'content-type');
        if (is_array($content_type)) {
            $content_type = reset($content_type);
        }
        $content_type = is_string($content_type) ? $content_type : '';
        if ('' === $content_type) {
            $content_type = 'application/octet-stream';
        }

        status_header(200);
        nocache_headers();
        header('Content-Type: ' . $content_type);
        header('Cache-Control: public, max-age=3600');
        echo wp_remote_retrieve_body($response); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function demo_tour(): array
    {
        return [
            'id' => 'relayforge_demo_tour',
            'slug' => 'relayforge-demo-tour',
            'title' => __('Demo Coastal Escape Tour', 'relayforge-wordpress'),
            'location' => __('Demo Bay', 'relayforge-wordpress'),
            'duration' => __('3 days', 'relayforge-wordpress'),
            'category' => __('Marine adventure', 'relayforge-wordpress'),
            'price' => 249,
            'coverImageUrl' => 'https://picsum.photos/1200/780?relayforge-demo-tour',
            'images' => [
                'https://picsum.photos/900/650?relayforge-gallery-1',
                'https://picsum.photos/900/650?relayforge-gallery-2',
                'https://picsum.photos/900/650?relayforge-gallery-3',
                'https://picsum.photos/900/650?relayforge-gallery-4',
            ],
            'description' => __('This demo tour shows how RelayForge tour detail pages look before connecting real API content. Use it to test the hero, gallery, facts, availability area, and inquiry panel with your active WordPress theme.', 'relayforge-wordpress'),
        ];
    }

    private function demo_destination(): array
    {
        return [
            'id' => 'relayforge_demo_destination',
            'slug' => 'relayforge-demo-destination',
            'name' => __('Demo Island Destination', 'relayforge-wordpress'),
            'country' => __('Demo Country', 'relayforge-wordpress'),
            'bestTime' => __('April to September', 'relayforge-wordpress'),
            'imageUrl' => 'https://picsum.photos/1200/780?relayforge-demo-destination',
            'shortDescription' => __('A sample destination page for previewing RelayForge layouts with your WordPress theme.', 'relayforge-wordpress'),
            'description' => __('This demo destination lets you preview the destination detail page without needing API data. It includes a theme-aware hero, overview, destination facts, linked tours, and an explore panel.', 'relayforge-wordpress'),
        ];
    }

    private function demo_related_tours(): array
    {
        return [
            [
                'id' => 'relayforge_demo_related_1',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Reef Safari', 'relayforge-wordpress'),
                'location' => __('Demo Island Destination', 'relayforge-wordpress'),
                'price' => 199,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-related-1',
            ],
            [
                'id' => 'relayforge_demo_related_2',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Sunset Cruise', 'relayforge-wordpress'),
                'location' => __('Demo Island Destination', 'relayforge-wordpress'),
                'price' => 149,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-related-2',
            ],
            [
                'id' => 'relayforge_demo_related_3',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Village and Beach Day', 'relayforge-wordpress'),
                'location' => __('Demo Island Destination', 'relayforge-wordpress'),
                'price' => 99,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-related-3',
            ],
        ];
    }

    private function demo_packages(): array
    {
        return [
            [
                'id' => 'demo_package_1',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Reef Safari', 'relayforge-wordpress'),
                'location' => __('Demo Island Destination', 'relayforge-wordpress'),
                'category' => __('Marine adventure', 'relayforge-wordpress'),
                'duration' => __('Full day', 'relayforge-wordpress'),
                'price' => 199,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-package-1',
                'description' => __('Snorkel, swim, and explore calm reef waters with a local guide.', 'relayforge-wordpress'),
            ],
            [
                'id' => 'demo_package_2',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Sunset Cruise', 'relayforge-wordpress'),
                'location' => __('Demo Bay', 'relayforge-wordpress'),
                'category' => __('Boat trip', 'relayforge-wordpress'),
                'duration' => __('4 hours', 'relayforge-wordpress'),
                'price' => 149,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-package-2',
                'description' => __('A relaxed evening cruise with scenic coastline views.', 'relayforge-wordpress'),
            ],
            [
                'id' => 'demo_package_3',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Village and Beach Day', 'relayforge-wordpress'),
                'location' => __('Demo Village', 'relayforge-wordpress'),
                'category' => __('Culture', 'relayforge-wordpress'),
                'duration' => __('1 day', 'relayforge-wordpress'),
                'price' => 99,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-package-3',
                'description' => __('Meet local hosts, taste coastal food, and finish at the beach.', 'relayforge-wordpress'),
            ],
            [
                'id' => 'demo_package_4',
                'slug' => 'relayforge-demo-tour',
                'title' => __('Demo Family Island Escape', 'relayforge-wordpress'),
                'location' => __('Demo Island Destination', 'relayforge-wordpress'),
                'category' => __('Family', 'relayforge-wordpress'),
                'duration' => __('2 days', 'relayforge-wordpress'),
                'price' => 349,
                'coverImageUrl' => 'https://picsum.photos/900/650?relayforge-package-4',
                'description' => __('A slower itinerary for families with flexible timing and light activities.', 'relayforge-wordpress'),
            ],
        ];
    }
}
