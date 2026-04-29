<?php
/**
 * Tours & destinations browser (RelayForge_Settings $this in scope).
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('RelayForge_Plugin')) {
    echo '<div class="wrap"><p>' . esc_html__('Plugin not ready.', 'relayforge-wordpress') . '</p></div>';

    return;
}

$plugin        = RelayForge_Plugin::instance();
$tours_res     = $plugin->api_client->get_tours(['limit' => 200, 'offset' => 0]);
$dest_res      = $plugin->api_client->get_destinations(['limit' => 200, 'offset' => 0]);
$tours         = is_array($tours_res['data']['tours'] ?? null) ? $tours_res['data']['tours'] : [];
$destinations  = is_array($dest_res['data']['destinations'] ?? null) ? $dest_res['data']['destinations'] : [];
$api_error     = ! $tours_res['ok'] ? (string) ($tours_res['error'] ?? __('API error', 'relayforge-wordpress')) : '';
$settings_url  = admin_url('admin.php?page=' . $this::OPTION_KEY . '_settings');
?>
<div class="wrap relayforge-admin rf-admin-shell">
    <div class="relayforge-admin__hero">
        <div>
            <h1><?php esc_html_e('Tours & destinations', 'relayforge-wordpress'); ?></h1>
            <p class="relayforge-admin__sub"><?php esc_html_e('Live listings from your RelayForge account. Editing still happens in RelayForge — this screen helps you check titles, links, and slugs.', 'relayforge-wordpress'); ?></p>
        </div>
        <a class="relayforge-content-back" href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('← Settings', 'relayforge-wordpress'); ?></a>
    </div>

    <p class="relayforge-content-blurb"><?php esc_html_e('You cannot create or delete tours here — use RelayForge for that. Use the shortcodes reference to show these items on your WordPress pages.', 'relayforge-wordpress'); ?></p>

    <?php if ($api_error) : ?>
        <div class="relayforge-content-error">
            <strong><?php esc_html_e('Could not connect to RelayForge:', 'relayforge-wordpress'); ?></strong> <?php echo esc_html($api_error); ?>
            — <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this::OPTION_KEY . '_settings')); ?>"><?php esc_html_e('check your connection', 'relayforge-wordpress'); ?></a>
        </div>
    <?php endif; ?>

    <div class="relayforge-content-section">
        <h2><?php echo esc_html(sprintf(__('Tours (%d)', 'relayforge-wordpress'), count($tours))); ?></h2>
        <?php if (empty($tours)) : ?>
            <div class="relayforge-content-empty"><?php esc_html_e('No tours found in your account.', 'relayforge-wordpress'); ?></div>
        <?php else : ?>
            <div class="relayforge-content-search">
                <input type="search" data-rf-search="tours" placeholder="<?php esc_attr_e('Search tours…', 'relayforge-wordpress'); ?>" />
                <span class="relayforge-content-count" data-rf-count="tours"><?php echo esc_html((string) count($tours)); ?> <?php esc_html_e('tours', 'relayforge-wordpress'); ?></span>
            </div>
            <table class="relayforge-content-table" data-rf-table="tours">
                <thead><tr>
                    <th><?php esc_html_e('Title', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Location', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Price', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Duration', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Category', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Actions', 'relayforge-wordpress'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($tours as $tour) : ?>
                    <?php
                    $t_title    = (string) ($tour['title'] ?? '');
                    $t_location = (string) ($tour['location'] ?? '');
                    $t_currency = (string) ($tour['currency'] ?? '');
                    $t_price    = isset($tour['fromPrice']) ? number_format_i18n((float) $tour['fromPrice'], 2) : (isset($tour['price']) ? number_format_i18n((float) $tour['price'], 2) : '');
                    $t_duration = (string) ($tour['duration'] ?? ($tour['durationDays'] ?? ''));
                    $t_category = (string) ($tour['category'] ?? ($tour['type'] ?? ''));
                    $t_slug     = (string) ($tour['slug'] ?? '');
                    $t_url      = $t_slug ? home_url('/tours/' . $t_slug) : '';
                    $t_search   = strtolower($t_title . ' ' . $t_location . ' ' . $t_category);
                    ?>
                    <tr data-rf-row data-rf-search="<?php echo esc_attr($t_search); ?>">
                        <td><strong><?php echo esc_html($t_title ?: '—'); ?></strong></td>
                        <td><?php echo esc_html($t_location ?: '—'); ?></td>
                        <td><?php echo esc_html($t_price ? ($t_currency ? $t_currency . ' ' . $t_price : $t_price) : '—'); ?></td>
                        <td><?php echo esc_html($t_duration ?: '—'); ?></td>
                        <td><?php if ($t_category) : ?><span class="relayforge-content-badge"><?php echo esc_html($t_category); ?></span><?php else : ?>—<?php endif; ?></td>
                        <td>
                            <?php if ($t_url) : ?>
                                <a class="relayforge-content-action" href="<?php echo esc_url($t_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View page', 'relayforge-wordpress'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="relayforge-content-section">
        <h2><?php echo esc_html(sprintf(__('Destinations (%d)', 'relayforge-wordpress'), count($destinations))); ?></h2>
        <?php if (empty($destinations)) : ?>
            <div class="relayforge-content-empty"><?php esc_html_e('No destinations found in your account.', 'relayforge-wordpress'); ?></div>
        <?php else : ?>
            <div class="relayforge-content-search">
                <input type="search" data-rf-search="destinations" placeholder="<?php esc_attr_e('Search destinations…', 'relayforge-wordpress'); ?>" />
                <span class="relayforge-content-count" data-rf-count="destinations"><?php echo esc_html((string) count($destinations)); ?> <?php esc_html_e('destinations', 'relayforge-wordpress'); ?></span>
            </div>
            <table class="relayforge-content-table" data-rf-table="destinations">
                <thead><tr>
                    <th><?php esc_html_e('Name', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Country', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Tours', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Best time', 'relayforge-wordpress'); ?></th>
                    <th><?php esc_html_e('Actions', 'relayforge-wordpress'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($destinations as $dest) : ?>
                    <?php
                    $d_name       = (string) ($dest['name'] ?? '');
                    $d_country    = (string) ($dest['country'] ?? '');
                    $d_tour_count = (string) ($dest['tourCount'] ?? ($dest['toursCount'] ?? ''));
                    $d_best_time  = (string) ($dest['bestTime'] ?? ($dest['bestTimeToVisit'] ?? ''));
                    $d_slug       = (string) ($dest['slug'] ?? '');
                    $d_url        = $d_slug ? home_url('/destinations/' . $d_slug) : '';
                    $d_search     = strtolower($d_name . ' ' . $d_country);
                    ?>
                    <tr data-rf-row data-rf-search="<?php echo esc_attr($d_search); ?>">
                        <td><strong><?php echo esc_html($d_name ?: '—'); ?></strong></td>
                        <td><?php echo esc_html($d_country ?: '—'); ?></td>
                        <td><?php echo esc_html('' !== $d_tour_count ? $d_tour_count : '—'); ?></td>
                        <td><?php echo esc_html($d_best_time ?: '—'); ?></td>
                        <td>
                            <?php if ($d_url) : ?>
                                <a class="relayforge-content-action" href="<?php echo esc_url($d_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View page', 'relayforge-wordpress'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
