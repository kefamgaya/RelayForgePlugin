<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="rfp rfp-typography-cards rfp-tours-grid columns-<?php echo esc_attr((string) $columns); ?> <?php echo esc_attr((string) ($grid_class ?? '')); ?>" data-rf-template-version="<?php echo esc_attr((string) ($template_version ?? '')); ?>">
    <?php if (! empty($error)) : ?>
        <div class="rfp-notice"><?php echo esc_html($error); ?></div>
    <?php elseif (empty($destinations)) : ?>
        <div class="rfp-notice"><?php esc_html_e('No destinations available right now.', 'relayforge-wordpress'); ?></div>
    <?php else : ?>
        <?php foreach ($destinations as $destination) : ?>
            <?php
            $custom_card = '';
            if (isset($render_destination_card) && is_callable($render_destination_card)) {
                $custom_card = (string) call_user_func($render_destination_card, $destination);
            }
            ?>
            <?php if ('' !== $custom_card) : ?>
                <?php echo $custom_card; ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php
            $name = (string) ($destination['name'] ?? 'Destination');
            $image_url = (string) ($destination['imageUrl'] ?? ($destination['coverImageUrl'] ?? ''));
            if ('' === $image_url && ! empty($image_fallback)) {
                $image_url = (string) $image_fallback;
            }
            $dest_slug = relayforge_content_slug($destination, 'destination');
            $link = '' !== $dest_slug ? home_url('/destinations/' . $dest_slug) : '';
            $aria_label = ('assist' === (string) ($accessibility_mode ?? 'assist')) ? sprintf(__('View destination: %s', 'relayforge-wordpress'), $name) : '';
            ?>
            <article class="rfp-card <?php echo esc_attr((string) ($card_class ?? '')); ?>">
                <?php if ('' !== $image_url) : ?>
                    <img class="rfp-card__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="<?php echo esc_attr((string) ($image_loading ?? 'lazy')); ?>" />
                <?php endif; ?>
                <div class="rfp-card__body">
                    <h3 class="rfp-card__title"><?php echo esc_html($name); ?></h3>
                    <?php if (! empty($destination['shortDescription'])) : ?>
                        <p class="rfp-card__meta"><?php echo esc_html($destination['shortDescription']); ?></p>
                    <?php endif; ?>
                    <?php if ('' !== $link) : ?>
                        <a class="rfp-button <?php echo esc_attr((string) ($button_class ?? '')); ?>" href="<?php echo esc_url($link); ?>" data-rf-track="card-cta" data-rf-card-type="destination" data-rf-card-id="<?php echo esc_attr((string) ($destination['id'] ?? ($destination['slug'] ?? $name))); ?>" data-rf-template="default" <?php if ('' !== $aria_label) : ?>aria-label="<?php echo esc_attr($aria_label); ?>"<?php endif; ?>><?php echo esc_html($cta_text); ?></a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
