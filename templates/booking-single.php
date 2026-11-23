<?php

/**
 * Booking Single
 */

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Settings\Settings;

global $post;
$booking = new Booking( $post->ID );

/** @var Timeframe $timeframe */
$timeframe                    = $booking->getBookableTimeFrame();
$location                     = $booking->getLocation();
$item                         = $booking->getItem();
$user                         = $booking->getUserData();
$show_contactinfo_unconfirmed = Settings::getOption( 'commonsbooking_options_templates', 'show_contactinfo_unconfirmed' );
$text_hidden_contactinfo      = Settings::getOption( 'commonsbooking_options_templates', 'text_hidden-contactinfo' );
$formatted_user_info          = $booking::getFormattedUserInfo();
$admin_booking_id             = $booking->getMeta( 'admin_booking_id' );
$current_status               = $booking->post_status;
$internal_comment             = $booking->getMeta( 'internal-comment' );


do_action( 'commonsbooking_before_booking-single', $booking->ID, $booking );

echo commonsbooking_sanitizeHTML( $booking->bookingNotice() ); ?>

<?php if ( $current_status === 'unconfirmed' ) :
	$expiry_ts = strtotime( $booking->post_date ) + 10 * 60;
?>
<style>
@keyframes cb-pulse {
	0%, 100% { opacity: 1; transform: scale(1); }
	50%       { opacity: .4; transform: scale(1.35); }
}
.cb-pending-banner {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: var(--commonsbooking-spacer-big, 15px);
	margin-bottom: var(--commonsbooking-spacer-big, 15px);
	background: var(--commonsbooking-color-noticebg, #fff9c5);
	border-radius: var(--commonsbooking-radius, 8px);
	font-size: var(--commonsbooking-font-size-normal, 14px);
	flex-wrap: wrap;
}
.cb-pulse-dot {
	width: 12px; height: 12px;
	border-radius: 50%;
	background: var(--commonsbooking-color-warning, #ff9218);
	flex-shrink: 0;
	animation: cb-pulse 1.4s ease-in-out infinite;
}
.cb-pending-banner.cb-expired {
	background: var(--commonsbooking-color-error, #d5425c);
	color: #fff;
}
.cb-pending-banner.cb-expired .cb-pulse-dot {
	background: #fff;
	animation: none;
}
#cb-countdown-timer {
	font-weight: bold;
	font-variant-numeric: tabular-nums;
}
</style>

<div class="cb-pending-banner" id="cb-pending-banner" data-expiry="<?php echo (int) $expiry_ts; ?>">
	<span class="cb-pulse-dot"></span>
	<span>
		<?php echo esc_html__( 'Please confirm your booking — reserved for', 'commonsbooking' ); ?>
		<span id="cb-countdown-timer">10:00</span>
	</span>
</div>

<script>
(function () {
	var expiry  = <?php echo (int) $expiry_ts; ?> * 1000;
	var banner  = document.getElementById('cb-pending-banner');
	var display = document.getElementById('cb-countdown-timer');
	if (!banner || !display) return;

	function pad(n) { return n < 10 ? '0' + n : n; }

	function tick() {
		var remaining = Math.max(0, Math.floor((expiry - Date.now()) / 1000));
		var m = Math.floor(remaining / 60);
		var s = remaining % 60;
		display.textContent = pad(m) + ':' + pad(s);

		if (remaining <= 0) {
			banner.classList.add('cb-expired');
			display.parentElement.textContent =
				'<?php echo esc_js( __( 'This reservation has expired. Please start a new booking.', 'commonsbooking' ) ); ?>';
			clearInterval(timer);
		}
	}

	tick();
	var timer = setInterval(tick, 1000);
})();
</script>
<?php endif; ?>

	<div class="cb-wrapper cb-booking-item">
		<div class="cb-list-header">
			<?php echo commonsbooking_sanitizeHTML( $item->thumbnail( 'cb_listing_small' ) ); ?>
			<div class="cb-list-info">
				<h2><?php echo commonsbooking_sanitizeHTML( $item->title() ); ?></h2>
				<?php echo commonsbooking_sanitizeHTML( $location->excerpt() ); ?>
			</div>
		</div><!-- .cb-list-header -->

	</div>

	<div class="cb-wrapper cb-booking-datetime">
		<div class="cb-list-header cb-col-30-70 cb-datetime">
			<div><?php echo esc_html__( 'Pickup', 'commonsbooking' ); ?></div>
			<div><?php echo commonsbooking_sanitizeHTML( $booking->pickupDatetime() ); ?></div>
		</div><!-- .cb-datetime -->
		<div class="cb-list-content cb-datetime cb-col-30-70">
			<div><?php echo esc_html__( 'Return', 'commonsbooking' ); ?></div>
			<div><?php echo commonsbooking_sanitizeHTML( $booking->returnDatetime() ); ?></div>
		</div><!-- .cb-bookigcode -->
		<?php
		if (
			$booking->getBookingCode() && $booking->post_status == 'confirmed' &&
			( $booking->showBookingCodes() || ( $timeframe && $timeframe->showBookingCodes() ) )
		) { // start if bookingcode
			?>
			<div class="cb-list-content cb-datetime cb-col-30-70">
				<div><?php echo esc_html__( 'Booking Code', 'commonsbooking' ); ?></div>
				<div><strong><?php echo commonsbooking_sanitizeHTML( $booking->getBookingCode() ); ?></strong></div>
			</div>
			<?php
		} // end if bookingcode
		?>
	</div><!-- cb-booking-datetime -->

	<!-- Location -->
	<div class="cb-wrapper cb-booking-location">
		<div class="cb-list-header">
			<h3><?php echo esc_html__( 'Location: ', 'commonsbooking' ); ?><?php echo $location->title(); ?></h3>
		</div>
		<?php
		$location_address = $location->formattedAddressOneLine();
		if ( ! empty( $location_address ) ) {
			?>
			<div class="cb-list-content cb-address cb-col-30-70">
				<div><?php echo esc_html__( 'Address', 'commonsbooking' ); ?></div>
				<div><?php echo commonsbooking_sanitizeHTML( $location_address ); ?></div>
			</div><!-- .cb-address -->
			<?php
		}
		$location_pickup_instructions = $location->formattedPickupInstructionsOneLine();
		if ( ! empty( $location_pickup_instructions ) ) {
			?>
			<div class="cb-list-content cb-pickupinstructions cb-col-30-70">
				<div><?php echo esc_html__( 'Pickup instructions', 'commonsbooking' ); ?></div>
				<div><?php echo commonsbooking_sanitizeHTML( $location_pickup_instructions ); ?></div>
			</div><!-- .cb-cb-pickupinstructions -->
			<?php
		}
		// show contact details only after booking is confirmed or if options are set to show contactinfo even on unconfirmed booking status
		if ( $post->post_status == 'confirmed' or $show_contactinfo_unconfirmed == 'on' ) {
			?>
			<div class="cb-list-content cb-contact cb-col-30-70">
				<div><?php echo esc_html__( 'Contact', 'commonsbooking' ); ?></div>
				<div><?php echo commonsbooking_sanitizeHTML( $location->formattedContactInfoOneLine() ); ?></div>
			</div><!-- .cb-contact -->
			<?php
			// else; show info-text to inform user to confirm booking to see contact details
		} else {
			?>
			<div class="cb-list-content cb-contact cb-col-30-70">
				<div><?php echo esc_html__( 'Contact', 'commonsbooking' ); ?></div>
				<div><strong><?php echo commonsbooking_sanitizeHTML( $text_hidden_contactinfo ); ?></strong></div>
			</div><!-- .cb-contact -->
			<?php
			// end if booking == confirmed
		}
		?>
	</div><!-- cb-booking-location -->

	<!-- User TODO User Class so we can query everything the same way. -->
	<div class="cb-wrapper cb-booking-user">
		<div class="cb-list-header">
			<h3><?php echo esc_html__( 'Your profile', 'commonsbooking' ); ?></h3>
		</div>
		<?php
		if ( commonsbooking_isCurrentUserAdmin() && $admin_booking_id ) {
			?>
		<div class="cb-list-content cb-user cb-col-30-70">
			<div><?php echo esc_html__( 'Admin Booking by', 'commonsbooking' ); ?></div>
			<div>
			<?php
				$booking_admin = get_user_by( 'ID', $admin_booking_id );
				echo esc_html( $booking_admin->user_login . ' (' . $booking_admin->first_name . ' ' . $booking_admin->last_name . ')' );
			?>
		</div>
		</div>
		<!-- internal comment /-->
		<div class="cb-list-content cb-user cb-col-30-70">
			<div><?php echo esc_html__( 'Internal comment', 'commonsbooking' ); ?></div>
			<div>
			<?php
					echo nl2br( commonsbooking_sanitizeHTML( $internal_comment ) );
			?>
		</div>
		</div>
			<?php
		} // end if
		?>
		<div class="cb-list-content cb-user cb-col-30-70">
					<div><?php echo esc_html__( 'Your E-Mail', 'commonsbooking' ); ?></div>
			<div><?php echo commonsbooking_sanitizeHTML( CB::get( 'user', 'user_email' ) ); ?></div>
		</div>
		<div class="cb-list-content cb-user cb-col-30-70">
			<div><?php echo esc_html__( 'Your data', 'commonsbooking' ); ?></div>
			<div><a href="<?php echo get_edit_profile_url( $user->ID ); ?>"><?php echo esc_html( $user->first_name ) . ' ' . esc_html( $user->last_name ) . ' (' . esc_html( $user->user_login ) . ')'; ?> </a>
				<br>
				<?php echo commonsbooking_sanitizeHTML( $formatted_user_info ); ?>
			</div>
		</div>
	</div>

	<!-- Booking comment -->
<?php
$bookingCommentActive = Settings::getOption( 'commonsbooking_options_general', 'booking-comment-active' );

if ( $bookingCommentActive ) {
	$bookingCommentTitle       = Settings::getOption( 'commonsbooking_options_general', 'booking-comment-title' );
	$bookingCommentDescription = Settings::getOption( 'commonsbooking_options_general', 'booking-comment-description' );

	if ( $post->post_status == 'unconfirmed' ) {
		?>
		<div class="cb-wrapper cb-booking-comment">
			<div class="cb-list-header">
				<h3><?php echo commonsbooking_sanitizeHTML( $bookingCommentTitle ); ?></h3>
			</div>
			<p><?php echo commonsbooking_sanitizeHTML( $bookingCommentDescription ); ?></p>
			<div class="cb-list-content cb-comment cb-col-100">
				<div>
					<textarea id="cb-booking-comment"
								name="comment"><?php echo commonsbooking_sanitizeHTML( $booking->returnComment() ); ?></textarea>
				</div>
			</div>
		</div>
		<?php
	} elseif ( $booking->returnComment() ) {
		?>
			<div class="cb-wrapper cb-booking-comment">
				<div class="cb-list-header">
					<h3><?php echo commonsbooking_sanitizeHTML( $bookingCommentTitle ); ?></h3>
				</div>
				<div class="cb-list-content cb-comment cb-col-100">
					<div><?php echo commonsbooking_sanitizeHTML( $booking->returnComment() ); ?></div>
				</div>
			</div>
			<?php
	}
}
if ( $current_status && $current_status !== 'draft' ) {

	?>

	<!-- Buttons & Form action -->
	<div class="cb-action cb-wrapper">

		<?php
		$form_action = 'confirm';
		include COMMONSBOOKING_PLUGIN_DIR . 'templates/booking-single-form.php';

		// if booking is unconfirmed cancel link throws user back to item detail page
		if ( $booking->post_status() == 'unconfirmed' ) {
			$form_action = 'delete_unconfirmed';
			include COMMONSBOOKING_PLUGIN_DIR . 'templates/booking-single-form.php';
		} else {
			// if booking is confirmed we display the cancel booking button
			$form_action = 'cancel';
			include COMMONSBOOKING_PLUGIN_DIR . 'templates/booking-single-form.php';
		}
		?>
	</div>
	<?php
}

do_action( 'commonsbooking_after_booking-single', $booking->ID, $booking );

?>
