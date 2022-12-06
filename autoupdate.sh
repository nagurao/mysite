#!/bin/bash
cd /var/www/mysite
git add .
git commit -m "Auto commit on `date +'%d-%m-%Y %H:%M:%S'`"
git push -u origin master