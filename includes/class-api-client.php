<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Api_Client
{
    private RelayForge_Settings $settings;
    private RelayForge_Cache $cache;

    public function __construct(RelayForge_Settings $settings, RelayForge_Cache $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;
    }

    public function get_tours(array $args = []): array
    {
        $response = $this->get('/tours', $args, $this->ttl('tours', 900));

        if (! empty($response['ok']) && isset($response['data']['tours']) && is_array($response['data']['tours'])) {
            $response['data']['tours'] = array_map([$this, 'normalize_tour'], $response['data']['tours']);
        }

        return $response;
    }

    public function get_reviews(array $args = []): array
    {
        return $this->get('/reviews', $args, $this->ttl('reviews', 1800));
    }

    public function get_forms(array $args = []): array
    {
        return $this->get('/forms', $args, $this->ttl('forms', 1800));
    }

    public function get_destinations(array $args = []): array
    {
        $response = $this->get('/destinations', $args, $this->ttl('destinations', 1800));

        if (! empty($response['ok']) && isset($response['data']['destinations']) && is_array($response['data']['destinations'])) {
            $response['data']['destinations'] = array_map([$this, 'normalize_destination'], $response['data']['destinations']);
        }

        return $response;
    }

    public function get_destination_by_slug(string $slug): array
    {
        $slug = sanitize_title($slug);
        if (! $slug) {
            return ['ok' => false, 'error' => 'Missing destination slug.'];
        }

        $items = $this->collect_all('/destinations', 'destinations', 1800);
        foreach ($items as $item) {
            if ($this->item_matches_slug($item, $slug, 'destination')) {
                return ['ok' => true, 'data' => ['destination' => $item]];
            }
        }

        return ['ok' => false, 'status' => 404, 'error' => 'Destination not found.'];
    }

    public function get_tour_by_slug(string $slug): array
    {
        $slug = sanitize_title($slug);
        if (! $slug) {
            return ['ok' => false, 'error' => 'Missing tour slug.'];
        }

        $items = $this->collect_all('/tours', 'tours', 900);
        foreach ($items as $item) {
            if ($this->item_matches_slug($item, $slug, 'tour')) {
                return ['ok' => true, 'data' => ['tour' => $item]];
            }
        }

        return ['ok' => false, 'status' => 404, 'error' => 'Tour not found.'];
    }

    /**
     * Find a tour by its public id field (matches booking form hidden tour_id).
     */
    public function get_tour_by_id(string $tour_id): array
    {
        $needle = trim((string) $tour_id);
        if ('' === $needle) {
            return ['ok' => false, 'error' => 'Missing tour id.'];
        }

        $items = $this->collect_all('/tours', 'tours', 900);
        foreach ($items as $item) {
            if ((string) ($item['id'] ?? '') === $needle) {
                return ['ok' => true, 'data' => ['tour' => $item]];
            }
        }

        return ['ok' => false, 'status' => 404, 'error' => 'Tour not found.'];
    }

    public function get_related_tours(array $tour, int $limit = 3): array
    {
        $items = $this->collect_all('/tours', 'tours', 900);
        $current_slug = sanitize_title((string) ($tour['slug'] ?? ''));
        $location = strtolower(trim((string) ($tour['location'] ?? '')));

        $filtered = array_values(array_filter($items, function ($item) use ($current_slug, $location) {
            $item_slug = sanitize_title((string) ($item['slug'] ?? ''));
            if ($current_slug && $item_slug === $current_slug) {
                return false;
            }

            if ($location) {
                return strtolower(trim((string) ($item['location'] ?? ''))) === $location;
            }

            return true;
        }));

        return array_slice($filtered, 0, max(1, $limit));
    }

    public function base_url(): string
    {
        return untrailingslashit((string) $this->settings->get('base_url', ''));
    }

    public function tenant_slug(): string
    {
        return sanitize_title((string) $this->settings->get('tenant_slug', ''));
    }

    public function form_embed_url(string $slug): string
    {
        $slug = sanitize_title($slug);
        $tenant_slug = $this->tenant_slug();

        if (! $slug || ! $tenant_slug || ! $this->base_url()) {
            return '';
        }

        return $this->base_url() . '/embed/forms/' . rawurlencode($tenant_slug) . '/' . rawurlencode($slug);
    }

    public function media_proxy_url(string $file_id): string
    {
        $file_id = trim($file_id);
        if ('' === $file_id) {
            return '';
        }

        return add_query_arg(
            [
                'action' => 'relayforge_media_view',
                'file_id' => $file_id,
            ],
            admin_url('admin-post.php')
        );
    }

    public function get_blogs(array $args = []): array
    {
        return $this->get('/blogs', $args, $this->ttl('blogs', 1800));
    }

    public function get_availability(array $args = []): array
    {
        return $this->get('/availability', $args, $this->ttl('availability', 300));
    }

    public function test_connection(): array
    {
        $response = $this->get_tours(['limit' => 1, 'offset' => 0]);

        return [
            'ok' => (bool) ($response['ok'] ?? false),
            'message' => ! empty($response['ok'])
                ? 'RelayForge connection successful.'
                : (string) ($response['error'] ?? 'RelayForge connection failed.'),
            'status' => (int) ($response['status'] ?? 0),
        ];
    }

    public function submit_form(array $payload): array
    {
        return $this->request('POST', '/forms/submit', [], $payload, 0);
    }

    /**
     * Create a booking / inquiry via the public RelayForge route
     * (see RelayForge src/app/api/[slug]/bookings/route.js — POST createPublicBooking).
     * This is not under /api/v1; slug is the tenant slug from settings.
     */
    public function submit_booking(array $payload): array
    {
        $base_url = untrailingslashit((string) $this->settings->get('base_url', ''));
        $api_key = (string) $this->settings->get('api_key', '');
        $tenant = $this->tenant_slug();

        if (! $base_url || '' === $tenant) {
            return ['ok' => false, 'error' => __('RelayForge needs a base URL and tenant slug to send bookings.', 'relayforge-wordpress')];
        }

        $url = $base_url . '/api/' . rawurlencode($tenant) . '/bookings';

        $args = [
            'timeout' => 18,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if ('' !== $api_key) {
            $args['headers']['X-API-Key'] = $api_key;
        }

        $args['body'] = wp_json_encode($payload);

        return $this->normalize_response(wp_remote_post($url, $args));
    }

    /**
     * Turn storage IDs, partial paths, or full URLs into a browser-ready URL for RelayForge media/assets.
     * Protected RelayForge media is proxied through WordPress so browser image tags do not need the API key.
     */
    private function resolve_relayforge_asset_url(string $raw): string
    {
        $u = trim($raw);
        if ('' === $u) {
            return '';
        }
        $media_id = $this->media_id_from_url_or_path($u);
        if ('' !== $media_id) {
            return $this->media_proxy_url($media_id);
        }
        if (preg_match('#^https?://#i', $u)) {
            return $u;
        }
        if (str_starts_with($u, 'api/')) {
            $u = '/' . $u;
        }
        $base = untrailingslashit((string) $this->settings->get('base_url', ''));
        // Non-media API-relative paths still need the RelayForge host.
        if (str_starts_with($u, '/')) {
            return '' !== $base ? $base . $u : $u;
        }
        return $this->media_url($u);
    }

    private function media_id_from_url_or_path(string $value): string
    {
        $path = $value;
        if (preg_match('#^https?://#i', $value)) {
            $path = (string) wp_parse_url($value, PHP_URL_PATH);
        }

        if (preg_match('~(?:^|/)api/v1/media/view/([^/?#]+)~', $path, $matches)) {
            return rawurldecode((string) $matches[1]);
        }

        if (preg_match('~(?:^|/)api/media/view/([^/?#]+)~', $path, $matches)) {
            return rawurldecode((string) $matches[1]);
        }

        return '';
    }

    /**
     * Public alias for templates / theme code (same rules as private resolver).
     */
    public function resolve_asset_url(string $raw): string
    {
        return $this->resolve_relayforge_asset_url($raw);
    }

    /**
     * First non-empty gallery or itinerary day image, resolved (matches Pemba getTourCoverImage).
     */
    public function tour_cover_image_url(array $tour): string
    {
        $imgs = $tour['images'] ?? null;
        if (is_string($imgs)) {
            $decoded = json_decode($imgs, true);
            $imgs = is_array($decoded) ? $decoded : [];
        }
        foreach ((array) $imgs as $entry) {
            if (is_string($entry) && '' !== trim($entry)) {
                $u = trim($entry);
                if (preg_match('#^https?://#i', $u)) {
                    return $u;
                }
                // Already normalized relative or absolute API path — or raw id.
                $r = $this->resolve_relayforge_asset_url($u);
                if ('' !== $r) {
                    return $r;
                }
            }
        }
        foreach (['coverImageUrl', 'imageUrl', 'thumbnailUrl', 'featuredImageUrl'] as $key) {
            if (empty($tour[$key]) || ! is_scalar($tour[$key])) {
                continue;
            }
            $r = $this->resolve_relayforge_asset_url(trim((string) $tour[$key]));
            if ('' !== $r) {
                return $r;
            }
        }

        return $this->first_resolved_image_from_itinerary($tour['itinerary'] ?? null);
    }

    /**
     * @param mixed $itinerary
     */
    private function first_resolved_image_from_itinerary($itinerary): string
    {
        if (! is_array($itinerary)) {
            return '';
        }
        foreach ($itinerary as $day) {
            if (! is_array($day)) {
                continue;
            }
            $dim = $day['images'] ?? [];
            if (is_string($dim)) {
                $dec = json_decode($dim, true);
                $dim = is_array($dec) ? $dec : [];
            }
            foreach ((array) $dim as $ref) {
                if (! is_string($ref) || '' === trim($ref)) {
                    continue;
                }
                $resolved = $this->resolve_relayforge_asset_url(trim($ref));
                if ('' !== $resolved) {
                    return $resolved;
                }
            }
        }

        return '';
    }

    /**
     * @param mixed $itinerary
     */
    private function normalize_itinerary_images($itinerary): ?array
    {
        if (! is_array($itinerary)) {
            return null;
        }
        $out = [];
        foreach ($itinerary as $day) {
            if (! is_array($day)) {
                $out[] = $day;
                continue;
            }
            $row = $day;
            if (isset($row['images']) && is_array($row['images'])) {
                $resolved = [];
                foreach ($row['images'] as $ref) {
                    if (! is_string($ref) || '' === trim($ref)) {
                        continue;
                    }
                    $u = $this->resolve_relayforge_asset_url(trim($ref));
                    if ('' !== $u) {
                        $resolved[] = $u;
                    }
                }
                $row['images'] = $resolved;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Align tour payloads with RelayForge GET /api/v1/tours (sanitizeTour): resolve image IDs to public proxy URLs when needed.
     */
    public function normalize_tour(array $tour): array
    {
        $out           = $tour;
        $raw_candidates = [];
        $img_field       = $tour['images'] ?? null;

        if (is_string($img_field)) {
            $decoded = json_decode($img_field, true);
            $img_field = is_array($decoded) ? $decoded : [];
        }

        foreach ((array) $img_field as $entry) {
            if (is_string($entry) && '' !== trim($entry)) {
                $raw_candidates[] = $entry;
            }
        }
        foreach (['coverImageUrl', 'imageUrl', 'thumbnailUrl', 'featuredImageUrl'] as $key) {
            if (! empty($out[$key]) && is_scalar($out[$key])) {
                $raw_candidates[] = (string) $out[$key];
            }
        }

        $urls = [];
        foreach (array_unique($raw_candidates) as $raw) {
            $resolved = $this->resolve_relayforge_asset_url(trim((string) $raw));
            if ('' !== $resolved) {
                $urls[] = $resolved;
            }
        }
        $urls = array_values(array_unique($urls));

        if ([] === $urls) {
            $from_itin = $this->first_resolved_image_from_itinerary($tour['itinerary'] ?? null);
            if ('' !== $from_itin) {
                $urls[] = $from_itin;
            }
        }

        $out['images'] = $urls;
        if (! empty($urls)) {
            $out['coverImageUrl'] = $urls[0];
        }

        if (isset($out['itinerary'])) {
            $norm = $this->normalize_itinerary_images($out['itinerary']);
            if (is_array($norm)) {
                $out['itinerary'] = $norm;
            }
        }

        return $out;
    }

    /**
     * GET /api/v1/destinations (sanitizeDestination) returns imageUrl as a path /api/v1/media/view/{imageId}.
     * Prefix with RelayForge base URL so WordPress img src and CSS url() load the correct host.
     */
    public function normalize_destination(array $destination): array
    {
        $out       = $destination;
        $image_id = isset($destination['imageId']) ? trim((string) $destination['imageId']) : '';
        $image_url = isset($destination['imageUrl']) ? trim((string) $destination['imageUrl']) : '';

        if ('' !== $image_url) {
            $out['imageUrl'] = $this->resolve_relayforge_asset_url($image_url);
        } elseif ('' !== $image_id) {
            $out['imageUrl'] = $this->media_url($image_id);
        } else {
            $out['imageUrl'] = '';
        }

        $extra_urls = [];
        $img_extra = $destination['images'] ?? null;
        if (is_string($img_extra)) {
            $dec = json_decode($img_extra, true);
            $img_extra = is_array($dec) ? $dec : [];
        }
        foreach ((array) $img_extra as $entry) {
            if (is_string($entry) && '' !== trim($entry)) {
                $r = $this->resolve_relayforge_asset_url(trim($entry));
                if ('' !== $r) {
                    $extra_urls[] = $r;
                }
            }
        }
        if ([] !== $extra_urls && '' === $out['imageUrl']) {
            $out['imageUrl'] = $extra_urls[0];
        }

        if (! empty($out['imageUrl'])) {
            $out['coverImageUrl'] = $out['imageUrl'];
        } elseif (! empty($extra_urls)) {
            $out['coverImageUrl'] = $extra_urls[0];
            $out['imageUrl']      = $extra_urls[0];
        }

        return $out;
    }

    public function media_url(string $file_id): string
    {
        return $this->media_proxy_url($file_id);
    }

    public function get_media_response(string $file_id)
    {
        $file_id = trim($file_id);
        $base_url = untrailingslashit((string) $this->settings->get('base_url', ''));
        $api_key = (string) $this->settings->get('api_key', '');

        if ('' === $file_id || '' === $base_url || '' === $api_key) {
            return new WP_Error('relayforge_media_unconfigured', 'RelayForge media is not configured.');
        }

        return wp_remote_get($this->build_url('/media/view/' . rawurlencode($file_id)), [
            'timeout' => 15,
            'headers' => [
                'X-API-Key' => $api_key,
                'Accept' => '*/*',
            ],
        ]);
    }

    public function get(string $path, array $query = [], int $ttl = 900): array
    {
        return $this->request('GET', $path, $query, null, $ttl);
    }

    public function request(string $method, string $path, array $query = [], ?array $body = null, int $ttl = 0): array
    {
        $base_url = untrailingslashit((string) $this->settings->get('base_url', ''));
        $api_key = (string) $this->settings->get('api_key', '');
        $query = $this->query_with_tenant($query);
        $cache_key = 'relayforge_wp_' . md5($method . '|' . $base_url . '|' . md5($api_key) . '|' . $path . '|' . wp_json_encode($query) . '|' . wp_json_encode($body));

        return $this->cache->remember($cache_key, $ttl, function () use ($method, $path, $query, $body, $base_url, $api_key) {
            if (! $base_url || ! $api_key) {
                return ['ok' => false, 'error' => 'RelayForge plugin is not configured yet.'];
            }

            $url = $this->build_url($path, $query);

            $args = [
                'timeout' => 10,
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ];

            if (null !== $body) {
                $args['body'] = wp_json_encode($body);
            }

            $response = 'POST' === strtoupper($method)
                ? wp_remote_post($url, $args)
                : wp_remote_get($url, $args);

            return $this->normalize_response($response);
        });
    }

    private function normalize_response($response): array
    {
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            return [
                'ok' => false,
                'status' => $status,
                'error' => is_array($decoded) ? (string) ($decoded['error'] ?? 'RelayForge request failed.') : 'RelayForge request failed.',
            ];
        }

        if (! is_array($decoded)) {
            return [
                'ok' => false,
                'status' => $status,
                'error' => 'RelayForge returned an invalid JSON response.',
            ];
        }

        return $decoded + ['ok' => true, 'status' => $status];
    }

    private function collect_all(string $path, string $key, int $ttl): array
    {
        $base_url = untrailingslashit((string) $this->settings->get('base_url', ''));
        $api_key = (string) $this->settings->get('api_key', '');
        $tenant_slug = (string) $this->settings->get('tenant_slug', '');
        $cache_key = 'relayforge_wp_collect_' . md5($base_url . '|' . md5($api_key) . '|' . $tenant_slug . '|' . $path . '|' . $key);

        return $this->cache->remember($cache_key, $ttl, function () use ($path, $key) {
            $offset = 0;
            $limit = 100;
            $max_pages = 10;
            $page = 0;
            $all = [];

            while ($page < $max_pages) {
                $response = $this->request('GET', $path, ['limit' => $limit, 'offset' => $offset], null, 0);
                $chunk = is_array($response['data'][$key] ?? null) ? $response['data'][$key] : [];

                if (! $response['ok'] || empty($chunk)) {
                    break;
                }

                $mapped = $chunk;
                if ('tours' === $key) {
                    $mapped = array_map([$this, 'normalize_tour'], $chunk);
                } elseif ('destinations' === $key) {
                    $mapped = array_map([$this, 'normalize_destination'], $chunk);
                }
                $all = array_merge($all, $mapped);
                $page++;

                if (count($chunk) < $limit) {
                    break;
                }

                $offset += $limit;
            }

            return $all;
        });
    }

    private function build_url(string $path, array $query = []): string
    {
        $base_url = untrailingslashit((string) $this->settings->get('base_url', ''));
        $url = $base_url . '/api/v1' . $path;
        $query = $this->query_with_tenant($query);

        return ! empty($query) ? add_query_arg($query, $url) : $url;
    }

    private function query_with_tenant(array $query): array
    {
        $tenant_slug = (string) $this->settings->get('tenant_slug', '');

        if ($tenant_slug && empty($query['tenantSlug'])) {
            $query['tenantSlug'] = $tenant_slug;
        }

        return $query;
    }

    private function item_matches_slug(array $item, string $slug, string $type): bool
    {
        foreach ($this->item_slug_candidates($item, $type) as $candidate) {
            if (sanitize_title($candidate) === $slug) {
                return true;
            }
        }

        return false;
    }

    private function item_slug_candidates(array $item, string $type): array
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

        return array_values(array_filter(array_map('strval', $values)));
    }

    private function ttl(string $type, int $default): int
    {
        $setting = (int) $this->settings->get('cache_ttl', $default);
        $ttl = in_array($setting, [0, 300, 900, 1800, 3600], true) ? $setting : $default;

        return (int) apply_filters('relayforge_cache_ttl', $ttl, $type);
    }
}
