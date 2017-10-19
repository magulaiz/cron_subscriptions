#!/bin/bash
pass=$(perl -e 'print crypt($ARGV[0], "password")' $3)
[ $(getent group "subscriptors") ] || groupadd "subscriptors"
#useradd -m -G "subscriptors" -d $2 -p $pass -s /bin/bash $1

if id -u "$1" >/dev/null 2>&1; then
    if [ -d $2 ];
    then
        rm -r $2/*
    fi
    usermod -d $2 $1 -g "subscriptors"
else
    useradd -m -g "subscriptors" -d $2 -p $pass -s /bin/bash $1
fi
mkdir -p $2
chown -R $1:$1 $2
mkdir $2/files
chown www-data:www-data $2/files
chmod 775 $2/files
mkdir $2/private
chown www-data:www-data $2/private
chmod 775 $2/private
mkdir $2/modules
chown $1:$1 $2/modules
chmod 775 $2/modules
mkdir $2/libraries
chown $1:$1 $2/libraries
chmod 775 $2/libraries