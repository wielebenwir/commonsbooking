<?php
/**
 * Use the classic editor for CommonsBooking post types in E2E tests.
 *
 * Upon initial load, the Gutenberg editor introduces itself with a modal
 * message that has to be dismissed. Because of this, we switch to the classic editor for E2E tests.
 *
 * @package CommonsBooking
 */

add_filter(
	'use_block_editor_for_post_type',
	static function ( $use_block_editor, $post_type ) {
		if ( in_array( $post_type, array( 'cb_item', 'cb_location' ), true ) ) {
			return false;
		}

		return $use_block_editor;
	},
	10,
	2
);
