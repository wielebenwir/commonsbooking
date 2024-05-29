# install-wp-cli.sh
#
# Felipe Elia <contato@felipeelia.com.br> and 10up contributors
#
# The following code is a derivative work of the code from the ElasticPress project,
# which is licensed GPLv2. This code therefore is also licensed under the terms
# of the GNU Public License, version 2.'

#!/usr/bin/env bash

echo "Installing WP-CLI in $1"

./bin/wp-env-cli $1 curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
./bin/wp-env-cli $1 chmod +x wp-cli.phar
./bin/wp-env-cli $1 mv wp-cli.phar /usr/local/bin/wp
