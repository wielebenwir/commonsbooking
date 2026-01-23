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
    it ('location page with single item redirects to item page', () => {
      cy.visit('/?cb_location=basictest-koln-dom-locmap-noadmin')
      cy.wait(1000)
      //redirect or redirect link present
      cy.url().then((url) => {
        const redirected = url.includes('?cb_item=basictest-noadmin');
        if (!redirected) {
          cy.contains('Please book this item on the item page.').should('exist');
        }
      });
      cy.screenshot('cb-location-page-single');
  } )
})
