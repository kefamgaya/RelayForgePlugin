<?php
if (! defined('ABSPATH')) {
    exit;
}

$tour = get_query_var('relayforge_tour', []);
$related_tours = get_query_var('relayforge_related_tours', []);
$api_client = get_query_var('relayforge_api_client');
$settings = get_query_var('relayforge_settings');
$show_price = ! ($settings instanceof RelayForge_Settings) || 'no' !== (string) $settings->get('cards_show_price', 'yes');
$show_currency = ($settings instanceof RelayForge_Settings) && 'yes' === (string) $settings->get('cards_show_currency', 'no');
$currency = $show_currency ? (string) ($tour['currency'] ?? '') : '';
$rfp_shortcodes = class_exists('RelayForge_Plugin') ? RelayForge_Plugin::instance()->shortcodes : null;
$rfp_tours_grid_mod = ($rfp_shortcodes instanceof RelayForge_Shortcodes) ? $rfp_shortcodes->get_cards_grid_combined_classes() : '';
$is_demo = (bool) get_query_var('relayforge_is_demo', false);
$availability_response = $api_client instanceof RelayForge_Api_Client
    ? $api_client->get_availability(['tourId' => $tour['id'] ?? '', 'limit' => 12, 'offset' => 0])
    : ['ok' => false];
$availability = is_array($availability_response['data']['availability'] ?? null) ? $availability_response['data']['availability'] : [];

// packageMeta fields
$pkg_meta           = is_array($tour['packageMeta'] ?? null) ? $tour['packageMeta'] : [];
$difficulty         = (string) ($pkg_meta['difficulty'] ?? ($tour['difficulty'] ?? ''));
$meeting_point      = (string) ($pkg_meta['meetingPoint'] ?? ($tour['meetingPoint'] ?? ''));
$min_group          = isset($pkg_meta['minGroupSize']) ? (int) $pkg_meta['minGroupSize'] : (isset($tour['minGroupSize']) ? (int) $tour['minGroupSize'] : 0);
$max_group          = isset($pkg_meta['maxGroupSize']) ? (int) $pkg_meta['maxGroupSize'] : (isset($tour['maxGroupSize']) ? (int) $tour['maxGroupSize'] : 0);
$min_age            = isset($pkg_meta['minAge']) ? (int) $pkg_meta['minAge'] : (isset($tour['minAge']) ? (int) $tour['minAge'] : 0);
$cancellation       = (string) ($pkg_meta['cancellationPolicy'] ?? ($tour['cancellationPolicy'] ?? ''));
$languages          = is_array($pkg_meta['languages'] ?? null) ? $pkg_meta['languages'] : (is_array($tour['languages'] ?? null) ? $tour['languages'] : []);
$is_featured        = (bool) ($pkg_meta['isFeatured'] ?? ($tour['isFeatured'] ?? false));
$what_to_bring      = is_array($pkg_meta['whatToBring'] ?? null) ? $pkg_meta['whatToBring'] : (is_array($tour['whatToBring'] ?? null) ? $tour['whatToBring'] : []);
$pricing_model_raw = (string) ($pkg_meta['pricingModel'] ?? ($tour['pricingModel'] ?? ''));
$pricing_model     = relayforge_normalize_booking_pricing_model($pricing_model_raw);
$base_group_size    = isset($pkg_meta['baseGroupSize']) ? (int) $pkg_meta['baseGroupSize'] : 0;
$base_group_price   = isset($pkg_meta['baseGroupPrice']) ? (float) $pkg_meta['baseGroupPrice'] : 0.0;
$extra_person_price = isset($pkg_meta['extraPersonPrice']) ? (float) $pkg_meta['extraPersonPrice'] : 0.0;

// Also check top-level inclusions/exclusions that packageMeta may override
$included_raw = is_array($pkg_meta['inclusions'] ?? null) ? $pkg_meta['inclusions'] : (is_array($tour['inclusions'] ?? null) ? $tour['inclusions'] : (is_array($tour['included'] ?? null) ? $tour['included'] : []));
$excluded_raw = is_array($pkg_meta['exclusions'] ?? null) ? $pkg_meta['exclusions'] : (is_array($tour['exclusions'] ?? null) ? $tour['exclusions'] : (is_array($tour['excluded'] ?? null) ? $tour['excluded'] : []));

if ($is_demo) {
    $availability = [
        ['date' => gmdate('Y-m-d', strtotime('+7 days')), 'remainingSlots' => 8],
        ['date' => gmdate('Y-m-d', strtotime('+14 days')), 'remainingSlots' => 4],
        ['date' => gmdate('Y-m-d', strtotime('+21 days')), 'remainingSlots' => 12],
    ];
}
$title       = (string) ($tour['title'] ?? __('Tour', 'relayforge-wordpress'));
$location    = (string) ($tour['location'] ?? '');
$description = (string) ($tour['description'] ?? '');
$gallery     = [];
if (! empty($tour['images']) && is_array($tour['images'])) {
    $gallery = array_values(array_filter(array_map('strval', $tour['images'])));
}
$cover_image = $gallery[0] ?? (string) ($tour['coverImageUrl'] ?? '');
if ($cover_image && ! in_array($cover_image, $gallery, true)) {
    array_unshift($gallery, $cover_image);
}

$duration   = (string) ($tour['duration'] ?? ($tour['durationDays'] ?? ''));
$category   = (string) ($tour['category'] ?? ($tour['type'] ?? ''));
$itinerary  = is_array($tour['itinerary'] ?? null) ? $tour['itinerary'] : [];
$highlights = is_array($tour['highlights'] ?? null) ? $tour['highlights'] : [];
$included   = $included_raw;
$excluded   = $excluded_raw;
$faqs       = is_array($tour['faqs'] ?? null) ? $tour['faqs'] : [];

if ($is_demo) {
    $itinerary = [
        [
            'title'   => __('Day 1: Arrival and coastline welcome', 'relayforge-wordpress'),
            'summary' => __('Meet your guide, settle in, and enjoy a relaxed coastal orientation.', 'relayforge-wordpress'),
            'activities' => [__('Airport transfer', 'relayforge-wordpress'), __('Welcome dinner', 'relayforge-wordpress')],
            'meals' => ['breakfast' => false, 'lunch' => false, 'dinner' => true],
            'accommodation' => ['type' => 'Hotel', 'name' => __('Pemba Island Lodge', 'relayforge-wordpress')],
        ],
        [
            'title'   => __('Day 2: Marine safari and local lunch', 'relayforge-wordpress'),
            'summary' => __('Explore reef waters, swim, and enjoy a locally hosted meal.', 'relayforge-wordpress'),
            'activities' => [__('Reef snorkelling', 'relayforge-wordpress'), __('Traditional dhow ride', 'relayforge-wordpress'), __('Village lunch', 'relayforge-wordpress')],
            'meals' => ['breakfast' => true, 'lunch' => true, 'dinner' => false],
            'accommodation' => ['type' => 'Hotel', 'name' => __('Pemba Island Lodge', 'relayforge-wordpress')],
        ],
        [
            'title'   => __('Day 3: Village visit and departure', 'relayforge-wordpress'),
            'summary' => __('Visit local makers, shop responsibly, and return with time to spare.', 'relayforge-wordpress'),
            'activities' => [__('Artisan market visit', 'relayforge-wordpress'), __('Airport transfer', 'relayforge-wordpress')],
            'meals' => ['breakfast' => true, 'lunch' => false, 'dinner' => false],
            'accommodation' => [],
        ],
    ];
    $highlights    = [__('Guided reef experience', 'relayforge-wordpress'), __('Local culture stop', 'relayforge-wordpress'), __('Flexible private itinerary', 'relayforge-wordpress')];
    $included      = [__('Local guide', 'relayforge-wordpress'), __('Selected transfers', 'relayforge-wordpress'), __('Activity coordination', 'relayforge-wordpress')];
    $excluded      = [__('Flights', 'relayforge-wordpress'), __('Personal expenses', 'relayforge-wordpress')];
    $what_to_bring = [__('Sunscreen', 'relayforge-wordpress'), __('Comfortable walking shoes', 'relayforge-wordpress'), __('Snorkelling mask (optional)', 'relayforge-wordpress')];
    $difficulty    = 'Easy';
    $languages     = ['English', 'Swahili'];
    $min_age       = 6;
    $min_group     = 1;
    $max_group     = 12;
    $meeting_point = __('Pemba Airport, Arrivals Hall', 'relayforge-wordpress');
    $cancellation  = __('Full refund if cancelled 7 days or more before departure.', 'relayforge-wordpress');
    $is_featured   = true;
    $faqs = [
        ['question' => __('Can this package be customized?', 'relayforge-wordpress'), 'answer' => __('Yes. Use the booking form to request custom dates, group sizes, or activities.', 'relayforge-wordpress')],
        ['question' => __('Is this suitable for families?', 'relayforge-wordpress'), 'answer' => __('Yes. The itinerary can be adjusted for children and slower travel days.', 'relayforge-wordpress')],
    ];
}
$hero_style = $cover_image
    ? ' style="background-image: var(--rfp-hero-overlay), url(' . esc_url($cover_image) . ');"'
    : '';

$rfp_title = $title;
include RELAYFORGE_WP_PATH . 'templates/rfp-header.php';
?>
<main class="rfp-detail rfp-tour-detail">
    <section class="rfp-tour-hero rfp-typography-cards"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <div class="rfp-tour-hero__inner">
            <?php if ($location) : ?>
                <p class="rfp-eyebrow"><?php echo esc_html($location); ?></p>
            <?php endif; ?>
            <h1><?php echo esc_html($title); ?></h1>
        </div>
    </section>

    <nav class="rfp-tour-tabs rfp-typography-cards" aria-label="<?php esc_attr_e('Tour sections', 'relayforge-wordpress'); ?>">
        <?php if (! empty($gallery)) : ?><a href="#rfp-tour-gallery"><?php esc_html_e('Gallery', 'relayforge-wordpress'); ?></a><?php endif; ?>
        <?php if (! empty($itinerary)) : ?><a href="#rfp-tour-itinerary"><?php esc_html_e('Itinerary', 'relayforge-wordpress'); ?></a><?php endif; ?>
        <a href="#rfp-tour-info"><?php esc_html_e('Tour info', 'relayforge-wordpress'); ?></a>
        <a href="#rfp-tour-availability"><?php esc_html_e('Availability', 'relayforge-wordpress'); ?></a>
        <?php if (! empty($faqs)) : ?><a href="#rfp-tour-faqs"><?php esc_html_e('FAQs', 'relayforge-wordpress'); ?></a><?php endif; ?>
    </nav>

    <div class="rfp-tour-layout">
        <div class="rfp-tour-main rfp-typography-cards">
            <?php if (! empty($gallery)) : ?>
                <section id="rfp-tour-gallery" class="rfp-tour-panel">
                    <h2><?php esc_html_e('Gallery', 'relayforge-wordpress'); ?></h2>
                    <div class="rfp-tour-gallery">
                        <?php foreach (array_slice($gallery, 0, 6) as $image) : ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (! empty($itinerary)) : ?>
                <section id="rfp-tour-itinerary" class="rfp-tour-panel">
                    <h2><?php esc_html_e('Itinerary', 'relayforge-wordpress'); ?></h2>
                    <div class="rfp-itinerary-list">
                        <?php foreach (array_values($itinerary) as $index => $day) : ?>
                            <?php
                            if (! is_array($day)) {
                                $day = ['summary' => (string) $day];
                            }
                            $day_title   = (string) ($day['title'] ?? sprintf(__('Day %d', 'relayforge-wordpress'), $index + 1));
                            $day_body    = (string) ($day['summary'] ?? ($day['description'] ?? ($day['body'] ?? '')));
                            $activities  = is_array($day['activities'] ?? null) ? $day['activities'] : [];
                            $timed_items = is_array($day['items'] ?? null) ? $day['items'] : [];
                            $meals       = is_array($day['meals'] ?? null) ? $day['meals'] : [];
                            $accomm      = is_array($day['accommodation'] ?? null) ? $day['accommodation'] : [];
                            ?>
                            <details class="rfp-itinerary-item" <?php echo 0 === $index ? 'open' : ''; ?>>
                                <summary><?php echo esc_html($day_title); ?></summary>
                                <?php if ($day_body) : ?><p><?php echo esc_html(wp_strip_all_tags($day_body)); ?></p><?php endif; ?>

                                <?php if (! empty($timed_items)) : ?>
                                    <ul class="rfp-itinerary-timed">
                                        <?php foreach ($timed_items as $item) : ?>
                                            <?php if (! is_array($item)) { continue; } ?>
                                            <li>
                                                <?php if (! empty($item['time'])) : ?><span class="rfp-itinerary-time"><?php echo esc_html((string) $item['time']); ?></span><?php endif; ?>
                                                <span class="rfp-itinerary-label"><?php echo esc_html((string) ($item['label'] ?? '')); ?></span>
                                                <?php if (! empty($item['description'])) : ?><span class="rfp-itinerary-desc"><?php echo esc_html((string) $item['description']); ?></span><?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif (! empty($activities)) : ?>
                                    <ul class="rfp-check-list rfp-itinerary-activities">
                                        <?php foreach ($activities as $act) : ?><li><?php echo esc_html((string) $act); ?></li><?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php
                                $meal_parts = [];
                                if (! empty($meals['breakfast'])) { $meal_parts[] = __('Breakfast', 'relayforge-wordpress'); }
                                if (! empty($meals['lunch'])) { $meal_parts[] = __('Lunch', 'relayforge-wordpress'); }
                                if (! empty($meals['dinner'])) { $meal_parts[] = __('Dinner', 'relayforge-wordpress'); }
                                ?>
                                <?php if (! empty($meal_parts)) : ?>
                                    <p class="rfp-itinerary-meals"><span><?php esc_html_e('Meals:', 'relayforge-wordpress'); ?></span> <?php echo esc_html(implode(', ', $meal_parts)); ?></p>
                                <?php endif; ?>

                                <?php if (! empty($accomm['name'])) : ?>
                                    <p class="rfp-itinerary-accomm"><span><?php esc_html_e('Stay:', 'relayforge-wordpress'); ?></span> <?php echo esc_html($accomm['name']); ?><?php if (! empty($accomm['type'])) { echo ' (' . esc_html($accomm['type']) . ')'; } ?></p>
                                <?php endif; ?>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section id="rfp-tour-info" class="rfp-tour-panel">
                <?php echo relayforge_icon_heading('info', __('Tour info', 'relayforge-wordpress'), 'h2'); ?>
                <div class="rfp-tour-facts">
                    <?php if ($duration) : ?>
                        <?php echo relayforge_fact('clock', __('Duration', 'relayforge-wordpress'), $duration); ?>
                    <?php endif; ?>
                    <?php if ($category) : ?>
                        <?php echo relayforge_fact('tag', __('Tour type', 'relayforge-wordpress'), $category); ?>
                    <?php endif; ?>
                    <?php if ($location) : ?>
                        <?php echo relayforge_fact('map-pin', __('Location', 'relayforge-wordpress'), $location); ?>
                    <?php endif; ?>
                    <?php if ($difficulty) : ?>
                        <?php echo relayforge_fact('mountain', __('Difficulty', 'relayforge-wordpress'), $difficulty); ?>
                    <?php endif; ?>
                    <?php if ($min_group || $max_group) : ?>
                        <?php
                        if ($min_group && $max_group) {
                            $group_size_text = sprintf(__('%d - %d people', 'relayforge-wordpress'), $min_group, $max_group);
                        } elseif ($max_group) {
                            $group_size_text = sprintf(__('Up to %d people', 'relayforge-wordpress'), $max_group);
                        } else {
                            $group_size_text = sprintf(__('From %d people', 'relayforge-wordpress'), $min_group);
                        }
                        echo relayforge_fact('users', __('Group size', 'relayforge-wordpress'), $group_size_text);
                        ?>
                    <?php endif; ?>
                    <?php if ($min_age) : ?>
                        <?php echo relayforge_fact('user', __('Min. age', 'relayforge-wordpress'), sprintf(__('%d years', 'relayforge-wordpress'), $min_age)); ?>
                    <?php endif; ?>
                    <?php if (! empty($languages)) : ?>
                        <?php echo relayforge_fact('languages', __('Languages', 'relayforge-wordpress'), implode(', ', array_map('strval', $languages))); ?>
                    <?php endif; ?>
                    <?php if ($show_price && isset($tour['price'])) : ?>
                        <?php echo relayforge_fact('wallet', __('Price', 'relayforge-wordpress'), ($currency ? $currency . ' ' : '') . number_format_i18n((float) $tour['price'], 2)); ?>
                    <?php endif; ?>
                </div>

                <?php if ($meeting_point) : ?>
                    <div class="rfp-tour-copy" style="margin-top:16px;">
                        <?php echo relayforge_icon_heading('map', __('Meeting point', 'relayforge-wordpress')); ?>
                        <p><?php echo esc_html($meeting_point); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($description) : ?>
                    <div class="rfp-tour-copy">
                        <?php echo relayforge_icon_heading('compass', __('About this tour', 'relayforge-wordpress')); ?>
                        <p><?php echo esc_html(wp_strip_all_tags($description)); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (! empty($highlights)) : ?>
                    <div class="rfp-tour-list-section">
                        <?php echo relayforge_icon_heading('sparkles', __('Highlights', 'relayforge-wordpress')); ?>
                        <ul class="rfp-check-list">
                            <?php foreach ($highlights as $item) : ?><li><?php echo esc_html((string) $item); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (! empty($included) || ! empty($excluded)) : ?>
                    <div class="rfp-tour-included-grid">
                        <?php if (! empty($included)) : ?>
                            <div><?php echo relayforge_icon_heading('check', __('Included', 'relayforge-wordpress')); ?><ul class="rfp-check-list"><?php foreach ($included as $item) : ?><li><?php echo esc_html((string) $item); ?></li><?php endforeach; ?></ul></div>
                        <?php endif; ?>
                        <?php if (! empty($excluded)) : ?>
                            <div><?php echo relayforge_icon_heading('x', __('Not included', 'relayforge-wordpress')); ?><ul class="rfp-plain-list"><?php foreach ($excluded as $item) : ?><li><?php echo esc_html((string) $item); ?></li><?php endforeach; ?></ul></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (! empty($what_to_bring)) : ?>
                    <div class="rfp-tour-list-section">
                        <?php echo relayforge_icon_heading('backpack', __('What to bring', 'relayforge-wordpress')); ?>
                        <ul class="rfp-check-list">
                            <?php foreach ($what_to_bring as $item) : ?><li><?php echo esc_html((string) $item); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($cancellation) : ?>
                    <div class="rfp-tour-copy">
                        <?php echo relayforge_icon_heading('shield-check', __('Cancellation policy', 'relayforge-wordpress')); ?>
                        <p><?php echo esc_html($cancellation); ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <section id="rfp-tour-availability" class="rfp-tour-panel">
                <?php echo relayforge_icon_heading('calendar', __('Availability', 'relayforge-wordpress'), 'h2'); ?>
                <?php if (empty($availability)) : ?>
                    <p class="rfp-notice"><?php esc_html_e('No availability published yet.', 'relayforge-wordpress'); ?></p>
                <?php else : ?>
                    <div class="rfp-availability">
                        <?php foreach ($availability as $slot) : ?>
                            <div class="rfp-availability__card">
                                <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime((string) ($slot['date'] ?? '')))); ?></strong>
                                <span>
                                    <?php
                                    $remaining = isset($slot['remainingSlots']) ? (int) $slot['remainingSlots'] : max(((int) ($slot['totalSlots'] ?? 0)) - ((int) ($slot['bookedSlots'] ?? 0)), 0);
                                    echo esc_html(sprintf(__('%d seats left', 'relayforge-wordpress'), $remaining));
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (! empty($related_tours)) : ?>
                <section class="rfp-tour-panel">
                    <h2><?php esc_html_e('Related tours', 'relayforge-wordpress'); ?></h2>
                    <div class="rfp rfp-typography-cards rfp-tours-grid columns-3 <?php echo esc_attr($rfp_tours_grid_mod); ?>">
                        <?php foreach (array_slice($related_tours, 0, 3) as $item) : ?>
                            <?php
                            if ($rfp_shortcodes instanceof RelayForge_Shortcodes) {
                                echo $rfp_shortcodes->render_saved_tour_card_markup($item, [
                                    'show_price' => $show_price ? 'yes' : 'no',
                                ]);
                                continue;
                            }
                            ?>
                            <article class="rfp-card">
                                <div class="rfp-card__body">
                                    <h3 class="rfp-card__title"><?php echo esc_html($item['title'] ?? __('Tour', 'relayforge-wordpress')); ?></h3>
                                    <?php if ($show_price && isset($item['price'])) : ?>
                                        <?php $item_currency = $show_currency ? (string) ($item['currency'] ?? '') : ''; ?>
                                        <p class="rfp-card__price"><?php echo esc_html(($item_currency ? $item_currency . ' ' : '') . number_format_i18n((float) $item['price'], 2)); ?></p>
                                    <?php endif; ?>
                                    <?php $item_slug = relayforge_content_slug($item, 'tour'); ?>
                                    <?php if ('' !== $item_slug) : ?>
                                        <a class="rfp-button" href="<?php echo esc_url(home_url('/tours/' . $item_slug)); ?>"><?php esc_html_e('View tour', 'relayforge-wordpress'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (! empty($faqs)) : ?>
                <section id="rfp-tour-faqs" class="rfp-tour-panel">
                    <h2><?php esc_html_e('FAQs', 'relayforge-wordpress'); ?></h2>
                    <div class="rfp-faq-list">
                        <?php foreach ($faqs as $faq) : ?>
                            <?php
                            $question = is_array($faq) ? (string) ($faq['question'] ?? '') : '';
                            $answer = is_array($faq) ? (string) ($faq['answer'] ?? '') : '';
                            ?>
                            <?php if ($question || $answer) : ?>
                                <details class="rfp-faq-item">
                                    <summary><?php echo esc_html($question ?: __('Question', 'relayforge-wordpress')); ?></summary>
                                    <?php if ($answer) : ?><p><?php echo esc_html(wp_strip_all_tags($answer)); ?></p><?php endif; ?>
                                </details>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside id="rfp-tour-booking" class="rfp-tour-booking">
            <div class="rfp-tour-booking__card">
                <p class="rfp-eyebrow"><?php esc_html_e('Plan this trip', 'relayforge-wordpress'); ?></p>
                <h2><?php esc_html_e('Send inquiry', 'relayforge-wordpress'); ?></h2>
                <?php if ($show_price) : ?>
                    <?php if ('group' === $pricing_model && $base_group_price > 0) : ?>
                        <p class="rfp-tour-booking__price">
                            <?php echo esc_html(sprintf(__('Group of %d: %s', 'relayforge-wordpress'), $base_group_size, ($currency ? $currency . ' ' : '') . number_format_i18n($base_group_price, 2))); ?>
                        </p>
                        <?php if ($extra_person_price > 0) : ?>
                            <p class="rfp-tour-booking__price-sub">
                                <?php echo esc_html(sprintf(__('+ %s per extra person', 'relayforge-wordpress'), ($currency ? $currency . ' ' : '') . number_format_i18n($extra_person_price, 2))); ?>
                            </p>
                        <?php endif; ?>
                    <?php elseif (isset($tour['price'])) : ?>
                        <p class="rfp-tour-booking__price"><?php echo esc_html(sprintf(__('From %s', 'relayforge-wordpress'), ($currency ? $currency . ' ' : '') . number_format_i18n((float) $tour['price'], 2))); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (isset($_GET['rf_booking'])) : ?>
                    <?php
                    $rfbk = sanitize_key((string) $_GET['rf_booking']);
                    if ('success' === $rfbk) :
                        ?>
                        <p class="rfp-success"><?php esc_html_e('Inquiry sent. We will contact you shortly.', 'relayforge-wordpress'); ?></p>
                    <?php elseif ('party' === $rfbk) : ?>
                        <p class="rfp-notice"><?php esc_html_e('Traveller count must respect the minimum and maximum group sizes for this tour.', 'relayforge-wordpress'); ?></p>
                    <?php elseif ('invalid' === $rfbk) : ?>
                        <p class="rfp-notice"><?php esc_html_e('We could not validate your request. Please reload the page and try again.', 'relayforge-wordpress'); ?></p>
                    <?php else : ?>
                        <p class="rfp-notice"><?php esc_html_e('Could not send inquiry. Please try again.', 'relayforge-wordpress'); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <form class="rfp-booking-form rfp-bms" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate>
                    <input type="hidden" name="action" value="relayforge_submit_booking" />
                    <input type="hidden" name="tour_id" value="<?php echo esc_attr((string) ($tour['id'] ?? 'demo_tour')); ?>" />
                    <input type="hidden" name="relayforge_redirect" value="<?php echo esc_url(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))); ?>" />
                    <input type="hidden" name="relayforge_booking_nonce" value="<?php echo esc_attr(wp_create_nonce('relayforge_submit_booking_' . (string) ($tour['id'] ?? 'demo_tour'))); ?>" />

<?php require RELAYFORGE_WP_PATH . 'templates/part-booking-form-steps.php'; ?>
                </form>
            </div>
        </aside>
    </div>
</main>

<div class="rfp-mobile-bar">
    <div class="rfp-mobile-bar__info">
        <?php if ($show_price && isset($tour['price'])) : ?>
            <span class="rfp-mobile-bar__price"><?php echo esc_html(sprintf(__('From %s', 'relayforge-wordpress'), ($currency ? $currency . ' ' : '') . number_format_i18n((float) $tour['price'], 2))); ?></span>
        <?php else : ?>
            <span class="rfp-mobile-bar__title"><?php echo esc_html($title); ?></span>
        <?php endif; ?>
    </div>
    <button type="button" class="rfp-button rfp-mobile-bar__btn" onclick="document.getElementById('rfp-booking-dialog').showModal()">
        <?php esc_html_e('Send inquiry', 'relayforge-wordpress'); ?>
    </button>
</div>

<dialog id="rfp-booking-dialog" class="rfp-booking-dialog" onclick="if(event.target===this)this.close()">
    <div class="rfp-booking-dialog__inner">
        <div class="rfp-booking-dialog__header">
            <div>
                <p class="rfp-eyebrow"><?php esc_html_e('Plan this trip', 'relayforge-wordpress'); ?></p>
                <h2><?php esc_html_e('Send inquiry', 'relayforge-wordpress'); ?></h2>
            </div>
            <button type="button" class="rfp-booking-dialog__close" onclick="document.getElementById('rfp-booking-dialog').close()" aria-label="<?php esc_attr_e('Close', 'relayforge-wordpress'); ?>">&#x2715;</button>
        </div>

        <?php if ($show_price) : ?>
            <?php if ('group' === $pricing_model && $base_group_price > 0) : ?>
                <p class="rfp-tour-booking__price">
                    <?php echo esc_html(sprintf(__('Group of %d: %s', 'relayforge-wordpress'), $base_group_size, ($currency ? $currency . ' ' : '') . number_format_i18n($base_group_price, 2))); ?>
                </p>
            <?php elseif (isset($tour['price'])) : ?>
                <p class="rfp-tour-booking__price"><?php echo esc_html(sprintf(__('From %s', 'relayforge-wordpress'), ($currency ? $currency . ' ' : '') . number_format_i18n((float) $tour['price'], 2))); ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <form class="rfp-booking-form rfp-bms" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate>
            <input type="hidden" name="action" value="relayforge_submit_booking" />
            <input type="hidden" name="tour_id" value="<?php echo esc_attr((string) ($tour['id'] ?? 'demo_tour')); ?>" />
            <input type="hidden" name="relayforge_redirect" value="<?php echo esc_url(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))); ?>" />
            <input type="hidden" name="relayforge_booking_nonce" value="<?php echo esc_attr(wp_create_nonce('relayforge_submit_booking_' . (string) ($tour['id'] ?? 'demo_tour'))); ?>" />

            <?php require RELAYFORGE_WP_PATH . 'templates/part-booking-form-steps.php'; ?>
        </form>
    </div>
</dialog>

<?php
include RELAYFORGE_WP_PATH . 'templates/rfp-footer.php';
