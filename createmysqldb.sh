#!/usr/bin/env bash
# Create MySql database
#
# usage createmysqldb.sh dbname dbuid dbpwd
#
# dbname must exist
# dbuid must have privileges to run DDL on database;

DBNAME=$1
DBUID=$2
DBPWD=$3
DBHOST=$4

echo "building tables"
cat ./src/sql/mysql/build-tables.sql | mysql -h $DBHOST -u $DBUID -p$DBPWD $DBNAME
echo "building procs and functions"
cat ./src/sql/mysql/build-procs.sql | mysql -h $DBHOST -u $DBUID -p$DBPWD $DBNAME
echo "building triggers"
cat ./src/sql/mysql/build-triggers.sql | mysql -h $DBHOST -u $DBUID -p$DBPWD $DBNAME
