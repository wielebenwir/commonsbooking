describe('test overbooking process', () => {

    function visitTestPage(testName, itemFixtures, locationFixtures){
        let item = itemFixtures.find( (item) => {
            return item.test_name === testName
        })
        //get post_name and post_id
        let itemPostName = item.post_name
        let locationPostId = getLocIDForTest(testName, locationFixtures)
        cy.visit( '/?cb_item=' + itemPostName + '&cb-location=' + locationPostId)
    }

    function getLocIDForTest(testName, locationFixtures){
        let location = locationFixtures.find( (location) => {
            return location.test_name === testName
        })
        return location.post_id
    }

    function updatePostMetaAndReload(postID, metaKey, metaValue){
        cy.exec('wp-env run tests-cli tests-wordpress wp post meta update ' + postID + ' ' + metaKey + ' ' + metaValue)
        cy.reload()
    }

    //Will check, if the f
    function assertFridayAndMondayAreClickable( ) {
        //assert if the monday and friday dates are clickable (does not mean that they are overbookable)
        //This should always be the case if the timeframes are configured correctly
        //check that everything is clickable, including the monday while it is not selected
        cy.get('.is-today').should('not.have.class','is-locked')
        //and that the monday is clickable too
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
    }

    function assertWeekendOverbooking ( shouldbeBookable, unbookableClass = 'is-locked', tuesdayBookable = false ) {
        //assert, if the monday & friday dates are bookable (meaning you can click them and proceed to booking)
        cy.get('.is-today').click();
        //the saturday should not be clickable
        cy.get('.is-start-date').next('.day-item').should('have.class', unbookableClass)
        //and the sunday also not
        cy.get('.is-start-date').next('.day-item').next('.day-item').should('have.class', unbookableClass)
        //the monday
        if (shouldbeBookable){
            //the monday should be clickable
            cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
            //but the tuesday should not be clickable in the tests where we count at least one day (default length of booking is 3 days)
            if (!tuesdayBookable){
                cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
                //and we should be able to proceed booking
                cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
                cy.get('#booking-form > [type="submit"]').should('not.be.disabled');
            }
            else {
                //the tuesday should be clickable when we don't count any overbooked days
                cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked')
                //likewise, the wednesday should not be clickable
                cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
                //and we should be able to proceed booking
                cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').next('.day-item').click();
                cy.get('#booking-form > [type="submit"]').should('not.be.disabled');
            }
        }
        else {
            cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked')
            //try to click it anyway and assert that we can't proceed booking
            cy.get('.is-start-date').next('.day-item').next('.day-item').next('.day-item').click();
            cy.get('#booking-form > [type="submit"]').should('be.disabled');
        }
    }

    function getNextFirstFriday() {
        const today = new Date();
        const currentMonth = today.getMonth();
        let nextMonth = currentMonth + 1;
        let year = today.getFullYear();
    
        if (nextMonth > 11) {
            nextMonth = 0;
            year++;
        }
    
        let firstFridayDate = new Date(year, nextMonth, 1);
    
        while (firstFridayDate.getDay() !== 5) {
            firstFridayDate.setDate(firstFridayDate.getDate() + 1);
        }
    
        return firstFridayDate.getTime();
    }


    beforeEach( function() {
        //freeze our date to the first friday in the next month
        cy.clock(getNextFirstFriday());
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
      visitTestPage(testName, this.bookableItems, this.bookableLocations)
      assertFridayAndMondayAreClickable(true)
      assertWeekendOverbooking(false)
  })

    it('Weekly Repetitions is not overbookable (1 day to overbook) works', function () {
        const testName = 'WeeklyRepetition NoOverbooking 1Day'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        //this test is different from the others, as it only tries to overbook sunday and not the whole weekend

        //assert saturday and monday are clickable
        cy.get('.is-today').next('.day-item').should('not.have.class', 'is-locked');
        //and that sunday is locked
        cy.get('.is-today').next('.day-item').next('.day-item').should( 'have.class', 'is-locked' );
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked');

        //now assert that we can't book over the sunday
        cy.get('.is-today').next('.day-item').click(); //this is the saturday
        //sunday should still be locked
        cy.get('.is-today').next('.day-item').next('.day-item').should( 'have.class', 'is-locked' );
        //monday should now be locked
        //this does not work because of #1087, still the submit button should be disabled
        //cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked');
        //click the monday and assert that we can't proceed booking
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('be.disabled');

        //now, enable overbooking
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_allow_lockdays_in_range', 'on')
        //assert that we can book over the sunday
        cy.get('.is-today').next('.day-item').click(); //this is the saturday
        //click the monday
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').click();
        //assert that we can proceed booking
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');

        //reset, so that we can run the tests again
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_allow_lockdays_in_range', '0')
    })

    it('Daily Repetition is not overbookable (1 day to overbook) works', function () {
        const testName = 'DailyRep Holiday NoOverbooking 1Day'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        //this test is different from the others, as it only tries to overbook sunday and not the whole weekend

        //assert saturday and monday are clickable
        cy.get('.is-today').next('.day-item').should('not.have.class', 'is-holiday');
        //and that sunday is locked
        cy.get('.is-today').next('.day-item').next('.day-item').should( 'have.class', 'is-holiday' );
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('not.have.class', 'is-locked');

        //now assert that we can't book over the sunday
        cy.get('.is-today').next('.day-item').click(); //this is the saturday
        //sunday should still be holiday
        cy.get('.is-today').next('.day-item').next('.day-item').should( 'have.class', 'is-holiday' );
        //monday should now be locked
        //this does not work because of #1087, still the submit button should be disabled
        //cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').should('have.class', 'is-locked');
        //click the monday and assert that we can't proceed booking
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').click();
        cy.get('#booking-form > [type="submit"]').should('be.disabled');

        //now, enable overbooking
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_allow_lockdays_in_range', 'on')
        //assert that we can book over the sunday
        cy.get('.is-today').next('.day-item').click(); //this is the saturday
        //click the monday
        cy.get('.is-today').next('.day-item').next('.day-item').next('.day-item').click();
        //assert that we can proceed booking
        cy.get('#booking-form > [type="submit"]').should('not.be.disabled');

        //reset, so that we can run the tests again
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_allow_lockdays_in_range', '0')
    })

    it("Weekly Repetition is overbookable count each day works", function () {
      //initially, overbooking should not work becasue the maximum bookable days are 3
        let timeframeID = 51 //TODO: get this from fixtures
        const testName = 'WeeklyRepetition Overbooking CountAll'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        assertFridayAndMondayAreClickable()
        assertWeekendOverbooking(false)

        //lets raise the max days
        updatePostMetaAndReload(timeframeID, 'timeframe-max-days', 4)

        //now the monday should be bookable after selection
        assertFridayAndMondayAreClickable()
        assertWeekendOverbooking(true)
        //change it back to three so we can re-run the test
        updatePostMetaAndReload(timeframeID, 'timeframe-max-days', 3)
    })

    it("Weekly Repetition is overbookable count block of days works", function () {
        const testName = 'WeeklyRepetition Overbooking CountOne'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        assertFridayAndMondayAreClickable()
        //in this default setup, only one day of the weekend is counted so that we can book up to the monday (but not the tuesday)
        assertWeekendOverbooking(true)

        //now, let's raise the block counter to two days and assert that everything becomes unclickable again
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_count_lockdays_maximum', 2)

        assertWeekendOverbooking(false)

        //reset, so that we can run the tests again
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_count_lockdays_maximum', 1)
    })

    it('Daily repetition /w Holiday on weekend no overbooking works', function () {
        const testName = 'DailyRep Holiday NoOverbooking'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        assertFridayAndMondayAreClickable()
        assertWeekendOverbooking(false,'is-holiday')

    })
    it("Daily repetition /w Holiday on weekend count each day works", function () {
        //initially, overbooking should not work becasue the maximum bookable days are 3
        const testName = 'DailyRep Holiday Overbooking CountAll'
        let timeframeID = 75 //TODO: get this from fixtures
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        assertFridayAndMondayAreClickable()
        assertWeekendOverbooking(false,'is-holiday')

        //lets raise the max days
        updatePostMetaAndReload(timeframeID, 'timeframe-max-days', 4)
        assertWeekendOverbooking(true,'is-holiday')
        //change it back to three so we can re-run the test
        updatePostMetaAndReload(timeframeID, 'timeframe-max-days', 3)
    })

    it("Daily repetition /w Holiday on weekend overbooking count block of days works", function () {
        const testName = 'DailyRep Holiday Overbooking CountOne'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        assertFridayAndMondayAreClickable()
        assertWeekendOverbooking(true,'is-holiday')

        //now, let's raise the block counter to two days and assert that everything becomes unclickable again
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_count_lockdays_maximum', 2)
        assertWeekendOverbooking(false,'is-holiday')

        //reset, so that we can run the tests again
        updatePostMetaAndReload(getLocIDForTest(testName,this.bookableLocations), '_cb_count_lockdays_maximum', 1)
    })

    it("Weekly Repetition is overbookable don't count days works", function () {
        const testName = 'WeeklyRepetition Overbooking NoCount'
        visitTestPage(testName, this.bookableItems, this.bookableLocations)
        assertFridayAndMondayAreClickable()
        //we should be able to book until tuesday
        assertWeekendOverbooking(true, 'is-locked', true)
    })
})
