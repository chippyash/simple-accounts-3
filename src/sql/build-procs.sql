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
    chartInternalId INT(10) UNSIGNED,
    nominal VARCHAR(10),
    type VARCHAR(10),
    name VARCHAR(30),
    prntNominal VARCHAR(10)
  )
  MODIFIES SQL DATA
  BEGIN
    DECLARE prntId INT(10) UNSIGNED;
    DECLARE cntPrnts INT;

    # check to see if we already have a root account
    IF (prntNominal = '') THEN
      SELECT count(id) FROM sa_coa_ledger l
        WHERE l.prntId = 0
        AND l.chartId = chartInternalId
      INTO cntPrnts;
      IF (cntPrnts > 0) THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 1859, MESSAGE_TEXT = _utf8'Chart already has root account';
      END IF;
    END IF;

    SET prntId = 0;
    IF (prntNominal != '') THEN
        SELECT id from sa_coa_ledger l
          WHERE l.nominal = prntNominal
          AND l.chartId = chartInternalId
          INTO prntId;
        IF (prntId = 0) THEN
          SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 1107, MESSAGE_TEXT = _utf8'Invalid parent account nominal';
        END IF;
    END IF;

    INSERT INTO sa_coa_ledger (`prntId`, `chartId`, `nominal`, `type`, `name`)
      VALUES (prntId, chartInternalId, nominal, type, name);
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
    DECLARE accDr INT(10) UNSIGNED;
    DECLARE accCr INT(10) UNSIGNED;
    SELECT id, acDr, acCr FROM sa_coa_ledger l
      WHERE l.nominal = nominal
      AND l.chartId = chartId
      INTO accId, accDr, accCr;
    IF (accDr > 0 OR accCr > 0) THEN
      SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 2000, MESSAGE_TEXT = _utf8'Account balance is non zero';
    END IF;

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

DROP PROCEDURE IF EXISTS sa_sp_get_tree;
CREATE DEFINER = CURRENT_USER PROCEDURE
  sa_sp_get_tree(
  chartId INT(10) UNSIGNED
)
READS SQL DATA
  BEGIN
    SELECT origid, destid, l3.nominal, l3.name, l3.type, l3.acDr, l3.acCr from sa_coa_graph
      LEFT JOIN sa_coa_ledger as l1 ON origid = l1.id
      LEFT JOIN sa_coa_ledger as l3 ON destid = l3.id
    WHERE l1.chartId = chartId
    UNION
    SELECT 0 as origid, min(l2.id) as destid, l2.nominal, l2.name, l2.type, l2.acDr, l2.acCr
    FROM sa_coa_ledger as l2
    WHERE l2.chartId = chartId
    ORDER BY origid, destid;
  END;
//
DELIMITER ;

