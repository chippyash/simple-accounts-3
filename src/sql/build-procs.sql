# Build script for simple accounts database
# Copyright, 2018, Ashley Kitson, UK
# License: GPL V3+, see License.md
DELIMITER //

DROP FUNCTION IF EXISTS sa_fu_add_chart;
CREATE DEFINER = CURRENT_USER FUNCTION
  sa_fu_add_chart(
    name VARCHAR(20)
  )
  RETURNS INT(10) UNSIGNED
  MODIFIES SQL DATA
  BEGIN
    INSERT INTO sa_coa(`name`) VALUES (name);
    RETURN last_insert_id();
  END;
//
DROP PROCEDURE IF EXISTS sa_sp_add_ledger;
CREATE DEFINER = CURRENT_USER PROCEDURE
  sa_sp_add_ledger(
    chartId INT(10) UNSIGNED,
    nominal VARCHAR(10),
    type VARCHAR(10),
    name VARCHAR(30),
    prntNominal VARCHAR(10)
  )
  MODIFIES SQL DATA
  BEGIN
    DECLARE prntId INT(10) UNSIGNED;
    SET prntId = 0;
    IF (prntNominal != '') THEN
        SELECT id from sa_coa_ledger l
          WHERE l.nominal = prntNominal
          AND l.chartId = chartId
          INTO prntId;
    END IF;

    INSERT INTO sa_coa_ledger (`prntId`, `chartId`, `nominal`, `type`, `name`)
      VALUES (prntId, chartId, nominal, type, name);
  END;
//
DROP PROCEDURE IF EXISTS sa_sp_del_ledger;
CREATE DEFINER = CURRENT_USER PROCEDURE
  sa_sp_del_ledger(
  chartId INT(10) UNSIGNED,
  nominal VARCHAR(10)
)
MODIFIES SQL DATA
  BEGIN
    DECLARE accId INT(10) UNSIGNED;
    SELECT id FROM sa_coa_ledger l
      WHERE l.nominal = nominal
      AND l.chartId = chartId
      INTO accId;

    DELETE FROM sa_coa_ledger
      WHERE prntId = accId;

    DELETE FROM sa_coa_ledger
      WHERE id = accId;
  END;
//
DROP FUNCTION IF EXISTS sa_fu_add_txn;
CREATE DEFINER = CURRENT_USER FUNCTION
  sa_fu_add_txn(
  chartId INT(10) UNSIGNED,
  note TEXT,
  date DATETIME,
  ref INT(10) UNSIGNED,
  arNominals TEXT,
  arAmounts TEXT,
  arTxnType TEXT
)
  RETURNS INT(10) UNSIGNED
MODIFIES SQL DATA
  BEGIN
    DECLARE jrnId INT(10) UNSIGNED;
    DECLARE numInArray INT;

    SET date = IFNULL(date, CURRENT_TIMESTAMP);
    SET ref = IFNULL(ref, 0);

    INSERT INTO sa_journal (`chartId`, `note`, `date`, `ref`)
      VALUES (chartId, note, date, ref);
    SELECT last_insert_id() INTO jrnId;

    SET numInArray = char_length(arNominals) - char_length(replace(arNominals, ',', '')) + 1;

    SET @x = numInArray;
    REPEAT
      SET @txnType = substring_index(substring_index(arTxnType, ',', @x),',',-1);
      SET @nominal = substring_index(substring_index(arNominals, ',', @x),',',-1);
      SET @drAmount = 0;
      SET @crAmount = 0;
      IF @txnType = 'dr' THEN
        SET @drAmount = substring_index(substring_index(arAmounts, ',', @x),',',-1);
      ELSE
        SET @crAmount = substring_index(substring_index(arAmounts, ',', @x),',',-1);
      END IF;

      INSERT INTO sa_journal_entry(`jrnId`, `nominal`, `acDr`, `acCr`)
        VALUE (jrnId, @nominal, @drAmount, @crAmount);
      SET @x = @x - 1;
    UNTIL @x = 0 END REPEAT;

    RETURN jrnId;
  END;
//
DELIMITER ;