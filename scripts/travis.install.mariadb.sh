#!/usr/bin/env bash
sudo apt-get install -y mariadb-plugin-oqgraph
sudo mysql < ./scripts/oqgraph.sql
sudo mysql < ./scripts/test-user.sql
./createmariadb.sh test test test