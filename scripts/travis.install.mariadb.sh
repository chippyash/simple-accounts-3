#!/usr/bin/env bash
sudo apt-get install -y mariadb-oqgraph-engine-10.0
sudo mysql < ./scripts/oqgraph.sql
sudo mysql < ./scripts/test-user.sql
./createmariadb.sh test test test