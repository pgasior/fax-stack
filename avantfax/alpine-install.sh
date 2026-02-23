#!/bin/bash -ex

echo "Checking for HylaFAX installation"

hyla=`which sendfax`
if [ "$?" -ne "0" ]; then
  echo You must install and configure HylaFAX first
  exit
fi

. ./alpine-prefs.txt

echo "Installing required packages"

apk add --no-cache \
  apache2-proxy \
  sqlite \
  sqlite-libs \
  php84 php84-apache2 php84-pear php84-fileinfo php84-mysqli php84-pdo_mysql php84-session php84-mbstring \
  apr apr-util \
  imagemagick \
  tiff \
  netpbm netpbm-extras \
  psutils \
  rsync \
  sudo cronie \
  mariadb-client \
  gettext-envsubst

pear84 channel-update pear.php.net
pear84 upgrade-all
pear84 install Mail Net_SMTP Mail_mime MDB2_driver_mysql
pear84 install Archive_Tar

ln -s /usr/bin/php84 /usr/bin/php

echo "Installing AvantFAX and configuring HylaFAX"

## SETUP SMARTY
chmod 0770 avantfax/includes/templates/admin_theme/templates_c/ avantfax/includes/templates/admin_theme/cache/  avantfax/includes/templates/main_theme/templates_c/ avantfax/includes/templates/main_theme/cache/
chown $HTTPDUSER:$HTTPDGROUP avantfax/includes/templates/admin_theme/templates_c/ avantfax/includes/templates/admin_theme/cache/  avantfax/includes/templates/main_theme/templates_c/ avantfax/includes/templates/main_theme/cache/

chmod 0755 avantfax/includes/faxcover.php avantfax/includes/faxrcvd.php avantfax/includes/notify.php avantfax/tools/update_contacts.php avantfax/tools/faxcover.php avantfax/includes/avantfaxcron.php avantfax/includes/dynconf.php

# done in startup script
# cp avantfax/includes/local_config-example.php avantfax/includes/local_config.php

echo "CoverCmd:		$INSTDIR/includes/faxcover.php" >> $HYLADIR/sendfax.conf
echo "Notify: requeued" >> $HYLADIR/sendfax.conf

# SETUP AVANTFAX JOBFMT

cat >> $HYLADIR/hyla.conf << EOF

#
## JobFmt for AvantFAX
#
JobFmt: "%-3j %3i %1a %15o %40M %-12.12e %5P %5D %7z %.25s"

EOF

mv avantfax $INSTDIR
chown -R $HTTPDUSER:$HTTPDGROUP $INSTDIR
chmod -R 0770 $INSTDIR/tmp $INSTDIR/faxes
chown -R $HTTPDUSER:uucp $INSTDIR/tmp $INSTDIR/faxes

cat > /etc/apache2/conf.d/avantfax.conf << EOF

<VirtualHost *:80>
    DocumentRoot $INSTDIR
    ServerName avantfax
    ErrorLog logs/avantfax-error_log
    CustomLog logs/avantfax-access_log common
    <Directory $INSTDIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF

# SYMLINK AVANTFAX SCRIPTS

ln -s $INSTDIR/includes/faxrcvd.php $SPOOL/bin/faxrcvd.php
ln -s $INSTDIR/includes/dynconf.php $SPOOL/bin/dynconf.php
ln -s $INSTDIR/includes/notify.php  $SPOOL/bin/notify.php

# FIX FILEINFO

ln -s /usr/share/file/magic* /usr/share/misc/

# SETUP SUDO PERMISSIONS

echo "Setting up sudo"

cat /etc/sudoers | grep -v requiretty > /tmp/sudoers
echo "$HTTPDUSER ALL= NOPASSWD: /sbin/reboot, /sbin/halt, /usr/sbin/faxdeluser, /usr/sbin/faxadduser -c -u * -p * *" >> /tmp/sudoers
mv /etc/sudoers /etc/sudoers.orig
mv /tmp/sudoers /etc/sudoers
chmod 0440 /etc/sudoers
chown root:root  /etc/sudoers


cat >>  $SPOOL/etc/config << EOF

#
## AvantFAX
#
NotifyCmd:      bin/notify.php

EOF

sed -i $'s/^default.*/default\tA4\t9920\t14030\t9240\t13200\t472\t345/' $HYLADIR/pagesizes

# ADD CRONTAB ENTRIES

echo "Setting up /etc/cron.d/avantfax"
printf "0 0 * * *\t$INSTDIR/includes/avantfaxcron.php -t 2\n" > /etc/cron.d/avantfax