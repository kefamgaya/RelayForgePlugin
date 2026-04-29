<?php
if (! defined('ABSPATH')) {
    exit;
}

$fields = is_array($fields ?? null) ? $fields : [];
$form = is_array($form ?? null) ? $form : [];
$primary = sanitize_hex_color((string) ($form['themePrimaryColor'] ?? '')) ?: '';
$surface = sanitize_hex_color((string) ($form['themeSurfaceColor'] ?? '')) ?: '';
$background = sanitize_hex_color((string) ($form['themeBackgroundColor'] ?? '')) ?: '';
$style = [];
$is_multistep = ! empty($form['isMultiStep']) && count($fields) > 1;
if ($primary) {
    $style[] = '--rfp-form-primary:' . $primary;
}
if ($surface) {
    $style[] = '--rfp-form-surface:' . $surface;
}
if ($background) {
    $style[] = '--rfp-form-bg:' . $background;
}
?>
<div class="rfp rfp-form-embed"<?php echo $style ? ' style="' . esc_attr(implode(';', $style)) . '"' : ''; ?>>
    <div class="rfp-form-embed__inner">
        <?php if (! empty($title)) : ?><h3><?php echo esc_html($title); ?></h3><?php endif; ?>
        <?php if (! empty($form['description'])) : ?><p class="rfp-form-embed__description"><?php echo esc_html((string) $form['description']); ?></p><?php endif; ?>

        <?php if ('success' === ($result ?? '')) : ?>
            <p class="rfp-success"><?php echo esc_html((string) ($form['successMessage'] ?? __('Thanks. Your response has been received.', 'relayforge-wordpress'))); ?></p>
        <?php elseif ('error' === ($result ?? '')) : ?>
            <p class="rfp-notice"><?php echo esc_html($message ?: __('Submission failed. Please try again.', 'relayforge-wordpress')); ?></p>
        <?php elseif ('invalid' === ($result ?? '')) : ?>
            <p class="rfp-notice"><?php esc_html_e('Security check failed. Please reload and try again.', 'relayforge-wordpress'); ?></p>
        <?php endif; ?>

        <?php if (! empty($error)) : ?>
            <p class="rfp-notice"><?php echo esc_html($error); ?></p>
        <?php elseif (empty($slug) && empty($form_id)) : ?>
            <p class="rfp-notice"><?php esc_html_e('Missing RelayForge form slug or ID.', 'relayforge-wordpress'); ?></p>
        <?php elseif ('iframe' === ($mode ?? 'native')) : ?>
            <?php if (empty($embed_url)) : ?>
                <p class="rfp-notice"><?php esc_html_e('This form does not have an embed URL yet.', 'relayforge-wordpress'); ?></p>
            <?php else : ?>
                <iframe class="rfp-form-iframe" src="<?php echo esc_url($embed_url); ?>" width="100%" height="<?php echo esc_attr((string) ($height ?? 720)); ?>" loading="lazy" title="<?php echo esc_attr($title ?: __('RelayForge form', 'relayforge-wordpress')); ?>"></iframe>
            <?php endif; ?>
        <?php elseif (empty($fields)) : ?>
            <p class="rfp-notice"><?php esc_html_e('This RelayForge form has no published fields.', 'relayforge-wordpress'); ?></p>
        <?php else : ?>
            <form class="rfp-native-form rfp-dynamic-form<?php echo $is_multistep ? ' rfp-dynamic-form--multistep' : ''; ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" <?php echo $is_multistep ? 'data-rfp-dynamic-form="multistep"' : ''; ?>>
                <input type="hidden" name="action" value="relayforge_submit_form" />
                <input type="hidden" name="relayforge_form_slug" value="<?php echo esc_attr((string) $slug); ?>" />
                <input type="hidden" name="relayforge_form_id" value="<?php echo esc_attr((string) $form_id); ?>" />
                <input type="hidden" name="relayforge_nonce" value="<?php echo esc_attr((string) $nonce); ?>" />
                <input type="hidden" name="relayforge_redirect" value="<?php echo esc_url((string) $redirect_url); ?>" />

                <?php if ($is_multistep) : ?>
                    <div class="rfp-form-progress" aria-live="polite">
                        <span data-rfp-form-step-label><?php echo esc_html(sprintf(__('Step %1$d of %2$d', 'relayforge-wordpress'), 1, count($fields))); ?></span>
                        <div class="rfp-form-progress__track"><span data-rfp-form-progress style="width:<?php echo esc_attr((string) (100 / max(1, count($fields)))); ?>%"></span></div>
                    </div>
                <?php endif; ?>

                <?php $field_index = 0; ?>
                <?php foreach ($fields as $field) : ?>
                    <?php
                    if (! is_array($field)) {
                        continue;
                    }
                    $field_id = sanitize_key((string) ($field['id'] ?? ''));
                    if ('' === $field_id) {
                        continue;
                    }
                    $field_type = sanitize_key((string) ($field['type'] ?? 'short_text'));
                    $field_label = (string) ($field['label'] ?? $field_id);
                    $field_placeholder = (string) ($field['placeholder'] ?? '');
                    $required = ! empty($field['required']);
                    $options = is_array($field['options'] ?? null) ? array_values(array_filter(array_map('strval', $field['options']))) : [];
                    $input_name = 'rfp_fields[' . $field_id . ']';
                    $step_attrs = $is_multistep ? ' data-rfp-form-step="' . esc_attr((string) $field_index) . '"' . (0 !== $field_index ? ' hidden' : '') : '';
                    ?>
                    <div class="rfp-form-field rfp-form-field--<?php echo esc_attr($field_type); ?>"<?php echo $step_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                        <label id="rfp-label-<?php echo esc_attr($field_id); ?>" class="rfp-form-label" for="rfp-field-<?php echo esc_attr($field_id); ?>">
                            <?php echo esc_html($field_label); ?><?php if ($required) : ?><span aria-hidden="true"> *</span><?php endif; ?>
                        </label>

                        <?php if ('long_text' === $field_type) : ?>
                            <textarea id="rfp-field-<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($input_name); ?>" rows="5" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php required($required); ?>></textarea>
                        <?php elseif ('email' === $field_type) : ?>
                            <input id="rfp-field-<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($input_name); ?>" type="email" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php required($required); ?> />
                        <?php elseif ('phone' === $field_type) : ?>
                            <input id="rfp-field-<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($input_name); ?>" type="tel" placeholder="<?php echo esc_attr($field_placeholder ?: '+255 ...'); ?>" <?php required($required); ?> />
                        <?php elseif ('date' === $field_type) : ?>
                            <input id="rfp-field-<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($input_name); ?>" type="date" <?php required($required); ?> />
                        <?php elseif ('select' === $field_type) : ?>
                            <select id="rfp-field-<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($input_name); ?>" <?php required($required); ?>>
                                <option value=""><?php esc_html_e('Select an option', 'relayforge-wordpress'); ?></option>
                                <?php foreach ($options as $option) : ?><option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option><?php endforeach; ?>
                            </select>
                        <?php elseif ('radio' === $field_type) : ?>
                            <div class="rfp-choice-list" role="radiogroup" aria-labelledby="rfp-label-<?php echo esc_attr($field_id); ?>">
                                <?php foreach ($options as $index => $option) : ?>
                                    <label><input name="<?php echo esc_attr($input_name); ?>" type="radio" value="<?php echo esc_attr($option); ?>" <?php echo (0 === $index) ? required($required, true, false) : ''; ?> /> <span><?php echo esc_html($option); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ('checkbox' === $field_type) : ?>
                            <div class="rfp-choice-list" role="group" aria-labelledby="rfp-label-<?php echo esc_attr($field_id); ?>">
                                <?php foreach ($options as $option) : ?>
                                    <label><input name="<?php echo esc_attr($input_name); ?>[]" type="checkbox" value="<?php echo esc_attr($option); ?>" /> <span><?php echo esc_html($option); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <input id="rfp-field-<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($input_name); ?>" type="text" placeholder="<?php echo esc_attr($field_placeholder); ?>" <?php required($required); ?> />
                        <?php endif; ?>
                    </div>
                    <?php $field_index++; ?>
                <?php endforeach; ?>

                <?php if ($is_multistep) : ?>
                    <div class="rfp-form-nav">
                        <button class="rfp-button rfp-button--secondary" type="button" data-rfp-form-back disabled><?php esc_html_e('Back', 'relayforge-wordpress'); ?></button>
                        <button class="rfp-button" type="button" data-rfp-form-next><?php esc_html_e('Next', 'relayforge-wordpress'); ?></button>
                        <button class="rfp-button" type="submit" data-rfp-form-submit hidden><?php echo esc_html($button_text); ?></button>
                    </div>
                <?php else : ?>
                    <button class="rfp-button" type="submit"><?php echo esc_html($button_text); ?></button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>
