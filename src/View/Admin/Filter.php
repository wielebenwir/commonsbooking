<?php

namespace CommonsBooking\View\Admin;

class Filter {

	/**
	 * Renders backend list filter.
	 *
	 * @param $postType
	 * @param $label
	 * @param $key
	 * @param $values
	 */
	public static function renderFilter( $postType, $label, $key, $values ) {
		// only add filter to post type you want
		if ( isset( $_GET['post_type'] ) && $postType == $_GET['post_type'] ) {
			?>
			<select name="<?php echo 'admin_' . commonsbooking_sanitizeHTML( $key ); ?>">
				<option value=""><?php echo commonsbooking_sanitizeHTML( $label ); ?></option>
				<?php
				$filterValue = isset( $_GET[ 'admin_' . $key ] ) ? sanitize_text_field( $_GET[ 'admin_' . $key ] ) : '';
				foreach ( $values as $value => $label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						$value,
						$value == $filterValue ? ' selected="selected"' : '',
						$label
					);
				}
				?>
			</select>
			<?php
		}
	}

	/**
	 * Renders Start-/Enddate filters for admin lists.
	 *
	 * @param $postType
	 * @param $startDateInputName
	 * @param $endDateInputName
	 * @param $from
	 * @param $to
	 */
	public static function renderDateFilter( $postType, $startDateInputName, $endDateInputName, $from, $to ) {
		if ( isset( $_GET['post_type'] ) && $postType == sanitize_text_field( $_GET['post_type'] ) ) {
			echo '<style>
                input[name=' . commonsbooking_sanitizeHTML( $startDateInputName ) . '],
                input[name=' . commonsbooking_sanitizeHTML( $endDateInputName ) . ']{
                    line-height: 28px;
                    height: 28px;
                    margin: 0;
                    width:150px;
                }
            </style>

            <label for="' . commonsbooking_sanitizeHTML( $startDateInputName ) . '">' . esc_html__('Start date', 'commonsbooking') . '</label>
            <input type="text" id="' . commonsbooking_sanitizeHTML( $startDateInputName ) . '" name="' . commonsbooking_sanitizeHTML( $startDateInputName ) . '" value="' . esc_attr( $from ) . '" />
            <label for="' . commonsbooking_sanitizeHTML( $endDateInputName ) . '">' . esc_html__('End date', 'commonsbooking') . '</label>
            <input type="text" id="' . commonsbooking_sanitizeHTML( $endDateInputName ) . '" name="' . commonsbooking_sanitizeHTML( $endDateInputName ) . '" value="' . esc_attr( $to ) . '" />

            <script>
            jQuery( function($) {
                var from = $(\'input[name=' . commonsbooking_sanitizeHTML( $startDateInputName ) . ']\'),
                    to = $(\'input[name=' . commonsbooking_sanitizeHTML( $endDateInputName ) . ']\');

                $(\'input[name=' . commonsbooking_sanitizeHTML( $startDateInputName ) . '], input[name=' . commonsbooking_sanitizeHTML( $endDateInputName ) . ']\' ).datepicker(
                    {
                        dateFormat : "yy-mm-dd"
                    }
                );
                from.on( \'change\', function() {
                    to.datepicker( \'option\', \'minDate\', from.val() );
                });
                to.on( \'change\', function() {
                    from.datepicker( \'option\', \'maxDate\', to.val() );
                });
            });
            </script>';
		}
	}
}
