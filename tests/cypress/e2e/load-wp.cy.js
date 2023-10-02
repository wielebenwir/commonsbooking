describe( 'Visit backend sites', function() {
	// Go to WordPress login page and login.
	beforeEach( function() {
		cy.visit( '/wp-login.php' );
		cy.wait( 1000 );
		cy.get( '#user_login' ).type( Cypress.env( "wpAdmin" ) );
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

    it ('can load the CB dashboard', function() {
        cy.visit( '/wp-admin/admin.php?page=cb-dashboard' );
        cy.contains("Welcome to CommonsBooking.");
        cy.screenshot( 'wp-admin-cb-dashboard' )
    } );

    it ('can load the timeframes menu page', function() {
        cy.visit( '/wp-admin/edit.php?post_type=cb_timeframe' );
        cy.contains("Timeframes");
        cy.screenshot( 'wp-admin-cb-timeframes' )
    } );

    it ('can load the items menu page', function() {
        cy.visit( '/wp-admin/edit.php?post_type=cb_item' );
        cy.contains("Items");
        cy.screenshot( 'wp-admin-cb-items' )
    } );

    it ('can load the locations menu page', function() {
        cy.visit( '/wp-admin/edit.php?post_type=cb_location' );
        cy.contains("Locations");
        cy.screenshot( 'wp-admin-cb-locations' )
    } );

    it ('can load the bookings menu page', function() {
        cy.visit( '/wp-admin/edit.php?post_type=cb_booking' );
        cy.contains("Bookings");
        cy.screenshot( 'wp-admin-cb-bookings' )
    } );

    it ('can load the maps menu page', function() {
        cy.visit( '/wp-admin/edit.php?post_type=cb_map' );
        cy.contains("Maps");
        cy.screenshot( 'wp-admin-cb-maps' )
    } );

    it ('can load the restrictions menu page', function() {
        cy.visit( '/wp-admin/edit.php?post_type=cb_restriction' );
        cy.contains("Restrictions");
        cy.screenshot( 'wp-admin-cb-restrictions' )
    } );

    it ('can load the item categories menu page', function() {
        cy.visit( '/wp-admin/edit-tags.php?taxonomy=cb_items_category' );
        cy.contains("Item Category");
        cy.screenshot( 'wp-admin-cb-item-categories' )
    } );

    it ('can load the location categories menu page', function() {
        cy.visit( '/wp-admin/edit-tags.php?taxonomy=cb_locations_category' );
        cy.contains("Location Category");
        cy.screenshot( 'wp-admin-cb-location-categories' )
    } );

    it ('can load the settings menu page', function() {
        cy.visit( '/wp-admin/options-general.php?page=commonsbooking_options' );
        cy.contains("Welcome to CommonsBooking");
        cy.screenshot( 'wp-admin-cb-settings' )
    } );
});
