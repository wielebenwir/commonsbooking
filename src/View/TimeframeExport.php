<?php


namespace CommonsBooking\View;

/**
 * This class is used to render the export button for the timeframe post type
 * For the functional part, @see \CommonsBooking\Service\TimeframeExport
 */
class TimeframeExport {

	const LOCATION_FIELD = 'location-fields';
	const ITEM_FIELD = 'item-fields';
	const USER_FIELD = 'user-fields';

	/**
	 * @param $field_args
	 * @param $field
	 */
	public static function renderExportButton( $field_args, $field ) {
		?>
        <div class="cmb-row cmb-type-text ">
            <div class="cmb-th">
                <label for="timeframe-export"><?php echo esc_html__( 'Download CSV', 'commonsbooking' ); ?></label>
            </div>
	        <div class="cmb-row cmb-type-text">
		        <div id="timeframe-export-in-progress">
			        <strong style="color: red">
						<span>
				            <?php echo esc_html__( 'preparing export .. please wait ...', 'commonsbooking' ) ?>
						</span>
			        </strong>
		        </div>
		        <div id="timeframe-export-done">
			        <strong style="color: green">
				        <span>
				            <?php echo esc_html__( 'Export finished', 'commonsbooking' ) ?>
				        </span>
			        </strong>
		        </div>
		        <div id="timeframe-export-failed">
			        <strong style="color: red">
				        <span>
				            <?php echo esc_html__( 'Export failed', 'commonsbooking' ) ?>
				        </span>
			        </strong>
		        </div>
	        </div>
            <div class="cmb-td">
	            <a  id="timeframe-export-start" class="button button-secondary" href="#">
					<?php echo esc_html__( 'Download Export', 'commonsbooking' ); ?>
                </a>
            </div>
        </div>
		<?php
	}

}
