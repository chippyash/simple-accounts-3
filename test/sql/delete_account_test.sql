# Test script for simple accounts database
# Copyright, 2018, Ashley Kitson, UK
# License: GPL V3+, see License.md

#before test
DELETE FROM sa_coa;
SET @testName = 'delete account';

#test
SELECT sa_fu_add_chart('Test') INTO @chartId;

CALL sa_sp_add_ledger(@chartId,'0000', 'REAL', 'COA', '');
CALL sa_sp_add_ledger(@chartId,'1000', 'ASSET', 'Assets', '0000');
CALL sa_sp_add_ledger(@chartId,'2000', 'LIABILITY', 'Liabilities', '0000');
#test 1
CALL sa_sp_del_ledger(@chartId,'2000');
SELECT count(*) FROM sa_coa_ledger INTO @numLedgers1;
#test 2
CALL sa_sp_del_ledger(@chartId,'0000');
SELECT count(*) FROM sa_coa_ledger INTO @numLedgers2;

#output
SELECT
  @testName as test,
  'deleting leaf account' as spec,
  if(@numLedgers1 = 2, 'Passed', 'Failed') as result
UNION SELECT
  @testName as test,
  'deleting parent account' as spec,
  if(@numLedgers2 = 0, 'Passed', 'Failed') as result
;
