# Contributing to CommonsBooking

Thank you for considering contributing to CommonsBooking! This plugin helps communities share resources like cargo bikes, tools, and more.

## How to Contribute

### Reporting Bugs

- Check the [issue tracker](https://github.com/wielebenwir/commonsbooking/issues) to see if your issue has already been reported
- Use the provided issue template when creating a new issue
- Include detailed steps to reproduce the bug
- Mention your WordPress version, PHP version, and browser if relevant

### Suggesting Features

- Search the issue tracker for similar feature requests before creating a new one
- Clearly explain the feature and why it would benefit the plugin users
- If possible, provide examples of how and for whom the feature might work

### Code Contributions

#### Setup Development Environment

1. Fork and clone the repository
2. Set up your development environment:
   ```bash
   npm run start         # Install dependencies
   npm run env:start     # Start WP-Env (requires Docker)
   ```

##### Running the Plugin

One of the easiest ways to start hacking WordPress plugins in general (if you have no other development environment set up) is using [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/). Install it and its dependencies (mainly Docker) and run this to start the environment:

The provided `.wp-env.json` should be sufficient for normal development. For details, see the [documentation of wp-env config](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-json). You can create a `.wp-env.override.json` for a custom configuration you don't want to check in.


For testing, you can activate the [kasimir theme](https://github.com/flegfleg/kasimir-theme) via [wp cli](https://make.wordpress.org/cli/handbook/) inside the wp-env docker container:
```bash
npm run env run cli wp theme activate kasimir-theme
```

#### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Adhere to PHPCS rules defined in `.phpcs.xml.dist`
- Configure your IDE to use `phpcbf` for auto-formatting
- Use the appropriate text domain (`commonsbooking`) for all strings

#### Testing

Before submitting a pull request:

##### Setup wordpress

To test the code, first run the [preparation scripts](https://github.com/wp-cli/scaffold-command#wp-scaffold-plugin-tests) to load the WordPress core and configure the database connection via `wp-config.php`. The following line can vary on your system; use the appropriate credentials, database port, and version of WordPress. The appropriate database port is printed out by `npm run env:start`:
```bash
bash bin/install-wp-tests.sh wordpress root password 127.0.0.1:49153 latest
```

##### PHPUnit

Testing the plugin code via `phpunit`. At the moment, it works only with a manually downloaded phar. We are using PHPUnit 9 and PHP7.4 for the automated tests. The tests might fail if you are using a different version.
```bash
php ~/phpunit.phar --bootstrap tests/php/bootstrap.php
```
##### E2E

E2E (end-to-end) tests are written in [cypress](https://www.cypress.io/). To run them, you need to install cypress and start the WordPress environment:
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

2. Ensure your code passes all GitHub Actions checks

#### Pull Requests

- Create a feature branch for your changes
- Include tests for new functionality
- Keep PRs focused - one issue per PR when possible
- Link to any related issues
- Wait for code review and address feedback

### Translations

Help translate CommonsBooking:

- Contribute through the [WordPress translation platform](https://translate.wordpress.org/projects/wp-plugins/commonsbooking/)
- Currently, we only manage German and English translations as po files in the repository, so they are available at build time.

Update the .pot file

```bash
wp i18n make-pot . languages/commonsbooking.pot
```
Make sure that all of your strings use the `__` function with the domain `commonsbooking`. Then you can use `poedit` to open the `commonsbooking-de_DE.po` and update the strings from the `pot` file.


## Development Workflow

1. Pick an issue to work on or create a new one
2. Discuss approach in the issue
3. Implement your solution
4. Test thoroughly
5. Submit a pull request
6. Address review feedback

### Building the Plugin Zip

To create the plugin zip file for uploading to a development server:
```bash
bin/build-zip.sh
```


## License

By contributing to CommonsBooking, you agree that your contributions will be licensed under the project's [GPLv2 (or later) License](http://www.gnu.org/licenses/gpl-2.0.html).

## Questions?

If you have questions about contributing, please [contact the team](https://commonsbooking.org/kontakt/) or file an issue.

Thank you for helping make CommonsBooking better!
