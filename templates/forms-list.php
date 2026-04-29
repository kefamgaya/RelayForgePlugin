<?php
if (! defined('ABSPATH')) {
    exit;
}

$forms = is_array($forms ?? null) ? $forms : [];
?>
<div class="rfp rfp-forms-list">
    <?php if (! empty($error)) : ?>
        <p class="rfp-notice"><?php echo esc_html((string) $error); ?></p>
    <?php elseif (empty($forms)) : ?>
        <p class="rfp-notice"><?php esc_html_e('No RelayForge forms are published yet.', 'relayforge-wordpress'); ?></p>
    <?php else : ?>
        <div class="rfp-forms-directory">
            <?php foreach ($forms as $form) : ?>
                <?php
                if (! is_array($form)) {
                    continue;
                }
                $title = (string) ($form['title'] ?? __('Form', 'relayforge-wordpress'));
                $description = (string) ($form['description'] ?? '');
                $slug = sanitize_title((string) ($form['slug'] ?? ''));
                $id = (string) ($form['formId'] ?? ($form['id'] ?? ''));
                $shortcode = '[relayforge_form ' . ($id ? 'id="' . $id . '"' : 'slug="' . $slug . '"') . ']';
                ?>
                <article class="rfp-form-list-card">
                    <div>
                        <h3><?php echo esc_html($title); ?></h3>
                        <?php if ($description) : ?><p><?php echo esc_html($description); ?></p><?php endif; ?>
                        <small><?php echo esc_html($id ? sprintf(__('Form ID: %s', 'relayforge-wordpress'), $id) : sprintf(__('Slug: %s', 'relayforge-wordpress'), $slug)); ?></small>
                    </div>
                    <?php if ($slug || $id) : ?>
                        <code><?php echo esc_html($shortcode); ?></code>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
