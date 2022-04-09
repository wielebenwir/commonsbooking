<?php

//adds shortcode for user statistics
add_shortcode( 'cb_statistics-user' , array( \CommonsBooking\View\Statistics::class, 'shortcodeUser' ) );


//adds shortcode for item statistics
add_shortcode( 'cb_statistics-item' , array( \CommonsBooking\View\Statistics::class, 'shortcodeItems' ) );


//adds shortcode for location statistics
add_shortcode( 'cb_statistics-location' , array( \CommonsBooking\View\Statistics::class, 'shortcodeLocations' ) );

