describe('load shortcodes', () => {
    //load fixtures
    beforeEach (function () {
        cy.fixture('bookableItems').then( (regItems) => {
            this.bookableItems = regItems.data
        })
        cy.fixture('bookableLocations').then( (regLocations) => {
            this.bookableLocations = regLocations.data
        })
    })

    it('can load cb_items shortcode', function () {
        cy.visit('/?page_id=13')
        cy.get('.cb-shortcode-items').should('be.visible')
        cy.screenshot('cb-items-shortcode')
        //assert that all items are present
        cy.get('.cb-shortcode-items').should('have.length', this.bookableItems.length)
        //iterate over fixture and check that all items are present
        this.bookableItems.forEach( (item) => {
            cy.contains('.cb-shortcode-items', convertEnDashToHyphen(item.post_title) )
                .should('be.visible')
                .find('.cb-button')
                .should('be.visible')
                .contains('Book item')
                .should(($button) => {
                    const href = $button.attr('href');
                    expect(href).to.include('?cb_item=' + item.post_name);
                })
        })
    })
    it('can load cb_locations shortcode', function () {
        cy.visit('/?page_id=15')
        cy.get('.cb-shortcode-locations').should('be.visible')
        cy.screenshot('cb-locations-shortcode')
        //assert that all locations are present
        cy.get('.cb-shortcode-locations').should('have.length', this.bookableLocations.length)
        //iterate over fixture and check that all locations are present
        this.bookableLocations.forEach( (location) => {
            cy.contains('.cb-shortcode-locations', convertEnDashToHyphen( location.post_title ) )
                .should('be.visible')
                .find('.cb-button')
                .should('be.visible')
                .contains('Book item')
                .should(($button) => {
                    const href = $button.attr('href');
                    expect(href).to.include('cb-location=' + location.post_id);
                })
        })
    } )

    it('can load cb_item_table shortcode', function () {
        cy.visit('/?page_id=17')
        cy.get('.cb-shortcode-items_table').should('be.visible')
        cy.screenshot('cb-item-table-shortcode')
        //assert that all items are present
        cy.get('.cb-shortcode-items_table').find('tbody > tr').should('have.length', this.bookableItems.length)
        //iterate over fixture and check that all items are present
        this.bookableItems.forEach( (item) => {
            //This SUCKED to debug, apparently the table converts the Unicode hyphens in post titles. So we have to convert our fixture data as well.
            let convertedTitle = convertEnDashToHyphen(item.post_title)
            cy.contains('.cb-items-table tbody tr',convertedTitle)
                .find('b > a') //select the link
                .should('exist')
                .should($link => {
                    const href = $link.attr('href');
                    expect(href).to.include('?cb_item=' + item.post_name);
                })
        })

    })

    it ('can load cb_map shortcode', function () {
        cy.visit('/?page_id=22')
        //wait a little bit to make sure the map tiles are loaded
        cy.wait(5000)
        const mapID = 'cb-map-123'
        cy.get('#' + mapID).should('be.visible')
        // Unfortunately I couldn't find a way to test the map itself without creating a new instance (which would not check if the data is correct)
        //That's why there is only the screenshot test
        cy.screenshot('cb-map-shortcode')
    })

    it ('can load cb_search shortcode: MapWithAutoSidebar,Filter', function () {
        cy.visit('/?page_id=102')
        //wait a little bit to make sure the map tiles are loaded
        cy.wait(5000)
        cy.get('.leaflet-container').should('be.visible')
        cy.get('.cb-common-filter').should('be.visible')
        cy.screenshot('cb-search-shortcode-map-filter')
    })

    it ('can load cb_search shortcode: FilterList', function () {
        cy.visit('/?page_id=108')
        //give time to fetch data
        cy.wait(5000);
        cy.get('.cb-common-filter').should('be.visible')
        cy.screenshot('cb-search-shortcode-filter-list')
        //TODO: Check for presence of individual items (needs fixing of #1419)
    })

    it ('can load cb_bookings shortcode', function () {
        cy.visit('/?page_id=128')
        cy.get('.cb-wrapper').contains('Please login');
        cy.screenshot( 'cb-bookings-shortcode-loggedout' );

        //login
        cy.visit( '/wp-login.php' );
        cy.wait( 1000 );
        cy.get( '#user_login' ).type( Cypress.env( "wpSubscriber" ) );
        cy.get( '#user_pass' ).type( Cypress.env( "wpPassword" ) );
        cy.get( '#wp-submit' ).click();

        cy.visit('/?page_id=128')
        //This depends on the booking-process.cy.js running before this test
        cy.get('.js-item--infos > :nth-child(2)').contains('Status: canceled')
        cy.screenshot( 'cb-bookings-shortcode' );

    })

    function convertEnDashToHyphen(text) {
        const regex = /–/g; // U+2013 is used here
        const hyphenMinus = '-';
        const convertedText = text.replace(regex, hyphenMinus);
        return convertedText;
    }


})