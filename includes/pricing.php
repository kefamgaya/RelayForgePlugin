<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Map RelayForge public API pricingModel values to booking UI slugs used by relayforge.js.
 *
 * Backend (tourPricing / packageMeta): flat_per_person | group_base_plus_extra
 *
 * Internal: per_person | group
 */
function relayforge_normalize_booking_pricing_model(string $raw): string
{
    $k = strtolower(str_replace([' ', '-'], '_', trim($raw)));

    if ('group_base_plus_extra' === $k || 'group' === $k) {
        return 'group';
    }

    return 'per_person';
}

/**
 * @param array<string, mixed> $tour Tour payload from API or demo router.
 *
 * @return array{min: int, max: int|null} max null = no ceiling (UI caps at 99)
 */
function relayforge_booking_party_constraints(array $tour): array
{
    $pkg = is_array($tour['packageMeta'] ?? null) ? $tour['packageMeta'] : [];
    $mg  = isset($pkg['minGroupSize']) ? (int) $pkg['minGroupSize'] : null;
    if (null === $mg || ! $mg) {
        $mg = isset($tour['minGroupSize']) ? (int) $tour['minGroupSize'] : 0;
    }
    $min = max(1, $mg);

    $xg = isset($pkg['maxGroupSize']) ? (int) $pkg['maxGroupSize'] : null;
    if (null === $xg || ! $xg) {
        $xg = isset($tour['maxGroupSize']) ? (int) $tour['maxGroupSize'] : 0;
    }
    $max = $xg > 0 ? max($min, $xg) : null;

    return [
        'min' => $min,
        'max' => $max,
    ];
}
