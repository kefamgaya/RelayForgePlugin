<?php
if (! defined('ABSPATH')) {
    exit;
}

$destination = get_query_var('relayforge_destination', []);
$api_client = get_query_var('relayforge_api_client');
$settings = get_query_var('relayforge_settings');
$show_price = ! ($settings instanceof RelayForge_Settings) || 'no' !== (string) $settings->get('cards_show_price', 'yes');
$query_related_tours = get_query_var('relayforge_related_tours', null);
$tour_response = $api_client instanceof RelayForge_Api_Client ? $api_client->get_tours(['limit' => 100, 'offset' => 0]) : ['ok' => false];
$tours = is_array($tour_response['data']['tours'] ?? null) ? $tour_response['data']['tours'] : [];
$destination_name = strtolower(trim((string) ($destination['name'] ?? '')));
$destination_id   = (string) ($destination['id'] ?? ($destination['legacyId'] ?? ''));
$related_tours = is_array($query_related_tours)
    ? $query_related_tours
    : array_values(array_filter($tours, function ($tour) use ($destination_name, $destination_id) {
        if ($destination_id && is_array($tour['destinationIds'] ?? null)) {
            return in_array($destination_id, array_map('strval', $tour['destinationIds']), true);
        }
        $location = strtolower(trim((string) ($tour['location'] ?? '')));
        return $destination_name && $location === $destination_name;
    }));

$name = (string) ($destination['name'] ?? __('Destination', 'relayforge-wordpress'));
$short_description = (string) ($destination['shortDescription'] ?? '');
$description = (string) ($destination['description'] ?? '');
$image = (string) ($destination['imageUrl'] ?? '');
$country = (string) ($destination['country'] ?? '');
$best_time = (string) ($destination['bestTime'] ?? ($destination['bestTimeToVisit'] ?? ''));
$tour_count = count($related_tours);
$rfp_shortcodes = class_exists('RelayForge_Plugin') ? RelayForge_Plugin::instance()->shortcodes : null;
$rfp_tours_grid_mod = ($rfp_shortcodes instanceof RelayForge_Shortcodes) ? $rfp_shortcodes->get_cards_grid_combined_classes() : '';
$gallery = [];
if (! empty($destination['images']) && is_array($destination['images'])) {
    $gallery = array_values(array_filter(array_map('strval', $destination['images'])));
}
if ($image && ! in_array($image, $gallery, true)) {
    array_unshift($gallery, $image);
}
$highlights = is_array($destination['highlights'] ?? null) ? $destination['highlights'] : [];
$faqs = is_array($destination['faqs'] ?? null) ? $destination['faqs'] : [];

if ((bool) get_query_var('relayforge_is_demo', false)) {
    $gallery = [
        $image,
        'https://picsum.photos/900/650?relayforge-destination-gallery-1',
        'https://picsum.photos/900/650?relayforge-destination-gallery-2',
    ];
    $highlights = [__('Flexible island packages', 'relayforge-wordpress'), __('Marine and culture experiences', 'relayforge-wordpress'), __('Private and group-friendly tours', 'relayforge-wordpress')];
    $faqs = [
        ['question' => __('Can I combine tours in this destination?', 'relayforge-wordpress'), 'answer' => __('Yes. Use the explore panel to request a custom itinerary.', 'relayforge-wordpress')],
        ['question' => __('When should I visit?', 'relayforge-wordpress'), 'answer' => __('The best travel period depends on weather and activities. The destination page can show this from RelayForge data.', 'relayforge-wordpress')],
    ];
}
$cover_for_hero = (isset($gallery[0]) && (string) $gallery[0] !== '') ? (string) $gallery[0] : $image;
$hero_style       = $cover_for_hero
    ? ' style="background-image: var(--rfp-hero-overlay), url(' . esc_url($cover_for_hero) . ');"'
    : '';

$rfp_title = $name;
include RELAYFORGE_WP_PATH . 'templates/rfp-header.php';
?>
<main class="rfp-detail rfp-tour-detail rfp-destination-detail">
    <section class="rfp-tour-hero rfp-destination-hero rfp-typography-cards"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <div class="rfp-tour-hero__inner">
            <h1><?php echo esc_html($name); ?></h1>
        </div>
    </section>

    <nav class="rfp-tour-tabs rfp-typography-cards" aria-label="<?php esc_attr_e('Destination sections', 'relayforge-wordpress'); ?>">
        <?php if (! empty($gallery)) : ?><a href="#rfp-destination-gallery"><?php esc_html_e('Gallery', 'relayforge-wordpress'); ?></a><?php endif; ?>
        <a href="#rfp-destination-overview"><?php esc_html_e('Overview', 'relayforge-wordpress'); ?></a>
        <a href="#rfp-destination-info"><?php esc_html_e('Destination info', 'relayforge-wordpress'); ?></a>
        <a href="#rfp-destination-tours"><?php esc_html_e('Tours', 'relayforge-wordpress'); ?></a>
        <?php if (! empty($faqs)) : ?><a href="#rfp-destination-faqs"><?php esc_html_e('FAQs', 'relayforge-wordpress'); ?></a><?php endif; ?>
    </nav>

    <div class="rfp-tour-layout">
        <div class="rfp-tour-main rfp-typography-cards">
            <?php if (! empty($gallery)) : ?>
                <section id="rfp-destination-gallery" class="rfp-tour-panel">
                    <h2><?php esc_html_e('Gallery', 'relayforge-wordpress'); ?></h2>
                    <div class="rfp-tour-gallery">
                        <?php foreach (array_slice($gallery, 0, 6) as $gallery_image) : ?>
                            <?php if ($gallery_image) : ?><img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" /><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section id="rfp-destination-overview" class="rfp-tour-panel">
                <?php echo relayforge_icon_heading('compass', __('Overview', 'relayforge-wordpress'), 'h2'); ?>
                <div class="rfp-tour-copy">
                    <p><?php echo esc_html(wp_strip_all_tags($description ?: $short_description)); ?></p>
                </div>
                <?php if (! empty($highlights)) : ?>
                    <div class="rfp-tour-list-section">
                        <?php echo relayforge_icon_heading('sparkles', __('Highlights', 'relayforge-wordpress')); ?>
                        <ul class="rfp-check-list">
                            <?php foreach ($highlights as $item) : ?><li><?php echo esc_html((string) $item); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </section>

            <section id="rfp-destination-info" class="rfp-tour-panel">
                <?php echo relayforge_icon_heading('map', __('Destination info', 'relayforge-wordpress'), 'h2'); ?>
                <div class="rfp-tour-facts">
                    <?php echo relayforge_fact('map-pin', __('Destination', 'relayforge-wordpress'), $name); ?>
                    <?php if ($country) : ?>
                        <?php echo relayforge_fact('globe', __('Country', 'relayforge-wordpress'), $country); ?>
                    <?php endif; ?>
                    <?php if ($best_time) : ?>
                        <?php echo relayforge_fact('sun', __('Best time', 'relayforge-wordpress'), $best_time); ?>
                    <?php endif; ?>
                    <?php echo relayforge_fact('route', __('Available tours', 'relayforge-wordpress'), (string) $tour_count); ?>
                </div>
            </section>

            <section id="rfp-destination-tours" class="rfp-tour-panel">
                <?php echo relayforge_icon_heading('route', __('Tours in this destination', 'relayforge-wordpress'), 'h2'); ?>
                <?php if (empty($related_tours)) : ?>
                    <p class="rfp-notice"><?php esc_html_e('No tours are linked to this destination yet.', 'relayforge-wordpress'); ?></p>
                <?php else : ?>
                    <div class="rfp rfp-typography-cards rfp-tours-grid columns-3 <?php echo esc_attr($rfp_tours_grid_mod); ?>">
                        <?php foreach (array_slice($related_tours, 0, 6) as $tour) : ?>
                            <?php
                            if ($rfp_shortcodes instanceof RelayForge_Shortcodes) {
                                echo $rfp_shortcodes->render_saved_tour_card_markup($tour, [
                                    'show_price' => $show_price ? 'yes' : 'no',
                                ]);
                                continue;
                            }
                            ?>
                            <article class="rfp-card">
                                <?php if (! empty($tour['coverImageUrl'])) : ?>
                                    <img class="rfp-card__image" src="<?php echo esc_url($tour['coverImageUrl']); ?>" alt="<?php echo esc_attr($tour['title'] ?? ''); ?>" loading="lazy" />
                                <?php endif; ?>
                                <div class="rfp-card__body">
                                    <h3 class="rfp-card__title"><?php echo esc_html($tour['title'] ?? __('Tour', 'relayforge-wordpress')); ?></h3>
                                    <?php if ($show_price && ! empty($tour['price'])) : ?>
                                        <p class="rfp-card__price"><?php echo esc_html(number_format_i18n((float) $tour['price'], 2)); ?></p>
                                    <?php endif; ?>
                                    <?php $tour_slug = relayforge_content_slug($tour, 'tour'); ?>
                                    <?php if ('' !== $tour_slug) : ?>
                                        <a class="rfp-button" href="<?php echo esc_url(home_url('/tours/' . $tour_slug)); ?>"><?php esc_html_e('View tour', 'relayforge-wordpress'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (! empty($faqs)) : ?>
                <section id="rfp-destination-faqs" class="rfp-tour-panel">
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

        <aside class="rfp-tour-booking">
            <div class="rfp-tour-booking__card">
                <p class="rfp-eyebrow"><?php esc_html_e('Explore', 'relayforge-wordpress'); ?></p>
                <h2><?php esc_html_e('Plan this destination', 'relayforge-wordpress'); ?></h2>
                <p><?php echo esc_html($short_description ?: __('Ask about available tours, travel dates, group size, or custom itineraries.', 'relayforge-wordpress')); ?></p>
                <a class="rfp-button" href="#rfp-destination-tours"><?php esc_html_e('View tours', 'relayforge-wordpress'); ?></a>
            </div>
        </aside>
    </div>
</main>
<?php
include RELAYFORGE_WP_PATH . 'templates/rfp-footer.php';
