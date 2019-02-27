# Build script for simple accounts database - MariaDb/OQGraph Variant
# Copyright, 2018, Ashley Kitson, UK
# License: BSD-3-Clause, see License.md

DELIMITER //
DROP TRIGGER IF EXISTS sp_tr_jrn_entry_updt;
//
CREATE DEFINER = CURRENT_USER TRIGGER sp_tr_jrn_entry_updt
  AFTER INSERT ON sa_journal_entry FOR EACH ROW
  BEGIN

    SELECT l.id FROM sa_coa_ledger l
      LEFT JOIN sa_journal j ON j.chartId = l.chartId
    WHERE l.nominal = NEW.nominal
    AND j.id = NEW.jrnId
    INTO @acId;

    UPDATE sa_coa_ledger l
      SET l.acDr = l.acDr + NEW.acDr,
      l.acCr = l.acCr + NEW.acCr
    WHERE id IN (
      SELECT linkid
      FROM sa_coa_graph
      WHERE latch = '1'
            AND linkid > 0
            AND destid = @acId
    );
  END;
//
DELIMITER ;
