[![PHP Composer](https://github.com/wielebenwir/commonsbooking/actions/workflows/phpunit.yml/badge.svg)](https://github.com/wielebenwir/commonsbooking/actions/workflows/phpunit.yml)
[![E2E Tests](https://github.com/wielebenwir/commonsbooking/actions/workflows/e2e.yml/badge.svg)](https://github.com/wielebenwir/commonsbooking/actions/workflows/e2e.yml)
[![WP compatibility](https://plugintests.com/plugins/wporg/commonsbooking/wp-badge.svg)](https://plugintests.com/plugins/wporg/commonsbooking/latest)
[![PHP compatibility](https://plugintests.com/plugins/wporg/commonsbooking/php-badge.svg)](https://plugintests.com/plugins/wporg/commonsbooking/latest)
[![codecov](https://codecov.io/gh/wielebenwir/commonsbooking/branch/master/graph/badge.svg?token=STJC8WPWIC)](https://codecov.io/gh/wielebenwir/commonsbooking)

# CommonsBooking

Donate link: https://www.wielebenwir.de/verein/unterstutzen
License: GPLv2 or later

CommonsBooking is a WordPress plugin for the management and booking of common goods. This plugin provides associations,
groups, and individuals with the ability to share items (such as cargo bikes and tools) among users. It is based on the
concept of [Commons](https://en.wikipedia.org/wiki/Commons), where resources are shared for the benefit of the community.

## Links

* [WordPress Plugin Page](https://wordpress.org/plugins/commonsbooking/) or the [official Website](https://commonsbooking.org)
* View the full [changelog](https://wordpress.org/plugins/commonsbooking/#developers).
* For users read the [documentation](https://commonsbooking.org/dokumentation) or get [Support](https://commonsbooking.org/kontakt/)
* For developers use the [Bug-Tracker](https://github.com/wielebenwir/commonsbooking/issues?q=is%3Aissue%20state%3Aopen%20label%3Abug)

## Installation

Refer to our [Documentation](https://commonsbooking.org/dokumentation/installation/installieren) for the normal installation and the necessary setup.
See [INSTALL.md](https://github.com/wielebenwir/commonsbooking/blob/master/INSTALL.md) for additional installation methods.

## Feedback

We appreciate your feedback through the following means:

* Rate our Plugin on [WordPress.org](https://wordpress.org/plugins/commonsbooking).
* File an issue, enhancements or bugs in the [Issue tracker](https://github.com/wielebenwir/commonsbooking/issues).
* Or e-mail us to [support@commonsbooking.org](mailto:support@commonsbooking.org).

## Contribute

Contributions are welcome either through:

* Translating the Plugin into more languages (see [Update translations](#update-translations))
* Improving the documentation at https://commonsbooking.org or help translating it (see [Issue #1986](https://github.com/wielebenwir/commonsbooking/issues/1986))
* Developing and testing new versions of the application (see [DEVELOP.md](https://github.com/wielebenwir/commonsbooking/blob/master/DEVELOP.md))

We also appreciate your donation to further develop the plugin: https://www.wielebenwir.de/verein/unterstutzen

### Update translations

See the [WordPress plugin translation page](https://translate.wordpress.org/projects/wp-plugins/commonsbooking/) for all languages that are available or work in progress.
Currently, we only manage German and English translations as po files in the repository, so they are available at build time.

Create a new .pot file using the
```
wp i18n make-pot . languages/commonsbooking.pot
```
command in the plugin directory. Make sure that all of your strings use the `__` function with the domain `commonsbooking`. Then you can use `poedit` to open the `commonsbooking-de_DE.po` and update the strings from the `pot` file.

#### Build plugin zip

To create the plugin zip file for uploading to a development server:
```
bin/build-zip.sh
```
