#!/bin/bash

add-apt-repository ppa:ondrej/php
apt-get update
apt-get install -y fping php7.4-cli php7.4-common php7.4-json php7.4-gmp php7.4-dev pkg-php-tools composer
pecl install ev
echo "extension=ev.so" >> /etc/php/7.4/cli/php.ini
setcap cap_net_raw+ep /usr/bin/fping
echo "net.core.somaxconn=4096" >> /etc/sysctl.conf
echo "net.ipv4.icmp_ratelimit=0" >> /etc/sysctl.conf
echo "net.ipv4.icmp_msgs_per_sec=100000" >> /etc/sysctl.conf
echo "net.ipv4.icmp_msgs_burst=5000" >> /etc/sysctl.conf
echo "fs.file-max = 500000" >> /etc/sysctl.conf
/sbin/sysctl -p
echo "DefaultLimitNOFILE=65535" >> /etc/systemd/system.conf
echo "DefaultLimitNOFILE=65535" >> /etc/systemd/user.conf
echo "* soft nofile 65535" >> /etc/security/limits.conf
echo "* hard nofile 65535" >> /etc/security/limits.conf
echo "You will need to reboot to increase open file descriptors. After rebooting, run `ulimit -n` and make sure it's 65535."