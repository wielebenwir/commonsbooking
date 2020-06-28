<?php 

/**
 * Plugin Options
 * 
 * Tabs -> field "groups" -> fields
 */

$options_array = array(

  /* Tab: main start*/
  'main' => array(
    'title' => __( 'Welcome', CB_TEXTDOMAIN ),
    'id' => 'main',
    'is_top_level' => TRUE, /* indicate first tab */
    'field_groups' => array (
      /* welcome group start */
      'welcome' => array(
        'title' => __( 'Welcome to CommonsBooking', CB_TEXTDOMAIN ),
        'id' => 'welcome',
        'desc'    => 'CB Version ' . CB_VERSION,
        'fields' => array(
        )
      )
    )
  ),
  /* Tab: main end*/
  /* Tab: general start*/
  'general' => array(
    'title' => __( 'General', CB_TEXTDOMAIN ),
    'id' => 'general',
    'field_groups' => array ( 
      /* posttype: naming, rewrite, archives start */
      'posttypes' => array(
        'title' => __( 'Naming and permalinks', CB_TEXTDOMAIN ),
        'id' => 'posttypes',
        'desc' => 'Customize names & slugs.',
        'fields' => array(
          array(
            'name'    => __( 'Item singular name', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_items-singular',
            'type'    => 'text',
            'default' => __( 'item', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Items plural name', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_items-plural',
            'type'    => 'text',
            'default' => __( 'items', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Items slug', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_items-slug',
            'description' => sprintf ( __( 'The url for the items archive. E.g: %s ', CB_TEXTDOMAIN ), network_site_url('/cb_items/') ),
            'type'    => 'text',
            'default' => __( 'cb2_item', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Create an item post type archive', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_items-archive',
            'type'    => 'checkbox',
            // 'default' => cmb2_set_checkbox_default_for_new_post( TRUE ),
          ),
          array(
            'name'    => __( 'Location singular name', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_locations-singular',
            'type'    => 'text',
            'default' => __( 'location', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Locations plural name', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_locations-plural',
            'type'    => 'text',
            'default' => __( 'locations', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Locations slug', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_locations-slug',
            'description' => sprintf ( __( 'The url for the locations archive. E.g: %s ', CB_TEXTDOMAIN ), network_site_url('/cb2-locations/') ),
            'type'    => 'text',
            'default' => __( 'cb2_location', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Create a locations post type archive', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_locations-archive',
            'type'    => 'checkbox',
            // 'default' => cmb2_set_checkbox_default_for_new_post( TRUE ),
          ),
          array(
            'name'    => __( 'Bookings slug', CB_TEXTDOMAIN ),
            'id'      => 'posttypes_bookings-slug',
            'description' => sprintf ( __( 'The url for the bookings archive. E.g: %s ', CB_TEXTDOMAIN ), network_site_url('/cb2-bookings/') ),
            'type'    => 'text',
            'default' => __( 'cb2_booking', CB_TEXTDOMAIN ),
          ),
        )
      ),
      /* designation: formats start */
      'formats'  => array(
        'title'  => __( 'Formats', CB_TEXTDOMAIN ),
        'id'     => 'formats',
        'fields' => array(
          array(
            'name'    => __('Calendar date format', CB_TEXTDOMAIN),
            'id'      => 'formats_date',
            'type'    => 'text',
            'description' => '<a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank">' . __( 'Documentation of date and time formatting', CB_TEXTDOMAIN) . '</a>',
            'default' =>  'j M'
          ),
          array(
            'name'    => __('Address format', CB_TEXTDOMAIN),
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
    'title' => __( 'Templates', CB_TEXTDOMAIN ),
    'id' => 'templates',
    'field_groups' => array ( 
      'emailtemplates' => array(
        'title' => __( 'Email templates', CB_TEXTDOMAIN ),
        'id' => 'emailtemplates',
        'desc' => '',
        'fields' => array(
          // TODO: old commons-booking_mail_registration_subject?
          // TODO: old commons-booking_mail_registration_body?
          array(
            'name' => __( 'Booking pending email subject', CB_TEXTDOMAIN ),
            'desc' => __('', CB_TEXTDOMAIN),
            'id' => 'emailtemplates_mail-booking-pending-subject',
            'type' => 'text',
            'default' => __( 'Pending booking', CB_TEXTDOMAIN ),
          ),
          array(
            'name' => __( 'Booking pending email body', CB_TEXTDOMAIN ),
            'desc' => __('', CB_TEXTDOMAIN),
            'id' => 'emailtemplates_mail-booking-pending-body',
            'type' => 'textarea',
            'default' => __( 'Pending booking of {{item-name}} at {{location-name}}.', CB_TEXTDOMAIN ),
          ),
          array(
            'name' => __( 'Booking confirmed email subject', CB_TEXTDOMAIN ),
            'id' => 'emailtemplates_mail-booking-confirmed-subject',
            'cb1_legacy_id' => 'commons-booking-settings-mail:commons-booking_mail_confirmation_subject',
            'type' => 'text',
            'default' => __( 'Your booking {{item_name}}.', CB_TEXTDOMAIN ),
          ),
          array(
            'name' => __( 'Booking confirmed email body', CB_TEXTDOMAIN ),
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
            ', CB_TEXTDOMAIN ),
          ),
          array(
            'name' => __( 'Booking cancelled email subject', CB_TEXTDOMAIN ),
            'id' => 'emailtemplates_mail-booking-cancelled-subject',
            'type' => 'text',
            'default' => __( 'cancelled booking.', CB_TEXTDOMAIN ),
          ),
          array(
            'name' => __( 'Booking cancelled email body', CB_TEXTDOMAIN ),
            'id' => 'emailtemplates_mail-booking-cancelled-body',
            'type' => 'textarea',
            'default' => __( 'cancelled booking of {{item-name}} at {{location-name}}.', CB_TEXTDOMAIN ),
          ),
        )
      ),
      /* email templates end */
      /* message templates start */
      'messagetemplates' => array(
        'title' => __( 'Booking process messages', CB_TEXTDOMAIN ),
        'id' => 'messagetemplates',
        'desc' => '',
        'fields' => array(
          array(
            'name'    => __( 'Please confirm your booking', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_please-confirm',
            'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_pleaseconfirm',
            'type'    => 'textarea_small',
            'default' => __('Please review your booking of {{item-name}} at {{location-name}} and confirm it.', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __( 'Booking confirmed', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-confirmed',
            'cb1_legacy_id'  => 'commons-booking-settings-messages:commons-booking_messages_booking_confirmed',
            'type'    => 'textarea_small',
            'default' => __( 'Your booking of {{item-name}} at {{location-name}} has been confirmed!', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Booking cancelled', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-cancelled',
            'type'    => 'textarea_small',
            'default' => __( 'Your booking has been cancelled!<br>', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Request cancel confirmation', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-cancel-request-text',
            'type'    => 'textarea_small',
            'default' => __( 'Click "OK" to cancel the booking.', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Request un-cancel confirmation', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-uncancel-request-text',
            'type'    => 'textarea_small',
            'default' => __( 'Click "OK" to un-cancel your booking.', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Access not allowed', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-not-allowed',
            'type'    => 'textarea_small',
            'default' => __( 'You are not allowed to access this booking.', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'No bookings', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-no_bookings',
            'type'    => 'textarea_small',
            'default' => __( 'No bookings yet.', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Not logged in', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_booking-not_logged-in',
            'type'    => 'textarea_small',
            'default' => __( 'You have to be logged in to access your bookings. {{site-registration-link}}', CB_TEXTDOMAIN ),
          ),
          array(
            'name'    => __( 'Not available', CB_TEXTDOMAIN ),
            'id'      => 'messagetemplates_item-not-available',
            'type'    => 'textarea_small',
            'default' => __( 'This item is currently not available.', CB_TEXTDOMAIN ),
          ),
        )
      ),
      /* message templates end */
      /* bookingbar templates start */
      'bookingbartemplates' => array(
        'title' => __('Bookingbar strings', CB_TEXTDOMAIN),
        'id' => 'bookingbartemplates',
        'desc' => '',
        'fields' => array(
          array(
            'name'    => __('Intro text', CB_TEXTDOMAIN),
            'desc'    => __('{{max-slots}} will be replaced with max slots.', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_intro-text',
            'type'    => 'textarea_small',
            'default' => __('Choose bookable slots on the calendar', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Notice: Too few slots selected', CB_TEXTDOMAIN),
            'desc'    => __('Displayed if a user tries to select too few slots than allowed.', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_notice-min-slots',
            'type'    => 'textarea_small',
            'default' => __('You need to book more slots.', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Notice: Too many slots selected', CB_TEXTDOMAIN),
            'desc'    => __('Displayed if a user tries to select more slots than allowed. {{max-slots}} will be replaced with max slots.', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_notice-max-slots',
            'type'    => 'textarea_small',
            'default' => __('You can not book more than {{max-slots}} slots.', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Notice: Booking over another booking', CB_TEXTDOMAIN),
            'desc'    => __('Displayed if a user tries to create a selection that would include a non-includable slot (e.g. another booking) .', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_notice-non-includable',
            'type'    => 'textarea_small',
            'default' => __('Your selection contains another booking.', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Notice: Not logged in', CB_TEXTDOMAIN),
            'desc'    => __('Displayed if a visitor clicks the calendar) .', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_notice-not-logged-in',
            'type'    => 'textarea_small',
            'default' => __('You need to be logged in to book.', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Pickup from', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_pickup-from',
            'type'    => 'text',
            'default' => __('Pickup from:', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Return until', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_return-before',
            'type'    => 'text',
            'default' => __('Return until:', CB_TEXTDOMAIN),
          ),
          array(
            'name'    => __('Button Label', CB_TEXTDOMAIN),
            'id'      => 'bookingbartemplates_button-label',
            'type'    => 'text',
            'default' => __('Book', CB_TEXTDOMAIN),
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
