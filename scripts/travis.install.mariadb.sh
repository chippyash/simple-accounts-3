#!/usr/bin/env bash
#sudo service mysql stop
#sudo apt-get install -y -o Dpkg::Options::='--force-confnew' mariadb-server mariadb-server-10.0 mariadb-oqgraph-engine-10.0
sudo apt-get install -y mariadb-oqgraph-engine-10.0
#sudo service mysql start
sudo mysql < ./scripts/oqgraph.sql
sudo mysql < ./scripts/test-user.sql
./createmariadb.sh test test test