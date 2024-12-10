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


do_action( 'commonsbooking_before_booking-single' );

echo commonsbooking_sanitizeHTML( $booking->bookingNotice() ); ?>

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

do_action( 'commonsbooking_after_booking-single' );

?>
