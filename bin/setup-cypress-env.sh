#!/bin/bash
wp-env --config=.wp-env.test.json run cli wp plugin install --activate wordpress-importer
# Install our example posts from a WP export file
wp-env --config=.wp-env.test.json run cli wp import /var/www/html/wp-content/plugins/commonsbooking/tests/cypress/wordpress-files/content-example.xml --authors=create
# Switch to Kasimir theme
wp-env --config=.wp-env.test.json run cli wp theme activate kasimir-theme
# Create subscriber with username "subscriber" and password "password"
wp-env --config=.wp-env.test.json run cli wp user create subscriber sub@sub.de --role=subscriber --user_pass=password
