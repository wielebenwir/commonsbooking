describe('test booking process', () => {
    beforeEach( function() {
        cy.visit( '/wp-login.php' );
        cy.wait( 1000 );
        cy.get( '#user_login' ).type( Cypress.env( "wpSubscriber" ) );
        cy.get( '#user_pass' ).type( Cypress.env( "wpPassword" ) );
        cy.get( '#wp-submit' ).click();
        cy.visit('/?cb_item=basictest-noadmin&cb-location=32');
    } );
  it('grays out button when no date is selected', () => {
    cy.screenshot('booking-form_no-date-selected');
    cy.get('#booking-form > [type="submit"]').should('be.disabled');
  })
    it('makes button clickable when dates are selected', () => {
        //we have to do different assertions when we are in the last day of the current month
        //this is because the next date available in the calendar is the first day of the next month
        //and is therefore not in the same div as the current day
        let lastDayOfMonth = false;
        //TODO: I don't know how to check this, so expect the test to fail on the last day of the month
        if (lastDayOfMonth){
            //make assertions with first day of next month
            cy.get('.container__months').get('.month-item').next().first('.day-item').click();
        }
        else {
            cy.get('.is-today').click();
        }
        //just click next directly adjacent day
        cy.get('.is-start-date').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');
        cy.get('#booking-form > [type="submit"]').contains('Continue to booking confirmation');
        cy.screenshot('booking-form_dates-selected');
    } )

    it ('can abort booking', () => {
        //we have to do different assertions when we are in the last day of the current month
        //this is because the next date available in the calendar is the first day of the next month
        //and is therefore not in the same div as the current day
        let lastDayOfMonth = false;
        //TODO: I don't know how to check this, so expect the test to fail on the last day of the month
        if (lastDayOfMonth){
            //make assertions with first day of next month
            cy.get('.container__months').get('.month-item').next().first('.day-item').click();
        }
        else {
            cy.get('.is-today').click();
        }
        //just click next directly adjacent day
        cy.get('.is-start-date').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').click();
        cy.get('.cb-notice').contains('Please check your booking and click confirm booking');
        cy.screenshot('booking-form_unconfirmed-booking');
        cy.get('.cb-action-delete_unconfirmed').click();
        cy.get('.cb-notice').contains('Booking canceled');
        cy.screenshot('booking-form_booking-delete-unconfirmed');
    } )
    it('can book an item', () => {
        //ATTENTION, after this tests, the above tests won't work anymore, because the item is booked and therefore the current day is not available anymore
        //we have to do different assertions when we are in the last day of the current month
        //this is because the next date available in the calendar is the first day of the next month
        //and is therefore not in the same div as the current day
        let lastDayOfMonth = false;
        //TODO: I don't know how to check this, so expect the test to fail on the last day of the month
        if (lastDayOfMonth){
            //make assertions with first day of next month
            cy.get('.container__months').get('.month-item').next().first('.day-item').click();
        }
        else {
            cy.get('.is-today').click();
        }
        //just click next directly adjacent day
        cy.get('.is-start-date').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').click();
        cy.get('.cb-notice').contains('Please check your booking and click confirm booking');
        cy.get('.cb-action-confirmed').click();
        cy.get('.cb-notice').contains('Your booking is confirmed');
        cy.screenshot('booking-form_booking-confirmed');

        //Cancel the booking again so that the test can be run again
        cy.get('.cb-action-canceled').click();
        cy.get('.cb-notice').contains('Your booking has been canceled');
        cy.screenshot('booking-form_booking-canceled');
    } )
})