describe('test overbooking process', () => {
    beforeEach( function() {
        //freeze our date to the 6th of November 2023, a Friday
        cy.clock(new Date(2023, 9, 6).getTime());
        //get data from fixtures
        cy.fixture('bookableItems').then( (regItems) => {
            this.bookableItems = regItems.data
        })
        cy.fixture('bookableLocations').then( (regLocations) => {
            this.bookableLocations = regLocations.data
        })
        cy.visit( '/wp-login.php' );
        cy.wait( 1000 );
        cy.get( '#user_login' ).type( Cypress.env( "wpSubscriber" ) );
        cy.get( '#user_pass' ).type( Cypress.env( "wpPassword" ) );
        cy.get( '#wp-submit' ).click();
    })

  it('WeeklyRepetition is not overbookable works', function () {
      const testName = 'WeeklyRepetition NoOverbooking'
      let item = this.bookableItems.find( (item) => {
          return item.test_name === testName
      })
      let location = this.bookableLocations.find( (location) => {
          return location.test_name === testName
      })
      //get post_name and post_id
      let itemPostName = item.post_name
      let locationPostId = location.post_id
      cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
      //check that everything is clickable, including the monday while it is not selected
      cy.get('.is-today').should('not.have.class','is-locked')
      //and that the monday is clickable too
      cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')


      cy.get('.is-today').click();
      //the next days should not be clickable
      cy.get('.is-start-date').next('.day-item').should('have.class', 'is-locked')
      //and the sunday also not
      cy.get('.is-start-date').next('.day-item').next('.day-item').should('have.class', 'is-locked')
      //as well as the monday
      cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
      //try to click it anyway and assert that we can't proceed booking
      cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
      cy.get('#booking-form > [type="submit"]').should('be.disabled');

  })
    it("Weekly Repetition is overbookable count each day works", function () {
      //initially, overbooking should not work becasue the maximum bookable days are 3
        const testName = 'WeeklyRepetition Overbooking CountAll'
        //TODO: Refactor duplicate code
        let item = this.bookableItems.find( (item) => {
            return item.test_name === testName
        })
        let location = this.bookableLocations.find( (location) => {
            return location.test_name === testName
        })
        //get post_name and post_id
        let itemPostName = item.post_name
        let locationPostId = location.post_id
        let timeframeID = 51 //TODO: get this from fixtures
        cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
        //perform just the same assertions as in the first test, then change parameters
        //check that everything is clickable, including the monday while it is not selected
        cy.get('.is-today').should('not.have.class','is-locked')
        //and that the monday is clickable too
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
        cy.get('.is-today').click();
        //the next days should not be clickable
        cy.get('.is-start-date').next('.day-item').should('have.class', 'is-locked')
        //and the sunday also not
        cy.get('.is-start-date').next('.day-item').next('.day-item').should('have.class', 'is-locked')
        //as well as the monday
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
        //try to click it anyway and assert that we can't proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('be.disabled');

        //now change the parameters
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + timeframeID + '" timeframe-max-days 4"')
        //and reload the page
        cy.reload()
        //now the monday should be clickable after selection
        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
        //and we should be able to proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');

        //change it back to three so we can re-run the test
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + timeframeID + '" timeframe-max-days 3"')
    })

    it("Weekly Repetition is overbookable count block of days works", function () {
        const testName = 'WeeklyRepetition Overbooking CountOne'
        let item = this.bookableItems.find( (item) => {
            return item.test_name === testName
        })
        let location = this.bookableLocations.find( (location) => {
            return location.test_name === testName
        })
        //get post_name and post_id
        let itemPostName = item.post_name
        let locationPostId = location.post_id
        cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
        //check that everything is clickable, including the monday while it is not selected
        cy.get('.is-today').should('not.have.class','is-locked')
        //and that the monday is clickable too
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')

        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
        //but the tuesday should not be clickable because we count one day
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
        //and we should be able to proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');

        //now, let's raise the block counter to two days and assert that everything becomes unclickable again
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + locationPostId + '" _cb_count_lockdays_maximum 2"')
        cy.reload()
        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')

        //reset, so that we can run the tests again
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + locationPostId + '" _cb_count_lockdays_maximum 1"')

    })

    it('Daily repetition /w Holiday on weekend no overbooking works', function () {
        const testName = 'DailyRep Holiday NoOverbooking'
        let item = this.bookableItems.find( (item) => {
            return item.test_name === testName
        })
        let location = this.bookableLocations.find( (location) => {
            return location.test_name === testName
        })
        //get post_name and post_id
        let itemPostName = item.post_name
        let locationPostId = location.post_id
        cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
        //check that everything is clickable, including the monday while it is not selected
        cy.get('.is-today').should('not.have.class','is-locked')
        //and that the monday is clickable too
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')


        cy.get('.is-today').click();
        //the next days should not be clickable
        cy.get('.is-start-date').next('.day-item').should('have.class', 'is-holiday')
        //and the sunday also not
        cy.get('.is-start-date').next('.day-item').next('.day-item').should('have.class', 'is-holiday')
        //as well as the monday
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
        //try to click it anyway and assert that we can't proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('be.disabled');

    })
    it("Daily repetition /w Holiday on weekend count each day works", function () {
        //initially, overbooking should not work becasue the maximum bookable days are 3
        const testName = 'DailyRep Holiday Overbooking CountAll'
        //TODO: Refactor duplicate code
        let item = this.bookableItems.find( (item) => {
            return item.test_name === testName
        })
        let location = this.bookableLocations.find( (location) => {
            return location.test_name === testName
        })
        //get post_name and post_id
        let itemPostName = item.post_name
        let locationPostId = location.post_id
        let timeframeID = 75 //TODO: get this from fixtures
        cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
        //perform just the same assertions as in the first test, then change parameters
        //check that everything is clickable, including the monday while it is not selected
        cy.get('.is-today').should('not.have.class','is-locked')
        //and that the monday is clickable too
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
        cy.get('.is-today').click();
        //the next days should not be clickable
        cy.get('.is-start-date').next('.day-item').should('have.class', 'is-holiday')
        //and the sunday also not
        cy.get('.is-start-date').next('.day-item').next('.day-item').should('have.class', 'is-holiday')
        //as well as the monday
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
        //try to click it anyway and assert that we can't proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('be.disabled');

        //now change the parameters
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + timeframeID + '" timeframe-max-days 4"')
        //and reload the page
        cy.reload()
        //now the monday should be clickable after selection
        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
        //and we should be able to proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');

        //change it back to three so we can re-run the test
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + timeframeID + '" timeframe-max-days 3"')
    })

    it("Daily repetition /w Holiday on weekend overbooking count block of days works", function () {
        const testName = 'DailyRep Holiday Overbooking CountOne'
        let item = this.bookableItems.find( (item) => {
            return item.test_name === testName
        })
        let location = this.bookableLocations.find( (location) => {
            return location.test_name === testName
        })
        //get post_name and post_id
        let itemPostName = item.post_name
        let locationPostId = location.post_id
        cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
        //check that everything is clickable, including the monday while it is not selected
        cy.get('.is-today').should('not.have.class','is-locked')
        //and that the monday is clickable too
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')

        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
        //but the tuesday should not be clickable because we count one day
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
        //and we should be able to proceed booking
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');

        //now, let's raise the block counter to two days and assert that everything becomes unclickable again
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + locationPostId + '" _cb_count_lockdays_maximum 2"')
        cy.reload()
        cy.get('.is-today').click();
        cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')

        //reset, so that we can run the tests again
        cy.exec('bin/wp-env-cli tests-wordpress "wp --allow-root post meta update "' + locationPostId + '" _cb_count_lockdays_maximum 1"')

    })


})
