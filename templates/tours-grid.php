<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="rfp rfp-typography-cards rfp-tours-grid columns-<?php echo esc_attr((string) $columns); ?> <?php echo esc_attr((string) ($grid_class ?? '')); ?>" data-rf-template-version="<?php echo esc_attr((string) ($template_version ?? '')); ?>">
    <?php if (! empty($error)) : ?>
        <div class="rfp-notice"><?php echo esc_html($error); ?></div>
    <?php elseif (empty($tours)) : ?>
        <div class="rfp-notice"><?php esc_html_e('No tours available right now.', 'relayforge-wordpress'); ?></div>
    <?php else : ?>
        <?php foreach ($tours as $tour) : ?>
            <?php
            $custom_card = '';
            if (isset($render_tour_card) && is_callable($render_tour_card)) {
                $custom_card = (string) call_user_func($render_tour_card, $tour);
            }
            ?>
            <?php if ('' !== $custom_card) : ?>
                <?php echo $custom_card; ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php
            $title = (string) ($tour['title'] ?? 'Tour');
            $image_url = function_exists('relayforge_tour_cover_image_url') ? relayforge_tour_cover_image_url($tour) : '';
            if ('' === $image_url) {
                $tour_imgs = is_array($tour['images'] ?? null) ? array_values(array_filter(array_map('strval', $tour['images']))) : [];
                $image_url = $tour_imgs[0] ?? (string) ($tour['coverImageUrl'] ?? ($tour['imageUrl'] ?? ($tour['thumbnailUrl'] ?? '')));
            }
            if ('' === $image_url && ! empty($image_fallback)) {
                $image_url = (string) $image_fallback;
            }
            $tour_slug = relayforge_content_slug($tour, 'tour');
            $link = '' !== $tour_slug ? home_url('/tours/' . $tour_slug) : '';
            $aria_label = ('assist' === (string) ($accessibility_mode ?? 'assist')) ? sprintf(__('View tour: %s', 'relayforge-wordpress'), $title) : '';
            ?>
            <article class="rfp-card <?php echo esc_attr((string) ($card_class ?? '')); ?>">
                <?php if ('' !== $image_url) : ?>
                    <img class="rfp-card__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="<?php echo esc_attr((string) ($image_loading ?? 'lazy')); ?>" />
                <?php endif; ?>
                <div class="rfp-card__body">
                    <h3 class="rfp-card__title"><?php echo esc_html($title); ?></h3>
                    <?php if (! empty($tour['location'])) : ?>
                        <p class="rfp-card__meta"><?php echo esc_html($tour['location']); ?></p>
                    <?php endif; ?>
                    <?php if (! empty($show_price) && isset($tour['price'])) : ?>
                        <p class="rfp-card__price"><?php echo esc_html(number_format_i18n((float) $tour['price'], 2)); ?></p>
                    <?php endif; ?>
                    <?php if ('' !== $link) : ?>
                        <a class="rfp-button <?php echo esc_attr((string) ($button_class ?? '')); ?>" href="<?php echo esc_url($link); ?>" data-rf-track="card-cta" data-rf-card-type="tour" data-rf-card-id="<?php echo esc_attr((string) ($tour['id'] ?? ($tour['slug'] ?? $title))); ?>" data-rf-template="default" <?php if ('' !== $aria_label) : ?>aria-label="<?php echo esc_attr($aria_label); ?>"<?php endif; ?>><?php echo esc_html($cta_text); ?></a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
