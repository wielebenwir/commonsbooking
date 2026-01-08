export function clickIfExist(element) {
    cy.get('body').then((body) => {
        if (body.find(element).length > 0) {
            cy.get(element).click();
        }
    });
}

describe('correctly render metaboxes for backend CPT creation', () => {
   beforeEach( function() {
        cy.loginAs( 'admin' );
    } );

    it('shows item metaboxes', () => {
        cy.visit( '/wp-admin/edit.php?post_type=cb_item' );
        cy.get('.page-title-action').click();
        //dismiss that annoying message about the block editor
        cy.wait(5000);
        clickIfExist('.components-modal__header > .components-button');
        cy.wait(1000);
        //Just assert, that the CMB2 wrapper exists, the rest is done through screenshots
        cy.get('.cmb2-wrap').should('exist');
        cy.screenshot('cb-item-metaboxes')
    })

    it('shows location metaboxes', () => {
        cy.visit( '/wp-admin/edit.php?post_type=cb_location' );
        cy.get('.page-title-action').click();
        cy.get('#_cb_location_adress').should('exist');
        cy.get('#_cb_location_info').should('exist');
        cy.screenshot('cb-location-metaboxes')
	    //TODO: This does not capture all metaboxes in screenshot because of a scrolling div, fix to show metaboxes
        //we abuse the fact here, that cypress refuses to take a screenshot if the map does not work, maybe we can do this more elegantly
        //wait a bit for the map to load
        cy.wait(5000).get('#cb_positioning_map').scrollIntoView().screenshot('cb-location-metaboxes-positioning-map')
    })

    it('retrieves geocoding info', () => {
        const TEST_STREET = "Domkloster 4";
        const TEST_ZIP = "50667";
        const TEST_CITY = "Köln";
        const TEST_COUNTRY = "Deutschland";
        const EXPECTED_LAT = "50.9413035";
        const EXPECTED_LNG = "6.9581380";
        cy.visit( '/wp-admin/post-new.php?post_type=cb_location' );
        cy.get('#_cb_location_street').clear().type(TEST_STREET);
        cy.get('#_cb_location_postcode').clear().type(TEST_ZIP);
        cy.get('#_cb_location_city').clear().type(TEST_CITY);
        cy.get('#_cb_location_country').clear().type(TEST_COUNTRY);
        cy.get('#get_gps').click();
        cy.get('#geo_latitude').should('have.prop', 'value', EXPECTED_LAT);
        cy.get('#geo_longitude').should('have.prop', 'value', EXPECTED_LNG);
    })

    it('shows timeframe metaboxes', () => {
        cy.visit( '/wp-admin/edit.php?post_type=cb_timeframe' );
        const BOOKABLE_ID = "2";
        const HOLIDAY_ID = "3";
        const REPAIR_ID = "5";
        cy.get('.page-title-action').click();
        cy.get('#type').select(BOOKABLE_ID);
        cy.screenshot('cb-timeframe-metaboxes-bookable')
        cy.get('#type').select(HOLIDAY_ID);
        cy.screenshot('cb-timeframe-metaboxes-holiday')
        cy.get('#type').select(REPAIR_ID);
        cy.screenshot('cb-timeframe-metaboxes-repair')
    })
    
    it('can load holidays from timeframe settings', () => {
        cy.visit( '/wp-admin/edit.php?post_type=cb_timeframe' );
        const HOLIDAY_ID = "3";
        const MANUAL_SELECTION = "manual";
        cy.get('.page-title-action').click();
        cy.get('#type').select(HOLIDAY_ID);
        cy.get('#timeframe-repetition').select(MANUAL_SELECTION);
        cy.get('#holiday_load_btn').click();
        cy.get('#timeframe_manual_date').should('have.prop', 'value').should('not.be.empty')
        cy.screenshot('cb-timeframe-holiday-loaded');
    })
})

