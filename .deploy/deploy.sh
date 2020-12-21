#!/bin/bash
cd /var/repo/iwgb-outbound-gateway || exit 1

rsync -a . /var/www/iwgb-outbound-gateway --delete --exclude .git --exclude .deploy --exclude .github --exclude vendor --exclude .gitignore

cd /var/repo/iwgb-outbound-gateway-static || exit 1
rsync -a . /var/www/iwgb-outbound-gateway

cd /var/www/iwgb-outbound-gateway || exit 1
mkdir var

chown -R www-data:www-data /var/www/iwgb-outbound-gateway
chmod -R 774 /var/www/iwgb-outbound-gateway
runuser -l deploy -c 'cd /var/www/iwgb-outbound-gateway && composer install'
