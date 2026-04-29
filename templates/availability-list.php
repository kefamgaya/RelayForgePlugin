<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="rfp rfp-typography-cards rfp-availability-shortcode">
    <?php if (! empty($error)) : ?>
        <div class="rfp-notice"><?php echo esc_html($error); ?></div>
    <?php elseif (empty($availability)) : ?>
        <div class="rfp-notice"><?php esc_html_e('No availability found.', 'relayforge-wordpress'); ?></div>
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
</div>
