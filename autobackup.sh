#!/bin/bash
rm /var/www/mysite/*.gz
mysqldump -unagu -pnagu -P3306 -hlocalhost readings | gzip -c > /var/www/mysite/db_backup.gz
cd /var/www/mysite/
cp *.gz /home/lamp/mysite/db_backup/`date +'%d-%m-%Y_%H:%M:%S'`.gz
