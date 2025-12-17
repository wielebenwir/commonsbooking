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
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    video: false,
    specPattern: 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
		supportFile: 'tests/cypress/support/e2e.js',
  },
});
