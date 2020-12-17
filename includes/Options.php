<?php

/**
 * Plugin Options
 *
 * Tabs -> field "groups" -> fields
 */
$options_array = array(

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
                            <p>To get startet visit our tutorials and documentation on <a href="https://commonsbooking.org/dokumentation">https://commonsbooking.org/dokumentation</a></p>

                            <h2>Questions or bug reports?</h2>
                            <p>Ask your questions or send us your bug reports here <a href="https://commonsbooking.org/kontakt/">https://commonsbooking.org/kontakt/</a></p>

                            <h2>Contribute</h2>
                            The future of this plugin depends on your support. You can support us by make a donation on our website: <a href="https://www.wielebenwir.de/verein/unterstutzen">wielebenwir</a>'
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
                     sprintf( commonsbooking_sanitizeHTML( __( 
                    'Customize names & slugs. <br><b>Important:</b> After changing these settings, the option <a href="%s">Permalinks</a> in Wordpress settings must be called and saved once for the changes to take effect.' 
                    , 'commonsbooking' ) ), admin_url( 'options-permalink.php' ) ),
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
                                'The url for the items archive. E.g: %s', 
                                'commonsbooking' ) ), network_site_url( '/cb_items/' ) ),
               'type'    => 'text',
               'default' => \CommonsBooking\Wordpress\CustomPostType\Item::$postType,
             ),
             array(
               'name'    => esc_html__( 'Locations slug', 'commonsbooking' ),
               'id'      => 'posttypes_locations-slug',
               // translators: %s = example url (like website.com/cb-locations/)
               'description' => 
                            sprintf ( commonsbooking_sanitizeHTML( __( 
                                'The url for the locations archive. E.g: %s', 
                                'commonsbooking' ) ), network_site_url('/cb-locations/') ),
               'type'    => 'text',
               'default' => \CommonsBooking\Wordpress\CustomPostType\Location::$postType,
             ),
           )
         ),
       )
     ),



    // /* Tab: general end*/

    'bookingcodes' => array(
        'title' => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
        'id' => 'general',
        'field_groups' => array (
            /* posttype: naming, rewrite, archives start */
            'bookingcodes' => array(
                'title' => commonsbooking_sanitizeHTML( __( 'Booking Codes', 'commonsbooking' ) ),
                'id' => 'bookingcodes',
                'desc' => '',
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
        'field_groups' => array(
            'emailtemplates' => array(
                'title'  => commonsbooking_sanitizeHTML( __('Email templates', 'commonsbooking') ),
                'id'     => 'emailtemplates',
                'desc'   => '',
                'fields' => array(
                    array(
                        'name' => commonsbooking_sanitizeHTML( __('Mail-Header from E-Mail', 'commonsbooking') ),
                        'desc' => commonsbooking_sanitizeHTML( __('E-Mail that will be shown as sender in generated emails', 'commonsbooking') ),
                        'id'   => 'emailheaders_from-email',
                        'type' => 'text',
                        //'default' => __( '', 'commonsbooking' ),
                    ),
                    array(
                        'name' => commonsbooking_sanitizeHTML( __( 'Mail-Header from Name', 'commonsbooking') ),
                        'desc' => commonsbooking_sanitizeHTML( __('Name that will be shown as sender in generated emails', 'commonsbooking') ),
                        'id'   => 'emailheaders_from-name',
                        'type' => 'text',
                        //'default' => commonsbooking_sanitizeHTML( '', 'commonsbooking' ),
                    ),
                    array(
                        'name'          => commonsbooking_sanitizeHTML( __('Booking confirmed email subject', 'commonsbooking') ),
                        'id'            => 'emailtemplates_mail-booking-confirmed-subject',
                        'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
                        'type'          => 'text',
                        'default'       => commonsbooking_sanitizeHTML( __('Your booking {{item:post_title}} at {{location:post_title}} {{booking:booking_timeframe_date}} ',
                            'commonsbooking') ),
                    ),
                    array(
                        'name'          => commonsbooking_sanitizeHTML( __('Booking confirmed email body', 'commonsbooking') ),
                        'id'            => 'emailtemplates_mail-booking-confirmed-body',
                        'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_body',
                        'type'          => 'textarea',
                        'default'       => commonsbooking_sanitizeHTML( __('
                            Hi {{user:first_name}},<br>
                            <p>thank you for booking {{item:post_title}} {{booking:booking_timeframe_date}}.
                            </p>
                            
                            Pick up date and time: <b>{{booking:pickupDatetime}}</b><br>
                            {{location:pickupInstructions}}
                            {{booking:formattedBookingCode}}
                            <br>
                            Return date and time:
                            <b>{{booking:returnDatetime}}</b>
                            <br><br>
                            <b>Location address</b><br>
                            {{location:formattedAddress}}<br>
                            {{location:formattedContactInfoOneLine}}
                            <br>
                            <p>Click here to see or cancel your booking: {{booking:bookingLink}}.<br>
                            <b>Notice:</b> You need to be logged in to see your booking. <br>
                            If the link leads you to the homepage of the webseite,
                            please login first and then click the link again.<br></p>
                            
                            <h3>Your information</h3>
                            <p>Login: {{user:user_nicename}}<br>
                            Name: {{user:first_name}} {{user:last_name}}.</p>
                            
                            <p>Thanks, the Team. </p>
                        ', 'commonsbooking') ),
                    ),
                    array(
                        'name'    => commonsbooking_sanitizeHTML( __('Booking canceled email subject', 'commonsbooking') ),
                        'id'      => 'emailtemplates_mail-booking-canceled-subject',
                        'type'    => 'text',
                        'default' => commonsbooking_sanitizeHTML( __('Booking canceled: {{item:post_title}} at {{location:post_title}} {{booking:booking_timeframe_date}}', 'commonsbooking') ),
                    ),
                    array(
                        'name'    => commonsbooking_sanitizeHTML( __('Booking canceled email body', 'commonsbooking') ),
                        'id'      => 'emailtemplates_mail-booking-canceled-body',
                        'type'    => 'textarea',
                        'default' => commonsbooking_sanitizeHTML( __('
                            Hi {{user:first_name}},<br>
                            <p>your booking {{item:post_title}} at {{location:post_title}} {{booking:booking_timeframe_date}} has been cancelled.
                            </p>               
                            <h3>Your information</h3>
                            <p>Login: {{user:user_nicename}}<br>
                            Name: {{user:first_name}} {{user:last_name}}.</p>
                            
                            <p>Thanks, the Team. </p>
                            ', 'commonsbooking') ),
                    ),
                )
            ),
            /* email templates end */



            /* message templates start */
            // 'messagetemplates' => array(
            //   'title' => commonsbooking_sanitizeHTML( 'Booking process messages', 'commonsbooking' ),
            //   'id' => 'messagetemplates',
            //   'desc' => '',
            //   'fields' => array(
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Please confirm your booking', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_please-confirm',
            //       'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_pleaseconfirm',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML('Please review your booking of {{item-name}} at {{location-name}} and confirm it.', 'commonsbooking'),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Booking confirmed', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-confirmed',
            //       'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_confirmed',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'Your booking of {{item-name}} at {{location-name}} has been confirmed!', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Booking cancelled', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-canceled',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'Your booking has been cancelled!<br>', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Request cancel confirmation', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-cancel-request-text',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'Click "OK" to cancel the booking.', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Request un-cancel confirmation', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-uncancel-request-text',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'Click "OK" to un-cancel your booking.', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Access not allowed', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-not-allowed',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'You are not allowed to access this booking.', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'No bookings', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-no_bookings',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'No bookings yet.', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Not logged in', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_booking-not_logged-in',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'You have to be logged in to access your bookings. {{site-registration-link}}', 'commonsbooking' ),
            //     ),
            //     array(
            //       'name'    => commonsbooking_sanitizeHTML( 'Not available', 'commonsbooking' ),
            //       'id'      => 'messagetemplates_item-not-available',
            //       'type'    => 'textarea_small',
            //       'default' => commonsbooking_sanitizeHTML( 'This item is currently not available.', 'commonsbooking' ),
            //     ),
            //   )
            // ),
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
                'title'       => __('Migrate from Commons Booking Version 0.X', 'commonsbooking'),
                'id'          => 'migration',
                'desc'        => commonsbooking_sanitizeHTML( __('Migrate data from CommonsBooking Version 0.X. <br>The migration includes: locations, items, timeframes and bookings. <br>Please read the documentation on <a href="https://commonsbooking.org/dokumentation/">https://commonsbooking.org/dokumentation/</a> before you start migration.', 'commonsbooking') ),
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

// register option tabs
foreach ($options_array as $tab_id => $tab) {
    new CommonsBooking\Wordpress\Options\OptionsTab($tab_id, $tab);
}
