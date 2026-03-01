#!/bin/bash -e

# chown -R uucp:uucp /var/spool/hylafaxplus

# # Check if FAX_USER is set, if not use default
# FAX_USER="admin"

# # Check if hosts.hfaxd file exists and contains the username
# HOSTS_FILE="/var/spool/hylafaxplus/etc/hosts.hfaxd"

# if [[ ! -f "$HOSTS_FILE" ]] || ! grep -q "^${FAX_USER}" "$HOSTS_FILE"; then
#     echo "Running faxadduser for user: $FAX_USER"
#     faxadduser -c -a "$FAX_ADMIN_PASSWORD" -p "$FAX_ADMIN_PASSWORD" "$FAX_USER"
# fi

RETRIES=30

until mariadb-admin -hmariadb --user=avantfax --password="${MYSQL_PASSWORD}" ping || [ $RETRIES -eq 0 ]; do
  echo "Waiting for MySQL, $RETRIES attempts left..."
  RETRIES=$((RETRIES - 1))
  sleep 2
done

[ $RETRIES -eq 0 ] && echo "MySQL did not start in time" && exit 1

mysql -hmariadb --user=avantfax --password="${MYSQL_PASSWORD}" avantfax < /tmp/avantfax/create_tables.sql

# shellcheck disable=SC2016
envsubst '$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DATABASE' < /tmp/templates/local_config.php > /var/www/avantfax/includes/local_config.php
cp /tmp/templates/config.ttyIAX /var/spool/hylafaxplus/etc/config.ttyIAX
cp /tmp/templates/ttyIAX /etc/iaxmodem/ttyIAX

/usr/bin/php /setup_db.php


/usr/bin/supervisord -c /etc/supervisord.conf