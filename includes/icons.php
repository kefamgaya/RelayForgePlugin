<?php

if (! defined('ABSPATH')) {
    exit;
}

function relayforge_icon(string $name, string $class = ''): string
{
    static $icons = null;

    if (null === $icons) {
        $icons = [
            'backpack' => '<path d="M8 8V6a4 4 0 0 1 8 0v2"/><path d="M6 9h12l1 11H5L6 9Z"/><path d="M9 13h6"/><path d="M9 17h6"/>',
            'binoculars' => '<path d="M7 8h3v11H5a3 3 0 0 1-3-3v-3a5 5 0 0 1 5-5Z"/><path d="M14 8h3a5 5 0 0 1 5 5v3a3 3 0 0 1-3 3h-5V8Z"/><path d="M10 8V6a2 2 0 0 1 4 0v2"/>',
            'calendar' => '<path d="M7 3v3"/><path d="M17 3v3"/><path d="M4 8h16"/><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01"/>',
            'camera' => '<path d="M4 8h4l2-3h4l2 3h4v11H4V8Z"/><circle cx="12" cy="14" r="3"/>',
            'car' => '<path d="M5 12 7 7h10l2 5"/><path d="M3 12h18v6H3z"/><circle cx="7" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/>',
            'check' => '<path d="m20 6-11 11-5-5"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'cloud-sun' => '<path d="M12 3v2"/><path d="m5.6 5.6 1.4 1.4"/><path d="M3 12h2"/><path d="M17 7a5 5 0 0 0-9.6 2"/><path d="M7 21h10a4 4 0 0 0 0-8h-.5A6 6 0 0 0 5 15.5 3 3 0 0 0 7 21Z"/>',
            'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-2 6-6 2 2-6 6-2Z"/>',
            'flag' => '<path d="M5 22V4"/><path d="M5 4h12l-2 4 2 4H5"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/>',
            'heart-handshake' => '<path d="M12 21s-7-4.4-9.3-8A5.2 5.2 0 0 1 12 6a5.2 5.2 0 0 1 9.3 7c-2.3 3.6-9.3 8-9.3 8Z"/><path d="m8 13 2 2 4-4"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 10v6"/><path d="M12 7h.01"/>',
            'landmark' => '<path d="M3 10h18"/><path d="m5 10 7-6 7 6"/><path d="M6 10v8"/><path d="M10 10v8"/><path d="M14 10v8"/><path d="M18 10v8"/><path d="M4 18h16"/>',
            'languages' => '<path d="M4 5h8"/><path d="M8 5v14"/><path d="M4 19h8"/><path d="M15 19l4-10 4 10"/><path d="M16.5 16h5"/>',
            'map' => '<path d="M9 18 3 21V6l6-3 6 3 6-3v15l-6 3-6-3Z"/><path d="M9 3v15"/><path d="M15 6v15"/>',
            'map-pin' => '<path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
            'mountain' => '<path d="m3 20 7-12 4 7 2-3 5 8H3Z"/><path d="m10 8 2 3"/>',
            'plane' => '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7Z"/>',
            'route' => '<circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M6 16V8a3 3 0 0 1 3-3h6"/><path d="M18 8v8a3 3 0 0 1-3 3H9"/>',
            'shield-check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-5"/>',
            'ship' => '<path d="M4 16h16l-2 4H6l-2-4Z"/><path d="M6 16V8h9l3 8"/><path d="M8 8V4h6v4"/>',
            'sparkles' => '<path d="M12 3 10 9l-6 2 6 2 2 6 2-6 6-2-6-2-2-6Z"/><path d="M19 3v4"/><path d="M17 5h4"/>',
            'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>',
            'tag' => '<path d="M20 13 13 20 4 11V4h7l9 9Z"/><circle cx="8" cy="8" r="1.5"/>',
            'ticket' => '<path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4V7Z"/><path d="M10 8v8"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'users' => '<circle cx="9" cy="8" r="3"/><path d="M2 21a7 7 0 0 1 14 0"/><circle cx="17" cy="9" r="3"/><path d="M17 16a6 6 0 0 1 5 5"/>',
            'utensils' => '<path d="M4 3v8"/><path d="M8 3v8"/><path d="M6 3v18"/><path d="M14 3v18"/><path d="M14 3c4 2 4 8 0 10"/>',
            'wallet' => '<path d="M4 7h16v13H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13"/><path d="M16 13h4"/>',
            'waves' => '<path d="M3 8c3 0 3 2 6 2s3-2 6-2 3 2 6 2"/><path d="M3 14c3 0 3 2 6 2s3-2 6-2 3 2 6 2"/><path d="M3 20c3 0 3 2 6 2s3-2 6-2 3 2 6 2"/>',
            'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        ];
    }

    $name = sanitize_key($name);
    if (! isset($icons[$name])) {
        return '';
    }

    $classes = trim('rfp-icon rfp-icon--' . $name . ' ' . sanitize_html_class($class));

    return '<svg class="' . esc_attr($classes) . '" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icons[$name] . '</svg>';
}

function relayforge_fact(string $icon, string $label, string $value): string
{
    if ('' === trim($value)) {
        return '';
    }

    return '<div class="rfp-tour-fact">'
        . '<span class="rfp-fact-icon">' . relayforge_icon($icon) . '</span>'
        . '<span class="rfp-fact-label">' . esc_html($label) . '</span>'
        . '<strong>' . esc_html($value) . '</strong>'
        . '</div>';
}

function relayforge_icon_heading(string $icon, string $text, string $tag = 'h3'): string
{
    $tag = in_array($tag, ['h2', 'h3', 'h4'], true) ? $tag : 'h3';

    return '<' . $tag . ' class="rfp-icon-heading">' . relayforge_icon($icon) . '<span>' . esc_html($text) . '</span></' . $tag . '>';
}
