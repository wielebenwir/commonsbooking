describe('check load of CPT frontend pages where available', () => {
    const testName = 'BasicTest'
  it('loads items', () => {
      cy.visit('/?cb_item=basictest-noadmin')
      //wait so that the tile layer for the location map can load before taking the screenshot
      cy.wait(2000)
      cy.screenshot('cb-item-template')
      cy.get('.entry-title').contains(testName);
      //Check that location is correctly assigned
      cy.get('.cb-title > a').contains(testName);
      cy.get('.cb-timeframe-calendar').should('be.visible');
      cy.get('#cb_locationview_map').should('be.visible')
      cy.get('#cb_locationview_map').scrollIntoView().screenshot('cb-itemtemplate-locationview-map')
  })
  it ('loads locations', () => {
      cy.visit('/?cb_location=basictest-koln-dom-locmap-noadmin')
      //wait so that the tile layer for the location map can load before taking the screenshot
      cy.wait(2000)
      cy.screenshot('cb-location-template')
      cy.get('.entry-title').contains(testName);
      //check for location map
      cy.get('#cb_locationview_map').should('be.visible')
      cy.get('#cb_locationview_map').scrollIntoView().screenshot('cb-locationtemplate-locationview-map')
      //check address
      cy.get('.cb-location-address > :nth-child(2)').contains("Domkloster 4, 50667 Köln")
      //check item
      cy.get('.cb-title').contains(testName);
      //timeframe calendar is visible because there is only one assigned location
      cy.get('.cb-timeframe-calendar').should('be.visible');
  } )
})
