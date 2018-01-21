#!/usr/bin/env bash
# Create database
# usage createdb.sh dbname dbuid dbpwd
#
# dbname must exist
# dbuid must have privileges to run DDL on database;

DBNAME=$1
DBUID=$2
DBPWD=$3

echo "building tables"
cat ./src/sql/build-tables.sql | mysql -u $DBUID -p$DBPWD $DBNAME
echo "building procs and functions"
cat ./src/sql/build-procs.sql | mysql -u $DBUID -p$DBPWD $DBNAME
echo "building triggers"
cat ./src/sql/build-triggers.sql | mysql -u $DBUID -p$DBPWD $DBNAME
