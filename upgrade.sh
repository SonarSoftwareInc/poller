#!/bin/bash

GIT_BRANCH="origin/master" # Default branch

# Detect Ubuntu version
UBUNTU_VERSION=$(lsb_release -rs)
if [[ "$UBUNTU_VERSION" == "20.04" ]]; then
  echo "Currently running maintenance release for Ubuntu 20.04, please consider upgrading to Ubuntu 24.04 for updates!"
  GIT_BRANCH="origin/ubuntu20_04"
fi

# Switch to the appropriate branch and pull updates
cd /usr/share/sonar_poller
git reset --hard "$GIT_BRANCH"
git pull "$GIT_BRANCH"

# Update version and dependencies
git describe --tags > version
sudo -u www-data composer install

chown -R www-data:www-data /usr/share/sonar_poller/www
chown -R www-data:www-data /usr/share/sonar_poller/ssl
chown -R www-data:www-data /usr/share/sonar_poller/logs
chown -R www-data:www-data /usr/share/sonar_poller/permanent_config
chown -R www-data:www-data /usr/share/sonar_poller/vendor

chmod +x /usr/share/sonar_poller/upgrade.sh

supervisorctl update
supervisorctl restart sonar_poller
