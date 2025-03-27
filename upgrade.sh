#!/bin/bash

GIT_BRANCH="origin/master" # Default branch

# Detect Ubuntu version
if [[ -f /etc/os-release ]]; then
  . /etc/os-release
  if [[ "$NAME" == "Ubuntu" && "$VERSION_ID" == "20.04" ]]; then
    echo "Currently running maintenance release for Ubuntu 20.04, please consider upgrading to Ubuntu 24.04 for updates!"
    GIT_BRANCH="origin/ubuntu20_04"
  elif [[ "$NAME" != "Ubuntu" || "$VERSION_ID" != "24.04" ]]; then
    echo "WARNING: Unsupported OS or version. This script supports only Ubuntu 20.04 and 24.04."
    GIT_BRANCH="origin/ubuntu20_04"
  fi
else
  echo "WARNING: /etc/os-release not found.  Unsupported and proceeding with OLD branch."
  GIT_BRANCH="origin/ubuntu20_04"
fi

# Switch to the appropriate branch and pull updates
cd /usr/share/sonar_poller
git reset --hard "$GIT_BRANCH"
git pull

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
