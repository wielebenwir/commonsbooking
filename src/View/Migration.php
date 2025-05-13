<?php

namespace CommonsBooking\View;

use CMB2_Field;
use CommonsBooking\Repository\CB1;
use CommonsBooking\Service\Upgrade;

/**
 * The class that renders the migration form to migrate from CB1 to CB2.
 */
class Migration {

	/**
	 * Render Migration Form.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field The field object
	 */
	public static function renderMigrationForm( array $field_args, CMB2_Field $field ) {
		$cb1Installed = CB1::isInstalled();

		?>
		<div class="cmb-row cmb-type-text ">
		<?php

		if ( ! $cb1Installed ) {
			echo '<strong style="color:red">' . esc_html__(
				'We could not detect a version of an older CommonsBooking Installation (Version 0.X).',
				'commonsbooking'
			) . '</strong>';
		} else {
			echo '<strong style="color: green">' . esc_html__(
				'Found a version of an older CommonsBooking Installation (Version 0.X). You can migrate.',
				'commonsbooking'
			) . '</strong>';
		}
		echo( '

            <div id="migration-state">
                <span id="locations-index">0</span>/<span id="locations-count">0</span> ' . esc_html__( ' Locations updated/saved', 'commonsbooking' ) . '<br>
                <span id="items-index">0</span>/<span id="items-count">0</span>' . esc_html__( ' Items updated/saved', 'commonsbooking' ) . '<br>
                <span id="timeframes-index">0</span>/<span id="timeframes-count">0</span>' . esc_html__( ' Timeframes updated/saved', 'commonsbooking' ) . '<br>
                <span id="bookings-index">0</span>/<span id="bookings-count">0</span>' . esc_html__( ' Bookings updated/saved', 'commonsbooking' ) . '<br>
                <span id="bookingCodes-index">0</span>/<span id="bookingCodes-count">0</span>' . esc_html__( ' Booking Codes updated/saved', 'commonsbooking' ) . '<br>
                <span id="termsUrl-count">0</span>' . esc_html__( ' Terms & Urls updated/saved', 'commonsbooking' ) . '<br>
                <span id="taxonomies-index">0</span>/<span id="taxonomies-count">0</span>' . esc_html__( ' Taxonomies updated/saved', 'commonsbooking' ) . '<br>
                <span id="options-count">0</span>' . esc_html__( ' Options updated/saved', 'commonsbooking' ) . '<br>
            </div>
            <div id="migration-in-progress">
                <p class="blinking" style="border:solid; border-color:red; border-width:4px; padding:20px"><strong style="color: red">
                ' . commonsbooking_sanitizeHTML( __( 'migration in process .. please wait ... <br>This could take several minutes. Do not close this browser tab', 'commonsbooking' ) ) . '
                </strong></p>
            </div>
            <div id="migration-done">
                <strong style="color: green">
                ' . esc_html__( 'Migration finished', 'commonsbooking' ) . '
                </strong>
            </div>
        ' );

		if ( $cb1Installed ) {
			?>
			</div>
			<div class="cmb-row cmb-type-text">
				<div class="cmb-td">
					<input type="checkbox" class="cmb2-option cmb2-list" name="get-geo-locations" id="get-geo-locations"
							checked>
					<label for="get-geo-locations"><?php echo esc_html__( 'Retrieve location geo coordinates.', 'commonsbooking' ); ?></label>
					<p class="cmb2-metabox-description">
												<?php echo esc_html__( 'If this option is enabled, CommonsBooking will try to derive the matching geo-coordinates from the address data of the locations during import. We use an interface to a GeoCoder service (Nominatim) for this task. This service allows only one query per second, so the runtime of the migration is increased by 1 second per location. The geo-coordinates are needed to use the location map integrated in CommonsBooking.', 'commonsbooking' ); ?>
					</p>
				</div>
			</div>
			<div class="cmb-row cmb-type-text">
			<a id="migration-start" class="button button-primary" href="#">
												<?php echo esc_html__( 'Start Migration', 'commonsbooking' ); ?>
			</a>
												<?php
		} // end if cb1installed
		?>
		</div>
		<?php
	}

	/**
	 * Renders booking migration (timeframe to booking cpt) form.
	 *
	 * @param array      $field_args
	 * @param CMB2_Field $field
	 */
	public static function renderBookingMigrationForm( array $field_args, CMB2_Field $field ) {

		echo( '
            <div class="cmb-row cmb-type-text">
                <div id="booking-migration-in-progress">
                    <strong style="color: red">
                    ' . esc_html__( 'migration in process .. please wait ...', 'commonsbooking' ) . '
                    </strong>
                </div>
                <div id="booking-migration-done">
                    <strong style="color: green">
                    ' . esc_html__( 'Migration finished', 'commonsbooking' ) . '
                    </strong>
                </div>
                <div id="booking-migration-failed">
	                <strong style="color: red">
	                ' . esc_html__( 'Migration failed', 'commonsbooking' ) . '
	                </strong>
	            </div>
                <a id="booking-update-start" class="button button-secondary" href="#">
				    ' . esc_html__( 'Migrate bookings', 'commonsbooking' ) . '
                </a>
            </div>
           '
		);
	}

	public static function renderUpgradeForm( array $field_args, CMB2_Field $field ) {
		if ( ! Upgrade::isAJAXUpgrade() ) {
			return false;
		}
		?>
		<div class="cmb-row cmb-type-text" id="upgrade-fields">
			<div id="upgrade-in-progress" class="blinking">
				<strong style="color: red">
				<?php echo esc_html__( 'upgrade in process .. please wait ...', 'commonsbooking' ); ?>
				</strong>
			</div>
			<div id="upgrade-done">
				<strong style="color: green">
				<?php echo esc_html__( 'Upgrade finished', 'commonsbooking' ); ?>
				</strong>
			</div>
			<a id="run-upgrade" class="button button-secondary" href="#">
				<?php echo esc_html__( 'Start Upgrade', 'commonsbooking' ); ?>
			</a>
		</div>
		<?php
	}
}
