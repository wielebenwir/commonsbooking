// cypress/support/commands.js
Cypress.Commands.add('waitForWordPressReady', () => {
  // Simple check if WordPress is reachable
  cy.request({
    url: '/',
    timeout: 60000,
    retryOnStatusCodeFailure: true,
    retryOnNetworkFailure: true,
    retries: 5
  }).then((response) => {
    expect(response.status).to.equal(200);
  });
});

Cypress.Commands.add('loginAs', (userType = 'admin') => {
  const username = userType;
  const password = Cypress.env('wpPassword');

  cy.visit('/wp-login.php', {
    timeout: 120000, // 2 minutes for CI
    failOnStatusCode: false
  });

  cy.wait(2000) //2 seconds so login form is completely present

  cy.get('#user_login', { timeout: 30000 }).type(username);
  cy.get('#user_pass').type(password);
  cy.get('#wp-submit').click();

  // Final check
  cy.url({ timeout: 30000 }).should('include', '/wp-admin/');
});

Cypress.Commands.add('logout', () => {
    cy.visit('/wp-admin');
    cy.get('body').then($body => {
        if ($body.find('#wpadminbar').length !== 0) {
            cy.get('#wp-admin-bar-my-account').invoke('addClass', 'hover');
            cy.get('#wp-admin-bar-logout > a').click();
        }
    });
});
