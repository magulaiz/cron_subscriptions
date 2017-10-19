#!/bin/bash
#user_exists=$(mysql -uroot -p1do9re6na5 -sse "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$1')")
#if ! [ $user_exists = 1 ]; then
    mysql -uroot -p1do9re6na5 -Bse "
    DROP DATABASE IF EXISTS $1;
    CREATE DATABASE $1;
    CREATE USER '$1'@'localhost' IDENTIFIED BY '$2';
    GRANT ALL PRIVILEGES ON $1.* TO '$1'@'localhost';
    FLUSH PRIVILEGES;"
#fi

