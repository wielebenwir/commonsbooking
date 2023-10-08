describe('test backend booking', () => {
    beforeEach( function() {
        cy.visit( '/wp-login.php' );
        cy.wait( 1000 );
        cy.get( '#user_login' ).type( Cypress.env( "wpAdmin" ) );
        cy.get( '#user_pass' ).type( Cypress.env( "wpPassword" ) );
        cy.get( '#wp-submit' ).click();
    } );
  it('can create entirely new admin booking', () => {
    const expectedStartDate = '10/20/2023'
    const expectedEndDate = '10/22/2023'
    cy.visit('/wp-admin/post-new.php?post_type=cb_booking')
    cy.get('#title').type('Test booking')
    //TODO: get this data from fixtures
    cy.get('#item-id').select('BasicTest - NoAdmin')
    cy.get('#location-id').select('BasicTest - Köln Dom LocMap NoAdmin')
    cy.get('#full-day').check()
    cy.get('#repetition-start_date').clear().type(expectedStartDate)
    //click somewhere outside of the datepicker to close it
    cy.get('body').click(0,0)
    cy.get('#repetition-end_date').clear().type(expectedEndDate)
    cy.get('body').click(0,0)
    //set status to confirmed
    cy.get('.edit-post-status > [aria-hidden="true"]').click()
    cy.get('#post_status').select('Confirmed')
    cy.get('.save-post-status').click()
    cy.get('#save-post').click()
    cy.get('#message > p').contains('Post updated.')
    cy.get('#post-status-display').contains('Confirmed')

    //let's go to the frontend booking calendar and check that our item exists there
    //set date to 20th of october 2023
    cy.clock(new Date(2023,9,20))
    cy.visit('/?cb_item=basictest-noadmin&cb-location=32')
    cy.get('.is-today').should('have.class', 'is-booked')
  })

  after( function () {
    cy.visit('/wp-admin/edit.php?post_type=cb_booking')
    cy.get('.submitdelete').click({multiple: true, force: true})
  })
})