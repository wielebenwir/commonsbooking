const { defineConfig } = require('cypress');
const { execSync } = require('node:child_process');

module.exports = defineConfig({
    fixturesFolder: 'tests/cypress/fixtures',
    screenshotsFolder: 'tests/cypress/screenshots',
    videosFolder: 'tests/cypress/videos',
    downloadsFolder: 'tests/cypress/downloads',
    pageLoadTimeout: 1200000,
    env: {
        wpAdmin: 'admin',
        wpSubscriber: 'subscriber',
        wpPassword: 'password',
    },
    e2e: {
        baseUrl: 'http://localhost:1001/',
        setupNodeEvents(on, config) {
            on('task', {
                wpPostMetaUpdate({ postId, metaKey, metaValue }) {
                    try {
                        return execSync(
                            `npx wp-env --config=.wp-env.test.json run cli wp post meta update ${postId} ${metaKey} ${metaValue}`,
                            {
                                encoding: 'utf8',
                            },
                        );
                    } catch (error) {
                        return error.stderr?.toString() || error.message;
                    }
                },
            });
        },
        video: false,
        specPattern: 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
        supportFile: 'tests/cypress/support/e2e.js',
    },
});
