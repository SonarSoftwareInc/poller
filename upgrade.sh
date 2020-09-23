(cd /usr/share/sonar_poller/poller; git reset --hard origin/master; git pull;)

chown -R www-data:www-data /usr/share/sonar_poller/poller/www
chown -R www-data:www-data /usr/share/sonar_poller/poller/ssl
chown -R www-data:www-data /usr/share/sonar_poller/poller/logs
chown -R www-data:www-data /usr/share/sonar_poller/poller/permanent_config

(cd /usr/share/sonar_poller/poller; git describe --tags > version;)
