<?php
    // {% if backend != 'true' %}
    if(!array_key_exists('backend', $templateData) || $templateData['backend'] != true) {
?>
    <div id="litepicker"></div>
    <div>
        <form method="get" id="booking-form" style="display:none;">
            <?php echo $templateData['wp_nonce']; ?>
            <input type="hidden" name="location-id" value="<?php echo $templateData['location']; ?>" />
            <input type="hidden" name="item-id" value="<?php echo $templateData['item']->ID; ?>" />
            <input type="hidden" name="type" value="<?php echo $templateData['type']; ?>" />
            <input type="hidden" name="post_type" value="cb_timeframe" />
            <input type="hidden" name="post_status" value="unconfirmed" />

            <label>Abholzeit am <span id="start-date"></span></label>
            <select name="start-date"></select>
            <label>Abgabezeit am <span id="end-date"></span></label>
            <select name="end-date"></select>

            <input type="submit" value="Buchen" style="font-size: 12px;padding:0;" />
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
                            include __DIR__ . 'calendar-day.php';
                        }
                    }
                }
            ?>
        </ul>
<?php
    }
}
