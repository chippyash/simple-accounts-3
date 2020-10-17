#!/usr/bin/env bash
# Run SQL tests
# dbName: test  DB must exist
# dbUid: test	test user requires all privileges on database
# dbPwd: test

DBNAME=$1
DBUID=$2
DBPWD=$3
DBHOST=$4

OUTPUT="$(cat ./test/sql/*test.sql | mysql -h $DBHOST -u $DBUID -p$DBPWD $DBNAME -N)"
PASSED=1
if [[ $OUTPUT == *"Failed"* ]]; then
	PASSED=0;
fi
echo "Simple Accounts SQL Tests"
echo ""
echo "${OUTPUT}"
echo ""

if [[ $PASSED == 1 ]]; then
	echo "Tests passed";
	EXIT=0
else
	FAILED=$(echo $OUTPUT | grep 'Failed' | wc -l);
	echo "$FAILED Test(s) failed";
	EXIT=-1
fi

NUMTEST=$(echo "$OUTPUT" | wc -l)
echo "Number of tests: ${NUMTEST}"
exit $EXIT;
