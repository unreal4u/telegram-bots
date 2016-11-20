#!/usr/bin/env bash

echo "[PROVISION] Installing epel and other basic stuff"
yum install -q -y epel-release firewalld deltarpm
# Enable installation after epel is installed
echo "[PROVISION] Installing additional software"
yum install -q -y lynx ntp vim-enhanced wget unzip git nginx mariadb-server redis

echo "[PROVISION] Enabling basic services"
# Enable services
systemctl enable ntpd
systemctl start ntpd
systemctl enable firewalld
systemctl start firewalld

# Set the correct time
ntpdate -u pool.ntp.org

PHP_VERSION="70"
echo "[PROVISION] Installing PHP${PHP_VERSION}"
# PHP 7.0.x install:
yum install -q -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum install -q -y \
  php${PHP_VERSION}-php \
  php${PHP_VERSION}-php-fpm \
  php${PHP_VERSION}-php-mysqlnd \
  php${PHP_VERSION}-php-intl \
  php${PHP_VERSION}-php-opcache \
  php${PHP_VERSION}-php-mbstring \
  php${PHP_VERSION}-php-bcmath \
  php${PHP_VERSION}-php-xml \
  php${PHP_VERSION}-php-process \
  php${PHP_VERSION}-php-pecl-xdebug \
  php${PHP_VERSION}-php-pecl-zip \
  php${PHP_VERSION}-php-pecl-redis \
  php${PHP_VERSION}-php-dbg
ln -s /usr/bin/php${PHP_VERSION} /usr/bin/php
ln -s /usr/bin/php${PHP_VERSION}-phpdbg /usr/bin/phpdbg

echo "[PROVISION] Installing composer"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/bin/

echo "[PROVISION] Moving configuration files to definitive locations"
chown root:root /home/vagrant/*.conf
mv /home/vagrant/php-fpm-telegram.conf /etc/opt/remi/php${PHP_VERSION}/php-fpm.d/
mv /home/vagrant/nginx-telegram.conf /etc/nginx/conf.d/
restorecon /etc/opt/remi/php${PHP_VERSION}/php-fpm.d/php-fpm-telegram.conf
restorecon /etc/nginx/conf.d/nginx-telegram.conf

sed -i "s/__PHPVERSION__/${PHP_VERSION}/" /etc/opt/remi/php${PHP_VERSION}/php-fpm.d/php-fpm-telegram.conf
sed -i 's/listen    /#listen    /' /etc/nginx/nginx.conf
sed -i 's/server_name  _/#server_name  _/' /etc/nginx/nginx.conf

echo "[PROVISION] SELinux access to shared vagrant folder"
mv /home/vagrant/phpfpm-access-to-shared-folder.te /root/
checkmodule -M -m -o /root/phpfpm-access-to-shared-folder.mod /root/phpfpm-access-to-shared-folder.te
semodule_package -o /root/phpfpm-access-to-shared-folder.pp -m /root/phpfpm-access-to-shared-folder.mod
semodule -i /root/phpfpm-access-to-shared-folder.pp

echo "[PROVISION] Opening up firewall"
firewall-cmd --zone=public --add-service http
firewall-cmd --zone=public --add-service http --permanent
# Open up for development purposes
firewall-cmd --zone=public --add-port 3306/tcp
firewall-cmd --zone=public --add-port 3306/tcp --permanent

echo "[PROVISION] Enabling services"
systemctl enable php${PHP_VERSION}-php-fpm nginx mariadb redis
systemctl start php${PHP_VERSION}-php-fpm nginx mariadb redis

echo "[PROVISION] Setting MariaDB up"
mysql -uroot < /home/vagrant/userrights.sql

echo "[PROVISION] Cleaning up"
rm -f /home/vagrant/userrights.sql
rm -f /root/phpfpm-access-to-shared-folder.*

echo "Provision completed on $(date +%F) $(date +%T)" > /home/vagrant/last-provision
