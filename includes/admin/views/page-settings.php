<?php
/**
 * Settings page with tabbed form.
 * Expects: $settings, $fields, $tabs, $errors, $saved, $is_new, $library_json, $this (RelayForge_Settings).
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap relayforge-admin rf-admin-shell">
    <div class="relayforge-admin__hero">
        <h1><?php echo esc_html__('RelayForge settings', 'relayforge-wordpress'); ?></h1>
        <p class="relayforge-admin__sub"><?php echo esc_html__('Connect your site, choose how listings look, and tune optional design and tracking — no code required for the basics.', 'relayforge-wordpress'); ?></p>
    </div>

    <?php if ($saved) : ?>
        <div class="relayforge-admin__notices">
            <div class="relayforge-admin__notice relayforge-admin__notice--success"><?php echo esc_html__('Settings saved.', 'relayforge-wordpress'); ?></div>
        </div>
    <?php elseif ($is_new) : ?>
        <div class="relayforge-admin__notices">
            <div class="relayforge-admin__notice relayforge-admin__notice--onboarding">
                <strong><?php echo esc_html__('Welcome — start with two quick steps:', 'relayforge-wordpress'); ?></strong><br>
                <?php echo esc_html__('1. Open the Connection tab and paste your secret key.', 'relayforge-wordpress'); ?><br>
                <?php echo esc_html__('2. Open the Card layouts tab to pick how tour and destination cards look.', 'relayforge-wordpress'); ?>
            </div>
        </div>
    <?php elseif (! empty($errors)) : ?>
        <div class="relayforge-admin__notices">
            <?php foreach ($errors as $error) : ?>
                <?php $class = ('error' === ($error['type'] ?? '')) ? ' relayforge-admin__notice--error' : ''; ?>
                <div class="relayforge-admin__notice<?php echo esc_attr($class); ?>"><?php echo esc_html((string) ($error['message'] ?? '')); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="relayforge-admin__workspace">
        <div class="relayforge-admin__tabs" role="tablist" aria-label="<?php echo esc_attr__('RelayForge settings sections', 'relayforge-wordpress'); ?>">
            <?php foreach ($tabs as $index => $tab) : ?>
                <button
                    type="button"
                    role="tab"
                    class="relayforge-admin__tab<?php echo 0 === $index ? ' is-active' : ''; ?>"
                    id="relayforge-tab-<?php echo esc_attr($tab['id']); ?>"
                    data-rf-tab="<?php echo esc_attr($tab['id']); ?>"
                    aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                    tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
                    aria-controls="relayforge-panel-<?php echo esc_attr($tab['id']); ?>"
                >
                    <span class="dashicons dashicons-<?php echo esc_attr((string) ($tab['icon'] ?? 'admin-generic')); ?>" aria-hidden="true"></span>
                    <span><?php echo esc_html($tab['label']); ?></span>
                    <small><?php echo esc_html($tab['nav_hint']); ?></small>
                </button>
            <?php endforeach; ?>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields($this::OPTION_KEY); ?>
            <input type="hidden" name="<?php echo esc_attr($this::OPTION_KEY); ?>[base_url]" value="<?php echo esc_attr((string) ($settings['base_url'] ?? 'https://relay.forgelabspro.com')); ?>" />

            <?php foreach ($tabs as $index => $tab) : ?>
                <section
                    role="tabpanel"
                    class="relayforge-admin__panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
                    id="relayforge-panel-<?php echo esc_attr($tab['id']); ?>"
                    data-rf-panel="<?php echo esc_attr($tab['id']); ?>"
                    aria-labelledby="relayforge-tab-<?php echo esc_attr($tab['id']); ?>"
                    <?php echo 0 !== $index ? ' hidden' : ''; ?>
                >
                    <div class="relayforge-admin__panel-head">
                        <h2><span class="dashicons dashicons-<?php echo esc_attr((string) ($tab['icon'] ?? 'admin-generic')); ?>" aria-hidden="true"></span><?php echo esc_html($tab['title']); ?></h2>
                        <p><?php echo esc_html($tab['description']); ?></p>
                    </div>
                    <div class="relayforge-admin__grid">
                        <?php foreach ($tab['fields'] as $field_key) : ?>
                            <?php if (! isset($fields[$field_key])) { continue; } ?>
                            <?php $is_full = 'textarea' === ($fields[$field_key]['type'] ?? '') ? ' relayforge-admin__field--full' : ''; ?>
                            <div class="relayforge-admin__field<?php echo esc_attr($is_full); ?>">
                                <?php $this->render_custom_field($field_key, $fields[$field_key], $settings); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ('cards' === $tab['id']) : ?>
                        <div class="relayforge-admin__field relayforge-admin__field--full" style="margin-top:14px;">
                            <label><?php echo esc_html__('Visual template gallery', 'relayforge-wordpress'); ?></label>
                            <?php $this->render_template_gallery(); ?>
                        </div>
                        <div class="relayforge-admin__field relayforge-admin__field--full" style="margin-top:14px;">
                            <?php $this->render_live_preview(); ?>
                        </div>
                    <?php elseif ('code' === $tab['id']) : ?>
                        <div class="relayforge-admin__field relayforge-admin__field--full" style="margin-top:14px;">
                            <label><?php echo esc_html__('Export template library (JSON)', 'relayforge-wordpress'); ?></label>
                            <textarea readonly rows="8"><?php echo esc_textarea($library_json); ?></textarea>
                            <small><?php echo esc_html__('Copy this to import the same pack on another site (Developer → Import).', 'relayforge-wordpress'); ?></small>
                        </div>
                        <div class="relayforge-admin__field relayforge-admin__field--full" style="margin-top:14px;">
                            <?php $this->render_cards_section(); ?>
                        </div>
                    <?php elseif ('diagnostics' === $tab['id']) : ?>
                        <div class="relayforge-admin__field relayforge-admin__field--full" style="margin-top:14px;">
                            <?php $this->render_diagnostics_panel(); ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <div class="relayforge-admin__actions">
                <button type="submit" class="relayforge-admin__save"><?php echo esc_html__('Save settings', 'relayforge-wordpress'); ?></button>
                <span class="relayforge-admin__hint"><?php echo esc_html__('Shortcodes do not change here — only connection, appearance, and optional code.', 'relayforge-wordpress'); ?></span>
            </div>
        </form>
    </div>
</div>
