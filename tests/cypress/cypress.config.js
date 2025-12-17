const { defineConfig } = require("cypress");

module.exports = defineConfig({
  fixturesFolder: 'tests/cypress/fixtures',
	screenshotsFolder: 'tests/cypress/screenshots',
	videosFolder: 'tests/cypress/videos',
	downloadsFolder: 'tests/cypress/downloads',
  env: {
      wpAdmin:'admin',
      wpSubscriber: 'subscriber',
      wpPassword:'password',
  },
  e2e: {
    baseUrl: 'http://localhost:1001/',
    pageLoadTimeout: 120000,
    defaultCommandTimeout: 10000,   // for cy.get() / cy.click() waits
    requestTimeout: 15000,          // for cy.request()
    responseTimeout: 15000,
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    video: false,
    specPattern: 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
		supportFile: 'tests/cypress/support/e2e.js',
  },
});
