<?php 

/**
 * Plugin Options
 * 
 * Tabs -> field "groups" -> fields
 */

$options_array = array(

  /* Tab: main start*/
  'main' => array(
    'title' => __( 'Welcome', 'commonsbooking' ),
    'id' => 'main',
    'is_top_level' => TRUE, /* indicate first tab */
    'field_groups' => array (
      /* welcome group start */
      'welcome' => array(
        'title' => __( 'Welcome to CommonsBooking', 'commonsbooking' ),
        'id' => 'welcome',
        'desc'    => 'CB Version ' . CB_VERSION,
        'fields' => array(
        )
      )
    )
  ),
  /* Tab: main end*/

  // /* Tab: general start*/
  // 'general' => array(
  //   'title' => __( 'General', 'commonsbooking' ),
  //   'id' => 'general',
  //   'field_groups' => array ( 
  //     /* posttype: naming, rewrite, archives start */
  //     'posttypes' => array(
  //       'title' => __( 'Naming and permalinks', 'commonsbooking' ),
  //       'id' => 'posttypes',
  //       'desc' => 'Customize names & slugs.',
  //       'fields' => array(
  //         array(
  //           'name'    => __( 'Item singular name', 'commonsbooking' ),
  //           'id'      => 'posttypes_items-singular',
  //           'type'    => 'text',
  //           'default' => __( 'item', 'commonsbooking' ),
  //         ),
  //         array(
  //           'name'    => __( 'Items plural name', 'commonsbooking' ),
  //           'id'      => 'posttypes_items-plural',
  //           'type'    => 'text',
  //           'default' => __( 'items', 'commonsbooking' ),
  //         ),
  //         array(
  //           'name'    => __( 'Items slug', 'commonsbooking' ),
  //           'id'      => 'posttypes_items-slug',
  //           'description' => sprintf ( __( 'The url for the items archive. E.g: %s ', 'commonsbooking' ), network_site_url('/cb_items/') ),
  //           'type'    => 'text',
  //           'default' => __( 'cb2_item', 'commonsbooking' ),
  //         ),
  //         array(
  //           'name'    => __( 'Create an item post type archive', 'commonsbooking' ),
  //           'id'      => 'posttypes_items-archive',
  //           'type'    => 'checkbox',
  //           // 'default' => cmb2_set_checkbox_default_for_new_post( TRUE ),
  //         ),
  //         array(
  //           'name'    => __( 'Location singular name', 'commonsbooking' ),
  //           'id'      => 'posttypes_locations-singular',
  //           'type'    => 'text',
  //           'default' => __( 'location', 'commonsbooking' ),
  //         ),
  //         array(
  //           'name'    => __( 'Locations plural name', 'commonsbooking' ),
  //           'id'      => 'posttypes_locations-plural',
  //           'type'    => 'text',
  //           'default' => __( 'locations', 'commonsbooking' ),
  //         ),
  //         array(
  //           'name'    => __( 'Locations slug', 'commonsbooking' ),
  //           'id'      => 'posttypes_locations-slug',
  //           'description' => sprintf ( __( 'The url for the locations archive. E.g: %s ', 'commonsbooking' ), network_site_url('/cb-locations/') ),
  //           'type'    => 'text',
  //           'default' => __( 'cb2_location', 'commonsbooking' ),
  //         ),
  //         array(
  //           'name'    => __( 'Create a locations post type archive', 'commonsbooking' ),
  //           'id'      => 'posttypes_locations-archive',
  //           'type'    => 'checkbox',
  //           // 'default' => cmb2_set_checkbox_default_for_new_post( TRUE ),
  //         ),
  //         array(
  //           'name'    => __( 'Bookings slug', 'commonsbooking' ),
  //           'id'      => 'posttypes_bookings-slug',
  //           'description' => sprintf ( __( 'The url for the bookings archive. E.g: %s ', 'commonsbooking' ), network_site_url('/cb-bookings/') ),
  //           'type'    => 'text',
  //           'default' => __( 'cb2_booking', 'commonsbooking' ),
  //         ),
  //       )
  //     ),
  //     /* designation: formats start */
  //     'formats'  => array(
  //       'title'  => __( 'Formats', 'commonsbooking' ),
  //       'id'     => 'formats',
  //       'fields' => array(
  //         array(
  //           'name'    => __('Calendar date format', 'commonsbooking'),
  //           'id'      => 'formats_date',
  //           'type'    => 'text',
  //           'description' => '<a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank">' . __( 'Documentation of date and time formatting', 'commonsbooking') . '</a>',
  //           'default' =>  'j M'
  //         ),
  //         array(
  //           'name'    => __('Address format', 'commonsbooking'),
  //           'id'      => 'formats_address',
  //           'type'    => 'textarea',
  //           'desc' => '', 
  //           'default' =>  '{{location-geo_street}}, {{location-geo_postcode}} {{location-geo_city}}'
  //         ),
  //       ),
  //     ),
  //   /* designation: formats end */
  //   )
  // ), 
  // /* Tab: general end*/



  /* Tab: templates start*/
  'templates' => array(
    'title' => __( 'Templates', 'commonsbooking' ),
    'id' => 'templates',
    'field_groups' => array ( 
      'emailtemplates' => array(
        'title' => __( 'Email templates', 'commonsbooking' ),
        'id' => 'emailtemplates',
        'desc' => '',
        'fields' => array(
          // TODO: old commons-booking_mail_registration_subject?
          // TODO: old commons-booking_mail_registration_body?
          array(
            'name' => __( 'Booking pending email subject', 'commonsbooking' ),
            'desc' => __('description', 'commonsbooking'),
            'id' => 'emailtemplates_mail-booking-pending-subject',
            'type' => 'text',
            'default' => __( 'Pending booking', 'commonsbooking' ),
          ),
          array(
            'name' => __( 'Booking pending email body', 'commonsbooking' ),
            'desc' => __('description', 'commonsbooking'),
            'id' => 'emailtemplates_mail-booking-pending-body',
            'type' => 'textarea',
            'default' => __( 'Pending booking of {{item-name}} at {{location-name}}.', 'commonsbooking' ),
          ),
          array(
            'name' => __( 'Booking confirmed email subject', 'commonsbooking' ),
            'id' => 'emailtemplates_mail-booking-confirmed-subject',
            'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
            'type' => 'text',
            'default' => __( 'Your booking {{item_name}}.', 'commonsbooking' ),
          ),
          array(
            'name' => __( 'Booking confirmed email body', 'commonsbooking' ),
            'id' => 'emailtemplates_mail-booking-confirmed-body',
            'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_body',
            'type' => 'textarea',
            'default' => __( '
            <h2>Hi {{user-first_name}},</h2>
            thank you for booking {{item:name}}.
            
            <b>Pick up information</b>
            <p>Pick up {{item_name}} at {{location-name}}.</p>
            
            <p>Pickup date an time:</b>
            <b>{{booking:pickup_datetime}}</b></p>
            {{location:pickupinstructions}}
            
            <p>Location address:</p>
            {{location:name}}<br>
            {{location:address_complete}}<br>
            {{location:contact}}<br>
            </p>
            
            
            <p>Click here to see or cancel your booking: {{booking-permalink}}.</p>
            
            <h3>Your information</h3>
            <p>Name: {{user-first_name}} {{user-last_name}}.</p>
            
            <p>Thanks, the Team. </p>
            ', 'commonsbooking' ),
          ),
          array(
            'name' => __( 'Booking cancelled email subject', 'commonsbooking' ),
            'id' => 'emailtemplates_mail-booking-cancelled-subject',
            'type' => 'text',
            'default' => __( 'cancelled booking.', 'commonsbooking' ),
          ),
          array(
            'name' => __( 'Booking cancelled email body', 'commonsbooking' ),
            'id' => 'emailtemplates_mail-booking-cancelled-body',
            'type' => 'textarea',
            'default' => __( 'cancelled booking of {{item-name}} at {{location-name}}.', 'commonsbooking' ),
          ),
        )
      ),
      /* email templates end */
      /* message templates start */
      'messagetemplates' => array(
        'title' => __( 'Booking process messages', 'commonsbooking' ),
        'id' => 'messagetemplates',
        'desc' => '',
        'fields' => array(
          array(
            'name'    => __( 'Please confirm your booking', 'commonsbooking' ),
            'id'      => 'messagetemplates_please-confirm',
            'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_pleaseconfirm',
            'type'    => 'textarea_small',
            'default' => __('Please review your booking of {{item-name}} at {{location-name}} and confirm it.', 'commonsbooking'),
          ),
          array(
            'name'    => __( 'Booking confirmed', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-confirmed',
            'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_confirmed',
            'type'    => 'textarea_small',
            'default' => __( 'Your booking of {{item-name}} at {{location-name}} has been confirmed!', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'Booking cancelled', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-cancelled',
            'type'    => 'textarea_small',
            'default' => __( 'Your booking has been cancelled!<br>', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'Request cancel confirmation', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-cancel-request-text',
            'type'    => 'textarea_small',
            'default' => __( 'Click "OK" to cancel the booking.', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'Request un-cancel confirmation', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-uncancel-request-text',
            'type'    => 'textarea_small',
            'default' => __( 'Click "OK" to un-cancel your booking.', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'Access not allowed', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-not-allowed',
            'type'    => 'textarea_small',
            'default' => __( 'You are not allowed to access this booking.', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'No bookings', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-no_bookings',
            'type'    => 'textarea_small',
            'default' => __( 'No bookings yet.', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'Not logged in', 'commonsbooking' ),
            'id'      => 'messagetemplates_booking-not_logged-in',
            'type'    => 'textarea_small',
            'default' => __( 'You have to be logged in to access your bookings. {{site-registration-link}}', 'commonsbooking' ),
          ),
          array(
            'name'    => __( 'Not available', 'commonsbooking' ),
            'id'      => 'messagetemplates_item-not-available',
            'type'    => 'textarea_small',
            'default' => __( 'This item is currently not available.', 'commonsbooking' ),
          ),
        )
      ),
      /* message templates end */
      /* bookingbar templates start */
      'bookingbartemplates' => array(
        'title' => __('Bookingbar strings', 'commonsbooking'),
        'id' => 'bookingbartemplates',
        'desc' => '',
        'fields' => array(
          array(
            'name'    => __('Intro text', 'commonsbooking'),
            'desc'    => __('{{max-slots}} will be replaced with max slots.', 'commonsbooking'),
            'id'      => 'bookingbartemplates_intro-text',
            'type'    => 'textarea_small',
            'default' => __('Choose bookable slots on the calendar', 'commonsbooking'),
          ),
          array(
            'name'    => __('Notice: Too few slots selected', 'commonsbooking'),
            'desc'    => __('Displayed if a user tries to select too few slots than allowed.', 'commonsbooking'),
            'id'      => 'bookingbartemplates_notice-min-slots',
            'type'    => 'textarea_small',
            'default' => __('You need to book more slots.', 'commonsbooking'),
          ),
          array(
            'name'    => __('Notice: Too many slots selected', 'commonsbooking'),
            'desc'    => __('Displayed if a user tries to select more slots than allowed. {{max-slots}} will be replaced with max slots.', 'commonsbooking'),
            'id'      => 'bookingbartemplates_notice-max-slots',
            'type'    => 'textarea_small',
            'default' => __('You can not book more than {{max-slots}} slots.', 'commonsbooking'),
          ),
          array(
            'name'    => __('Notice: Booking over another booking', 'commonsbooking'),
            'desc'    => __('Displayed if a user tries to create a selection that would include a non-includable slot (e.g. another booking) .', 'commonsbooking'),
            'id'      => 'bookingbartemplates_notice-non-includable',
            'type'    => 'textarea_small',
            'default' => __('Your selection contains another booking.', 'commonsbooking'),
          ),
          array(
            'name'    => __('Notice: Not logged in', 'commonsbooking'),
            'desc'    => __('Displayed if a visitor clicks the calendar) .', 'commonsbooking'),
            'id'      => 'bookingbartemplates_notice-not-logged-in',
            'type'    => 'textarea_small',
            'default' => __('You need to be logged in to book.', 'commonsbooking'),
          ),
          array(
            'name'    => __('Pickup from', 'commonsbooking'),
            'id'      => 'bookingbartemplates_pickup-from',
            'type'    => 'text',
            'default' => __('Pickup from:', 'commonsbooking'),
          ),
          array(
            'name'    => __('Return until', 'commonsbooking'),
            'id'      => 'bookingbartemplates_return-before',
            'type'    => 'text',
            'default' => __('Return until:', 'commonsbooking'),
          ),
          array(
            'name'    => __('Button Label', 'commonsbooking'),
            'id'      => 'bookingbartemplates_button-label',
            'type'    => 'text',
            'default' => __('Book', 'commonsbooking'),
          ),
        )
      ),
    )
  )
  /* Tab: templates end*/

);

// register option tabs
foreach ($options_array as $tab_id => $tab) {
    $field_groups = $tab['field_groups'];
    new CommonsBooking\Wordpress\Options\OptionsTab($tab_id, $tab);
}
