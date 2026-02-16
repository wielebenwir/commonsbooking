<?php


use CommonsBooking\Service\BookingRule;
use CommonsBooking\View\Migration;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Settings\Settings;
use CommonsBooking\View\TimeframeExport;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

// We need static types, because german month names don't work for datepicker
$dateFormat = 'd/m/Y';
if ( str_starts_with( get_locale(), 'de_' ) ) {
	$dateFormat = 'd.m.Y';
}

if ( str_starts_with( get_locale(), 'en_' ) ) {
	$dateFormat = 'm/d/Y';
}


/**
 * Plugin Options
 *
 * This file is used and included in functions that register or set admin options (search for includes/OptionsArray.php to get references)
 *
 * Tabs -> field "groups" -> fields
 * Notice: options are stored in database wp_options with prefix 'commonsboking_options_' followed by the tab id (e.g. commonsbooking_options_main)
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
					sprintf(
						commonsbooking_sanitizeHTML(
							__(
								'You are using CommonsBooking Version %s

                            <h2>How to start</h2>
							<p>To get startet visit our tutorials and documentation on <a target="_blank" href="https://commonsbooking.org/documentation">https://commonsbooking.org/documentation</a></p>

                            <h2>Questions or bug reports?</h2>
                            <p>Ask your questions or send us your bug reports here <a target="_blank" href="https://commonsbooking.org/contact/">https://commonsbooking.org/contact/</a></p>

                            <h2>Contribute</h2>
                            The future of this plugin depends on your support. You can support us by make a donation on our website: <a target="_blank" href="https://www.wielebenwir.de/verein/unterstutzen">wielebenwir</a>',
								'commonsbooking'
							)
						),
						commonsbooking_sanitizeHTML( COMMONSBOOKING_VERSION )
					),
				'fields' => array(),
			),
		),
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
					commonsbooking_sanitizeHTML(
						__(
							'Customize names & slugs. You can set an individual slug for items and locations to create individual permalinks. <br><b>Notice</b>: If the new settings do not work directly (you will get a 404 page error on frontend pages), you must click on the Settings -> Permalinks page after saving these settings to refresh the Wordpress permalink settings.',
							'commonsbooking'
						)
					),
				'fields' => array(
					array(
						'name'        => commonsbooking_sanitizeHTML( __( 'Items slug', 'commonsbooking' ) ),
						'id'          => 'posttypes_items-slug',
						// translators: %s = example url (like website.com/cb-items/)
						'description' =>
							sprintf(
								commonsbooking_sanitizeHTML(
									__(
										'The slug for article detail page. Type in the slug without slashes. Example: <strong>cb_item</strong> or <strong>article</strong>.  The url for the items detail will be like %s',
										'commonsbooking'
									)
								),
								network_site_url( '/cb_item/' )
							),
						'type'        => 'text',
						'default'     => Item::$postType,
					),
					array(
						'name'        => esc_html__( 'Locations slug', 'commonsbooking' ),
						'id'          => 'posttypes_locations-slug',
						// translators: %s = example url (like website.com/cb-locations/)
						'description' =>
							sprintf(
								commonsbooking_sanitizeHTML(
									__(
										'The slug for location detail page. Type in the slug without slashes. Example: <strong>cb_location</strong> or <strong>location</strong>.  The url for the items detail will be like %s',
										'commonsbooking'
									)
								),
								network_site_url( '/cb_location/' )
							),
						'type'        => 'text',
						'default'     => Location::$postType,
					),
					array(
						'name'        => esc_html__( 'Bookings Page', 'commonsbooking' ),
						'id'          => 'bookings_page',
						// translators: %s = example url (like website.com/cb-locations/)
						'description' =>
							sprintf(
								commonsbooking_sanitizeHTML(
									__(
										'The page where you included the [cb_bookings] shortcode. This is used in the Users Widget',
										'commonsbooking'
									)
								),
								network_site_url( '/bookings/' )
							),
						'type'        => 'select',
						'options'     => Wordpress::getPageListTitle(),
					),
				),
			),
			'bookingCommentSettings' => array(
				'title'  => __( 'Booking comment', 'commonsbooking' ),
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
						'default'     => __( 'Booking comment', 'commonsbooking' ),
					),
					array(
						'name'        => commonsbooking_sanitizeHTML( __( 'Description', 'commonsbooking' ) ),
						'id'          => 'booking-comment-description',
						'description' => commonsbooking_sanitizeHTML( __( 'Short infotext to inform the user how the comment field will be used (e.g. only internal comment etc.) ', 'commonsbooking' ) ),
						'type'        => 'textarea_small',
						'default'     => __( 'Here you can leave a comment about your booking. This will be sent to the station.', 'commonsbooking' ),
					),
				),
			),
			'globalLocationSettings' => array(
				'title'  => __( 'Global location settings', 'commonsbooking' ),
				'desc'   => commonsbooking_sanitizeHTML( __( 'These settings are used for all locations. You can overwrite these settings for each location in the location settings.', 'commonsbooking' ) ),
				'id'     => 'globalLocationSettings',
				'fields' => Location::getOverbookingSettingsMetaboxes(),
			),
		),
	),
	/* Tab: general end*/

	/* Tab booking codes start */
	'bookingcodes' => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Booking codes', 'commonsbooking' ) ),
		'id'           => 'bookingcodes',
		'field_groups' => array(
			'bookingcodes' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Booking codes', 'commonsbooking' ) ),
				'id'     => 'bookingcodes',
				'desc'   =>
					commonsbooking_sanitizeHTML(
						__(
							'Enter the booking codes to be generated in advance for booking types with all-day booking time frames.  Enter booking codes as a comma separated list, e.g.: Code1,Code2,Code3,Code4
				<br>More information in the documentation: <a href="https://commonsbooking.org/documentation/basics/booking-codes/" target="_blank">Booking codes</a>',
							'commonsbooking'
						)
					),
				'fields' => array(
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Booking codes', 'commonsbooking' ) ),
						'id'   => 'bookingcodes',
						'type' => 'textarea',
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Show booking codes for x days', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'Displays booking codes for the next x days on the timeframe page', 'commonsbooking' ) ),
						'id'      => 'bookingcodes-listed-timeframe',
						'type'       => 'text_small',
						'attributes' => array(
							'type' => 'number',
							'min'  => '0',
						),
						'default' => '30',
					),
				),
			),
			'mail_booking_codes' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Booking codes by email', 'commonsbooking' ) ),
				'id'     => 'mail-booking-codes',
				'desc'   =>
					commonsbooking_sanitizeHTML( __( 'Send booking codes by email to location email(s) (automated by cron or ad hoc)', 'commonsbooking' ) ),
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Subject for booking codes email', 'commonsbooking' ) ),
						'id'      => 'mail-booking-codes-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Booking codes for {{codes:formatDateRange}} {{item:post_title}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Body for booking codes email', 'commonsbooking' ) ),
						'id'      => 'mail-booking-codes-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'
<h1>Booking codes for {{item:post_title}} : {{codes:formatDateRange}}</h1>

<p>Booking codes Table:</p>
<br>
{{codes:codeTable}}
<br>
<p>Thanks, the Team.</p>
                            ',
								'commonsbooking'
							)
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Backup E-Mail for booking codes email', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'Email address that receives a bcc copy of booking codes mailing (not used if empty)', 'commonsbooking' ) ),
						'id'      => 'mail-booking-codes-bcc',
						'type'    => 'text',
					),
					array(
						'name'        => commonsbooking_sanitizeHTML( __( 'Attach iCalendar file to booking codes email', 'commonsbooking' ) ),
						'id'          => 'mail-booking-codes-attach-ical',
						'description' => commonsbooking_sanitizeHTML( __( 'Will attach an iCalendar compatible file with booking codes per day to import in their respective calendar application.', 'commonsbooking' ) ),
						'type'        => 'checkbox',
					),
				),
			),
		),
	),

	/* Tab: templates start*/
	'templates'    => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Templates', 'commonsbooking' ) ),
		'id'           => 'templates',
		'field_groups' => array(
			/* field group email templates start */
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
						'name'    => commonsbooking_sanitizeHTML( __( 'Mail-Signature', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'E-Mail signature that will appear wherever you put {{booking:getEmailSignature}}', 'commonsbooking' ) ),
						'id'      => 'emailbody_signature',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'
<p>
Thanks and all the best,
the Team.
</p>					',
								'commonsbooking'
							)
						),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Booking confirmed email subject', 'commonsbooking' ) ),
						'id'            => 'emailtemplates_mail-booking-confirmed-subject',
						'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
						'type'          => 'text',
						'default'       => commonsbooking_sanitizeHTML(
							__(
								'Your booking {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}} ',
								'commonsbooking'
							)
						),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Booking confirmed email body', 'commonsbooking' ) ),
						'id'            => 'emailtemplates_mail-booking-confirmed-body',
						'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_body',
						'type'          => 'textarea',
						'default'       => commonsbooking_sanitizeHTML(
							__(
								'
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
{{booking:getEmailSignature}}
                        ',
								'commonsbooking'
							)
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Attach iCalendar file to booking email', 'commonsbooking' ) ),
						'id'      => 'emailtemplates_mail-booking_ics_attach',
						'type'    => 'checkbox',
						'desc' => esc_html__( 'Will attach an iCalendar compatible file for users to import in their respective calendar application.', 'commonsbooking' ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'iCalendar event title', 'commonsbooking' ) ),
						'id'      => 'emailtemplates_mail-booking_ics_event-title',
						'type'    => 'text',
						'desc' => esc_html__( 'The title of the attached event', 'commonsbooking' ),
						'default'       => commonsbooking_sanitizeHTML(
							__(
								'{{item:post_title}} at {{location:post_title}}',
								'commonsbooking'
							)
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'iCalendar event description', 'commonsbooking' ) ),
						'id'      => 'emailtemplates_mail-booking_ics_event-description',
						'type'    => 'textarea',
						'desc' => esc_html__( 'The description for the attached event.', 'commonsbooking' ),
						'default'       => commonsbooking_sanitizeHTML(
							__(
								' Pick up: {{booking:pickupDatetime}}
Return date: {{booking:returnDatetime}}
{{location:formattedPickupInstructions}}
{{booking:formattedBookingCode}} ',
								'commonsbooking'
							)
						),
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
						'default' => commonsbooking_sanitizeHTML(
							__(
								'
Hi {{user:first_name}},<br>
<br>
your booking of {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}} has been canceled.<br>
<br>
{{booking:getEmailSignature}}
                            ',
								'commonsbooking'
							)
						),
					),
				),
			),
			/* field group email templates end */

			/* field group template and booking message templates start */
			'messagetemplates' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Template and booking process messages', 'commonsbooking' ) ),
				'id'     => 'messagetemplates',
				'desc'   => '',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Booking confirmed message', 'commonsbooking' ) ),
						'id'      => 'booking-confirmed-notice',
						'type'    => 'textarea_small',
						'desc'    => commonsbooking_sanitizeHTML( __( 'This text is shown as a status message on booking page after a user has confirmed the booking', 'commonsbooking' ) ),
						'default' => esc_html__( 'Your booking is confirmed. A confirmation mail has been sent to you.', 'commonsbooking' ),
					),
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
					array(
						'name'    => esc_html__( 'Text book this item on booking page', 'commonsbooking' ),
						'id'      => 'text_book-this-item',
						'type'    => 'textarea_small',
						'desc'    => esc_html__( 'This text is displayed on a booking detail page above the booking calendar .', 'commonsbooking' ),
						'default' => esc_html__( 'Book this item at this location', 'commonsbooking' ),
					),
					array(
						'name'    => esc_html__( 'Label for booking button', 'commonsbooking' ),
						'id'      => 'label-booking-button',
						'type'    => 'text',
						'desc'    => esc_html__( 'This text is displayed on the booking button on item/location listing pages.', 'commonsbooking' ),
						'default' => esc_html__( 'Book item', 'commonsbooking' ),
					),
					array(
						'name'    => esc_html__( 'User details on booking page', 'commonsbooking' ),
						'id'      => 'user_details_template',
						'type'    => 'textarea',
						'desc'    => esc_html__( 'This textblock is displayed on the booking details page. Please use template-tags to fill in user details', 'commonsbooking' ),
						'default' => commonsbooking_sanitizeHTML( __( '{{[Phone: ]user:phone}}<br>{{[Address: ]user:address}}', 'commonsbooking' ) ),
					),
				),
			),
			/* message templates end */



			/* field group image options */
			'imageoptions' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Image formatting', 'commonsbooking' ) ),
				'id'     => 'imageoptions',
				'desc'   => '',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Listing image small width (in px)', 'commonsbooking' ) ),
						'id'      => 'image_listing_small_width',
						'type'    => 'text',
						'desc'    => commonsbooking_sanitizeHTML( __( 'Defines the image width of small images in location and item listings', 'commonsbooking' ) ),
						'default' => '60',
						'attributes' => array(
							'type' => 'number',
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Listing image small height (in px)', 'commonsbooking' ) ),
						'id'      => 'image_listing_small_height',
						'type'    => 'text',
						'desc'    => commonsbooking_sanitizeHTML( __( 'Defines the image height of small images in location and item listings', 'commonsbooking' ) ),
						'default' => '60',
						'attributes' => array(
							'type' => 'number',
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Listing image medium width (in px)', 'commonsbooking' ) ),
						'id'      => 'image_listing_medium_width',
						'type'    => 'text',
						'desc'    => commonsbooking_sanitizeHTML( __( 'Defines the image width of medium images in location and item listings', 'commonsbooking' ) ),
						'default' => '100',
						'attributes' => array(
							'type' => 'number',
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Listing image medium height (in px)', 'commonsbooking' ) ),
						'id'      => 'image_listing_medium_height',
						'type'    => 'text',
						'desc'    => commonsbooking_sanitizeHTML( __( 'Defines the image height of medium images in location and item listings', 'commonsbooking' ) ),
						'default' => '100',
						'attributes' => array(
							'type' => 'number',
						),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Crop images', 'commonsbooking' ) ),
						'id'      => 'image_listing_crop',
						'type'    => 'checkbox',
						'desc'    => commonsbooking_sanitizeHTML( __( 'If checked the image will be cropped to specified dimensions using center crop positions', 'commonsbooking' ) ),
					),
				),
			),
			/* image options end */

			/* field group color setting */

			'colorscheme' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Color schemes', 'commonsbooking' ) ),
				'id'     => 'colorscheme',
				'desc'   => '',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Base color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_primarycolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'Defines the color that is used in headings and buttons', 'commonsbooking' ) ),
						'default' => '#84AE53',
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Secondary color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_secondarycolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color shown when hovering a button or a link', 'commonsbooking' ) ),
						'default' => '#506CA9',
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Button color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_buttoncolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The default color for buttons', 'commonsbooking' ) ),
						'default' => '#74ce3c',
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Confirmation / Available Color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_acceptcolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color that is used to signify if an item is available or that an action has been completed successfully', 'commonsbooking' ) ),
						'default' => '#74ce3c',
					),

					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Cancel / Not Available Color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_cancelcolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color that is used to signify if an item is unavailable or for buttons to abort actions', 'commonsbooking' ) ),
						'default' => '#d5425c',
					),

					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Temporarily Unavailable Color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_holidaycolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color that is used to signify if an item is temporarily unbookable (i.e. holiday)', 'commonsbooking' ) ),
						'default' => '#ff9218',
					),

					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Greyed out Color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_greyedoutcolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color used to signify that no timeframe has been created for an item or a button that is not yet clickable', 'commonsbooking' ) ),
						'default' => '#e0e0e0',
					),

					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Background Color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_backgroundcolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color used for the background of tables and similar elements', 'commonsbooking' ) ),
						'default' => '#f6f6f6',
					),

					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Notice Background Color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_noticebackgroundcolor',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color used for the background of notices', 'commonsbooking' ) ),
						'default' => '#FFF9C5',
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Light text color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_lighttext',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color used for light text on dark backgrounds', 'commonsbooking' ) ),
						'default' => '#a0a0a0',
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Dark text color', 'commonsbooking' ) ),
						'id'      => 'colorscheme_darktext',
						'type'    => 'colorpicker',
						'desc'    => commonsbooking_sanitizeHTML( __( 'The color used for dark text on light backgrounds', 'commonsbooking' ) ),
						'default' => '#000',
					),
				),
			),
			/* color settings end*/

		),
	),
	/* Tab: templates end*/

	/* Tab: restrictions start*/
	'restrictions' => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Restrictions', 'commonsbooking' ) ),
		'id'           => 'restrictions',
		'field_groups' => array(
			/* field group email templates start */
			'emailtemplates' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Manage Item Restriction Templates', 'commonsbooking' ) ),
				'desc'   => commonsbooking_sanitizeHTML( __( 'Templates for restriction emails.<br><a href="https://commonsbooking.org/documentation/settings/restrictions/" target="_blank">More Information in the documentation</a>', 'commonsbooking' ) ),
				'id'     => 'restricition-templates',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Mail-Header from E-Mail', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'E-Mail that will be shown as sender in generated emails', 'commonsbooking' ) ),
						'id'      => 'restrictions-from-email',
						'type'    => 'text',
						'default' => get_option( 'admin_email' ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Mail-Header from Name', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'Name that will be shown as sender in generated emails', 'commonsbooking' ) ),
						'id'      => 'restrictions-from-name',
						'type'    => 'text',
						'default' => get_option( 'blogname' ),
					),

					// E-Mail repair
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Breakdown email subject', 'commonsbooking' ) ),
						'id'      => 'restrictions-repair-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Breakdown of {{item:post_title}} for your booking {{booking:formattedBookingDate}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Breakdown email body', 'commonsbooking' ) ),
						'id'      => 'restrictions-repair-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hello {{user:first_name}},</h2>

                        <p>Unfortunately, the article {{item:post_title}} you booked is no longer usable from {{restriction:formattedStartDateTime}} to probably {{restriction:formattedEndDateTime}}. <br>The reason is:</br>{{restriction:hint}}
                        </br></br>
                        <strong>This affects your booking {{booking:formattedBookingDate}}</strong></br>
                        </br>
                        <p>
                        We had to cancel your booking for this period. You will receive confirmation of the cancellation in a separate email.<br>
                        If you have several bookings in the affected period, you will receive this information e-mail for each booking as well as separate cancellation information.<br>
                        Please book the item again for a different period or check our website to see if an alternative item is available.<br>We apologize for any inconvenience.
                        </p>
                        {{booking:getEmailSignature}}
                        ',
								'commonsbooking'
							)
						),
					),

					// E-Mail hint
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Usage restriction email subject', 'commonsbooking' ) ),
						'id'      => 'restrictions-hint-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Restriction of use for {{item:post_title}} for your booking {{booking:formattedBookingDate}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Restriction email body', 'commonsbooking' ) ),
						'id'      => 'restrictions-hint-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hello {{user:first_name}},</h2>
                        <p>
                        The article {{item:post_title}} you booked can only be used to a limited extent from {{restriction:formattedStartDateTime}} to probably {{restriction:formattedEndDateTime}}.
                        </p>
                        </br></br>
                        The reason is:</br>
                        {{restriction:hint}}
                        </br></br>
                        <strong>This affects your booking {{booking:formattedBookingDate}}</strong><br>
                        Please check if you want to keep your booking despite the restrictions. </br>
                        If not, please cancel your booking using the following link:
                        {{booking:BookingLink}}
                        </br>
                        <p>
                        If you have several bookings in the affected period, you will receive this information email for each booking.<br>
                        We strive to fix the restriction as soon as possible.
                        You will receive an email when the restriction is resolved.
                        </p>
                        {{booking:getEmailSignature}}',
								'commonsbooking'
							)
						),
					),

					// E-Mail restriction cancellation
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Restriction cancelled subject', 'commonsbooking' ) ),
						'id'      => 'restrictions-restriction-cancelled-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Restriction for article {{item:post_title}} no longer exists', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Restriction cancelled email body', 'commonsbooking' ) ),
						'id'      => 'restrictions-restriction-cancelled-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hello {{user:first_name}},</h2>
                        <p>The article {{item:post_title}} is now fully usable again.</p>
                        <p>This also affects your booking {{booking:formattedBookingDate}}
                        </br>
                        </br>Here is the link to your booking: {{booking:BookingLink}}
                        </br>
                        </p>
                        {{booking:getEmailSignature}}',
								'commonsbooking'
							)
						),
					),
				),
			),
			/* field group restriction settings start */
			'restrictionsettings' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Restriction settings', 'commonsbooking' ) ),
				'id'     => 'restrictionsettings',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Settings for restrictions.<br><a href="https://commonsbooking.org/documentation/settings/restrictions/" target="_blank">More Information in the documentation</a>', 'commonsbooking' ) ),
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Do not cancel bookings on total breakdown', 'commonsbooking' ) ),
						'id'      => 'restrictions-no-cancel-on-total-breakdown',
						'type'    => 'checkbox',
						'desc'    => commonsbooking_sanitizeHTML( __( 'If checked, bookings will not be cancelled if the item has broken down. The user will be notified and once the item becomes available again, the old bookings are still valid.', 'commonsbooking' ) ),
					),
				),
			),
			/* field group restriction settings end */
			'bookingRules' => array(
				'title' => commonsbooking_sanitizeHTML( __( 'Restrict bookings by booking rules', 'commonsbooking' ) ),
				'desc'  => commonsbooking_sanitizeHTML( __( 'You can apply rules to individual items or categories of items/locations, which will restrict how users are able to book and, if violated, abort the booking process', 'commonsbooking' ) ),
				'id'    => 'bookingrules',
				'fields' => array(
					array(
						'name'  => commonsbooking_sanitizeHTML( __( 'Count cancelled bookings towards quota', 'commonsbooking' ) ),
						'desc'  => commonsbooking_sanitizeHTML( __( 'Check if bookings that have been cancelled in the booking period shall be counted towards the amount of booked days for the user. <a target=\"_blank\" href=\"https://commonsbooking.org/documentation/first-steps/manage-booking-restrictions/\">More info in the documentation</a>', 'commonsbooking' ) ),
						'id'    => 'bookingrules-count-cancelled',
						'type'  => 'checkbox',
					),
					array(
						'id'        => 'rules_group',
						'type'      => 'group',
						'repeatable' => true,
						'options'   => array(
							'group_title'   => commonsbooking_sanitizeHTML( __( 'Rule ', 'commonsbooking' ) ) . '{#}',
							'add_button'    => commonsbooking_sanitizeHTML( __( 'Add another rule', 'commonsbooking' ) ),
							'remove_button' => commonsbooking_sanitizeHTML( __( 'Remove rule', 'commonsbooking' ) ),
						),
						'fields' => array(
							array(
								'name'      => commonsbooking_sanitizeHTML( __( 'Rule type', 'commonsbooking' ) ),
								'desc'      => commonsbooking_sanitizeHTML( __( 'Select the kind of rule', 'commonsbooking' ) ),
								'id'        => 'rule-type',
								'type'      => 'select',
								'show_option_none' => true,
								'default'   => 'none',
								'options'   => BookingRule::getRulesForSelect(),

							),
							// The following labels are not translated because they are replaced by the rule
							array(
								'name' => commonsbooking_sanitizeHTML( __( 'Rule description', 'commonsbooking' ) ),
								'desc' => commonsbooking_sanitizeHTML( 'You shall be replaced' ),
								'id'   => 'rule-description',
								'type' => 'title',
							),
							array(
								'name'  => commonsbooking_sanitizeHTML( 'Parameter 1' ),
								'desc'  => 'Parameter description',
								'id'    => 'rule-param1',
								'type'  => 'text_small',
							),
							array(
								'name'  => commonsbooking_sanitizeHTML( 'Parameter 2' ),
								'desc'  => 'Parameter description',
								'id'    => 'rule-param2',
								'type'  => 'text_small',
							),
							array(
								'name'  => commonsbooking_sanitizeHTML( __( 'Select an option', 'commonsbooking' ) ),
								'desc'  => 'Select parameter description',
								'id'    => 'rule-select-param',
								'type'  => 'select',
							),
							array(
								'name'  => commonsbooking_sanitizeHTML( __( 'Applies to all', 'commonsbooking' ) ),
								'desc'  => commonsbooking_sanitizeHTML( __( 'Check if this rule applies to all items', 'commonsbooking' ) ),
								'id'    => 'rule-applies-all',
								'type'  => 'checkbox',
							),
							array(
								'name'      => commonsbooking_sanitizeHTML( __( 'Applies to categories', 'commonsbooking' ) ),
								'desc'      => commonsbooking_sanitizeHTML( __( 'Check the categories that these rules apply to', 'commonsbooking' ) ),
								'id'        => 'rule-applies-categories',
								'type'      => 'multicheck',
								'options'   => CustomPostType::sanitizeOptions(
									array_merge(
										\CommonsBooking\Repository\Item::getTerms(),
										\CommonsBooking\Repository\Location::getTerms()
									)
								),
							),
							array(
								'name'      => commonsbooking_sanitizeHTML( __( 'Groups exempt from rule', 'commonsbooking' ) ),
								'desc'      => commonsbooking_sanitizeHTML( __( 'Here you can define if the rule should not apply to a specific user group. Will apply to all groups if left empty (Administrators and item / location admins are always excluded).', 'commonsbooking' ) ),
								'id'        => 'rule-exempt-roles',
								'type'      => 'pw_multiselect',
								'options'   => CustomPostType::sanitizeOptions(
									UserRepository::getUserRoles()
								),
							),
						),
					),

				),
			),
			/* field group email templates end */
		),
	),
	/* Tab: restrictions end*/

	/* Tab: reminder start*/
	'reminder'     => array(
		'title'        => commonsbooking_sanitizeHTML( __( 'Reminder', 'commonsbooking' ) ),
		'id'           => 'reminder',
		'field_groups' => array(

			/* field group pre booking reminder */
			'pre-booking-reminder' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Booking reminder', 'commonsbooking' ) ),
				'id'     => 'pre-booking-reminder',
				'desc'   => commonsbooking_sanitizeHTML(
					__(
						'You can set here whether users should receive a reminder email before the start of a booking.<br><a href="https://commonsbooking.org/documentation/settings/reminder/" target="_blank">More Information in the documentation</a>',
						'commonsbooking'
					)
				),
				'fields' => array(
					// settings pre booking reminder -- activate reminder
					array(
						'name' => esc_html__( 'Activate', 'commonsbooking' ),
						'id'   => 'pre-booking-reminder-activate',
						'type' => 'checkbox',
					),
					// E-Mail pre booking reminder
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'E-mail subject', 'commonsbooking' ) ),
						'id'      => 'pre-booking-reminder-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Upcoming booking of {{item:post_title}} {{booking:formattedBookingDate}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'email body', 'commonsbooking' ) ),
						'id'      => 'pre-booking-reminder-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hi {{user:first_name}},</h2>
<p>Your booking period for the item {{item:post_title}} will start soon.<br>
Your booking period: {{booking:formattedBookingDate}}<br><br>

If you no longer need the item you booked, please cancel the booking so other people can possibly use it.
<br>
For booking details and cancellation, click on this booking link: {{booking:bookingLink}}
<br>

{{booking:getEmailSignature}}',
								'commonsbooking'
							)
						),
					),

					// settings pre booking reminder -- min days
					array(
						'name'       => commonsbooking_sanitizeHTML( __( 'Sent reminder x days before booking start', 'commonsbooking' ) ),
						'id'         => 'pre-booking-days-before',
						'desc'       => '<p>' . commonsbooking_sanitizeHTML(
							__(
								'This reminder email will be sent to users x days before the start of the booking. If the booking is made less days before the specified days, no reminder email will be sent',
								'commonsbooking'
							)
						) . '</p>',
						'type'       => 'text_small',
						'attributes' => array(
							'type' => 'number',
							'min'  => '1',
						),
						'default'    => 2,

					),

					// settings pre booking reminder -- set sending time
					array(
						'name'             => esc_html__( 'Time', 'commonsbooking' ),
						'id'               => 'pre-booking-time',
						'desc'             => '<br>' . commonsbooking_sanitizeHTML(
							__(
								'Define when the reminder should be sent. The actual sending may differ from the defined value by a few hours, depending on how your WordPress is configured.',
								'commonsbooking'
							)
						),
						'type'             => 'select',
						'show_option_none' => false,
						'default'          => '1',
						'options'          => array(
							'0'  => '00:00',
							'1'  => '01:00',
							'2'  => '02:00',
							'3'  => '03:00',
							'4'  => '04:00',
							'5'  => '05:00',
							'6'  => '06:00',
							'7'  => '07:00',
							'8'  => '08:00',
							'9'  => '09:00',
							'10' => '10:00',
							'11' => '11:00',
							'12' => '12:00',
							'13' => '13:00',
							'14' => '14:00',
							'15' => '15:00',
							'16' => '16:00',
							'17' => '17:00',
							'18' => '18:00',
							'19' => '19:00',
							'20' => '20:00',
							'21' => '21:00',
							'22' => '22:00',
							'23' => '23:00',
						),

					),
				),
			),
			/* field group pre booking reminder settings end */


			/* field group post booking notice */
			'post-booking-notice'  => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'email after booking has ended', 'commonsbooking' ) ),
				'id'     => 'post-booking-notice',
				'desc'   => commonsbooking_sanitizeHTML(
					__(
						'Here you can set whether users should receive an additional e-mail after completing a booking. This can be used, for example, to inquire about the users satisfaction or possible problems during the booking.
					<br>The email will be sent around midnight after the booking day has ended.',
						'commonsbooking'
					)
				),
				'fields' => array(
					// settings post booking reminder -- activate reminder
					array(
						'name' => esc_html__( 'Activate', 'commonsbooking' ),
						'id'   => 'post-booking-notice-activate',
						'type' => 'checkbox',
					),
					// E-Mail post booking reminder
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'E-mail subject', 'commonsbooking' ) ),
						'id'      => 'post-booking-notice-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Your booking of {{item:post_title}} {{booking:formattedBookingDate}} has ended', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'email body', 'commonsbooking' ) ),
						'id'      => 'post-booking-notice-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hi {{user:first_name}},</h2>
<p>Your booking of {{item:post_title}} at {{location:post_title}} has ended.<br>
We hope that everything worked as expected.<br>
Please let us know if any problems occurred.<br>
</p>
{{booking:getEmailSignature}}',
								'commonsbooking'
							)
						),
					),
				),
			),
			/* field group post booking reminder settings end */

			/* field group booking start reminder for locations */
			'booking-start-location-reminder' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Reminder for locations before booking starts', 'commonsbooking' ) ),
				'id'     => 'booking-start-location-reminder',
				'desc'   => commonsbooking_sanitizeHTML(
					__(
						'You can set here whether locations should receive a reminder email before the start of a booking.<br><a href="https://commonsbooking.org/documentation/settings/reminder/" target="_blank">More Information in the documentation</a>',
						'commonsbooking'
					)
				),
				'fields' => array(
					// settings booking start reminder -- activate reminder
					array(
						'name' => esc_html__( 'Activate', 'commonsbooking' ),
						'id'   => 'booking-start-location-reminder-activate',
						'type' => 'checkbox',
						'desc' => esc_html__( 'The reminders need to be enabled for all locations individually. This is only the main on/off switch.', 'commonsbooking' ),
					),
					// E-Mail booking start reminder for locations
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'E-mail subject', 'commonsbooking' ) ),
						'id'      => 'booking-start-location-reminder-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Upcoming booking of {{item:post_title}} {{booking:formattedBookingDate}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'email body', 'commonsbooking' ) ),
						'id'      => 'booking-start-location-reminder-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hi,</h2>
<p>The booking period for the item {{item:post_title}} at {{location:post_title}} will start soon.<br>
The booking period: {{booking:formattedBookingDate}}<br><br>
This item has been booked by {{user:first_name}} {{user:last_name}} ( {{user:user_email}} ). <br>

{{booking:getEmailSignature}}',
								'commonsbooking'
							)
						),
					),
					// settings booking start reminder -- set sending time
					array(
						'name'             => esc_html__( 'Time', 'commonsbooking' ),
						'id'               => 'booking-start-location-reminder-time',
						'desc'             => '<br>' . commonsbooking_sanitizeHTML(
							__(
								'Define when the reminder should be sent. The actual sending may differ from the defined value by a few hours, depending on how your WordPress is configured.',
								'commonsbooking'
							)
						),
						'type'        => 'text_time',
						'attributes'  => array(
							'data-timepicker' => wp_json_encode(
								array(
									'stepMinute' => 60,
									'timeFormat' => 'HH:mm',
								)
							),
						),
						'time_format' => esc_html( get_option( 'time_format' ) ),
					),
					array(
						'name'             => esc_html__( 'Bookings of', 'commonsbooking' ),
						'id'               => 'booking-start-location-reminder-day',
						'desc'             => '<br>' . commonsbooking_sanitizeHTML(
							__(
								'Define for which booking start day the notifications should be sent',
								'commonsbooking'
							)
						),
						'type'             => 'select',
						'show_option_none' => false,
						'default'          => '1',
						'options'          => array(
							'1' => esc_html__( 'current day', 'commonsbooking' ),
							'2' => esc_html__( 'next day', 'commonsbooking' ),
						),
					),
				),
			),
			/* field group booking start reminder for locations end */

			/* field group booking end reminder for locations */
			'booking-end-location-reminder' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Reminder for locations before booking ends', 'commonsbooking' ) ),
				'id'     => 'booking-end-location-reminder',
				'desc'   => commonsbooking_sanitizeHTML(
					__(
						'You can set here whether locations should receive a reminder email before the end of a booking.<br><a href="https://commonsbooking.org/documentation/settings/reminder/" target="_blank">More Information in the documentation</a>',
						'commonsbooking'
					)
				),
				'fields' => array(
					// settings booking end reminder -- activate reminder
					array(
						'name' => esc_html__( 'Activate', 'commonsbooking' ),
						'id'   => 'booking-end-location-reminder-activate',
						'type' => 'checkbox',
						'desc' => esc_html__( 'The reminders need to be enabled for all locations individually. This is only the main on/off switch.', 'commonsbooking' ),
					),
					// E-Mail booking end reminder for locations
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'E-mail subject', 'commonsbooking' ) ),
						'id'      => 'booking-end-location-reminder-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Booking of {{item:post_title}} {{booking:formattedBookingDate}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'email body', 'commonsbooking' ) ),
						'id'      => 'booking-end-location-reminder-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML(
							__(
								'<h2>Hi,</h2>
<p>The booking period for the item {{item:post_title}} at {{location:post_title}} will end soon.<br>
The booking period: {{booking:formattedBookingDate}}<br><br>
This item has been booked by {{user:first_name}} {{user:last_name}} ( {{user:user_email}} ). <br>


{{booking:getEmailSignature}}',
								'commonsbooking'
							)
						),
					),
					// settings booking end reminder -- set sending time
					array(
						'name'             => esc_html__( 'Time', 'commonsbooking' ),
						'id'               => 'booking-end-location-reminder-time',
						'desc'             => '<br>' . commonsbooking_sanitizeHTML(
							__(
								'Define when the reminder should be sent. The actual sending may differ from the defined value by a few hours, depending on how your WordPress is configured.',
								'commonsbooking'
							)
						),
						'type'        => 'text_time',
						'default' => '1',
						'attributes'  => array(
							'data-timepicker' => wp_json_encode(
								array(
									'stepMinute' => 60,
									'timeFormat' => 'HH:mm',
								)
							),
						),
						'time_format' => esc_html( get_option( 'time_format' ) ),
					),
					array(
						'name'             => esc_html__( 'Bookings of', 'commonsbooking' ),
						'id'               => 'booking-end-location-reminder-day',
						'desc'             => '<br>' . commonsbooking_sanitizeHTML(
							__(
								'Define for which booking end day the notifications should be sent',
								'commonsbooking'
							)
						),
						'type'             => 'select',
						'show_option_none' => false,
						'default'          => '1',
						'options'          => array(
							'1' => esc_html__( 'current day', 'commonsbooking' ),
							'2' => esc_html__( 'next day', 'commonsbooking' ),
						),
					),
				),
			),
			/* field group booking end reminder for locations end */
		),
		/* field group container end */
	),
	/* Tab: reminder end*/

	/* Tab: migration start */
	'migration'    => array(
		'title'        => __( 'Migration', 'commonsbooking' ),
		'id'           => 'migration',
		'field_groups' => array(
			'upgrade'           => array(
				'title'  => esc_html__( 'Finish upgrade to latest version', 'commonsbooking' ),
				'id'     => 'upgrade',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Click here to finish the upgrade to the latest version. <br> This needs to be done after updating the plugin to a new version. <br> If you do not do this, the plugin may not work correctly.', 'commonsbooking' ) ),
				'fields' => [
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Finish upgrade', 'commonsbooking' ) ),
						'id'            => 'upgrade-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( Migration::class, 'renderUpgradeForm' ),
					),
				],
			),
			// migration cb1 -> cb2
			'migration'         => array(
				'title'  => esc_html__( 'Migrate from Commons Booking Version 0.X', 'commonsbooking' ),
				'id'     => 'migration',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Migrate data from CommonsBooking Version 0.X. <br>The migration includes: locations, items, timeframes and bookings. <br><span style="color:red">If you have clicked "Migrate" before, starting the migration again will overwrite any changes you made to  locations, items, timeframes and bookings</span>.<br>Please read the documentation <a target="_blank" href="https://commonsbooking.org/documentation/setup/migration-from-cb1/">How to migrate from version 0.9.x to 2.x.x </a> before you start migration.', 'commonsbooking' ) ),
				'fields' => [
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Start Migration', 'commonsbooking' ) ),
						'id'            => 'migration-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( Migration::class, 'renderMigrationForm' ),
					),
				],
			),


			// cb1 user fields
			'cb1-user-fields'   => array(
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
					),
				],
			),

			// booking migration button
			'booking-migration' => array(
				'title'  => esc_html__( 'Migrate bookings to new version', 'commonsbooking' ),
				'id'     => 'booking-migration',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Migrate bookings to new format so that they are listed at bookings menu item. <br><strong>This function is only for special cases during migration. Please use it only in case of problems with migration.</strong>', 'commonsbooking' ) ),
				'fields' => [
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Migrate bookings', 'commonsbooking' ) ),
						'id'            => 'booking-migration-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( Migration::class, 'renderBookingMigrationForm' ),
					),
				],
			),
		),
	),
	/* Tab: migration end */

	/* Tab: export start */
	'export'       => array(
		'title'        => __( 'Export', 'commonsbooking' ),
		'id'           => 'export',
		'field_groups' => array(
			'download' => array(
				'title'  => esc_html__( 'Download timeframes export', 'commonsbooking' ),
				'id'     => 'download',
				'fields' => [
					array(
						'name'    => esc_html__( 'Type', 'commonsbooking' ),
						'desc'    => esc_html__( 'Select Type of this timeframe (e.g. bookable, repair, holidays, booking). See Documentation for detailed information.', 'commonsbooking' ),
						'id'      => 'export-type',
						'type'    => 'select',
						'options' => Timeframe::getTypes( true ),
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Location-Fields', 'commonsbooking' ) ),
						'desc' => sprintf(
							commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a post- or a meta-field. Comma separated list. Beside the standard post fields and standard postmeta-fields, the following custom meta fields are available. Copy only the values in [] in the field without the brackets. %s', 'commonsbooking' ) ),
							commonsbooking_sanitizeHTML( Settings::returnFormattedMetaboxFields( 'cb_location' ) )
						),
						'id'   => TimeframeExport::LOCATION_FIELD,
						'type' => 'text',
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Item-Fields', 'commonsbooking' ) ),
						'desc' => sprintf(
							commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a post- or a meta-field. Comma separated list. Beside the standard post fields and standard postmeta-fields, the following custom meta fields are available. Copy only the values in [] in the field without the brackets. %s', 'commonsbooking' ) ),
							commonsbooking_sanitizeHTML( Settings::returnFormattedMetaboxFields( 'cb_item' ) )
						),
						'id'   => TimeframeExport::ITEM_FIELD,
						'type' => 'text',
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'User-Fields', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a userfield or a meta-field. Comma separated list.', 'commonsbooking' ) ),
						'id'   => TimeframeExport::USER_FIELD,
						'type' => 'text',
					),
					array(
						'name'        => esc_html__( 'Export start date', 'commonsbooking' ),
						'id'          => 'export-timerange-start',
						'type'        => 'text_date_timestamp',
						'date_format' => $dateFormat,
						'default'     => date( $dateFormat ),
						'attributes'  => array(
							'required' => 'required',
						),
					),
					array(
						'name'        => esc_html__( 'Export end date', 'commonsbooking' ),
						'id'          => 'export-timerange-end',
						'type'        => 'text_date_timestamp',
						'date_format' => $dateFormat,
						'attributes'  => array(
							'required' => 'required',
						),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Export', 'commonsbooking' ) ),
						'id'            => 'export-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( TimeframeExport::class, 'renderExportButton' ),
					),
				],
			),
			'cron'     => array(
				'title'  => esc_html__( 'Cron settings for timeframes export', 'commonsbooking' ),
				'id'     => 'cron',
				'fields' => [
					array(
						'name' => esc_html__( 'Run as cronjob', 'commonsbooking' ),
						'id'   => 'export-cron',
						'type' => 'checkbox',
					),
					array(
						'name'    => esc_html__( 'Export interval', 'commonsbooking' ),
						'id'      => 'export-interval',
						'type'    => 'select',
						'options' => [
							'five_minutes'   => '5 ' . esc_html__( 'minutes', 'commonsbooking' ),
							'thirty_minutes' => '30 ' . esc_html__( 'minutes', 'commonsbooking' ),
							'daily'          => esc_html__( 'daily', 'commonsbooking' ),
						],
					),
					array(
						'name'       => esc_html__( 'Export timerange', 'commonsbooking' ),
						'desc'       => commonsbooking_sanitizeHTML( __( 'Export timerange in days.', 'commonsbooking' ) ),
						'id'         => 'export-timerange',
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
						'type' => 'text',
					),
				],
			),
		),
	),
	/* Tab: export end */

	/* Tab: API  start */
	'api'          => array(
		'title'        => __( 'API', 'commonsbooking' ),
		'id'           => 'api',
		'field_groups' => array(
			'api' => array(
				'title'  => esc_html__( 'Configure API Access', 'commonsbooking' ),
				'id'     => 'api_access',
				'fields' => [
					array(
						'name' => esc_html__( 'Activate API', 'commonsbooking' ),
						'desc' => commonsbooking_sanitizeHTML( __( 'If selected, the API is enabled. See more information in the documentation: <a target="_blank" href="https://commonsbooking.org/documentation/api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'   => 'api-activated',
						'type' => 'checkbox',
					),
					array(
						'name' => esc_html__( 'Enable API Access without API-Key', 'commonsbooking' ),
						'desc' => commonsbooking_sanitizeHTML( __( 'If selected, the API is accessible without an API-Key. For details see: <a target="_blank" href="https://commonsbooking.org/documentation/api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'   => 'apikey_not_required',
						'type' => 'checkbox',
					),

					array(
						// Repeatable group -> API Shares
						'name'       => esc_html__( 'API shares', 'commonsbooking' ),
						'desc'       => commonsbooking_sanitizeHTML( __( 'You can define on or more API shares. Read the documentation for more information about API shares and configuration <a target="_blank" href="https://commonsbooking.org/documentation/api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'         => 'api_share_group',
						'type'       => 'group',
						'repeatable' => true,
						'options'    => array(
							'group_title'   => commonsbooking_sanitizeHTML( __( 'API', 'commonsbooking' ) ) . '{#}',
							'add_button'    => commonsbooking_sanitizeHTML( __( 'Add Another API', 'commonsbooking' ) ),
							'remove_button' => commonsbooking_sanitizeHTML( __( 'Remove API', 'commonsbooking' ) ),
							'closed'        => false,  // Repeater fields closed by default - neat & compact.
							'sortable'      => false,  // Allow changing the order of repeated groups.
						),

						'fields' => array(
							array(
								'name' => esc_html__( 'API name', 'commonsbooking' ),
								'desc' => commonsbooking_sanitizeHTML( __( 'Internal name for this API share', 'commonsbooking' ) ),
								'id'   => 'api_name',
								'type' => 'text',
							),
							array(
								'name' => esc_html__( 'API enabled', 'commonsbooking' ),
								'desc' => commonsbooking_sanitizeHTML( __( 'If checked this API share is enabled', 'commonsbooking' ) ),
								'id'   => 'api_enabled',
								'type' => 'checkbox',
							),
							array(
								'name' => esc_html__( 'Push URL', 'commonsbooking' ),
								'desc' => commonsbooking_sanitizeHTML( __( 'URL that gets push information everytime there was a change on CommonsBooking data', 'commonsbooking' ) ),
								'id'   => 'push_url',
								'type' => 'text',
							),
							array(
								'name' => esc_html__( 'API Key', 'commonsbooking' ),
								'id'   => 'api_key',
								'type' => 'text',
								'desc' => commonsbooking_sanitizeHTML( __( ' You must set an API-Key. The API key should consist of alphanumeric characters and be at least 24 characters long.', 'commonsbooking' ) ),
							),
							array(
								'name'       => esc_html__( 'API Owner', 'commonsbooking' ),
								'desc'       => commonsbooking_sanitizeHTML( __( 'The owner value is provided by the API. It is set to the blog name by default in this version. In future versions you may be able to change this information', 'commonsbooking' ) ),
								'id'         => 'api_owner',
								'type'       => 'text',
								'attributes' => array(
									'disabled' => 'disabled',
									'readonly' => 'readonly',
								),
								'default'    => get_bloginfo( 'name' ),
							),

						),
					),
				],
			),
		),
	),
	/* Tab: export end */

	/* Tab: advanced options start */
	'advanced-options'     => array(
		'title'        => __( 'Advanced Options', 'commonsbooking' ),
		'id'           => 'advanced-options',
		'field_groups' => array(
			'custom_metadata' => array(
				'title'  => esc_html__( 'Set Custom metadata to locations and items', 'commonsbooking' ),
				'desc'   => commonsbooking_sanitizeHTML(
					__(
						'This is an advanced feature and should only be used if you are experienced or instructed how to set it up properly. In future versions we will add more detailed information and documentation.',
						'commonsbooking'
					)
				),
				'id'     => 'meta_data_group',
				'fields' => [
					array(
						'name' => esc_html__( 'Meta Data', 'commonsbooking' ),
						'desc' => commonsbooking_sanitizeHTML(
							__(
								'Use only this format, separated by semicolon and each entry in a new line: <br>post_type(item/location);field-name;label(english),type(checkbox,number,text),description(in english)<br>
                                        Example: item;waterproof;Waterproof material;checkbox;"This item is waterproof and can be used in heavy rain" ',
								'commonsbooking'
							)
						),
						'id'   => 'metadata',
						'type' => 'textarea',
					),
				],
			),
			'icalfeed' => array(
				'title' => esc_html__( 'iCalendar Feed', 'commonsbooking' ),
				'desc'  => commonsbooking_sanitizeHTML(
					__(
						'Enables users to copy a url for a dynamic iCalendar feed into their own digital calendars. This feature is experimental.',
						'commonsbooking'
					)
				),
				'id'    => 'icalendar_group',
				'fields' => [
					array(
						'name' => esc_html__( 'Enable iCalendar feed', 'commonsbooking' ),
						'id'   => 'feed_enabled',
						'type' => 'checkbox',
					),
					array(
						'name'  => esc_html__( 'Event title', 'commonsbooking' ),
						'desc'  => esc_html__( 'You can use template tags here as well', 'commonsbooking' ),
						'default'       => commonsbooking_sanitizeHTML(
							__(
								'{{item:post_title}} at {{location:post_title}}',
								'commonsbooking'
							)
						),
						'id'    => 'event_title',
						'type'  => 'text',
					),
					array(
						'name'  => esc_html__( 'Event description', 'commonsbooking' ),
						'desc'  => esc_html__( 'You can use template tags here as well', 'commonsbooking' ),
						'default'       => commonsbooking_sanitizeHTML(
							__(
								'
Pick up: {{booking:pickupDatetime}}
Return date: {{booking:returnDatetime}}
{{location:formattedPickupInstructions}}
{{booking:formattedBookingCode}} ',
								'commonsbooking'
							)
						),
						'id'    => 'event_desc',
						'type'  => 'textarea',
					),
				],
			),
			'experimental' => array(
				'title'  => commonsbooking_sanitizeHTML( __( 'Advanced caching settings', 'commonsbooking' ) ),
				'id'     => 'caching_group',
				'desc'   =>
					commonsbooking_sanitizeHTML( __( 'Allows you to change options regarding the caching system', 'commonsbooking' ) ),
				'fields' => array(
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Clear Cache', 'commonsbooking' ) ),
						'id'            => 'commonsbooking-clear_cache-button',
						'type'          => 'text',
						'render_row_cb' => array( \CommonsBooking\Plugin::class, 'renderClearCacheButton' ),
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Cache Adapter', 'commonsbooking' ) ),
						'id'            => 'cache_adapter',
						'type'          => 'select',
						'options'       => \CommonsBooking\Plugin::getAdapters( true ),
						'default'       => 'filesystem',
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Location of cache', 'commonsbooking' ) ),
						'desc'          => commonsbooking_sanitizeHTML( __( 'The location of the cache. A directory for the filesystem cache, a REDIS DSN, ...', 'commonsbooking' ) ),
						'id'            => 'cache_location',
						'type'          => 'text',
						'default'       => sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'symfony-cache',
					),
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Current connection status', 'commonsbooking' ) ),
						'id'            => 'cache_status',
						'type'          => 'text',
						'render_row_cb' => array( \CommonsBooking\Plugin::class, 'renderCacheStatus' ),
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Periodical warmup through cronjob', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Will periodically warm up the cache through a cronjob. This can be useful, when you have a lot of timeframes / bookings but your site is rarely accessed. <br> You NEED to hook WP-Cron Into the System Task Scheduler for this to have any positive effect. <b> You probably don\'t want this. </b>', 'commonsbooking' ) ),
						'id'   => 'warmup_cron',
						'show_option_none' => true,
						'type' => 'select',
						'options' => array(
							'ten_minutes' => esc_html__( 'Every 10 minutes', 'commonsbooking' ),
							'thirty_minutes' => esc_html__( 'Every 30 minutes', 'commonsbooking' ),
							'hourly' => esc_html__( 'Every hour', 'commonsbooking' ),
							'daily' => esc_html__( 'Every day', 'commonsbooking' ),
						),
					),
				),
			),
		),
	),
	/* Tab: advanced options end */
);
