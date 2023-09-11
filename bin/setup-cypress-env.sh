# setup-cypress-env.sh
# Felipe Elia <contato@felipeelia.com.br> and 10up contributors
#
# The following code is a derivative work of the code from the ElasticPress project,
# which is licensed GPLv2. This code therefore is also licensed under the terms
# of the GNU Public License, version 2.'

#!/bin/bash

./bin/wp-env-cli tests-wordpress "wp --allow-root import /var/www/html/wp-content/plugins/commonsbooking/cypress/wordpress-files/content-example.xml --authors=create"
