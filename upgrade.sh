(cd /usr/share/sonar_poller; git reset --hard origin/master; git pull;)

(cd /usr/share/sonar_poller; git describe --tags > version;)
(cd /usr/share/sonar_poller; composer install;)

chown -R www-data:www-data /usr/share/sonar_poller/www
chown -R www-data:www-data /usr/share/sonar_poller/ssl
chown -R www-data:www-data /usr/share/sonar_poller/logs
chown -R www-data:www-data /usr/share/sonar_poller/permanent_config
chown -R www-data:www-data /usr/share/sonar_poller/vendor

chmod +x /usr/share/sonar_poller/upgrade.sh

supervisorctl update
supervisorctl restart sonar_poller
