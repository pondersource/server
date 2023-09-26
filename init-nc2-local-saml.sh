#!/bin/bash

rm -rf data/
rm -f config/config.php
set -e

echo Installing Nextcloud
php console.php maintenance:install --admin-user "Admin" --admin-pass "!QAZ1qaz" --database "mysql" --database-name "nextcloud" --database-user "nextcloud" --database-pass "userp@ssword" --database-host "sunet-mdb2"
echo Enabling apps
echo Assuming that you have mounted the user_saml app from the host
php console.php app:enable user_saml
php console.php app:enable mfachecker
php console.php app:enable files_accesscontrol
php console.php app:enable mfazones
php console.php app:enable twofactor_totp
echo Editing config
sed -i "8 i\    2 => 'sunet-nc2'," config/config.php
sed -i "8 i\    1 => 'mesh.pondersource.org'," config/config.php
sed -i "3 i\  'allow_local_remote_servers' => true," config/config.php
