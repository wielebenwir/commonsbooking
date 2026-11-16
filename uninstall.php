<?php
/**
 * Runs when the plugin is deleted via the WordPress admin.
 *
 * Data is kept by default. To remove everything, add this to wp-config.php
 * BEFORE deleting the plugin:
 *
 *     define( 'COMMONSBOOKING_REMOVE_ALL_DATA', true );
 *
 * This is inspired by WooCommerce/PolyLang.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'COMMONSBOOKING_REMOVE_ALL_DATA' ) || true !== COMMONSBOOKING_REMOVE_ALL_DATA ) {
	return;
}

global $wpdb;

// Posts of all custom post types (incl. meta and comments).
$post_types   = array( 'cb_item', 'cb_location', 'cb_timeframe', 'cb_booking', 'cb_restriction', 'cb_map' );
$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ($placeholders)", $post_types ) );
foreach ( $post_ids as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}

// Booking codes table.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cb_bookingcodes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

// Options (commonsbooking_* settings, plugin/db versions, cb_map_* data).
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( 'commonsbooking_' ) . '%',
		$wpdb->esc_like( 'cb_map_' ) . '%'
	)
);

// Custom role.
remove_role( 'cb_manager' );
