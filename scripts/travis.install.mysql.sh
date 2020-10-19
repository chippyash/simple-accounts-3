#!/usr/bin/env bash
sudo service mysql start
sudo mysql < ./scripts/test-user.sql
./createmysqldb.sh test test test localhost