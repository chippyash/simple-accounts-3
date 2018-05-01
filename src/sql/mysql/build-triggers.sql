# Build script for simple accounts database - Mysql Variant
# Copyright, 2018, Ashley Kitson, UK
# License: GPL V3+, see License.md

DELIMITER //
DROP TRIGGER IF EXISTS sp_tr_jrn_entry_updt;
//
CREATE DEFINER = CURRENT_USER TRIGGER sp_tr_jrn_entry_updt
  AFTER INSERT ON sa_journal_entry FOR EACH ROW
  BEGIN

    # get the internal ledger id
    SELECT l.id FROM sa_coa_ledger l
      LEFT JOIN sa_journal j ON j.chartId = l.chartId
    WHERE l.nominal = NEW.nominal
    AND j.id = NEW.jrnId
    INTO @acId;

    # create a concatenated string of parent ids as
    # creating temporary tables to hold parent ledger ids
    # borks, and you can't select from a table whilst updating

    SELECT GROUP_CONCAT(DISTINCT parent.id SEPARATOR ',')
    FROM sa_coa_ledger AS node,
      sa_coa_ledger AS parent
    WHERE node.lft BETWEEN parent.lft AND parent.rgt
          AND node.id = @acId
    GROUP BY node.id
    INTO @parents;

    SET @numInArray =
    char_length(@parents) - char_length(replace(@parents, ',', '')) + 1;

    # update the parent ledger accounts
    WHILE (@numInArray > 0)
    DO
      SET @prntId = substring_index(substring_index(@parents, ',', @numInArray), ',', -1);
      UPDATE sa_coa_ledger l
      SET l.acDr = l.acDr + NEW.acDr,
        l.acCr = l.acCr + NEW.acCr
      WHERE id = @prntId;
      SET @numInArray = @numInArray - 1;
    END WHILE;
  END;
//
DELIMITER ;
