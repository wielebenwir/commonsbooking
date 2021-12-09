<?php


use CommonsBooking\Helper;
use CommonsBooking\View\Migration;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Settings\Settings;
use CommonsBooking\View\TimeframeExport;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

// We need static types, because german month names dont't work for datepicker
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
				)
			),
			/* message templates end */
		)
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
				'desc'   => commonsbooking_sanitizeHTML( __( 'Templates for restriction emails.<br><a href="https://commonsbooking.org/?p=1762" target="_blank">More Information in the documentation</a>', 'commonsbooking' ) ),
				'id'     => 'restricition-templates',
				'fields' => array(
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Mail-Header from E-Mail', 'commonsbooking' ) ),
						'desc'    => commonsbooking_sanitizeHTML( __( 'Email that will be shown as sender in generated emails', 'commonsbooking' ) ),
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
						'default' => commonsbooking_sanitizeHTML( __( 'Breakdown of {{restriction:itemName}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Breakdown email body', 'commonsbooking' ) ),
						'id'      => 'restrictions-repair-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML( __( '<h2>Hi {{user:first_name}},</h2>

<p>Unfortunately, the item {{restriction:itemName}} you booked is damaged and not usable from {{restriction:formattedStartDateTime}} until {{restriction:formattedEndDateTime}} expected.
</br></br>
The reason is:</br>
{{restriction:hint}}
</br>
</br>
We had to cancel your booking for this period.  You will receive a confirmation of the cancellation in a separate email.
Please book the item again for another period or check our website to see if an alternative item is available.
</br>
We ask for your understanding. 
</br>
Best regards,</br>
the team
</p>', 'commonsbooking' ) ),
					),

					// E-Mail hint
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Usage restriction email subject', 'commonsbooking' ) ),
						'id'      => 'restrictions-hint-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Restriction of use for {{restriction:itemName}}', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Restriction email body', 'commonsbooking' ) ),
						'id'      => 'restrictions-hint-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML( __( '<h2>Hi {{user:first_name}},</h2>
<p>
The item {{restriction:itemName}} you booked is damaged and will have limited use from {{restriction:formattedStartDateTime} until probably {{restriction:formattedEndDateTime}}.
</br></br>
The reason is:</br>
{{restriction:hint}}
</br>
</br>
Please check if you want to continue your booking despite this usage restriction. 
If not, we ask you to cancel your booking via the following link:
{{booking:BookingLink}} 
</br>
</br>
We will do our best to fix the restriction as soon as possible.
We will sent you an email when the restriction is fixed.
</br>
Best regards,</br>
the team
</p>', 'commonsbooking' ) ),
					),

					// E-Mail restriction cancellation
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Restriction cancelled subject', 'commonsbooking' ) ),
						'id'      => 'restrictions-restriction-cancelled-subject',
						'type'    => 'text',
						'default' => commonsbooking_sanitizeHTML( __( 'Restriction for article {{restriction:itemName}} no longer exists', 'commonsbooking' ) ),
					),
					array(
						'name'    => commonsbooking_sanitizeHTML( __( 'Restriction cancelled email body', 'commonsbooking' ) ),
						'id'      => 'restrictions-restriction-cancelled-body',
						'type'    => 'textarea',
						'default' => commonsbooking_sanitizeHTML( __( '<h2>Hi {{user:first_name}},</h2>
<p>
The item {{restriction:itemName}} is now fully usable again. 
</p>', 'commonsbooking' ) ),
					),
				)
			),
			/* field group email templates end */
		)
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
				'desc'   => commonsbooking_sanitizeHTML( __(
					'You can set here whether users should receive a reminder email before the start of a booking.<br><a href="https://commonsbooking.org/?p=1763" target="_blank">More Information in the documentation</a>'
					, 'commonsbooking' ) ),
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
						'default' => commonsbooking_sanitizeHTML( __( '<h2>Hi {{user:first_name}},</h2>
<p>Your booking period for the item {{item:}} will start soon.<br>
Your booking period: {{booking:formattedBookingDate}}<br><br>

If you no longer need the item you booked, please cancel the booking so other people can possibly use it.
<br>
For booking details and cancellation, click on this booking link: {{booking:bookingLink}}
<br>
Best regards,
the team</p>', 'commonsbooking' ) ),
					),

					// settings pre booking reminder -- min days 
					array(
						'name'       => commonsbooking_sanitizeHTML( __( 'Sent reminder x days before booking start', 'commonsbooking' ) ),
						'id'         => 'pre-booking-days-before',
						'desc'       => '<p>' . commonsbooking_sanitizeHTML( __(
								'This reminder email will be sent to users x days before the start of the booking. If the booking is made less days before the specified days, no reminder email will be sent'
								, 'commonsbooking' ) ) . '</p>',
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
						'desc'             => '<br>' . commonsbooking_sanitizeHTML( __(
								'Define when the reminder should be sent. The actual sending may differ from the defined value by a few hours, depending on how your WordPress is configured.'
								, 'commonsbooking' ) ),
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
				'desc'   => commonsbooking_sanitizeHTML( __(
					'Here you can set whether users should receive an additional e-mail after completing a booking. This can be used, for example, to inquire about the users satisfaction or possible problems during the booking.
					<br>The email will be sent around midnight after the booking day has ended.'
					, 'commonsbooking' ) ),
				'fields' => array(
					// settings pre booking reminder -- activate reminder
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
						'default' => commonsbooking_sanitizeHTML( __( '<h2>Hi {{user:first_name}},</h2>
<p>Your booking has ended.<br>
We hope that everything worked as expected.<br>
Please let us know if any problems occurred.<br>
<br>
Best regards,<br>
The team</p>', 'commonsbooking' ) ),
					),
				),
			),
			/* field group post booking reminder settings end */
		),
		/* field group container end */
	),
	/* Tab: reminder end*/

	/* Tab: migration start */
	'migration'    => array(
		'title'        => __( 'Migration', 'commonsbooking' ),
		'id'           => 'migration',
		'field_groups' => array(
			'migration'         => array(
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
			'booking-migration' => array(
				'title'  => esc_html__( 'Migrate bookings to new version', 'commonsbooking' ),
				'id'     => 'booking-migration',
				'desc'   => commonsbooking_sanitizeHTML( __( 'Migrate bookings to new format so that they are listed at bookings menu item.', 'commonsbooking' ) ),
				'fields' => [
					array(
						'name'          => commonsbooking_sanitizeHTML( __( 'Migrate bookings', 'commonsbooking' ) ),
						'id'            => 'booking-migration-custom-field',
						'type'          => 'text',
						'render_row_cb' => array( Migration::class, 'renderBookingMigrationForm' ),
					)
				]
			),
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
					)
				]
			)
		)
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
						'id'      => "export-type",
						'type'    => 'select',
						'options' => $typeOptions,
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Location-Fields', 'commonsbooking' ) ),
						'desc' => sprintf ( commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a post- or a meta-field. Comma separated list. Beside the standard post fields and standard postmeta-fields, the following custom meta fields are available. Copy only the values in [] in the field without the brackets. %s', 'commonsbooking' ) ), 
						Settings::returnFormattedMetaboxFields('cb_location') ),
						'id'   => 'location-fields',
						'type' => 'text'
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'Item-Fields', 'commonsbooking' ) ),
						'desc' => sprintf ( commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a post- or a meta-field. Comma separated list. Beside the standard post fields and standard postmeta-fields, the following custom meta fields are available. Copy only the values in [] in the field without the brackets. %s', 'commonsbooking' ) ), 
						Settings::returnFormattedMetaboxFields('cb_location') ),
						'id'   => 'item-fields',
						'type' => 'text'
					),
					array(
						'name' => commonsbooking_sanitizeHTML( __( 'User-Fields', 'commonsbooking' ) ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Just add field names, no matter if its a userfield or a meta-field. Comma separated list.', 'commonsbooking' ) ), 
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
						'id'            => 'export-custom-field',
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
						'desc' => commonsbooking_sanitizeHTML( __( 'If selected, the API is enabled. See more information in the documentation: <a target="_blank" href="https://commonsbooking.org/docs/schnittstellen-api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'   => 'api-activated',
						'type' => 'checkbox',
					),
					array(
						'name' => esc_html__( 'Enable API Access without API-Key', 'commonsbooking' ),
						'desc' => commonsbooking_sanitizeHTML( __( 'If selected, the API is accessible without an API-Key. For details see: <a target="_blank" href="https://commonsbooking.org/docs/schnittstellen-api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'   => 'apikey_not_required',
						'type' => 'checkbox',
					),

					array(
						// Repeatable group -> API Shares
						'name'       => esc_html__( 'API shares', 'commonsbooking' ),
						'desc'       => commonsbooking_sanitizeHTML( __( 'You can define on ore more API shares. Read the documentation for more information about API shares and configuration <a target="_blank" href="https://commonsbooking.org/docs/schnittstellen-api/commonsbooking-api/">API documentation</a>', 'commonsbooking' ) ),
						'id'         => "api_share_group",
						'type'       => 'group',
						'repeatable' => true,
						'options'    => array(
							'group_title'   => 'API {#}',
							'add_button'    => 'Add Another API',
							'remove_button' => 'Remove API',
							'closed'        => false,  // Repeater fields closed by default - neat & compact.
							'sortable'      => false,  // Allow changing the order of repeated groups.
						),

						'fields' => [
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

						]
					),
				]
			),
		),
	),
	/* Tab: export end */

	/* Tab: meta data set start */
	'metadata'     => array(
		'title'        => __( 'Meta Data-Sets', 'commonsbooking' ),
		'id'           => 'custom_metadata',
		'field_groups' => array(
			'custom_metadata' => array(
				'title'  => esc_html__( 'Set Custom metadata to locations and items', 'commonsbooking' ),
				'desc'   => commonsbooking_sanitizeHTML( __(
					'This is an advanced feature and should only be used if you are experienced or instructed how to set it up properly. In future versions we will add more detailed information and documentation.'
					, 'commonsbooking' ) ),
				'id'     => 'meta_data_group',
				'fields' => [
					array(
						'name' => esc_html__( 'Meta Data', 'commonsbooking' ),
						'desc' => commonsbooking_sanitizeHTML( __( 'Use only this format, separated by semicolon and and each entry in a new line: <br>post_type(item/location);field-name;label(english),type(checkbox,number,text),description(in english)<br>
                                        Example: item;waterproof;Waterproof material;checkbox;"This item is waterproof and can be used in heavy rain" ', 'commonsbooking' ) ),
						'id'   => "metadata",
						'type' => 'textarea',
					),
				]
			),
		),
	),
	/* Tab: meta data end */
);
