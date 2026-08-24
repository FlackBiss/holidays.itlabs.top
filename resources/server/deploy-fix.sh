#!/usr/bin/env bash
set -euo pipefail

deploy_live=/home/shared-backend/web/holidays.itlabs.top/public_html
deploy_mirror=/home/shared-backend/web/holidays.itlabs.top/private/releases/20260824-101500-holidays-backend

test "$(readlink -f "$deploy_live")" = "$deploy_live"
test "$(readlink -f "$deploy_mirror")" = "$deploy_mirror"

tar -xzf /tmp/holidays-fix-20260824-redirect-content.tar.gz -C "$deploy_live"
tar -xzf /tmp/holidays-fix-20260824-redirect-content.tar.gz -C "$deploy_mirror"
rm -f "$deploy_live/src/Controller/Admin/AnimationDocumentCrudController.php"
rm -f "$deploy_mirror/src/Controller/Admin/AnimationDocumentCrudController.php"
rm -f "$deploy_live/.htaccess"
rm -f "$deploy_mirror/.htaccess"
chown -R shared-backend:shared-backend "$deploy_live/src" "$deploy_live/tests" "$deploy_live/migrations"
chown -R shared-backend:shared-backend "$deploy_mirror/src" "$deploy_mirror/tests" "$deploy_mirror/migrations"

install -o shared-backend -g shared-backend -m 0644 /tmp/apache2.conf_publicroot /home/shared-backend/conf/web/holidays.itlabs.top/apache2.conf_publicroot
install -o shared-backend -g shared-backend -m 0644 /tmp/apache2.ssl.conf_publicroot /home/shared-backend/conf/web/holidays.itlabs.top/apache2.ssl.conf_publicroot
install -o shared-backend -g shared-backend -m 0644 /tmp/nginx.conf_publicroot /home/shared-backend/conf/web/holidays.itlabs.top/nginx.conf_publicroot
install -o shared-backend -g shared-backend -m 0644 /tmp/nginx.ssl.conf_publicroot /home/shared-backend/conf/web/holidays.itlabs.top/nginx.ssl.conf_publicroot
apache2ctl configtest
ulimit -n 65535
nginx -t
systemctl reload apache2
systemctl reload nginx

cd "$deploy_live"
sudo -u shared-backend php bin/console doctrine:migrations:migrate --no-interaction
sudo -u shared-backend php bin/console app:seed-content
sudo -u shared-backend php bin/console cache:clear
sudo -u shared-backend php bin/console cache:warmup
