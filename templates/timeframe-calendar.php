<?php

/**
 * This file displays the booking calendar. The calendar is rendered by javascript and included via <div id="litepicker"></div>
 * The variable $templateData is set by the files item-single.php or location-single.php
 * This file is loaded as sub-template in the files files item-single.php and location-single.php
 * We recommend not to edit this file as it might be modified and enhancend during updates
 */

    global $templateData;

    // we check if template is used not used in backend ...
    if(!array_key_exists('backend', $templateData) || $templateData['backend'] != true) {
?>
    <script type="text/javascript">
		<?php
			echo 'let calendarData = ' . commonsbooking_sanitizeHTML($templateData['calendar_data']) . ';';
		?>
    </script>
    <!-- generate calendar /-->
    <div id="litepicker"></div>

    <!-- show booking form with date / time selection /-->
    <div id="booking-form-container">
        <form method="get" id="booking-form">
			<?php wp_nonce_field(
				\CommonsBooking\Wordpress\CustomPostType\Booking::getWPAction(),
				\CommonsBooking\Wordpress\CustomPostType\Booking::getWPNonceId(),
				false,
				true
			); ?>

            <input type="hidden" name="location-id" value="<?php echo esc_attr($templateData['location']->ID); ?>"/>
            <input type="hidden" name="item-id" value="<?php echo esc_attr($templateData['item']->ID); ?>"/>
            <input type="hidden" name="type" value="<?php echo esc_attr($templateData['type']); ?>"/>
            <input type="hidden" name="post_type" value="cb_booking"/>
            <input type="hidden" name="post_status" value="unconfirmed"/>

            <div class="time-selection-container">
                <a id="resetPicker">
                    <?php echo esc_html__( 'Reset date selection', 'commonsbooking' ); ?>
                </a>
                <div class="time-selection repetition-start">
                    <label for="repetition-start">
                        <?php echo esc_html__( 'Pickup', 'commonsbooking' ); ?>:
                    </label>
                    <div>
                        <span class="hint-selection"><?php echo esc_html__( 'Please select the pickup date in the calendar', 'commonsbooking' ); ?></span>
                        <span class="date"></span>
                        <select style="display: none" id="repetition-start" name="repetition-start"></select>

                    </div>
                </div>
                <div class="time-selection repetition-end">
                    <label for="repetition-end">
                        <?php echo esc_html__( 'Return', 'commonsbooking' ); ?>:
                    </label>
                    <div>
                        <span class="hint-selection"><?php echo esc_html__( 'Please select the return date in the calendar', 'commonsbooking' ); ?></span>
                        <span class="date"></span>
                        <select style="display: none" id="repetition-end" name="repetition-end"></select>
                    </div>
                </div>
				<?php
                    $restrictions = $templateData['restrictions'];
                    if(count($restrictions)) {
                        ?>
                        <div class="restriction">
                            <?php echo '⚠ ' . esc_html__( 'Usage Restrictions', 'commonsbooking' ); ?>:
                            
                                <span class="restrictions">
                                    <ul>
                                <?php
                                    foreach ( $restrictions as $restriction ) {
                                        if($restriction->isActive()) {
                                            echo "<li>";
	                                            echo commonsbooking_sanitizeHTML( sprintf(
													//translators: %1$s = Start date and time formatted
	                                                __( 'From %1$s', 'commonsbooking' ),
	                                                $restriction->getFormattedStartDateTime() ) );

												// If there's
												if($restriction->hasEnddate()) {
													echo " " . commonsbooking_sanitizeHTML( sprintf(
														//translators: %1$s = End date and time formatted
															__( 'until probably %1$s:', 'commonsbooking' ),
															$restriction->getFormattedEndDateTime() ) );
													echo "</br>";
												} else {
													echo ":</br>";
												}

		                                        echo "<strong>" . commonsbooking_sanitizeHTML( $restriction->getHint() ) . "</strong>";
                                            echo "</li>";
                                        }
                                    }
                                ?>
                                    </ul>
                                </span>
                           
                        </div>
                    <?php
                    }
                    ?>
            </div>
			<?php
			if ( is_user_logged_in() ) { ?>
                <input type="submit" disabled="disabled"
                       value="<?php echo esc_html__( 'Continue to booking confirmation', 'commonsbooking' ); ?>"/>
			<?php } ?>
        </form>
    </div>
	<div id="calendar-footnote">
			<?php 
			// get Calendar Data
			$calendarData = json_decode($templateData['calendar_data']);

			//translators %1$s maximum days, %2$s maximum days
			echo sprintf ( commonsbooking_sanitizeHTML( __('<div id="calendar-footnote-colorkey">
			<strong>Calendar info</strong><br>
                <div class="colorkey-square colorkey-accept"></div> bookable | 
                <div class="colorkey-square colorkey-cancel"></div> booked/blocked  | 
                <div class="colorkey-square colorkey-holiday"></div> station closed  | 
                <div class="colorkey-square colorkey-greyedout"></div> not bookable </div>
			Maximum %1$s days bookable in a row. Depending on the setting, it is also possible to book over a gray area (e.g. weekend).
			Bookings are limited to a maximum of %2$s days in advance.
            </div>', 'commonsbooking') ),
				commonsbooking_sanitizeHTML($calendarData->maxDays),
				commonsbooking_sanitizeHTML($calendarData->advanceBookingDays)
			);
			?> 
	</div>
<?php

    // if template is used in backend
    } else {
        foreach ($templateData['calendar']['weeks'] as $week) {
?>
        <ul class="cb-calendar">
			<?php
			$dayNrs = [ 1, 2, 3, 4, 5, 6, 0 ];
			foreach ( $dayNrs as $dayNr ) {
				/** @var \CommonsBooking\Model\Day $day */
				foreach ( $templateData['week']->getDays() as $day ) {
					if ( $day->getDayOfWeek() == $dayNr ) {
						include __DIR__ . 'timeframe-calendar-day.php';
					}
				}
			}
			?>
        </ul>
		<?php
	}
}
