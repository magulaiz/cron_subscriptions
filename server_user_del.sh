#!/bin/bash
userdel -r $1 >/dev/null 2>&1
mysql_user_exists=$(mysql -uroot -p1do9re6na5 -sse "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$1')")
if [ $mysql_user_exists = 1 ]; then
    mysql -uroot -p1do9re6na5 -Bse "
    DROP USER '$1'@'localhost';
    FLUSH PRIVILEGES;"
fi
mysql -uroot -p1do9re6na5 -Bse "
DROP DATABASE IF EXISTS $1;"
