// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')
Cypress.on('uncaught:exception', (err, runnable) => {
  // WordPress is throwing this error in the latest trunk when trying to login with a changed date #61224 in Trac. This workaround is in place until the issue is resolved
  if (err.message.includes('Cannot read properties of undefined (reading \'serialize\')')) {
    return false
  }
  
  // #1632
  if (err.message.includes('Cannot destructure property \'documentElement\' of \'o\' as it is null.')) {
  	return false
  }
  // we still want to ensure there are no other unexpected
  // errors, so we let them fail the test
})
