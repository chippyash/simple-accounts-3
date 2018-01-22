# Test script for simple accounts database
# Copyright, 2018, Ashley Kitson, UK
# License: GPL V3+, see License.md

#before test
DELETE FROM sa_coa;
SET @testName = 'add transaction';

#test
SELECT sa_fu_add_chart('Test')
INTO @chartId;

CALL sa_sp_add_ledger(@chartId, '0000', 'REAL', 'COA', '');
CALL sa_sp_add_ledger(@chartId, '1000', 'ASSET', 'Assets', '0000');
CALL sa_sp_add_ledger(@chartId, '2000', 'LIABILITY', 'Liabilities', '0000');
#test 1
SELECT sa_fu_add_txn(@chartId, 'foo', NULL, 'PUR', 23 , '1000,2000', '12,12', 'dr,cr')
INTO @jrnId;
#test 2
SELECT (note = 'foo')
FROM sa_journal
INTO @testnote;
#test 3
SELECT !isnull(date)
FROM sa_journal
INTO @testdate;
#test 5
SELECT count(*)
FROM sa_journal_entry
WHERE jrnId = @jrnId
INTO @entryCount;
#test 6
SELECT
  acDr,
  acCr
FROM sa_coa_ledger
WHERE nominal = '1000'
      AND chartId = @chartId
INTO @ac1000dr, @ac1000cr;
SELECT
  acDr,
  acCr
FROM sa_coa_ledger
WHERE nominal = '2000'
      AND chartId = @chartId
INTO @ac2000dr, @ac2000cr;
#test 7
SELECT
  acDr,
  acCr
FROM sa_coa_ledger
WHERE nominal = '0000'
      AND chartId = @chartId
INTO @ac0000dr, @ac0000cr;
#test 8
SELECT (src = 'PUR')
FROM sa_journal
WHERE id = @jrnId
INTO @testsrc;
#test 9
SELECT (ref = 23)
FROM sa_journal
WHERE id = @jrnId
INTO @testref;

#output
SELECT
  @testName                               AS test,
  'adding journal creates journal header' AS spec,
  if(@jrnId > 0, 'Passed', 'Failed')      AS result
UNION SELECT
        @testName                         AS test,
        'note is added if supplied'       AS spec,
        if(@testnote, 'Passed', 'Failed') AS result
UNION SELECT
        @testName                         AS test,
        'src is added if supplied'       AS spec,
#   @testsrc as result
        if(@testsrc, 'Passed', 'Failed') AS result
UNION SELECT
        @testName                         AS test,
        'ref is added if supplied'       AS spec,
        if(@testref, 'Passed', 'Failed') AS result
UNION SELECT
        @testName                           AS test,
        'date is defaulted if not supplied' AS spec,
        if(@testdate, 'Passed', 'Failed')   AS result
UNION SELECT
        @testName                               AS test,
        'transaction entries are written'       AS spec,
        if(@entryCount = 2, 'Passed', 'Failed') AS result
UNION SELECT
        @testName                                AS test,
        'transaction entries update the ledgers' AS spec,
        if(@ac1000dr = 12 AND @ac1000cr = 0 AND @ac2000dr = 0 AND @ac2000cr = 12,
           'Passed', 'Failed')                   AS result
UNION SELECT
        @testName                                           AS test,
        'transaction entries update ledger parent accounts' AS spec,
        if(@ac0000dr = @ac0000cr AND @ac0000cr > 0,
           'Passed', 'Failed')                              AS result;
