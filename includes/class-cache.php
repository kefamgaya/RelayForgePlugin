<?php

if (! defined('ABSPATH')) {
    exit;
}

class RelayForge_Cache
{
    public function remember(string $key, int $ttl, callable $callback)
    {
        if ($ttl > 0) {
            $cached = get_transient($key);
            if (false !== $cached) {
                return $cached;
            }
        }

        $value = $callback();

        if ($ttl > 0) {
            set_transient($key, $value, $ttl);
        }

        return $value;
    }

    public function flush_prefix(string $prefix): void
    {
        global $wpdb;

        $like = '_transient_' . $wpdb->esc_like($prefix) . '%';
        $timeout_like = '_transient_timeout_' . $wpdb->esc_like($prefix) . '%';

        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like, $timeout_like));
    }
}
