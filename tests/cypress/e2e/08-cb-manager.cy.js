describe('test functionality of cb-manager role', function () {
    it('cb-manager can see assigned item on backend item page', function () {
        cy.loginAs('cbmanager');
        cy.visit('/wp-admin/edit.php?post_type=cb_item');
        cy.get('.row-title').contains('ManagedItem').should('have.length', '1');
    });
    it('cb-manager can see assigned location on backend location page', function () {
        cy.loginAs('cbmanager');
        cy.visit('/wp-admin/edit.php?post_type=cb_location');
        cy.get('.row-title').contains('ManagedLocation').should('have.length', '1');
    });
    it('cb-manager can create restriciton for managed item and location', function () {
        cy.loginAs('cbmanager');
        cy.visit('wp-admin/edit.php?post_type=cb_restriction');
        cy.get('.page-title-action').click();
        cy.get('[name="restriction-location-id"] option')
            .should('have.length', 1)
            .and('have.text', 'ManagedLocation');
        cy.get('[name="restriction-item-id"] option')
            .should('have.length', 2)
            .then(($options) => {
                expect($options.eq(0)).to.have.text('All');
                expect($options.eq(1)).to.have.text('ManagedItem');
            });
    });
    it('cb-manager can see restriction with multi-assigments (regression #2391)', function () {
        cy.loginAs('admin');
        cy.visit('wp-admin/edit.php?post_type=cb_restriction');
        cy.get('.page-title-action').click();
        cy.get('[name="post_title"]').type('ManagedRestriction');
        cy.get('[name="restriction-item-id"]').select('ManagedItem');
        cy.get('[name="publish"]').click();
        cy.logout();
        cy.loginAs('cbmanager');
        cy.visit('wp-admin/edit.php?post_type=cb_restriction');
        cy.get('.row-title').contains('ManagedRestriction').should('have.length', '1');
    });
});
