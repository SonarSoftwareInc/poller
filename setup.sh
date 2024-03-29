#!/bin/bash

if [ "$EUID" -ne 0 ]
  then echo "Please run as root."
  exit
fi

echo "Installing the Sonar poller...";

## Add PHP repository, setup PHP
add-apt-repository -y ppa:ondrej/php
apt-get -y update
apt-get install -y php7.4-cli php7.4-xml php7.4-common php7.4-json php7.4-gmp php7.4-dev php7.4-sqlite3 php7.4-zip php7.4-fpm php7.4-mbstring composer openssl git php-pear snmp

update-alternatives --set php /usr/bin/php7.4
update-alternatives --set phpize /usr/bin/phpize7.4
update-alternatives --set php-config /usr/bin/php-config7.4

pecl channel-update pecl.php.net
print "\n" | pecl install ev
grep -qxF 'extension=ev.so' /etc/php/7.4/cli/php.ini || echo "extension=ev.so" >> /etc/php/7.4/cli/php.ini

## Install the latest fping
apt-get install -y build-essential
wget https://fping.org/dist/fping-5.0.tar.gz
tar -zxvf fping-5.0.tar.gz
(cd fping-5.0; ./configure; make && make install;)
setcap cap_net_raw+ep /usr/local/sbin/fping

## Setup sysctl for better monitoring performance
grep -qxF 'net.core.somaxconn=4096' /etc/sysctl.conf || echo "net.core.somaxconn=4096" >> /etc/sysctl.conf
grep -qxF 'net.ipv4.icmp_ratelimit=0' /etc/sysctl.conf || echo "net.ipv4.icmp_ratelimit=0" >> /etc/sysctl.conf
grep -qxF 'net.ipv4.icmp_msgs_per_sec=100000' /etc/sysctl.conf || echo "net.ipv4.icmp_msgs_per_sec=100000" >> /etc/sysctl.conf
grep -qxF 'net.ipv4.icmp_msgs_burst=5000' /etc/sysctl.conf || echo "net.ipv4.icmp_msgs_burst=5000" >> /etc/sysctl.conf
grep -qxF 'fs.file-max = 500000' /etc/sysctl.conf || echo "fs.file-max = 500000" >> /etc/sysctl.conf

/sbin/sysctl -p
grep -qxF 'DefaultLimitNOFILE=65535' /etc/systemd/system.conf || echo "DefaultLimitNOFILE=65535" >> /etc/systemd/system.conf
grep -qxF 'DefaultLimitNOFILE=65535' /etc/systemd/system.conf || echo "DefaultLimitNOFILE=65535" >> /etc/systemd/system.conf
grep -qxF '* soft nofile 65535' /etc/systemd/system.conf || echo "* soft nofile 65535" >> /etc/systemd/system.conf
grep -qxF '* hard nofile 65535' /etc/systemd/system.conf || echo "* hard nofile 65535" >> /etc/systemd/system.conf

## Clone the repo and run initial setup
(cd /usr/share; rm -rf sonar_poller; mkdir sonar_poller; cd sonar_poller; git clone https://github.com/SonarSoftwareInc/poller.git .;)

## Write version and prevent potential dubious ownership error
git config --global --add safe.directory /usr/share/sonar_poller
(cd /usr/share/sonar_poller; git describe --tags > version;)

## Setup permissions
chown -R www-data:www-data /usr/share/sonar_poller

## Setup nginx and self signed cert
apt-get install -y nginx
cp /usr/share/sonar_poller/ssl/self-signed.conf /etc/nginx/snippets/
cp /usr/share/sonar_poller/ssl/default /etc/nginx/sites-available/
systemctl restart nginx

## Install vendor libraries needed for poller
mkdir /var/www/.composer
chown www-data:www-data /var/www/.composer
cd /usr/share/sonar_poller/
sudo -u www-data composer install

## Setup log rotation
cp /usr/share/sonar_poller/config/sonar_poller_logs /etc/logrotate.d/

## Check for upgrades daily
chmod +x /usr/share/sonar_poller/upgrade.sh
echo "0 0 * * * root bash /usr/share/sonar_poller/upgrade.sh" > /etc/cron.d/sonar_poller_upgrade

## Setup supervisor for poller
apt-get install -y supervisor
cp /usr/share/sonar_poller/config/sonar_poller.conf /etc/supervisor/conf.d/
systemctl restart supervisor


## Reboot to apply ulimit changes
echo "Rebooting...";
reboot now
