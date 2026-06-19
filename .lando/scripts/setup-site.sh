#!/bin/bash

# Disable xdebug during setup.
sed -i 's/^zend_extension/;zend_extension/g' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy settings files.
echo "Copying settings files."
cp -n ../drupal/settings.local.php /app/web/sites/default/settings.local.php

maxcounter=1000
counter=1
while ! mysql -h database --protocol TCP -u"drupal9" -p"drupal9" -e "show databases;" > /dev/null 2>&1; do
  echo "...Waiting for Drupal MYSQL..."
  sleep 1
  counter=$((counter + 1))
  if [ "$counter" -gt $maxcounter ]; then
      >&2 echo "We have been waiting for MySQL too long already; failing."
      exit 1
  fi;
done

echo "Drupal MYSQL ready!"

if tables=$(drush sqlq 'show tables;') && [ -z "$tables" ]; then
  echo "Syncing drupal database ..."
  /app/vendor/drush/drush/drush @dev sql-dump --gzip --result-file=../drupaldb.sql -y
  /app/vendor/drush/drush/drush rsync @dev:../drupaldb.sql.gz @self:/app/drupaldb.sql.gz -y
  gunzip -c /app/drupaldb.sql.gz | sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' | /app/vendor/drush/drush/drush sql-cli
  /app/vendor/drush/drush/drush @dev ssh rm ../drupaldb.sql.gz
  rm /app/drupaldb.sql.gz
  /app/vendor/drush/drush/drush --root=web deploy -y
fi

echo "Database up and connected"

maxcounter=1000
counter=1
while ! mysql -h forumdb --protocol TCP -u"forum" -p"forum" -e "show databases;" > /dev/null 2>&1; do
  echo "...Waiting for Forum MYSQL..."
  sleep 1
  counter=$((counter + 1))
  if [ "$counter" -gt $maxcounter ]; then
      >&2 echo "We have been waiting for Forum MySQL too long already; failing."
      exit 1
  fi;
done

echo "Forum MYSQL ready!"

# Re-enable xdebug.
sed -i 's/^;zend_extension/zend_extension/g' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

echo "************************************************"
echo "OAFC Site Setup Complete!"
echo "************************************************"
