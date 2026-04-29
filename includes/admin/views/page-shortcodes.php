<?php
/**
 * Shortcode reference (RelayForge_Settings $this unused; consistent shell).
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}

$tours_ex      = '[relayforge_tours limit="6" columns="3" template="default" show_price="yes"]';
$dests_ex      = '[relayforge_destinations limit="6" columns="3" template="default"]';
$packages_ex   = '[relayforge_packages show_price="yes"]';
$form_ex       = '[relayforge_form slug="contact-form" mode="native"]';
$forms_ex      = '[relayforge_forms]';

?>
<div class="wrap relayforge-admin rf-admin-shell">
    <div class="relayforge-admin__hero">
        <div>
            <h1><?php esc_html_e('Shortcode reference', 'relayforge-wordpress'); ?></h1>
            <p class="relayforge-admin__sub"><?php esc_html_e('Copy a line below and paste it into a page or post (use a Shortcode block in the editor).', 'relayforge-wordpress'); ?></p>
        </div>
    </div>

    <div class="rf-sc-block">
        <h2>[relayforge_tours]</h2>
        <p><?php esc_html_e('Shows a grid of tours from your RelayForge account.', 'relayforge-wordpress'); ?></p>
        <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($tours_ex); ?>">
            <code><?php echo esc_html($tours_ex); ?></code>
            <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
        </div>
        <table class="rf-sc-attrs">
            <thead><tr><th><?php esc_html_e('Attribute', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Default', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Description', 'relayforge-wordpress'); ?></th></tr></thead>
            <tbody>
                <tr><td><code>limit</code></td><td><code>12</code></td><td><?php esc_html_e('How many tours to show at most.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>columns</code></td><td><code>3</code></td><td><?php esc_html_e('Columns in the grid (1–4).', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>template</code></td><td><code>default</code></td><td><?php esc_html_e('Card style preset on the Cards tab.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>show_price</code></td><td><code>yes</code></td><td><?php esc_html_e('Show prices: yes or no.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>destination</code></td><td></td><td><?php esc_html_e('Limit to one destination slug (for example destination="zanzibar").', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>category</code></td><td></td><td><?php esc_html_e('Tour category slug.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>featured</code></td><td></td><td><?php esc_html_e('Use featured="yes" for featured tours only.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>custom_css_class</code></td><td></td><td><?php esc_html_e('Extra class on the grid wrapper.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>cta_text</code></td><td><?php esc_html_e('View tour', 'relayforge-wordpress'); ?></td><td><?php esc_html_e('Button label on each card.', 'relayforge-wordpress'); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="rf-sc-block">
        <h2>[relayforge_destinations]</h2>
        <p><?php esc_html_e('Shows a grid of destinations.', 'relayforge-wordpress'); ?></p>
        <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($dests_ex); ?>">
            <code><?php echo esc_html($dests_ex); ?></code>
            <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
        </div>
        <table class="rf-sc-attrs">
            <thead><tr><th><?php esc_html_e('Attribute', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Default', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Description', 'relayforge-wordpress'); ?></th></tr></thead>
            <tbody>
                <tr><td><code>limit</code></td><td><code>12</code></td><td><?php esc_html_e('Maximum destinations to show.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>columns</code></td><td><code>3</code></td><td><?php esc_html_e('Columns in the grid (1–4).', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>template</code></td><td><code>default</code></td><td><?php esc_html_e('Card style preset on the Cards tab.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>custom_css_class</code></td><td></td><td><?php esc_html_e('Extra class on the grid wrapper.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>cta_text</code></td><td><?php esc_html_e('Explore', 'relayforge-wordpress'); ?></td><td><?php esc_html_e('Button label on each card.', 'relayforge-wordpress'); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="rf-sc-block">
        <h2>[relayforge_packages]</h2>
        <p><?php esc_html_e('Lists packages grouped by type.', 'relayforge-wordpress'); ?></p>
        <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($packages_ex); ?>">
            <code><?php echo esc_html($packages_ex); ?></code>
            <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
        </div>
        <table class="rf-sc-attrs">
            <thead><tr><th><?php esc_html_e('Attribute', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Default', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Description', 'relayforge-wordpress'); ?></th></tr></thead>
            <tbody>
                <tr><td><code>show_price</code></td><td><code>yes</code></td><td><?php esc_html_e('Show prices: yes or no.', 'relayforge-wordpress'); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="rf-sc-block">
        <h2>[relayforge_form]</h2>
        <p><?php esc_html_e('Renders one published RelayForge tenant form. Native mode uses WordPress markup and submits to RelayForge via the API.', 'relayforge-wordpress'); ?></p>
        <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($form_ex); ?>">
            <code><?php echo esc_html($form_ex); ?></code>
            <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
        </div>
        <table class="rf-sc-attrs">
            <thead><tr><th><?php esc_html_e('Attribute', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Default', 'relayforge-wordpress'); ?></th><th><?php esc_html_e('Description', 'relayforge-wordpress'); ?></th></tr></thead>
            <tbody>
                <tr><td><code>slug</code></td><td></td><td><?php esc_html_e('Published form slug from RelayForge, such as contact-form.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>id</code></td><td></td><td><?php esc_html_e('Published form ID. Use this instead of slug if preferred.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>mode</code></td><td><code>native</code></td><td><?php esc_html_e('native or iframe.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>title</code></td><td><?php esc_html_e('Form title', 'relayforge-wordpress'); ?></td><td><?php esc_html_e('Optional title override.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>button_text</code></td><td><?php esc_html_e('Form submit label', 'relayforge-wordpress'); ?></td><td><?php esc_html_e('Optional submit button override.', 'relayforge-wordpress'); ?></td></tr>
                <tr><td><code>height</code></td><td><code>720</code></td><td><?php esc_html_e('Iframe height only.', 'relayforge-wordpress'); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="rf-sc-block">
        <h2>[relayforge_forms]</h2>
        <p><?php esc_html_e('Lists published RelayForge forms and their copyable single-form shortcode.', 'relayforge-wordpress'); ?></p>
        <div class="rf-copy-field" data-rf-copy="<?php echo esc_attr($forms_ex); ?>">
            <code><?php echo esc_html($forms_ex); ?></code>
            <button type="button" class="button rf-copy-btn"><?php esc_html_e('Copy', 'relayforge-wordpress'); ?></button>
        </div>
    </div>

    <div class="rf-sc-tip">
        <h3><?php esc_html_e('Block editor', 'relayforge-wordpress'); ?></h3>
        <p><?php esc_html_e('Add a “Shortcode” block, paste the shortcode, then publish or update the page. In the classic editor, paste directly into the content area.', 'relayforge-wordpress'); ?></p>
    </div>
</div>
