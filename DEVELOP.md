# Development

## Run plugin

First, we have to install the necessary dependencies and packages, we can do this by using the
```
npm run start
```
command.

The most easy way to start hacking WordPress plugins in general (if you have no other development environment set up) is using [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/). Install it and it's dependencies (mainly Docker) and run this to start the enviroment:
```
npm run env:start
```
The provided `.wp-env.json` should be sufficient for normal development, for details see the [documentation of wp-env config](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-json). [You can create](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-override-json) a `.wp-env.override.json` for a custom configuration you don't want to check in.

For testing, you can activate the [kasimir theme](github.com/flegfleg/kasimir-theme) via [wp cli](https://make.wordpress.org/cli/handbook/) inside the wp-env docker container:
```
npm run env run cli wp theme activate kasimir-theme
```

## Test plugin

To test the code you first run the [preparation scripts](https://github.com/wp-cli/scaffold-command#wp-scaffold-plugin-tests) to load the wordpress core and configure database connection via `wp-config.php`. The following line can vary on your system, use the appropriate credentials, databse port and version of wordpress. The appropriate database port is printed out by `npm run env:start`:
```
bash bin/install-wp-tests.sh wordpress root password 127.0.0.1:49153 latest
```

Testing the plugin code via `phpunit`. At the moment it works only with a manually downloaded phar. We are using PHPUnit 9 and PHP7.4 for the automated tests. The tests might fail if you are using a different version.
```
php ~/phpunit.phar --bootstrap tests/php/bootstrap.php
```

E2E (end to end) tests are written in [cypress](https://www.cypress.io/). To run them you need to install cypress and start the wordpress environment:
```bash
npm run env:start
```
Now, install the test data needed for the tests:
```bash
npm run cypress:setup
```

Then you can run the tests:
```bash
npm run cypress:run
```
Or open Cypress using
```bash
npm run cypress:open
```
