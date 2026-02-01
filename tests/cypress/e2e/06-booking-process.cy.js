describe('test booking process', () => {
  function getTestDate() {
    let dt = new Date();
    var test = new Date(dt.getTime()),
        month = test.getMonth();
  
    test.setDate(test.getDate() + 1);
    if (test.getMonth() !== month ) {
      dt.setDate(dt.getDate() + 1);
    }

    return dt.getTime();
  }

  beforeEach( function() {
    cy.clock(getTestDate());
    cy.loginAs( 'subscriber' );
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
        cy.get('.is-today').click();
        //just click next directly adjacent day
        cy.get('.is-start-date').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');
        cy.get('#booking-form > [type="submit"]').contains('Continue to booking confirmation');
        cy.screenshot('booking-form_dates-selected');
    } )

    it ('can abort booking process early', function () {
        cy.get('.is-today').click();
        //just click next directly adjacent day
        cy.get('.is-start-date').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').click();
        cy.get('.cb-notice').contains('Please check your booking and click confirm booking');
        cy.screenshot('booking-form_unconfirmed-booking');
        cy.get('.cb-action-delete_unconfirmed').click();
        cy.get('.cb-notice').contains('Booking canceled');
        cy.screenshot('booking-form_booking-delete-unconfirmed');
    } )
    it('can book an item', function()  {
        cy.get('.is-today').click();
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
