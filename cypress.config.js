const { defineConfig } = require("cypress");

module.exports = defineConfig({
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
    video: false
  },
});
