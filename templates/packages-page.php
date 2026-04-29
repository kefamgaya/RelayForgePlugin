<?php
if (! defined('ABSPATH')) {
    exit;
}

$packages = get_query_var('relayforge_packages', []);
$error = (string) get_query_var('relayforge_packages_error', '');
$current_page = max(1, (int) get_query_var('relayforge_packages_page', 1));
$per_page = max(1, (int) get_query_var('relayforge_packages_per_page', 12));
$total_packages = max(0, (int) get_query_var('relayforge_packages_total', count($packages)));
$total_pages = max(1, (int) ceil($total_packages / $per_page));
$settings = get_query_var('relayforge_settings');
$show_price = ! ($settings instanceof RelayForge_Settings) || 'no' !== (string) $settings->get('cards_show_price', 'yes');
$show_currency = ($settings instanceof RelayForge_Settings) && 'yes' === (string) $settings->get('cards_show_currency', 'no');

$locations = [];
$categories = [];
$max_price = 0;
foreach ($packages as $package) {
    $location = trim((string) ($package['location'] ?? ''));
    $category = trim((string) ($package['category'] ?? ($package['type'] ?? '')));
    if ($location) {
        $locations[$location] = $location;
    }
    if ($category) {
        $categories[$category] = $category;
    }
    if (isset($package['price'])) {
        $max_price = max($max_price, (float) $package['price']);
    }
}

ksort($locations);
ksort($categories);

$rfp_sc = class_exists('RelayForge_Plugin') ? RelayForge_Plugin::instance()->shortcodes : null;
$rfp_packages_grid_mod = ($rfp_sc instanceof RelayForge_Shortcodes) ? $rfp_sc->get_cards_grid_combined_classes() : '';

$rfp_title = __('Tours & Packages', 'relayforge-wordpress');
include RELAYFORGE_WP_PATH . 'templates/rfp-header.php';
?>
<main class="rfp-detail rfp-packages-page">
    <section class="rfp-packages-hero rfp-typography-cards">
        <p class="rfp-eyebrow"><?php esc_html_e('Packages', 'relayforge-wordpress'); ?></p>
        <h1><?php esc_html_e('Find the right package', 'relayforge-wordpress'); ?></h1>
        <p><?php esc_html_e('Browse packages, filter by destination or type, and open a detail page to send an inquiry.', 'relayforge-wordpress'); ?></p>
    </section>

    <section class="rfp-packages-browser rfp-typography-cards" data-rfp-packages-browser>
        <aside class="rfp-packages-filters" aria-label="<?php esc_attr_e('Package filters', 'relayforge-wordpress'); ?>">
            <label>
                <?php esc_html_e('Search', 'relayforge-wordpress'); ?>
                <input type="search" data-rfp-filter="search" placeholder="<?php esc_attr_e('Search packages', 'relayforge-wordpress'); ?>" />
            </label>
            <label>
                <?php esc_html_e('Destination', 'relayforge-wordpress'); ?>
                <select data-rfp-filter="location">
                    <option value=""><?php esc_html_e('All destinations', 'relayforge-wordpress'); ?></option>
                    <?php foreach ($locations as $location) : ?>
                        <option value="<?php echo esc_attr(strtolower($location)); ?>"><?php echo esc_html($location); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <?php esc_html_e('Package type', 'relayforge-wordpress'); ?>
                <select data-rfp-filter="category">
                    <option value=""><?php esc_html_e('All types', 'relayforge-wordpress'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr(strtolower($category)); ?>"><?php echo esc_html($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($show_price && $max_price > 0) : ?>
                <label>
                    <?php esc_html_e('Max price', 'relayforge-wordpress'); ?>
                    <input type="range" data-rfp-filter="price" min="0" max="<?php echo esc_attr((string) ceil($max_price)); ?>" value="<?php echo esc_attr((string) ceil($max_price)); ?>" />
                    <span data-rfp-price-label><?php echo esc_html(number_format_i18n($max_price, 0)); ?></span>
                </label>
            <?php endif; ?>
            <button type="button" data-rfp-reset-filters><?php esc_html_e('Reset filters', 'relayforge-wordpress'); ?></button>
        </aside>

        <div class="rfp-packages-results">
            <?php if ($error) : ?>
                <p class="rfp-notice"><?php echo esc_html($error); ?></p>
            <?php elseif (empty($packages)) : ?>
                <p class="rfp-notice"><?php esc_html_e('No packages available right now.', 'relayforge-wordpress'); ?></p>
            <?php else : ?>
                <div class="rfp-packages-count"><span data-rfp-result-count><?php echo esc_html((string) count($packages)); ?></span> <?php echo esc_html(sprintf(__('of %d packages shown', 'relayforge-wordpress'), $total_packages)); ?></div>
                <div class="rfp rfp-typography-cards rfp-tours-grid columns-3 rfp-packages-grid <?php echo esc_attr($rfp_packages_grid_mod); ?>">
                    <?php foreach ($packages as $package) : ?>
                        <?php
                        $title = (string) ($package['title'] ?? __('Package', 'relayforge-wordpress'));
                        $location = (string) ($package['location'] ?? '');
                        $category = (string) ($package['category'] ?? ($package['type'] ?? ''));
                        $description = (string) ($package['description'] ?? '');
                        $price = isset($package['price']) ? (float) $package['price'] : 0;
                        $slug = relayforge_content_slug($package, 'tour');
                        $detail_url = $slug ? home_url('/tours/' . $slug) : '#';
                        ?>
                        <?php if ($rfp_sc instanceof RelayForge_Shortcodes) : ?>
                            <div
                                class="rfp-package-browser-card"
                                role="article"
                                data-rfp-package
                                data-title="<?php echo esc_attr(strtolower($title . ' ' . $description)); ?>"
                                data-location="<?php echo esc_attr(strtolower($location)); ?>"
                                data-category="<?php echo esc_attr(strtolower($category)); ?>"
                                data-price="<?php echo esc_attr((string) $price); ?>"
                            >
                                <?php
                                echo $rfp_sc->render_saved_tour_card_markup($package, [
                                    'cta_text' => __('View package', 'relayforge-wordpress'),
                                    'show_price' => $show_price ? 'yes' : 'no',
                                ]);
                                ?>
                            </div>
                        <?php else : ?>
                            <article class="rfp-card rfp-package-card" data-rfp-package data-title="<?php echo esc_attr(strtolower($title . ' ' . $description)); ?>" data-location="<?php echo esc_attr(strtolower($location)); ?>" data-category="<?php echo esc_attr(strtolower($category)); ?>" data-price="<?php echo esc_attr((string) $price); ?>">
                                <?php
                                $pkg_imgs  = is_array($package['images'] ?? null) ? array_values(array_filter(array_map('strval', $package['images']))) : [];
                                $pkg_cover_legacy = isset($pkg_imgs[0]) ? (string) $pkg_imgs[0] : (string) ($package['coverImageUrl'] ?? ($package['imageUrl'] ?? ($package['thumbnailUrl'] ?? '')));
                                ?>
                                <?php if ('' !== $pkg_cover_legacy) : ?>
                                    <img class="rfp-card__image" src="<?php echo esc_url($pkg_cover_legacy); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                                <?php endif; ?>
                                <div class="rfp-card__body">
                                    <?php if ($category) : ?><p class="rfp-card__meta"><?php echo esc_html($category); ?></p><?php endif; ?>
                                    <h2 class="rfp-card__title"><?php echo esc_html($title); ?></h2>
                                    <?php if ($location) : ?><p class="rfp-card__meta"><?php echo esc_html($location); ?></p><?php endif; ?>
                                    <?php if ($show_price && isset($package['price'])) : ?>
                                        <?php $pkg_currency = $show_currency ? (string) ($package['currency'] ?? '') : ''; ?>
                                        <p class="rfp-card__price"><?php echo esc_html(($pkg_currency ? $pkg_currency . ' ' : '') . number_format_i18n($price, 2)); ?></p>
                                    <?php endif; ?>
                                    <div class="rfp-package-card__actions">
                                        <a class="rfp-button" href="<?php echo esc_url($detail_url); ?>"><?php esc_html_e('View package', 'relayforge-wordpress'); ?></a>
                                    </div>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <p class="rfp-notice" data-rfp-no-results hidden><?php esc_html_e('No packages match those filters.', 'relayforge-wordpress'); ?></p>
                <?php if ($total_pages > 1) : ?>
                    <nav class="rfp-packages-pagination" aria-label="<?php esc_attr_e('Packages pagination', 'relayforge-wordpress'); ?>">
                        <?php if ($current_page > 1) : ?>
                            <a class="rfp-button rfp-button--secondary" href="<?php echo esc_url(add_query_arg('rfp_page', $current_page - 1, home_url('/packages/'))); ?>"><?php esc_html_e('Previous', 'relayforge-wordpress'); ?></a>
                        <?php endif; ?>
                        <span><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'relayforge-wordpress'), $current_page, $total_pages)); ?></span>
                        <?php if ($current_page < $total_pages) : ?>
                            <a class="rfp-button" href="<?php echo esc_url(add_query_arg('rfp_page', $current_page + 1, home_url('/packages/'))); ?>"><?php esc_html_e('Next', 'relayforge-wordpress'); ?></a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
include RELAYFORGE_WP_PATH . 'templates/rfp-footer.php';
