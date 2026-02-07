[![PHP Composer](https://github.com/wielebenwir/commonsbooking/actions/workflows/phpunit.yml/badge.svg)](https://github.com/wielebenwir/commonsbooking/actions/workflows/phpunit.yml)
[![E2E Tests](https://github.com/wielebenwir/commonsbooking/actions/workflows/e2e.yml/badge.svg)](https://github.com/wielebenwir/commonsbooking/actions/workflows/e2e.yml)
[![WP compatibility](https://plugintests.com/plugins/wporg/commonsbooking/wp-badge.svg)](https://plugintests.com/plugins/wporg/commonsbooking/latest) 
[![PHP compatibility](https://plugintests.com/plugins/wporg/commonsbooking/php-badge.svg)](https://plugintests.com/plugins/wporg/commonsbooking/latest)
[![codecov](https://codecov.io/gh/wielebenwir/commonsbooking/branch/master/graph/badge.svg?token=STJC8WPWIC)](https://codecov.io/gh/wielebenwir/commonsbooking)

# CommonsBooking

Contributors: wielebenwirteam, m0rb, flegfleg, chriwen  
Donate link: https://www.wielebenwir.de/verein/unterstutzen  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

CommonsBooking is a plugin for the management and booking of common goods. This plugin provides associations, groups, and individuals with the ability to share items (such as cargo bikes and tools) among users. It is based on the concept of Commons, where resources are shared for the benefit of the community.

## Links

* [WordPress Plugin Page](https://wordpress.org/plugins/commonsbooking/)
* [View Changelog](https://wordpress.org/plugins/commonsbooking/#developers)
* [Official Website](https://commonsbooking.org)
* For users read the [documentation](https://commonsbooking.org/dokumentation) or get [Support](https://commonsbooking.org/kontakt/)
* For developers use the [Bug-Tracker](https://github.com/wielebenwir/commonsbooking/issues?q=is%3Aissue%20state%3Aopen%20label%3Abug) 

## Installation

### Using The WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'commonsbooking'
3. Click 'Install Now'
4. Activate the plugin in the plugins dashboard
 

### Uploading in WordPress Dashboard 

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `commonsbooking.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the plugins dashboard

### Using FTP

1. Download `commonsbooking.zip`
2. Extract the `commonsbooking` directory to your computer
3. Upload the `commonsbooking` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the plugins dashboard

### Using GitHub (developers only)

1. Make sure that composer is installed on your system
2. Navigate into your wp-content/plugins directory
3. Open a terminal and run `git clone https://github.com/wielebenwir/commonsbooking`
4. cd into the directory commonsbooking and run `npm start`
> This might fail, if you don't have the PHP extension [uopz](https://www.php.net/manual/en/book.uopz.php) installed. Try running `composer install --no-dev && npm install && npm run dist`  if you just quickly want to test a specific branch without installing the extension.
5. Activate the plugin in the plugins dashboard

## Contribute

Contributions are welcome either through 

* Translating WordPress into your native tongue ([see the already existing WordPress Plugin Translations](https://translate.wordpress.org/projects/wp-plugins/commonsbooking/))
* Improving or translating the documentation at https://commonsbooking.org
* or through developing and testing new versions of the application (see [Development](#development))

## Development

### Prerequisites

To avoid setup-related errors, make sure you have the following installed:

- PHP
- Composer
- Node.js + npm

Optional (for [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)):

- Docker
- On Windows: WSL2 enabled (recommended by Docker Desktop)


### Run plugin

First, we have to install the necessary dependencies and packages: We can do this using 
```
npm run start
```
`npm run start` runs `composer install`, `npm install` and then builds assets via `grunt dist`.

The most easy way to start hacking WordPress plugins in general (if you have no other development environment set up) is using [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/). Install it and it's dependencies (mainly Docker) and run this to start the enviroment:
```
npm run env:start
```
The provided `.wp-env.json` should be sufficient for normal development, for details see the [documentation of wp-env config](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-json). [You can create](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-override-json) a `.wp-env.override.json` for a custom configuration you don't want to check in.

For testing, you can activate the [kasimir theme](github.com/flegfleg/kasimir-theme) via [wp cli](https://make.wordpress.org/cli/handbook/) inside the wp-env docker container:
```
npm run env run cli wp theme activate kasimir-theme
```

### Test plugin

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

### Update translations

Currently, we only manage German and English translations as po files in the repository, so they are available at build time. 
See the [WordPress plugin translation page](https://translate.wordpress.org/projects/wp-plugins/commonsbooking/) for other languages available at runtime.

Create a new .pot file using the 
```
wp i18n make-pot . languages/commonsbooking.pot
```
command in the plugin directory. Make sure that all of your strings use the `__` function with the domain `commonsbooking`. Then you can use `poedit` to open the `commonsbooking-de_DE.po` and update the strings from the `pot` file. 

### Build plugin zip

To create the plugin zip file for uploading to a development server:
```
bin/build-zip.sh
```
