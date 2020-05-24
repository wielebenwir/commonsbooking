<?php 

/**
 * Plugin Options
 * 
 * Tabs -> field "groups" -> fields
 */

$options_array = array(

  /* Tab: main start*/
  'main' => array(
    'title' => __( 'Welcome', TRANSLATION_CONST ),
    'id' => 'main',
    'is_top_level' => TRUE, /* indicate first tab */
    'field_groups' => array (
      /* welcome group start */
      'welcome' => array(
        'title' => __( 'Welcome to CommonsBooking', TRANSLATION_CONST ),
        'id' => 'welcome',
        'desc'    => 'CB Version ' . CB_VERSION,
        'fields' => array(
        )
      ),
      'test2' => array(
        'title' => __( 'Second group', TRANSLATION_CONST ),
        'id' => 'test2',
        'desc' => 'Second group description',
        'fields' => array(
          array(
            'name'    => __( 'Item singular name', TRANSLATION_CONST ),
            'id'      => 'test2',
            'type'    => 'text',
            'default' => __( 'fdsfd', TRANSLATION_CONST ),
          ),
        )
      ),
    )
  ),
  /* Tab: main end*/
  /* Tab: general start*/
  'general' => array(
    'title' => __( 'General', TRANSLATION_CONST ),
    'id' => 'general',
    'field_groups' => array ( 
      /* posttype: naming, rewrite, archives start */
      'posttypes' => array(
        'title' => __( 'Naming and permalinks', TRANSLATION_CONST ),
        'id' => 'posttypes',
        'desc' => 'Customize names & slugs.',
        'fields' => array(
          array(
            'name'    => __( 'Item singular name', TRANSLATION_CONST ),
            'id'      => 'posttypes_items-singular',
            'type'    => 'text',
            'default' => __( 'item', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Items plural name', TRANSLATION_CONST ),
            'id'      => 'posttypes_items-plural',
            'type'    => 'text',
            'default' => __( 'items', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Items slug', TRANSLATION_CONST ),
            'id'      => 'posttypes_items-slug',
            'description' => sprintf ( __( 'The url for the items archive. E.g: %s ', TRANSLATION_CONST ), network_site_url('/cb2-items/') ),
            'type'    => 'text',
            'default' => __( 'cb2_item', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Create an item post type archive', TRANSLATION_CONST ),
            'id'      => 'posttypes_items-archive',
            'type'    => 'checkbox',
            // 'default' => cmb2_set_checkbox_default_for_new_post( TRUE ),
          ),
          array(
            'name'    => __( 'Location singular name', TRANSLATION_CONST ),
            'id'      => 'posttypes_locations-singular',
            'type'    => 'text',
            'default' => __( 'location', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Locations plural name', TRANSLATION_CONST ),
            'id'      => 'posttypes_locations-plural',
            'type'    => 'text',
            'default' => __( 'locations', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Locations slug', TRANSLATION_CONST ),
            'id'      => 'posttypes_locations-slug',
            'description' => sprintf ( __( 'The url for the locations archive. E.g: %s ', TRANSLATION_CONST ), network_site_url('/cb2-locations/') ),
            'type'    => 'text',
            'default' => __( 'cb2_location', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Create a locations post type archive', TRANSLATION_CONST ),
            'id'      => 'posttypes_locations-archive',
            'type'    => 'checkbox',
            // 'default' => cmb2_set_checkbox_default_for_new_post( TRUE ),
          ),
          array(
            'name'    => __( 'Bookings slug', TRANSLATION_CONST ),
            'id'      => 'posttypes_bookings-slug',
            'description' => sprintf ( __( 'The url for the bookings archive. E.g: %s ', TRANSLATION_CONST ), network_site_url('/cb2-bookings/') ),
            'type'    => 'text',
            'default' => __( 'cb2_booking', TRANSLATION_CONST ),
          ),
        )
      ),
      /* designation: formats start */
      'formats'  => array(
        'title'  => __( 'Formats', TRANSLATION_CONST ),
        'id'     => 'formats',
        'fields' => array(
          array(
            'name'    => __('Calendar date format', TRANSLATION_CONST),
            'id'      => 'formats_date',
            'type'    => 'text',
            'description' => '<a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank">' . __( 'Documentation of date and time formatting', TRANSLATION_CONST) . '</a>',
            'default' =>  'j M'
          ),
          array(
            'name'    => __('Address format', TRANSLATION_CONST),
            'id'      => 'formats_address',
            'type'    => 'textarea',
            'desc' => '', 
            'default' =>  '{{location-geo_street}}, {{location-geo_postcode}} {{location-geo_city}}'
          ),
        ),
      ),
    /* designation: formats end */
    )
  ), 
  /* Tab: general end*/
  /* Tab: templates start*/
  'templates' => array(
    'title' => __( 'Templates', TRANSLATION_CONST ),
    'id' => 'templates',
    'field_groups' => array ( 
      'emailtemplates' => array(
        'title' => __( 'Email templates', TRANSLATION_CONST ),
        'id' => 'emailtemplates',
        'desc' => '',
        'fields' => array(
          // TODO: old commons-booking_mail_registration_subject?
          // TODO: old commons-booking_mail_registration_body?
          array(
            'name' => __( 'Booking pending email subject', TRANSLATION_CONST ),
            'desc' => __('', TRANSLATION_CONST),
            'id' => 'emailtemplates_mail-booking-pending-subject',
            'type' => 'text',
            'default' => __( 'Pending booking', TRANSLATION_CONST ),
          ),
          array(
            'name' => __( 'Booking pending email body', TRANSLATION_CONST ),
            'desc' => __('', TRANSLATION_CONST),
            'id' => 'emailtemplates_mail-booking-pending-body',
            'type' => 'textarea',
            'default' => __( 'Pending booking of {{item-name}} at {{location-name}}.', TRANSLATION_CONST ),
          ),
          array(
            'name' => __( 'Booking approved email subject', TRANSLATION_CONST ),
            'id' => 'emailtemplates_mail-booking-approved-subject',
            'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
            'type' => 'text',
            'default' => __( 'Your booking {{item_name}}.', TRANSLATION_CONST ),
          ),
          array(
            'name' => __( 'Booking approved email body', TRANSLATION_CONST ),
            'id' => 'emailtemplates_mail-booking-approved-body',
            'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_body',
            'type' => 'textarea',
            'default' => __( '<h2>Hi {{user-first_name}}, thanks for booking {{item_name}}!</h2>
              <p>Click here to see or cancel you booking: {{booking-permalink}}.</p>
              <h3>Pick up information</h3>
              <p>Pick up {{item_name}} at {{location-name}}.<br>
              Your booking periode: {{booking-periods}}<br>
              Pick up the item here: {{location-geo_address}}<br>
              Opening hours of the location: {{location-opening_hours}}.</p>
              <h3>Your information</h3>
              <p>Name: {{user-first_name}} {{user-last_name}}.</p>
              <p>Thanks, the Team. </p>', TRANSLATION_CONST ),
          ),
          array(
            'name' => __( 'Booking canceled email subject', TRANSLATION_CONST ),
            'id' => 'emailtemplates_mail-booking-canceled-subject',
            'type' => 'text',
            'default' => __( 'Canceled booking.', TRANSLATION_CONST ),
          ),
          array(
            'name' => __( 'Booking canceled email body', TRANSLATION_CONST ),
            'id' => 'emailtemplates_mail-booking-canceled-body',
            'type' => 'textarea',
            'default' => __( 'Canceled booking of {{item-name}} at {{location-name}}.', TRANSLATION_CONST ),
          ),
        )
      ),
      /* email templates end */
      /* message templates start */
      'messagetemplates' => array(
        'title' => __( 'Booking process messages', TRANSLATION_CONST ),
        'id' => 'messagetemplates',
        'desc' => '',
        'fields' => array(
          array(
            'name'    => __( 'Please confirm your booking', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_please-confirm',
            'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_pleaseconfirm',
            'type'    => 'textarea_small',
            'default' => __('Please review your booking of {{item-name}} at {{location-name}} and confirm it.', TRANSLATION_CONST),
          ),
          array(
            'name'    => __( 'Booking confirmed', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-confirmed',
            'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_confirmed',
            'type'    => 'textarea_small',
            'default' => __( 'Your booking of {{item-name}} at {{location-name}} has been confirmed!', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Booking canceled', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-canceled',
            'type'    => 'textarea_small',
            'default' => __( 'Your booking has been canceled!<br>', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Request cancel confirmation', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-cancel-request-text',
            'type'    => 'textarea_small',
            'default' => __( 'Click "OK" to cancel the booking.', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Request un-cancel confirmation', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-uncancel-request-text',
            'type'    => 'textarea_small',
            'default' => __( 'Click "OK" to un-cancel your booking.', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Access not allowed', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-not-allowed',
            'type'    => 'textarea_small',
            'default' => __( 'You are not allowed to access this booking.', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'No bookings', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-no_bookings',
            'type'    => 'textarea_small',
            'default' => __( 'No bookings yet.', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Not logged in', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_booking-not_logged-in',
            'type'    => 'textarea_small',
            'default' => __( 'You have to be logged in to access your bookings. {{site-registration-link}}', TRANSLATION_CONST ),
          ),
          array(
            'name'    => __( 'Not available', TRANSLATION_CONST ),
            'id'      => 'messagetemplates_item-not-available',
            'type'    => 'textarea_small',
            'default' => __( 'This item is currently not available.', TRANSLATION_CONST ),
          ),
        )
      ),
      /* message templates end */
      /* bookingbar templates start */
      'bookingbartemplates' => array(
        'title' => __('Bookingbar strings', TRANSLATION_CONST),
        'id' => 'bookingbartemplates',
        'desc' => '',
        'fields' => array(
          array(
            'name'    => __('Intro text', TRANSLATION_CONST),
            'desc'    => __('{{max-slots}} will be replaced with max slots.', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_intro-text',
            'type'    => 'textarea_small',
            'default' => __('Choose bookable slots on the calendar', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Notice: Too few slots selected', TRANSLATION_CONST),
            'desc'    => __('Displayed if a user tries to select too few slots than allowed.', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_notice-min-slots',
            'type'    => 'textarea_small',
            'default' => __('You need to book more slots.', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Notice: Too many slots selected', TRANSLATION_CONST),
            'desc'    => __('Displayed if a user tries to select more slots than allowed. {{max-slots}} will be replaced with max slots.', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_notice-max-slots',
            'type'    => 'textarea_small',
            'default' => __('You can not book more than {{max-slots}} slots.', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Notice: Booking over another booking', TRANSLATION_CONST),
            'desc'    => __('Displayed if a user tries to create a selection that would include a non-includable slot (e.g. another booking) .', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_notice-non-includable',
            'type'    => 'textarea_small',
            'default' => __('Your selection contains another booking.', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Notice: Not logged in', TRANSLATION_CONST),
            'desc'    => __('Displayed if a visitor clicks the calendar) .', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_notice-not-logged-in',
            'type'    => 'textarea_small',
            'default' => __('You need to be logged in to book.', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Pickup from', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_pickup-from',
            'type'    => 'text',
            'default' => __('Pickup from:', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Return until', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_return-before',
            'type'    => 'text',
            'default' => __('Return until:', TRANSLATION_CONST),
          ),
          array(
            'name'    => __('Button Label', TRANSLATION_CONST),
            'id'      => 'bookingbartemplates_button-label',
            'type'    => 'text',
            'default' => __('Book', TRANSLATION_CONST),
          ),
        )
      ),
    )
  )
  /* Tab: templates end*/

);
?>