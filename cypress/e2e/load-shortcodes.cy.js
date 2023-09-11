describe('load shortcodes', () => {
    it('can load cb_items shortcode', () => {
        cy.visit('/?page_id=13')
        cy.get('.cb-shortcode-items').should('be.visible')
        cy.screenshot('cb-items-shortcode')
        cy.get('.cb-shortcode-items').find('.cb-button').should('be.visible')
        cy.get('.cb-shortcode-items').find('.cb-button').contains('Book item')
        cy.get('.cb-shortcode-items').find('.cb-button').click()
        cy.url().should('include', '/?cb_item=basictest-noadmin&cb-location=32')
    })
    it('can load cb_locations shortcode', () => {
        cy.visit('/?page_id=15')
        cy.get('.cb-shortcode-locations').should('be.visible')
        cy.screenshot('cb-locations-shortcode')
        cy.get('.cb-shortcode-locations').find('.cb-button').should('be.visible')
        cy.get('.cb-shortcode-locations').find('.cb-button').contains('Book item')
        cy.get('.cb-shortcode-locations').find('.cb-button').click()
        cy.url().should('include', '/?cb_item=basictest-noadmin&cb-location=32')
    } )

    it('can load cb_item_table shortcode', () => {
        cy.visit('/?page_id=17')
        cy.get('.cb-shortcode-items_table').should('be.visible')
        cy.screenshot('cb-item-table-shortcode')
        cy.get('.cb-shortcode-items_table').find('tbody > tr > :nth-child(1)').contains('BasicTest')
        cy.get('.cb-shortcode-items_table').find('tbody > tr > :nth-child(1)').find('b > a').should('be.visible')
        cy.get('.cb-shortcode-items_table').find('tbody > tr > :nth-child(1)').find('b > a').click()
        cy.url().should('include', '/?cb_item=basictest-noadmin&cb-location=32')
    } )

    it ('can load cb_map shortcode', () => {
        cy.visit('/?page_id=22')
        //wait a little bit to make sure the map tiles are loaded
        cy.wait(5000)
        const mapID = 'cb-map-36'
        cy.get('#' + mapID).should('be.visible')
        // Unfortunately I couldn't find a way to test the map itself without creating a new instance (which would not check if the data is correct)
        //That's why there is only the screenshot test
        cy.screenshot('cb-map-shortcode')
    } )
})