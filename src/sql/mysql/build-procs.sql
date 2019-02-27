# Build script for simple accounts database - MySql Variant
# Copyright, 2018, Ashley Kitson, UK
# License: BSD-3-Clause, see License.md
DELIMITER //

DROP FUNCTION IF EXISTS sa_fu_add_chart;
CREATE DEFINER = CURRENT_USER FUNCTION
  sa_fu_add_chart(
  name VARCHAR(20)
)
  RETURNS INT(10) UNSIGNED
MODIFIES SQL DATA DETERMINISTIC
  BEGIN
    INSERT INTO sa_coa (`name`) VALUES (name);
    RETURN last_insert_id();
  END;
//

DROP PROCEDURE IF EXISTS sa_sp_add_ledger;
CREATE DEFINER = CURRENT_USER PROCEDURE
  sa_sp_add_ledger(
  chartInternalId INT(10) UNSIGNED,
  nominal         VARCHAR(10),
  type            VARCHAR(10),
  name            VARCHAR(30),
  prntNominal     VARCHAR(10)
)
MODIFIES SQL DATA DETERMINISTIC
  BEGIN
    DECLARE vPrntId INT(10) UNSIGNED;
    DECLARE cntPrnts INT;
    DECLARE rightChildId INT;
    DECLARE myLeft INT;
    DECLARE myRight INT;

    # check to see if we already have a root account
    IF (prntNominal = '')
    THEN
      SELECT count(id)
      FROM sa_coa_ledger l
      WHERE l.prntId = 0
            AND l.chartId = chartInternalId
      INTO cntPrnts;

      IF (cntPrnts > 0)
      THEN
        SIGNAL SQLSTATE '45000'
        SET MYSQL_ERRNO = 1859, MESSAGE_TEXT = _utf8'Chart already has root account';
      END IF;
    END IF;

    SET vPrntId := 0;
    # Find the parent ledger id if the nominal id is not empty
    # as id cannot be zero, return zero if not found
    IF (prntNominal != '')
    THEN
      SELECT IFNULL((SELECT id
                     from sa_coa_ledger l
                     WHERE l.nominal = prntNominal
                           AND l.chartId = chartInternalId), 0)
      INTO vPrntId;

      IF (vPrntId = 0)
      THEN
        SIGNAL SQLSTATE '45000'
        SET MYSQL_ERRNO = 1107, MESSAGE_TEXT = _utf8'Invalid parent account nominal';
      END IF;
    END IF;

    IF (vPrntId = 0)
    THEN
      # We are inserting the root node - easy case
      INSERT INTO sa_coa_ledger (`prntId`, `lft`, `rgt`, `chartId`, `nominal`, `type`, `name`)
      VALUES (0, 1, 2, chartInternalId, nominal, type, name);
    ELSE
      # Does the parent have any children?
      SELECT IFNULL((SELECT max(id)
                     FROM sa_coa_ledger
                     WHERE prntId = vPrntId), 0)
      INTO rightChildId;

      IF (rightChildId = 0)
      THEN
        # no children
        SELECT lft
        FROM sa_coa_ledger
        WHERE id = vPrntId
        INTO myLeft;

        UPDATE sa_coa_ledger
        SET rgt = rgt + 2
        WHERE rgt > myLeft;

        UPDATE sa_coa_ledger
        SET lft = lft + 2
        WHERE lft > myLeft;

        INSERT INTO sa_coa_ledger (`prntId`, `lft`, `rgt`, `chartId`, `nominal`, `type`, `name`)
        VALUES
          (vPrntId, myLeft + 1, myLeft + 2, chartInternalId, nominal, type, name);
      ELSE
        # has children, add to right of last child
        SELECT rgt
        FROM sa_coa_ledger
        WHERE id = rightChildId
        INTO myRight;

        UPDATE sa_coa_ledger
        SET rgt = rgt + 2
        WHERE rgt > myRight;

        UPDATE sa_coa_ledger
        SET lft = lft + 2
        WHERE lft > myRight;

        INSERT INTO sa_coa_ledger (`prntId`, `lft`, `rgt`, `chartId`, `nominal`, `type`, `name`)
        VALUES
          (vPrntId, myRight + 1, myRight + 2, chartInternalId, nominal, type, name);
      END IF;
    END IF;
  END;
//

DROP PROCEDURE IF EXISTS sa_sp_del_ledger;
CREATE DEFINER = CURRENT_USER PROCEDURE
  sa_sp_del_ledger(
  chartId INT(10) UNSIGNED,
  nominal VARCHAR(10)
)
MODIFIES SQL DATA DETERMINISTIC
  BEGIN
    DECLARE accId INT(10) UNSIGNED;
    DECLARE accDr INT(10) UNSIGNED;
    DECLARE accCr INT(10) UNSIGNED;
    SELECT
      id,
      acDr,
      acCr
    FROM sa_coa_ledger l
    WHERE l.nominal = nominal
          AND l.chartId = chartId
    INTO accId, accDr, accCr;

    IF (accDr > 0 OR accCr > 0)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MYSQL_ERRNO = 2000, MESSAGE_TEXT = _utf8'Account balance is non zero';
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
  chartId    INT(10) UNSIGNED,
  note       TEXT,
  date       DATETIME,
  src        VARCHAR(6),
  ref        INT(10) UNSIGNED,
  arNominals TEXT,
  arAmounts  TEXT,
  arTxnType  TEXT
)
  RETURNS INT(10) UNSIGNED
MODIFIES SQL DATA DETERMINISTIC
  BEGIN
    DECLARE jrnId INT(10) UNSIGNED;
    DECLARE numInArray INT;

    SET date = IFNULL(date, CURRENT_TIMESTAMP);

    INSERT INTO sa_journal (`chartId`, `note`, `date`, `src`, `ref`)
    VALUES (chartId, note, date, src, ref);

    SELECT last_insert_id()
    INTO jrnId;

    SET numInArray =
    char_length(arNominals) - char_length(replace(arNominals, ',', '')) + 1;

    SET @x = numInArray;
    REPEAT
      SET @txnType = substring_index(substring_index(arTxnType, ',', @x), ',', -1);
      SET @nominal = substring_index(substring_index(arNominals, ',', @x), ',', -1);
      SET @drAmount = 0;
      SET @crAmount = 0;
      IF @txnType = 'dr'
      THEN
        SET @drAmount = substring_index(substring_index(arAmounts, ',', @x), ',',
                                        -1);
      ELSE
        SET @crAmount = substring_index(substring_index(arAmounts, ',', @x), ',',
                                        -1);
      END IF;

      INSERT INTO sa_journal_entry (`jrnId`, `nominal`, `acDr`, `acCr`)
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
  SELECT
    prntId as origid,
    id as destid,
    nominal,
    name,
    type,
    acDr,
    acCr
  FROM sa_coa_ledger
  WHERE `chartId` = chartId
  ORDER BY origid, destid;
END;
//
DELIMITER ;

