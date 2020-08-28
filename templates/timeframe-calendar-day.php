<li class="type-cb_day">

    <div class="cb-day-title">
        <span><?php echo $day->getFormattedDate('d'); ?></span>
        <span><?php echo $day->getFormattedDate('M'); ?>></span>
    </div>

    <?php
        foreach ($day.getGrid() as $slotNr => $slot) {
            if(array_key_exists('timeframe', $slot) && $slot['timeframe']) {
                if($slot['timeframe']['type'] == '2') { ?>
                <div class="cb-timeframe cb-timeframe--type-<?php echo $slot['timeframe']->type; ?>" data-type-label="{{ slot.timeframe|get_type_label }}" style="border-bottom: 1px solid gray;">
                    <span>
                        {{ slot.timestart }} - {{ slot.timeend }}
                    </span>
                        {% if backend != 'true' %}
                        <form method="get">
                            {{ wp_nonce|raw }}
                            <input type="hidden" name="location-id" value="{{ slot.timeframe|get_meta_field('location-id') }}" />
                            <input type="hidden" name="item-id" value="{{ slot.timeframe|get_meta_field('item-id') }}" />
                            <input type="hidden" name="type" value="6" />
                            <input type="hidden" name="post_type" value="cb_timeframe" />
                            <input type="hidden" name="post_status" value="unconfirmed" />
                            <input type="hidden" name="repetition-start" value="{{ slot.timestampstart }}">
                            <input type="hidden" name="repetition-end" value="{{ slot.timestampend }}">
                            <input type="submit" value="Buchen" style="font-size: 12px;padding:0;" />
                        </form>
                        {% endif %}
                    </div>
                <?php
                } else { ?>
                    <div class="cb-timeframe cb-timeframe--type-{{ slot.timeframe.type }}" data-type-label="{{ slot.timeframe|get_type_label }}" >
                    <span>
                        {{ slot.timestart }} - {{ slot.timeend }}
                    </span>
                    </div>
                <?php
                }
            }
        }
    ?>
</li>
