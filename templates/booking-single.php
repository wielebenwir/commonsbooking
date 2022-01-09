<?php

/**
 * Booking Single
 */

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;

global $post;
$booking       = new \CommonsBooking\Model\Booking($post->ID);

/** @var \CommonsBooking\Model\Timeframe $timeframe */
$timeframe     = $booking->getBookableTimeFrame();
$location      = $booking->getLocation();
$item          = $booking->getItem();
$show_contactinfo_unconfirmed = Settings::getOption('commonsbooking_options_templates', 'show_contactinfo_unconfirmed');
$text_hidden_contactinfo = Settings::getOption('commonsbooking_options_templates', 'text_hidden-contactinfo');
?>

<?php echo $booking->bookingNotice(); ?>

<div class="cb-wrapper cb-booking-item">
    <div class="cb-list-header">
	<?php echo $item->thumbnail(); ?>
    <div class="cb-list-info">
        <h2><?php echo $item->title(); ?></h2>
        <?php echo $location->excerpt(); ?>
    </div>
</div><!-- .cb-list-header -->

</div>

<div class="cb-wrapper cb-booking-datetime">
    <div class="cb-list-header cb-col-30-70 cb-datetime">
        <div><?php echo esc_html__('Pickup', 'commonsbooking'); ?></div>
        <div><?php echo $booking->pickupDatetime(); ?></div>
    </div><!-- .cb-datetime -->
    <div class="cb-list-content cb-datetime cb-col-30-70">
        <div><?php echo esc_html__('Return', 'commonsbooking'); ?></div>
        <div><?php echo $booking->returnDatetime(); ?></div>
    </div><!-- .cb-bookigcode -->
    <?php
    if (
        $booking->getBookingCode() && $booking->post_status == "confirmed" &&
        ( $booking->showBookingCodes() || ($timeframe && $timeframe->showBookingCodes()) )
    )
    { // start if bookingcode
    ?>
        <div class="cb-list-content cb-datetime cb-col-30-70">
            <div><?php echo esc_html__('Booking Code', 'commonsbooking'); ?></div>
            <div><strong><?php echo $booking->getBookingCode(); ?></strong></div>
        </div>
    <?php
    } // end if bookingcode
    ?>
</div><!-- cb-booking-datetime -->

<!-- Location -->
<div class="cb-wrapper cb-booking-location">
    <div class="cb-list-header">
        <h3><?php echo esc_html__('Location: ', 'commonsbooking'); ?> <?php echo $location->title();?></h3>
    </div>
    <div class="cb-list-content cb-address cb-col-30-70">
        <div><?php echo esc_html__('Address', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedAddressOneLine(); ?></div>

    </div><!-- .cb-address -->
    <div class="cb-list-content cb-pickupinstructions cb-col-30-70">
        <div><?php echo esc_html__('Pickup instructions', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedPickupInstructionsOneLine(); ?></div>
    </div><!-- .cb-cb-pickupinstructions -->
    <?php 
    // show contact details only after booking is confirmed or if options are set to show contactinfo even on unconfirmed booking status
    if($post->post_status == 'confirmed' OR $show_contactinfo_unconfirmed == 'on') { ?>
    <div class="cb-list-content cb-contact cb-col-30-70">
        <div><?php echo esc_html__('Contact', 'commonsbooking'); ?></div>
        <div><?php echo $location->formattedContactInfoOneLine(); ?></div>
    </div><!-- .cb-contact -->
<?php
// else; show info-text to inform user to confirm booking to see contact details
} else {
?>
    <div class="cb-list-content cb-contact cb-col-30-70">
        <div><?php echo esc_html__('Contact', 'commonsbooking'); ?></div>
        <div><strong><?php echo $text_hidden_contactinfo; ?></strong></div>
    </div><!-- .cb-contact -->
<?php 
// end if booking == confirmed
}
?>
</div><!-- cb-booking-location -->

<!-- User TODO User Class so we can query everything the same way. -->
<div class="cb-wrapper cb-booking-user">
    <div class="cb-list-header">
        <h3><?php echo esc_html__('Your profile', 'commonsbooking'); ?></h3>
    </div>
    <div class="cb-list-content cb-user cb-col-30-70">
        <div><?php echo esc_html__('Your E-Mail', 'commonsbooking') ?></div>
        <div><?php echo CB::get('user', 'user_email'); ?></div>
    </div>
    <div class="cb-list-content cb-user cb-col-30-70">
        <div><?php echo esc_html__('Your User name', 'commonsbooking') ?></div>
        <div><?php echo CB::get('user', 'first_name'); ?> <?php echo CB::get('user', 'last_name'); ?><br>
        <?php echo CB::get('user', 'address'); ?>
        </div>
    </div>
</div>

<!-- Booking comment -->
<?php
    $bookingCommentActive = Settings::getOption('commonsbooking_options_general', 'booking-comment-active');

    if($bookingCommentActive) {
        $bookingCommentTitle = Settings::getOption('commonsbooking_options_general', 'booking-comment-title');
        $bookingCommentDescription = Settings::getOption('commonsbooking_options_general', 'booking-comment-description');

        if($post->post_status == 'unconfirmed') { ?>
            <div class="cb-wrapper cb-booking-comment">
                <div class="cb-list-header">
                    <h3><?php echo $bookingCommentTitle; ?></h3>
                </div>
                <p><?php echo $bookingCommentDescription; ?></p>
                <div class="cb-list-content cb-comment cb-col-100">
                    <div>
                        <textarea id="cb-booking-comment" name="comment"><?php echo $booking->returnComment(); ?></textarea>
                    </div>
                </div>
            </div>
    <?php
        } else {
            if($booking->returnComment()) {
    ?>
            <div class="cb-wrapper cb-booking-comment">
                <div class="cb-list-header">
                    <h3><?php echo $bookingCommentTitle; ?></h3>
                </div>
                <div class="cb-list-content cb-comment cb-col-100">
                    <div><?php echo $booking->returnComment(); ?></div>
                </div>
            </div>
    <?php
            }
        }
    }
$current_status = $booking->post_status;
if($current_status && $current_status !== 'draft') {

?>

<!-- Buttons & Form action -->
<div class="cb-action cb-wrapper">
    <?php
        $form_action = 'confirm';
        include COMMONSBOOKING_PLUGIN_DIR . 'templates/booking-single-form.php';

		// if booking is unconfirmed cancel link throws user back to item detail page
		if ($booking->post_status() == "unconfirmed") {
			echo '<a href="' . get_permalink($item->ID) . '">' . esc_html__('Cancel', 'commonsbooking') . '</a>';
		} else {
			// if booking is confirmed we display the cancel booking button
			$form_action = 'cancel';
			include COMMONSBOOKING_PLUGIN_DIR . 'templates/booking-single-form.php';
		}
    ?>
</div>
<?php
}
?>