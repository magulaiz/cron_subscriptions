#!/bin/bash
user_exists=$(mysql -uroot -p1do9re6na5 -sse "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$1')")
if [ $user_exists = 1 ]; then
    mysql -uroot -p1do9re6na5 -Bse "
    DROP DATABASE IF EXISTS $1;
    DROP USER '$1'@'localhost';"
fi

