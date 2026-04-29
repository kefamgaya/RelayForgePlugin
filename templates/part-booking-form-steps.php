<?php
/**
 * Inline booking wizard (sidebar + modal). Three steps aligned with app-style flow:
 * Date & guests, Your details, Review & send.
 *
 * @var array  $tour
 * @var int    $min_group
 * @var int    $max_group
 */

if (! defined('ABSPATH')) {
    exit;
}

$min_guests = max(1, (int) $min_group);
$max_party    = isset($max_group) && (int) $max_group > 0 ? min(99, (int) $max_group) : 99;
$min_date     = gmdate('Y-m-d');

$pricing_model_esc = isset($pricing_model) ? (string) $pricing_model : 'per_person';
$unit_price        = isset($tour['price']) ? (float) $tour['price'] : 0.0;
$currency_esc      = isset($currency) ? (string) $currency : '';
$bgs_int           = isset($base_group_size) ? (int) $base_group_size : 0;
$bgp               = isset($base_group_price) ? (float) $base_group_price : 0.0;
$xpp               = isset($extra_person_price) ? (float) $extra_person_price : 0.0;

$estimate_flag =
	! empty( $show_price )
	&& (
		( 'group' !== $pricing_model_esc && $unit_price > 0 )
		|| ( 'group' === $pricing_model_esc && (
			( $bgp > 0 && $bgs_int > 0 )
			|| $unit_price > 0
		) )
	);

$default_adults = min($max_party, max(1, $min_guests));

$booking_steps       = 3;
$rfp_calendar_dom_id = 'rfp-cal-' . str_replace('.', '', uniqid('', true));

?>
<div
	class="rfp-bms-steps"
	data-rfp-min-guests="<?php echo esc_attr((string) $min_guests); ?>"
	data-rfp-max-party="<?php echo esc_attr((string) $max_party); ?>"
	data-rfp-estimate-enabled="<?php echo $estimate_flag ? '1' : '0'; ?>"
	data-rfp-pricing-model="<?php echo esc_attr($pricing_model_esc); ?>"
	data-rfp-price="<?php echo esc_attr((string) $unit_price); ?>"
	data-rfp-base-group-size="<?php echo esc_attr((string) $bgs_int); ?>"
	data-rfp-base-group-price="<?php echo esc_attr((string) $bgp); ?>"
	data-rfp-extra-person-price="<?php echo esc_attr((string) $xpp); ?>"
	data-rfp-currency="<?php echo esc_attr($currency_esc); ?>"
	data-rfp-min-date="<?php echo esc_attr($min_date); ?>"
	data-rfp-date-empty-msg="<?php echo esc_attr(__('Tap to choose a date', 'relayforge-wordpress')); ?>"
>
<div class="rfp-bms__dots" aria-hidden="true">
	<?php
	for ($rfp_sb = 0; $rfp_sb < $booking_steps; $rfp_sb++) {
		echo '<span class="rfp-bms__dot"></span>';
	}
	?>
</div>
<p class="rfp-bms__label"></p>

<!-- Step 1: Date & guests -->
<div class="rfp-bms__step rfp-bms__step--single rfp-bms__step--date-guests" data-rfp-step="1">
	<p class="rfp-bms__hint"><?php esc_html_e('When would you like to go — and how many travellers?', 'relayforge-wordpress'); ?></p>
	<?php if ( ! empty( $show_price ) ) : ?>
		<details class="rfp-bms__pricing-details">
			<summary><?php esc_html_e('How pricing works for this listing', 'relayforge-wordpress'); ?></summary>
			<p>
				<?php
				if ( 'group' === $pricing_model_esc ) {
					esc_html_e(
						'RelayForge group base plus extra: a fixed amount covers your base party size; each extra traveller adds the listed per-person extra (or per-person rate when extras are not set).',
						'relayforge-wordpress'
					);
				} else {
					esc_html_e(
						'RelayForge flat per person: estimated total is per-person price × number of travellers.',
						'relayforge-wordpress'
					);
				}
				?>
			</p>
		</details>
	<?php endif; ?>
	<input type="hidden" name="preferred_date" value="" autocomplete="off" />

	<div class="rfp-bms__date-shell">
		<button
			type="button"
			class="rfp-bms__date-trigger"
			data-rfp-date-trigger
			aria-expanded="false"
			aria-controls="<?php echo esc_attr($rfp_calendar_dom_id); ?>"
			aria-haspopup="dialog"
		>
			<span class="screen-reader-text"><?php esc_html_e('Preferred travel date', 'relayforge-wordpress'); ?></span>
			<span class="rfp-bms__date-trigger-label" aria-hidden="true"><?php esc_html_e('Travel date', 'relayforge-wordpress'); ?></span>
			<span class="rfp-bms__date-value" data-rfp-date-summary><?php esc_html_e('Tap to choose a date', 'relayforge-wordpress'); ?></span>
		</button>
		<div
			class="rfp-bms__calendar-panel"
			data-rfp-calendar-panel
			hidden
			role="dialog"
			aria-modal="false"
			aria-label="<?php esc_attr_e('Choose travel date', 'relayforge-wordpress'); ?>"
			id="<?php echo esc_attr($rfp_calendar_dom_id); ?>"
			tabindex="-1"
		>
			<div class="rfp-bms__calendar-head">
				<button type="button" class="rfp-bms__calendar-prev" data-rfp-cal-prev aria-label="<?php esc_attr_e('Previous month', 'relayforge-wordpress'); ?>">
					&#8249;
				</button>
				<div class="rfp-bms__calendar-title" data-rfp-cal-title></div>
				<button type="button" class="rfp-bms__calendar-next" data-rfp-cal-next aria-label="<?php esc_attr_e('Next month', 'relayforge-wordpress'); ?>">
					&#8250;
				</button>
			</div>
			<div class="rfp-bms__calendar-weekdays" aria-hidden="true"></div>
			<div class="rfp-bms__calendar-grid" data-rfp-cal-grid></div>
		</div>
	</div>

	<input type="hidden" name="number_of_people" value="<?php echo esc_attr((string) max(1, $default_adults)); ?>" autocomplete="off" />
	<input type="hidden" name="rfp_adults" value="<?php echo esc_attr((string) max(1, $default_adults)); ?>" autocomplete="off" />
	<input type="hidden" name="rfp_children" value="0" autocomplete="off" />

	<div class="rfp-bms__guest-block" role="group" aria-label="<?php esc_attr_e('Party size', 'relayforge-wordpress'); ?>">
		<div class="rfp-bms__guest-row">
			<span class="rfp-bms__guest-label"><?php esc_html_e('Adults', 'relayforge-wordpress'); ?></span>
			<div class="rfp-bms__stepper">
				<button type="button" class="rfp-bms__stepper-btn" data-rfp-stepper-target="adults" data-rfp-stepper-delta="-1" aria-label="<?php esc_attr_e('Fewer adults', 'relayforge-wordpress'); ?>">&#x2212;</button>
				<span class="rfp-bms__stepper-num" aria-hidden="true" data-rfp-display-adults><?php echo (int) $default_adults; ?></span>
				<button type="button" class="rfp-bms__stepper-btn" data-rfp-stepper-target="adults" data-rfp-stepper-delta="1" aria-label="<?php esc_attr_e('More adults', 'relayforge-wordpress'); ?>">+</button>
			</div>
		</div>
		<div class="rfp-bms__guest-row">
			<span class="rfp-bms__guest-label"><?php esc_html_e('Children', 'relayforge-wordpress'); ?></span>
			<div class="rfp-bms__stepper">
				<button type="button" class="rfp-bms__stepper-btn" data-rfp-stepper-target="children" data-rfp-stepper-delta="-1" aria-label="<?php esc_attr_e('Fewer children', 'relayforge-wordpress'); ?>">&#x2212;</button>
				<span class="rfp-bms__stepper-num" aria-hidden="true" data-rfp-display-children="">0</span>
				<button type="button" class="rfp-bms__stepper-btn" data-rfp-stepper-target="children" data-rfp-stepper-delta="1" aria-label="<?php esc_attr_e('More children', 'relayforge-wordpress'); ?>">+</button>
			</div>
		</div>
	</div>

	<?php
	$has_max_bound   = isset( $max_group ) && (int) $max_group > 0;
	$show_party_line = ( $min_guests > 1 ) || $has_max_bound;
	?>
	<p class="rfp-bms__party-bounds rfp-bms__fineprint" <?php echo $show_party_line ? '' : 'hidden'; ?> data-rfp-party-bounds>
		<?php
		if ( $min_guests > 1 && $has_max_bound ) {
			echo esc_html(
				sprintf(
					/* translators: 1: min guests, 2: max guests */
					__( 'Travellers: %1$d–%2$d (tour limits).', 'relayforge-wordpress' ),
					(int) $min_guests,
					(int) $max_party
				)
			);
		} elseif ( $min_guests > 1 ) {
			echo esc_html(
				sprintf(
					/* translators: %d: minimum guests */
					__( 'Minimum %d travellers for this tour.', 'relayforge-wordpress' ),
					(int) $min_guests
				)
			);
		} elseif ( $has_max_bound ) {
			echo esc_html(
				sprintf(
					/* translators: %d: maximum guests */
					__( 'Maximum %d travellers for this tour.', 'relayforge-wordpress' ),
					(int) $max_party
				)
			);
		}
		?>
	</p>
	<p class="rfp-bms__party-line" data-rfp-party-line aria-live="polite"></p>
	<div class="rfp-bms__estimate" data-rfp-estimate-wrap hidden>
		<span class="rfp-bms__estimate-label"><?php esc_html_e('Estimated total', 'relayforge-wordpress'); ?></span>
		<span class="rfp-bms__estimate-amount" data-rfp-estimate-amount>&#8212;</span>
	</div>
</div>

<!-- Step 2: Details -->
<div class="rfp-bms__step rfp-bms__step--details" data-rfp-step="2" hidden>
	<p class="rfp-bms__hint"><?php esc_html_e('How can we reach you?', 'relayforge-wordpress'); ?></p>
	<label class="rfp-bms__field">
		<span><?php esc_html_e('Full name', 'relayforge-wordpress'); ?> <span class="rfp-bms__req"><?php esc_html_e('*', 'relayforge-wordpress'); ?></span></span>
		<input type="text" name="customer_name" autocomplete="name" />
	</label>
	<label class="rfp-bms__field">
		<span><?php esc_html_e('Email', 'relayforge-wordpress'); ?> <span class="rfp-bms__req"><?php esc_html_e('*', 'relayforge-wordpress'); ?></span></span>
		<input type="email" name="customer_email" autocomplete="email" />
	</label>
	<label class="rfp-bms__field">
		<span><?php esc_html_e('Phone', 'relayforge-wordpress'); ?> <span class="rfp-bms__opt"><?php esc_html_e('Optional', 'relayforge-wordpress'); ?></span></span>
		<input type="tel" name="customer_phone" autocomplete="tel" />
	</label>
	<label class="rfp-bms__field">
		<span><?php esc_html_e('Notes', 'relayforge-wordpress'); ?> <span class="rfp-bms__opt"><?php esc_html_e('Optional', 'relayforge-wordpress'); ?></span></span>
		<textarea name="message" rows="3"></textarea>
	</label>
</div>

<!-- Step 3: Review -->
<div class="rfp-bms__step rfp-bms__step--review" data-rfp-step="3" hidden>
	<p class="rfp-bms__hint"><?php esc_html_e('Review your inquiry before we send it.', 'relayforge-wordpress'); ?></p>
	<dl class="rfp-bms__review">
		<div><dt><?php esc_html_e('Date', 'relayforge-wordpress'); ?></dt><dd data-rfp-rv="date">&#8212;</dd></div>
		<div><dt><?php esc_html_e('Guests', 'relayforge-wordpress'); ?></dt><dd data-rfp-rv="guests">&#8212;</dd></div>
		<div data-rfp-rv-row="total" hidden><dt><?php esc_html_e('Total', 'relayforge-wordpress'); ?></dt><dd data-rfp-rv="total">&#8212;</dd></div>
		<div><dt><?php esc_html_e('Contact', 'relayforge-wordpress'); ?></dt><dd data-rfp-rv="contact">&#8212;</dd></div>
		<div data-rfp-rv-row="notes" hidden><dt><?php esc_html_e('Notes', 'relayforge-wordpress'); ?></dt><dd data-rfp-rv="message">&#8212;</dd></div>
	</dl>
</div>

</div><!-- .rfp-bms-steps -->

<p class="rfp-bms__error" role="alert" hidden></p>

<div class="rfp-bms__nav">
	<div class="rfp-bms__nav-primary">
		<button type="button" class="rfp-bms__back" data-rfp-bms-back hidden><?php esc_html_e('Back', 'relayforge-wordpress'); ?></button>
		<button type="button" class="rfp-bms__next" data-rfp-bms-next><?php esc_html_e('Continue', 'relayforge-wordpress'); ?></button>
	</div>
	<button type="submit" class="rfp-button rfp-bms__submit rfp-bms__submit-floor" hidden data-rfp-bms-send><?php esc_html_e('Send inquiry', 'relayforge-wordpress'); ?></button>
</div>
