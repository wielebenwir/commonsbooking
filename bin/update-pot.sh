#!/bin/sh
#This script will generate a new .pot file and replace it with the old one when there are changes
#We do this so that we don't spam our commit log when the only thing that has changed is the POT-Creation-Date
#Please run this script directly from the plugin folder like so: bin/update-pot.sh
wp i18n make-pot . languages/commonsbooking.pot.new
#now, only move the .pot.new file over, when something else than the creation date has changed
if diff -I '^"POT-Creation-Date:' languages/commonsbooking.pot languages/commonsbooking.pot.new >/dev/null 2>&1; then
  rm languages/commonsbooking.pot.new
  echo "no changes detected"
else
  mv languages/commonsbooking.pot.new languages/commonsbooking.pot
  echo "updating po file from change .pot"
  wp i18n update-po languages/commonsbooking.pot languages/commonsbooking-de_DE.po
  echo "updated translation file"
fi
