(cd /usr/share/sonar_poller; git reset --hard origin/master; git pull;)

chown -R www-data:www-data /usr/share/sonar_poller/www
chown -R www-data:www-data /usr/share/sonar_poller/ssl
chown -R www-data:www-data /usr/share/sonar_poller/logs
chown -R www-data:www-data /usr/share/sonar_poller/permanent_config

(cd /usr/share/sonar_poller; git describe --tags > version;)
supervisorctl update sonar_poller
