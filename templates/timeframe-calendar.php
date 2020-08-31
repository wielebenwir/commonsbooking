<?php
    // {% if backend != 'true' %}
    if(!array_key_exists('backend', $templateData) || $templateData['backend'] != true) {
?>
    <div id="litepicker"></div>
    <div id="booking-form-container">
        <form method="get" id="booking-form" style="display:none;">
            <?php echo $templateData['wp_nonce']; ?>
            <input type="hidden" name="location-id" value="<?php echo $templateData['location']->ID; ?>" />
            <input type="hidden" name="item-id" value="<?php echo $templateData['item']->ID; ?>" />
            <input type="hidden" name="type" value="<?php echo $templateData['type']; ?>" />
            <input type="hidden" name="post_type" value="cb_timeframe" />
            <input type="hidden" name="post_status" value="unconfirmed" />

            <div class="time-selection-container">

                <div class="time-selection repetition-start">
                    <label for="repetition-start"><?php echo __('Pickup time', 'commonsbooking'); ?></label>
                        <span class="date"></span>
                        <select id="repetition-start" name="repetition-start"></select>
                </div>
                  <div class="time-selection repetition-end">
                    <label for="repetition-end"><?php echo __('Return time', 'commonsbooking'); ?> </label>
                        <span class="date"></span>
                        <select id="repetition-end" name="repetition-end"></select>
                </div>
            <p id="fullDayInfo"></p>
            </div>
            <?php
            if(is_user_logged_in()) { ?>
                <input type="submit" value="<?php echo __('Book', 'commonsbooking'); ?>" />
            <?php } else {
                $current_url = $_SERVER['REQUEST_URI'];
            ?>
            <div class="cb-notice">
            <?php
            printf(
                /* translators: %1$s: wp_login_url, 1$s: wp_registration_url */
                __( 'To be able to book, you must first <a href="%1$s">login</a> or <a href="%2$s">register as new user</a>.', 'commonsbooking' ),
                esc_url( wp_login_url( $current_url ) ), esc_url( wp_registration_url() )
            );
            ?>
            </div> 
            <?php } ?>
            
        </form>
    </div>
<?php } else {
    //{% for week in calendar.weeks %}
    foreach ($templateData['calendar']['weeks'] as $week) {
?>
        <ul class="cb-calendar">
            <?php
                $dayNrs = [1,2,3,4,5,6,0];
                foreach($dayNrs as $dayNr) {
                    /** @var \CommonsBooking\Model\Day $day */
                    foreach ($templateData['week']->getDays() as $day) {
                        if($day->getDayOfWeek() == $dayNr) {
                            include __DIR__ . 'timeframe-calendar-day.php';
                        }
                    }
                }
            ?>
        </ul>
<?php
    }
}
