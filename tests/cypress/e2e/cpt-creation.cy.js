export function clickIfExist(element) {
    cy.get('body').then((body) => {
        if (body.find(element).length > 0) {
            cy.get(element).click();
        }
    });
}

describe('correctly render metaboxes for backend CPT creation', () => {
   beforeEach( function() {
        cy.visit( '/wp-login.php' );
        cy.wait( 1000 );
        cy.get( '#user_login' ).type( Cypress.env( "wpAdmin" ) );
        cy.get( '#user_pass' ).type( Cypress.env( "wpPassword" ) );
        cy.get( '#wp-submit' ).click();
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
	//TODO: This does not capture all metaboxes in screenshot because of a scrolling div, fix to show metaboxes
        cy.screenshot('cb-location-metaboxes')
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
})

