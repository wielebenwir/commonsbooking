<?php

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
    'main'      => array(
        'title'        => commonsbooking_sanitizeHTML( __('Welcome', 'commonsbooking') ),
        'id'           => 'main',
        'is_top_level' => true, /* indicate first tab */
        'field_groups' => array(
            /* welcome group start */
            'welcome' => array(
                'title'  => commonsbooking_sanitizeHTML( __('Welcome to CommonsBooking', 'commonsbooking') ),
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

    // /* Tab: general start*/
     'general' => array(
       'title' => commonsbooking_sanitizeHTML( __( 'General', 'commonsbooking' ) ),
       'id' => 'general',
       'field_groups' => array (
         /* posttype: naming, rewrite, archives start */
         'posttypes' => array(
           'title' => __( 'Naming and permalinks', 'commonsbooking' ),
           'id' => 'posttypes',
           // tranlsators: %s = admin url options page
           'desc' =>
            commonsbooking_sanitizeHTML( __( 
                    'Customize names & slugs. You can set an individual slug for items and locations to create individual permalinks. <br><b>Notice</b>: If the new settings do not work directly (you will get a 404 page error on frontend pages), you must click on the Settings -> Permalinks page after saving these settings to refresh the Wordpress permalink settings.'
                    , 'commonsbooking' ) ),
           'fields' => array(
//             array(
//               'name'    => esc_html_e( 'Item singular name', 'commonsbooking' ),
//               'id'      => 'posttypes_items-singular',
//               'type'    => 'text',
//               'default' => esc_html_e( 'item', 'commonsbooking' ),
//             ),
//             array(
//               'name'    => esc_html_e( 'Items plural name', 'commonsbooking' ),
//               'id'      => 'posttypes_items-plural',
//               'type'    => 'text',
//               'default' => esc_html_e( 'items', 'commonsbooking' ),
//             ),
             array(
               'name'    => commonsbooking_sanitizeHTML( __( 'Items slug', 'commonsbooking' ) ),
               'id'      => 'posttypes_items-slug',
               // translators: %s = example url (like website.com/cb-items/)
               'description' =>
                            sprintf ( commonsbooking_sanitizeHTML( __(
                                'The slug for article detail page. Type in the slug without slashes. Example: <strong>cb_item</strong> or <strong>article</strong>.  The url for the items detail will be like %s',
                                'commonsbooking' ) ), network_site_url( '/cb_item/' ) ),
               'type'    => 'text',
               'default' => \CommonsBooking\Wordpress\CustomPostType\Item::$postType,
             ),
             array(
               'name'    => esc_html__( 'Locations slug', 'commonsbooking' ),
               'id'      => 'posttypes_locations-slug',
               // translators: %s = example url (like website.com/cb-locations/)
               'description' =>
                            sprintf ( commonsbooking_sanitizeHTML( __(
                                'The slug for location detail page. Type in the slug without slashes. Example: <strong>cb_location</strong> or <strong>location</strong>.  The url for the items detail will be like %s',
                                'commonsbooking' ) ), network_site_url('/cb_location/') ),
               'type'    => 'text',
               'default' => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
             ),
             array(
                'name'    => esc_html__( 'Bookings Page', 'commonsbooking' ),
                'id'      => 'bookings_page',
                // translators: %s = example url (like website.com/cb-locations/)
                'description' =>
                             sprintf ( commonsbooking_sanitizeHTML( __(
                                 'The page where you included the [cb_bookings] shortcode. This is used in the Users Widget',
                                 'commonsbooking' ) ), network_site_url('/bookings/') ),
                'type'    => 'select',
                'options' => \CommonsBooking\Helper\Wordpress::getPageListTitle(),
              ),
           )
         ),
       )
     ),



    /* Tab: general end*/


    /* Tab Booking Codes start */
    'bookingcodes' => array(
        'title' => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
        'id' => 'bookingcodes',
        'field_groups' => array (
            'bookingcodes' => array(
                'title' => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
                'id' => 'bookingcodes',
                'desc' => 
                 commonsbooking_sanitizeHTML( __('Enter the booking codes to be generated in advance for booking types with all-day booking time frames.  Enter booking codes as a comma separated list, e.g.: Code1,Code2,Code3,Code4
                <br>More information in the documentation: <a href="https://commonsbooking.org/?p=870" target="_blank">Booking Codes</a>', 'commonsbooking') ),
                'fields' => array(
                    array(
                        'name'    => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
                        'id'      => 'bookingcodes',
                        'type'    => 'textarea',
                    ),
                )
            )
        )
    ),

    /* Tab: templates start*/
    'templates' => array(
        'title'        => commonsbooking_sanitizeHTML( __('Templates', 'commonsbooking') ),
        'id'           => 'templates',
        // field group email templates start
        'field_groups' => array(
            'emailtemplates' => array(
                'title'  => commonsbooking_sanitizeHTML( __('Email templates', 'commonsbooking') ),
                'id'     => 'emailtemplates',
                'fields' => array(
                    array(
                        'name' => commonsbooking_sanitizeHTML( __('Mail-Header from E-Mail', 'commonsbooking') ),
                        'desc' => commonsbooking_sanitizeHTML( __('E-Mail that will be shown as sender in generated emails', 'commonsbooking') ),
                        'id'   => 'emailheaders_from-email',
                        'type' => 'text',
                        'default' => get_option('admin_email'),
                    ),
                    array(
                        'name' => commonsbooking_sanitizeHTML( __( 'Mail-Header from Name', 'commonsbooking') ),
                        'desc' => commonsbooking_sanitizeHTML( __('Name that will be shown as sender in generated emails', 'commonsbooking') ),
                        'id'   => 'emailheaders_from-name',
                        'type' => 'text',
                        'default' => get_option('blogname'),
                    ),
                    array(
                        'name'          => commonsbooking_sanitizeHTML( __('Booking confirmed email subject', 'commonsbooking') ),
                        'id'            => 'emailtemplates_mail-booking-confirmed-subject',
                        'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
                        'type'          => 'text',
                        'default'       => commonsbooking_sanitizeHTML( __('Your booking {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}} ',
                            'commonsbooking') ),
                    ),
                    array(
                        'name'          => commonsbooking_sanitizeHTML( __('Booking confirmed email body', 'commonsbooking') ),
                        'id'            => 'emailtemplates_mail-booking-confirmed-body',
                        'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_body',
                        'type'          => 'textarea',
                        'default'       => commonsbooking_sanitizeHTML( __('
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
                        ', 'commonsbooking') ),
                    ),
                    array(
                        'name'    => commonsbooking_sanitizeHTML( __('Booking canceled email subject', 'commonsbooking') ),
                        'id'      => 'emailtemplates_mail-booking-canceled-subject',
                        'type'    => 'text',
                        'default' => commonsbooking_sanitizeHTML( __('Booking canceled: {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}}', 'commonsbooking') ),
                    ),
                    array(
                        'name'    => commonsbooking_sanitizeHTML( __('Booking canceled email body', 'commonsbooking') ),
                        'id'      => 'emailtemplates_mail-booking-canceled-body',
                        'type'    => 'textarea',
                        'default' => commonsbooking_sanitizeHTML( __('
Hi {{user:first_name}},<br>
<br>
your booking of {{item:post_title}} at {{location:post_title}} {{booking:formattedBookingDate}} has been canceled.<br>
<br>          
Thanks, the Team.
                            ', 'commonsbooking') ),
                    ),
                )
            ),
            /* field group email templates end */




            /* field group template and booking message templates start */
            'messagetemplates' => array(
              'title' => commonsbooking_sanitizeHTML( __( 'Template and booking process messages', 'commonsbooking' ) ),
              'id' => 'messagetemplates',
              'desc' => '',
              'fields' => array(
                array(
                  'name'    => commonsbooking_sanitizeHTML ( __( 'Item not available', 'commonsbooking' ) ),
                  'id'      => 'item-not-available',
                  'type'    => 'textarea_small',
                  'desc'    => commonsbooking_sanitizeHTML( __('This text is shown on item listings (shortcode cb_items) and item detail page if there is no valid bookable timeframe set for this item', 'commonsbooking') ),
                  'default' => esc_html__( 'This item is currently not bookable.', 'commonsbooking' ),
                ),
                array(
                    'name'    => esc_html__( 'Location without available items', 'commonsbooking' ),
                    'id'      => 'location-without-items',
                    'type'    => 'textarea_small',
                    'desc'    => esc_html__('This text is shown on location listings and location detail page if there are no items available at this location', 'commonsbooking'),
                    'default' => esc_html__( 'No items available at this location right now.', 'commonsbooking' ),
                  ),
              )
            ),
            /* message templates end */
        )
    ),
    /* Tab: templates end*/

    /* Tab: migration start */
    'migration' => array(
        'title'        => __('Migration', 'commonsbooking'),
        'id'           => 'migration',
        'field_groups' => array(
            'migration' => array(
                'title'       => esc_html__('Migrate from Commons Booking Version 0.X', 'commonsbooking'),
                'id'          => 'migration',
                'desc'        => commonsbooking_sanitizeHTML( __('Migrate data from CommonsBooking Version 0.X. <br>The migration includes: locations, items, timeframes and bookings. <br><span style="color:red">If you have clicked "Migrate" before, starting the migration again will overwrite any changes you made to  locations, items, timeframes and bookings</span>.<br>Please read the documentation <a target="_blank" href="https://commonsbooking.org/dokumentation/?p=434">How to migrate from version 0.9.x to 2.x.x </a> before you start migration.', 'commonsbooking') ),
                'fields'      => [
                    array(
                        'name'          => commonsbooking_sanitizeHTML( __('Start Migration', 'commonsbooking') ),
                        'id'            => 'migration-custom-field',
                        'type'          => 'text',
                        'render_row_cb' => array(\CommonsBooking\View\Migration::class, 'renderMigrationForm'),
                    )
                ]
            ),
            'cb1-user-fields' => array(
                'title'       => commonsbooking_sanitizeHTML( __('CommonsBooking Version 0.X profile fields', 'commonsbooking') ),
                'id'          => 'cb1-user-fields',
                'desc'        => commonsbooking_sanitizeHTML( __('Enable the following legacy CommonsBooking Version 0.X user profile fields:', 'commonsbooking')  ) . '<br><i> first_name,  last_name,  phone,  address,   terms_accepted </i>',
                'fields'      => [
                    array(
                        'name'          => esc_html__('Enable', 'commonsbooking'),
                        'id'            => 'enable-cb1-user-fields',
                        'type'          => 'checkbox',
                    ),
                    array(
                        'name'          => esc_html__('Terms & Services Url', 'commonsbooking'),
                        'id'            => 'cb1-terms-url',
                        'type'          => 'text',
                    )
                ]
            )
        )
    )
    /* Tab: migration end */
);
