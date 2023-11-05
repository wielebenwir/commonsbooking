<?php

namespace CommonsBooking\Service;

class Holiday {
	/**
	 * Returns state mapping. According to https://de.wikipedia.org/wiki/Land_(Deutschland)#Amtliche_bzw._Eigenbezeichnungen
	 * @return string[]
	 */
	static function returnStates(): array {
		return [
			'BW' => 'BADEN WUERTEMBERG',
			'BY' => 'BAYERN',
			'BE' => 'BERLIN',
			'BB' => 'BRANDENBURG',
			'HB' => 'BREMEN',
			'HH' => 'HAMBURG',
			'HE' => 'HESSEN',
			'MV' => 'MECKLENBURG VORPOMMERN',
			'NI' => 'NIEDERSACHSEN',
			'NW' => 'NORDRHEIN WESTPHALEN',
			'RP' => 'RHEINLAND PFALZ',
			'SL' => 'SAARLAND',
			'SN' => 'SACHSEN',
			'ST' => 'SACHSEN ANHALT',
			'SH' => 'SCHLESWIG HOLSTEIN',
			'TH' => 'THUERINGEN',
			'BUND' => 'NATIONAL'
		];
	}


	/**
	 * Will render the holiday fields in the timeframe settings.
	 * TODO: These do not necessarily need to be CMB2 fields but can just be custom HTML fields. All the necessary information is pulled through javascript already.
	 * @param $field
	 * @param $value
	 * @param $object_id
	 * @param $object_type
	 * @param $field_type
	 *
	 * @return void
	 */
	public static function renderFields( $field, $value, $object_id, $object_type, $field_type ) {

		// make sure we specify each part of the value we need.
		$value = wp_parse_args( $value, array(
			'holiday_year'  => '',
			'holiday_state' => '',
		) );

		?>
		<div class="cb_admin_holiday_table_wrapper">
			<div class="cb_admin_holiday_table">
				<label
					for="<?php echo $field_type->_id( 'holiday_year' ); ?>"><?php echo esc_html__( 'Year', 'commonsbooking' );?></label>
				<?php echo $field_type->select( array(
					'name'  => $field_type->_name( '[holiday_year]' ),
					'id'    => $field_type->_id( 'holiday_year' ),
					'class' => 'multicheck',
					'desc' => '',
					'options' => self::getYearsOption(),
				) ); ?>
				<br>
			</div>
			<div class="cb_admin_holiday_table">
				<label
					for="<?php echo $field_type->_id( 'holiday_state' ); ?>"><?php echo esc_html__( 'State', 'commonsbooking' ); ?></label>
				<?php echo $field_type->select( array(
					'name'  => $field_type->_name( '[holiday_state]' ),
					'id'    => $field_type->_id( 'holiday_state' ),
					'desc'  => '',
					'type' => 'multicheck',
					'class' => 'cmb2_select',
					'options' => self::getStatesOption(),
				) ); ?>
				<br>
			</div>
			<br>
			<div class="cb_admin_holiday_table">
				<button type="button" id="holiday_load_btn"
				><?php echo esc_html__( 'Load Holidays', 'commonsbooking' ); ?></button>
			</div>
		</div>


		<br class="clear">
		<?php
		echo $field_type->_desc( true );
	}


	/**
	 * Will get the states defined in the holiday class and return them as HTML select options.
	 *
	 * @return string
	 */
	public static function getStatesOption( ): string {
		$state_list = self::returnStates();
		$state_options = '';
		foreach ( $state_list as $abrev => $state ) {
			$state_options .= '<option value="'. $abrev .'" '. selected( false, $abrev, false ) .'>'. $state .'</option>';
		}

		return $state_options;
	}

	/**
	 * Will get the years for the next 3 years.
	 * Formatted in HTML select options
	 * @return string
	 */
	public static function getYearsOption( ): string {
		$year = intval(date('Y'));
		$year_options = '';

		for ( $i = 0 ; $i < 3; $i++ ) {
			$year_options .= '<option value="'. $year .'" ';
			if($i === 0){
				$year_options .= ' selected ';
			};

			$year_options .='>'. $year++ .'</option>';
		}
		return $year_options;
	}
}