<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="rfp rfp-typography-cards rfp-reviews-list">
    <?php if (! empty($error)) : ?>
        <div class="rfp-notice"><?php echo esc_html($error); ?></div>
    <?php elseif (empty($reviews)) : ?>
        <div class="rfp-notice"><?php esc_html_e('No reviews available yet.', 'relayforge-wordpress'); ?></div>
    <?php else : ?>
        <?php foreach ($reviews as $review) : ?>
            <article class="rfp-review">
                <div class="rfp-review__header">
                    <strong><?php echo esc_html($review['reviewerName'] ?? 'Guest'); ?></strong>
                    <?php if (isset($review['rating'])) : ?>
                        <span><?php echo esc_html((string) $review['rating']); ?>/5</span>
                    <?php endif; ?>
                </div>
                <p><?php echo esc_html($review['comment'] ?? ''); ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
