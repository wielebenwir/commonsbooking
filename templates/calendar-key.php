<?php

/**
 * Template: calendar-key
 *
 * This template part is used by timeframe-calendar and the item table
 */

global $templateData;
?>

<div class="cb-calendar-key" role="region" aria-label="<?php echo esc_attr__('Calendar legend', 'commonsbooking'); ?>">
	<ul aria-labelledby="calendar-legend-title">
		<li id="calendar-legend-title" class="calendar-key-title"><?php echo esc_html__('Calendar Legend', 'commonsbooking'); ?>:</li>
		<li>
			<span class="cal-key av" aria-hidden="true"></span>
			<span class="status"><?php echo esc_html__('Available', 'commonsbooking'); ?></span>
		</li>
		<li>
			<span class="cal-key bo" aria-hidden="true"></span>
			<span class="status"><?php echo esc_html__('Booked', 'commonsbooking'); ?></span>
		</li>
		<li>
			<span class="cal-key ho" aria-hidden="true"></span>
			<span class="status"><?php echo esc_html__('Holiday, closed', 'commonsbooking'); ?></span>
		</li>
		<li>
			<span class="cal-key gr" aria-hidden="true"></span>
			<span class="status"><?php echo esc_html__('Outside bookable range', 'commonsbooking'); ?></span>
		</li>
	</ul>
</div>
