#!/usr/bin/env bash
# Create MariaDb database
# Requires OQGraph support
#
# usage createmariadb.sh dbname dbuid dbpwd
#
# dbname must exist
# dbuid must have privileges to run DDL on database;

DBNAME=$1
DBUID=$2
DBPWD=$3

echo "building tables"
cat ./src/sql/mariadb/build-tables.sql | mysql -u $DBUID -p$DBPWD $DBNAME
echo "building procs and functions"
cat ./src/sql/mariadb/build-procs.sql | mysql -u $DBUID -p$DBPWD $DBNAME
echo "building triggers"
cat ./src/sql/mariadb/build-triggers.sql | mysql -u $DBUID -p$DBPWD $DBNAME
