describe( 'Run a pull', function() {
	// Go to WordPress login page and login.
	beforeEach( function() {
		cy.visit( '/wp-login.php' );
		cy.wait( 1000 );
		cy.get( '#user_login' ).type( Cypress.env( "wpUser" ) );
		cy.get( '#user_pass' ).type( Cypress.env( "wpPassword" ) );
		cy.get( '#wp-submit' ).click();
	} );
	
	it( 'can open WP dashboard', function() {
		cy.wait( 2000 );
		cy.url().should('eq', 'http://localhost:1001/wp-admin/');
        // Assert that the page title contains "Dashboard"
        cy.title().should('include', 'Dashboard');

        // Assert that the "Welcome to WordPress!" message is present
        cy.contains('Welcome to WordPress!');
        cy.screenshot( 'wp-admin-dashboard' )
	} );

    it ('can load the CB pages', function() {
        cy.visit( '/wp-admin/admin.php?page=cb-dashboard' );
        cy.contains("Welcome to CommonsBooking.");
        cy.screenshot( 'wp-admin-cb-dashboard' )

        //Timeframes menu page
        cy.visit( '/wp-admin/edit.php?post_type=cb_timeframe' );
        cy.contains("Timeframes");
        cy.screenshot( 'wp-admin-cb-timeframes' )

        //Items menu page
        cy.visit( '/wp-admin/edit.php?post_type=cb_item' );
        cy.contains("Items");
        cy.screenshot( 'wp-admin-cb-items' )

        //Locations menu page
        cy.visit( '/wp-admin/edit.php?post_type=cb_location' );
        cy.contains("Locations");
        cy.screenshot( 'wp-admin-cb-locations' )

        //Bookings menu page
        cy.visit( '/wp-admin/edit.php?post_type=cb_booking' );
        cy.contains("Bookings");
        cy.screenshot( 'wp-admin-cb-bookings' )

        //Maps menu page
        cy.visit( '/wp-admin/edit.php?post_type=cb_map' );
        cy.contains("Maps");
        cy.screenshot( 'wp-admin-cb-maps' )

        //Restrictions menu page
        cy.visit( '/wp-admin/edit.php?post_type=cb_restriction' );
        cy.contains("Restrictions");
        cy.screenshot( 'wp-admin-cb-restrictions' )

        //Item Categories menu page
        cy.visit( '/wp-admin/edit-tags.php?taxonomy=cb_items_category' );
        cy.contains("Item Category");
        cy.screenshot( 'wp-admin-cb-item-categories' )

        //Location Categories menu page
        cy.visit( '/wp-admin/edit-tags.php?taxonomy=cb_locations_category' );
        cy.contains("Location Category");
        cy.screenshot( 'wp-admin-cb-location-categories' )

        //Settings page
        cy.visit( '/wp-admin/options-general.php?page=commonsbooking_options' );
        cy.contains("Welcome to CommonsBooking");
        cy.screenshot( 'wp-admin-cb-settings' )

    } );
});
