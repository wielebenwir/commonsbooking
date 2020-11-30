#!/bin/sh
version=$(awk '/[^[:graph:]]Version/{print $NF}' commonsbooking.php)
echo "Zipping Version $version..."
zip -r "commonsbooking-$version.zip" assets includes languages src templates vendor commonsbooking.php index.php LICENSE.txt readme.txt