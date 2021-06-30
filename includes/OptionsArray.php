<?php

// We need static types, because german month names dont't work for datepicker
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\View\Migration;
use CommonsBooking\View\TimeframeExport;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

$dateFormat = "d/m/Y";
if ( strpos( get_locale(), 'de_' ) !== false ) {
	$dateFormat = "d.m.Y";
}

if ( strpos( get_locale(), 'en_' ) !== false ) {
	$dateFormat = "m/d/Y";
}

$typeOptions = [
	'all' => esc_html__( 'All timeframe types', 'commonsbooking' )
];
$typeOptions += Timeframe::getTypes();

/**
 * Plugin Options
 *
 * This file is used and included in functions that register or set admin options (search for includes/OptionsArray.php to get references)
 *
 * Tabs -> field "groups" -> fields
 * Notice: options are stored in database wp_options with prefix 'commonsboking_options_' followed by the tab id (e.g. commonsbooking_options_main)
 *
 */
return array(

	/* Tab: main start*/
	'main'         => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Welcome', 'commonsbooking' ) ),
		'id'           => 'main',
		'is_top_level' => true, /* indicate first tab */
		'field_groups' => array(
			/* welcome group start */
			'welcome' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Welcome to CommonsBooking', 'commonsbooking' ) ),
				'id'     => 'welcome',
				'desc'   =>
				// translators: %s = version number
					sprintf( commonsbooking_sanitizeHTML( __( 'You are using CommonsBooking Version %s

                            <h2>How to start</h2>
                            <p>To get startet visit our tutorials and documentation on <a target="_blank" href="https://commonsbooking.org/dokumentation">https://commonsbooking.org/dokumentation</a></p>

                            <h2>Questions or bug reports?</h2>
                            <p>Ask your questions or send us your bug reports here <a target="_blank" href="https://commonsbooking.org/kontakt/">https://commonsbooking.org/kontakt/</a></p>

                            <h2>Contribute</h2>
                            The future of this plugin depends on your support. You can support us by make a donation on our website: <a target="_blank" href="https://www.wielebenwir.de/verein/unterstutzen">wielebenwir</a>'
						, 'commonsbooking' ) ), COMMONSBOOKING_VERSION ),
				'fields' => array()
			)
		)
	),

	/* Tab: main end*/

	/* Tab: general start*/
	'general'      => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'General', 'commonsbooking' ) ),
		'id'           => 'general',
		'field_groups' => array(
			/* posttype: naming, rewrite, archives start */
			'posttypes'              => array(
				'title'  => __( 'Naming and permalinks', 'commonsbooking' ),
				'id'     => 'posttypes',
				// tranlsators: %s = admin url options page
				'desc'   =>
					commonsbooking_sanitizeHTML( __(
						'Customize names & slugs. You can set an individual slug for items and locations to create individual permalinks. <br><b>Notice</b>: If the new settings do not work directly (you will get a 404 page error on frontend pages), you must click on the Settings -> Permalinks page after saving these settings to refresh the Wordpress permalink settings.'
						, 'commonsbooking' ) ),
				'fields' => array(
					array(
						'name'        => commonsbooking_sanitizeHTML( __( 'Items slug', 'commonsbooking' ) ),
						'id'          => 'posttypes_items-slug',
						// translators: %s = example url (like website.com/cb-items/)
						'description' =>
							sprintf( commonsbooking_sanitizeHTML( __(
								'The slug for article detail page. Type in the slug without slashes. Example: <strong>cb_item</strong> or <strong>article</strong>.  The url for the items detail will be like %s',
								'commonsbooking' ) ), network_site_url( '/cb_item/' ) ),
						'type'        => 'text',
						'default'     => Item::$postType,
					),
					array(
						'name'        => esc_html__( 'Locations slug', 'commonsbooking' ),
						'id'          => 'posttypes_locations-slug',
						// translators: %s = example url (like website.com/cb-locations/)
						'description' =>
							sprintf( commonsbooking_sanitizeHTML( __(
								'The slug for location detail page. Type in the slug without slashes. Example: <strong>cb_location</strong> or <strong>location</strong>.  The url for the items detail will be like %s',
								'commonsbooking' ) ), network_site_url( '/cb_location/' ) ),
						'type'        => 'text',
						'default'     => Location::$postType,
					),
					array(
						'name'        => esc_html__( 'Bookings Page', 'commonsbooking' ),
						'id'          => 'bookings_page',
						// translators: %s = example url (like website.com/cb-locations/)
						'description' =>
							sprintf( commonsbooking_sanitizeHTML( __(
								'The page where you included the [cb_bookings] shortcode. This is used in the Users Widget',
								'commonsbooking' ) ), network_site_url( '/bookings/' ) ),
						'type'        => 'select',
						'options'     => Wordpress::getPageListTitle(),
					),
				),
			),
			'bookingCommentSettings' => array(
				'title'  => __( "Booking comment", 'commonsbooking' ),
				'id'     => 'bookingCommentSettings',
				'fields' => array(
					array(
						'name'        => esc_html__( 'Activate booking comments in booking page', 'commonsbooking' ),
						'id'          => 'booking-comment-active',
						'description' => commonsbooking_sanitizeHTML( __( 'If enabled, users can enter an internal comment about their booking on the booking confirmation page. This comment can be included in the booking confirmation email.', 'commonsbooking' ) ),
						'type'        => 'checkbox',
					),
					array(
						'name'        => commonsbooking_sanitizeHTML( __( 'Headline above the comment field in frontend', 'commonsbooking' ) ),
						'id'          => 'booking-comment-title',
						'description' => commonsbooking_sanitizeHTML( __( 'Text that will be shown above the comment field in the booking confirmation page.', 'commonsbooking' ) ),
						'type'        => 'text',
						'default'     => __( 'Booking comment', 'commonstbooking' ),
					),
					array(
						'name'        => commonsbooking_sanitizeHTML( __( 'Description', 'commonsbooking' ) ),
						'id'          => 'booking-comment-description',
						'description' => commonsbooking_sanitizeHTML( __( 'Short infotext to inform the user how the comment field will be used (e.g. only internal comment etc.) ', 'commonsbooking' ) ),
						'type'        => 'textarea_small',
						'default'     => __( 'Here you can leave a comment about your booking. This will be sent to the station.', 'commonsbooking' ),
					),
				)
			)
		)
	),
	/* Tab: general end*/

	/* Tab Booking Codes start */
	'bookingcodes' => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
		'id'           => 'bookingcodes',
		'field_groups' => array(
			'bookingcodes' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
				'id'     => 'bookingcodes',
				'desc'   =>
					commonsbooking_sanitizeHTML( __( 'Enter the booking codes to be generated in advance for booking types with all-day booking time frames.  Enter booking codes as a comma separated list, e.g.: Code1,Code2,Code3,Code4
                <br>More information in the documentation: <a href="https://commonsbooking.org/?p=870" target="_blank">Booking Codes</a>', 'commonsbooking' ) ),
				'fields' => array(
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
						'id'   => 'bookingcodes',
						'type' => 'textarea',
					),
				)
			)
		)
	),

	/* Tab: templates start*/
	'templates'    => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Templates', 'commonsbooking' ) ),
		'id'           => 'templates',
		// field group email templates start
		'field_groups' => array(
			'emailtemplates'   => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Email templates', 'commonsbooking' ) ),
				'id'     => 'emailtemplates',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Mail-Header from E-Mail', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'E-Mail that will be shown as sender in generated emails', 'commonsbooking' ) ),
						'id'      => 'emailheaders_from-email',
						'type'    => 'text',
						'default' => get_option( 'admin_email' ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Mail-Header from Name', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'Name that will be shown as sender in generated emails', 'commonsbooking' ) ),
						'id'      => 'emailheaders_from-name',
						'type'    => 'text',
						'default' => get_option( 'blogname' ),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Booking confirmed email subject', 'commonsbooking' ) ),
						'id'            => 'emailtemplates_mail-booking-confirmed-subject',
						'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
						'type'          => 'text',
						'default'       => commonsbooking_sanitizeHTML( __( 'Your booking {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}} ',
							'commonsbooking' ) ),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Booking confirmed email body', 'commonsbooking' ) ),
						'id'            => 'emailtemplates_mail-booking-confirmed-body',
						'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_body',
						'type'          => 'textarea',
						'default'       => commonsbooking_sanitizeHTML( __( '
Hi {{user:first_name}},<br>
<br>
thank you for booking {{item:post_title}} {{booking:formattedBookingDate}}.<br>
<br>
Pick up: <strong>{{booking:pickupDatetime}}</strong><br>
Return date: <strong>{{booking:returnDatetime}}</strong>
{{location:formattedPickupInstructions}}
{{booking:formattedBookingCode}}
<br>
<strong>Location</strong><br>
{{location:formattedAddress}}
{{location:formattedContactInfoOneLine}}
<br>
<strong>Click here to see or cancel your booking: {{booking:bookingLink}}</strong><br>
<br>
<strong>Notice:</strong> You need to be logged in to see your booking.<br>
If the link leads you to the homepage of the webseite,
please login first and then click the link again.<br>
<br>
<strong>Your information:</strong><br>
Login: {{user:user_login}}<br>
Name: {{user:first_name}} {{user:last_name}}<br>
<br>
Thanks, the Team.
                        ', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Booking canceled email subject', 'commonsbooking' ) ),
						'id'      => 'emailtemplates_mail-booking-canceled-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Booking canceled: {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Booking canceled email body', 'commonsbooking' ) ),
						'id'      => 'emailtemplates_mail-booking-canceled-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML( __( '
Hi {{user:first_name}},<br>
<br>
your booking of {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}} has been canceled.<br>
<br>          
Thanks, the Team.
                            ', 'commonsbooking' ) ),
					),
				)
			),
			/* field group email templates end */

			/* field group template and booking message templates start */
			'messagetemplates' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Template and booking process messages', 'commonsbooking' ) ),
				'id'     => 'messagetemplates',
				'desc'   => '',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Item not available', 'commonsbooking' ) ),
						'id'      => 'item-not-available',
						'type'    => 'textarea_small',
						'desc'    => commonsbooking_sanitizeHTML( __( 'This text is shown on item listings (shortcode cb_items) and item detail page if there is no valid bookable timeframe set for this item', 'commonsbooking' ) ),
						'default' => esc_html__( 'This item is currently not bookable.', 'commonsbooking' ),
					),
					array(
						'name'    => esc_html__( 'Location without available items', 'commonsbooking' ),
						'id'      => 'location-without-items',
						'type'    => 'textarea_small',
						'desc'    => esc_html__( 'This text is shown on location listings and location detail page if there are no items available at this location', 'commonsbooking' ),
						'default' => esc_html__( 'No items available at this location right now.', 'commonsbooking' ),
					),
					array(
						'name' => esc_html__( 'Show contactdetails on booking without confirmation?', 'commonsbooking' ),
						'id'   => 'show_contactinfo_unconfirmed',
						'type' => 'checkbox',
						'desc' => esc_html__( 'If activated the contactdetails (e.g. phone number, pickupinstructions) will be shown on booking page even if the booking is not confirmed by user. Otherwise these info will be shown only after booking is confirmed', 'commonsbooking' ),
					),
					array(
						'name'    => esc_html__( 'Infotext hidden contactdetails', 'commonsbooking' ),
						'id'      => 'text_hidden-contactinfo',
						'type'    => 'textarea_small',
						'desc'    => esc_html__( 'This text is displayed when contact details of the station are shown only after booking confirmation.', 'commonsbooking' ),
						'default' => esc_html__( 'Please confirm the booking to see the contact details for pickup and return.', 'commonsbooking' ),
					),
				)
			),
			/* message templates end */
		)
	),
	/* Tab: templates end*/

	/* Tab: migration start */
	'migration'    => array(
		'title'        => __( 'Migration', 'commonsbooking' ),
		'id'           => 'migration',
		'field_groups' => array(
			'migration'       => array(
				'title'  => esc_html__( 'Migrate from Commons Booking Version 0.X', 'commonsbooking' ),
				'id'     => 'migration',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Migrate data from CommonsBooking Version 0.X. <br>The migration includes: locations, items, timeframes and bookings. <br><span style="color:red">If you have clicked "Migrate" before, starting the migration again will overwrite any changes you made to  locations, items, timeframes and bookings</span>.<br>Please read the documentation <a target="_blank" href="https://commonsbooking.org/dokumentation/?p=434">How to migrate from version 0.9.x to 2.x.x </a> before you start migration.', 'commonsbooking' ) ),
				'fields' => [
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Start Migration', 'commonsbooking' ) ),
						'id'            => 'migration-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( Migration::class, 'renderMigrationForm' ),
					)
				]
			),
			'cb1-user-fields' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'CommonsBooking Version 0.X profile fields', 'commonsbooking' ) ),
				'id'     => 'cb1-user-fields',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Enable the following legacy CommonsBooking Version 0.X user profile fields:', 'commonsbooking' ) ) . '<br><i> first_name,  last_name,  phone,  address,   terms_accepted </i>',
				'fields' => [
					array(
						'name' => esc_html__( 'Enable', 'commonsbooking' ),
						'id'   => 'enable-cb1-user-fields',
						'type' => 'checkbox',
					),
					array(
						'name' => esc_html__( 'Terms & Services Url', 'commonsbooking' ),
						'id'   => 'cb1-terms-url',
						'type' => 'text',
					)
				]
			)
		)
	),
	/* Tab: migration end */

	/* Tab: API and export start */
	'export'       => array(
		'title'        => __( 'Export / API', 'commonsbooking' ),
		'id'           => 'export',
		'field_groups' => array(
			'api'      => array(
				'title'  => esc_html__( 'Configure API Access', 'commonsbooking' ),
				'id'     => 'api',
				'fields' => [
					array(
						'name' => esc_html__( 'Activate API', 'commonsbooking' ),
						'desc' => commonsbooking_sanitizeHTML( __( 'If selected, the API is enabled. See more information in the documentation: <a target="_blank" href="https://commonsbooking.org/docs/schnittstellen-api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'   => "api-activated",
						'type' => 'checkbox',
					),
				]
			),
			'download' => array(
				'title'  => esc_html__( 'Download timeframes export', 'commonsbooking' ),
				'id'     => 'download',
				'fields' => [
					array(
						'name'    => esc_html__( 'Type', 'commonsbooking' ),
						'desc'    => esc_html__( 'Select Type of this timeframe (e.g. bookable, repair, holidays, booking). See Documentation for detailed information.', 'commonsbooking' ),
						'id'      => "export-type",
						'type'    => 'select',
						'options' => $typeOptions,
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Location-Fields', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a post- or a meta-field. Comma separated list.', 'commonsbooking' ) ),
						'id'   => 'location-fields',
						'type' => 'text'
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Item-Fields', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a post- or a meta-field. Comma separated list.', 'commonsbooking' ) ),
						'id'   => 'item-fields',
						'type' => 'text'
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'User-Fields', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a user- or a meta-field. Comma separated list.', 'commonsbooking' ) ),
						'id'   => 'user-fields',
						'type' => 'text'
					),
					array(
						'name'        => esc_html__( 'Export start date', 'commonsbooking' ),
						'id'          => "export-timerange-start",
						'type'        => 'text_date_timestamp',
						'date_format' => $dateFormat,
						'default'     => date( $dateFormat ),
						'attributes'  => array(
							'required' => 'required',
						),
					),
					array(
						'name'        => esc_html__( 'Export end date', 'commonsbooking' ),
						'id'          => "export-timerange-end",
						'type'        => 'text_date_timestamp',
						'date_format' => $dateFormat,
						'attributes'  => array(
							'required' => 'required',
						),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Export', 'commonsbooking' ) ),
						'id'            => 'migration-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( TimeframeExport::class, 'renderExportForm' ),
					)
				]
			),
			'cron'     => array(
				'title'  => esc_html__( 'Cron settings for timeframes export', 'commonsbooking' ),
				'id'     => 'cron',
				'fields' => [
					array(
						'name' => esc_html__( 'Run as cronjob', 'commonsbooking' ),
						'id'   => "export-cron",
						'type' => 'checkbox'
					),
					array(
						'name'    => esc_html__( 'Export interval', 'commonsbooking' ),
						'id'      => "export-interval",
						'type'    => 'select',
						'options' => [
							'five_minutes'   => "5 " . esc_html__( 'minutes', 'commonsbooking' ),
							'thirty_minutes' => "30 " . esc_html__( 'minutes', 'commonsbooking' ),
							'daily'          => esc_html__( 'daily', 'commonsbooking' ),
						],
					),
					array(
						'name'       => esc_html__( 'Export timerange', 'commonsbooking' ),
						'desc'       => commonsbooking_sanitizeHTML( __( 'Export timerange in days.', 'commonsbooking' ) ),
						'id'         => "export-timerange",
						'type'       => 'text',
						'attributes' => array(
							'type'    => 'number',
							'pattern' => '\d*',
						),
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Filepath', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Absolute path on your webserver (including trailing slash) where export file will be saved to.', 'commonsbooking' ) ),
						'id'   => 'export-filepath',
						'type' => 'text'
					),
				]
			)
		)
	)
	/* Tab: export end */
);
