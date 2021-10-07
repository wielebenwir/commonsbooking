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
		//only add filter to post type you want
		if ( isset( $_GET['post_type'] ) && $postType == $_GET['post_type'] ) {
			?>
			<select name="<?php echo 'admin_' . $key; ?>">
				<option value=""><?php echo $label; ?></option>
				<?php
				$filterValue = isset( $_GET[ 'admin_' . $key ] ) ? sanitize_text_field( $_GET[ 'admin_' . $key ] ) : '';
				foreach ( $values as $value => $label ) {
					printf
					(
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

}