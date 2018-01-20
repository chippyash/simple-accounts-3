# Test script for simple accounts database
# Copyright, 2018, Ashley Kitson, UK
# License: GPL V3+, see License.md

#before test
DELETE FROM sa_coa;
SET @testName = 'add account';

#test
SELECT sa_fu_add_chart('Test') INTO @chartId;

CALL sa_sp_add_ledger(@chartId,'0000', 'REAL', 'COA', '');
CALL sa_sp_add_ledger(@chartId,'1000', 'ASSET', 'Assets', '0000');
CALL sa_sp_add_ledger(@chartId,'2000', 'LIABILITY', 'Liabilities', '0000');

#results
SELECT count(*) FROM sa_coa INTO @numCharts;
SELECT count(*) FROM sa_coa_ledger INTO @numLedgers;
SELECT id from sa_coa_ledger where nominal = '0000' into @baseId;
SELECT prntId from sa_coa_ledger where nominal = '1000' into @nom1000prnt;
SELECT prntId from sa_coa_ledger where nominal = '2000' into @nom2000prnt;


#output
SELECT
  @testName as test,
  'number of charts = 1' as spec,
  if(@numCharts = 1, 'Passed', 'Failed') as result
UNION SELECT
  @testName as test,
  'number of ledgers = 3' as spec,
  if(@numLedgers = 3, 'Passed', 'Failed') as result
UNION SELECT
        @testName as test,
        'parent id for ac 1000 is correct' as spec,
        if(@baseId = @nom1000prnt, 'Passed', 'Failed') as result
UNION SELECT
        @testName as test,
        'parent id for ac 2000 is correct' as spec,
        if(@baseId = @nom2000prnt, 'Passed', 'Failed') as result
;
