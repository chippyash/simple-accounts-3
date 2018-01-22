# Build script for simple accounts database
# Copyright, 2018, Ashley Kitson, UK
# License: GPL V3+, see License.md

DROP TABLE IF EXISTS sa_coa_ledger;
DROP TABLE IF EXISTS sa_coa_graph;
DROP TABLE IF EXISTS sa_journal_entry;
DROP TABLE IF EXISTS sa_journal;
DROP TABLE IF EXISTS sa_coa;
DROP TABLE IF EXISTS sa_ac_type;

CREATE TABLE `sa_ac_type` (
  `type` varchar(10) NOT NULL COMMENT 'External value of account type',
  `value` smallint(6) NOT NULL COMMENT 'Internal value of account type',
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Account type enumeration';

# Whilst the account type name is really important
# the value is chosen deliberately to provide a BITWISE
# capability
# The main code uses a subset of this functionality
# It is left to you to use it elsewhere
# The values for each account type are chosen for their bit values
# Don't fuck with them
# 00000001011
INSERT INTO sa_ac_type (type, value) VALUES ('ASSET', 11);
# 00000011011
INSERT INTO sa_ac_type (type, value) VALUES ('BANK', 27);
# 00000000101
INSERT INTO sa_ac_type (type, value) VALUES ('CR', 5);
# 00000101100
INSERT INTO sa_ac_type (type, value) VALUES ('CUSTOMER', 44);
# 00000000011
INSERT INTO sa_ac_type (type, value) VALUES ('DR', 3);
# 00000000000
INSERT INTO sa_ac_type (type, value) VALUES ('DUMMY', 0);
# 01010000101
INSERT INTO sa_ac_type (type, value) VALUES ('EQUITY', 645);
# 00001001101
INSERT INTO sa_ac_type (type, value) VALUES ('EXPENSE', 77);
# 00110000101
INSERT INTO sa_ac_type (type, value) VALUES ('INCOME', 389);
# 00010000101
INSERT INTO sa_ac_type (type, value) VALUES ('LIABILITY', 133);
# 00000000001
INSERT INTO sa_ac_type (type, value) VALUES ('REAL', 1);
# 10010000101
INSERT INTO sa_ac_type (type, value) VALUES ('SUPPLIER', 1157);

CREATE TABLE `sa_coa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id of chart',
  `name` varchar(20) NOT NULL COMMENT 'name of chart',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sa_coa_name_uindex` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='A Chart of Account';

CREATE TABLE `sa_coa_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'internal ledger id',
  `prntId` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'internal id of parent account',
  `chartId` int(10) unsigned DEFAULT NULL COMMENT 'id of chart that this account belongs to',
  `nominal` char(10) NOT NULL COMMENT 'nominal id for this account',
  `type` varchar(10) DEFAULT NULL COMMENT 'type of account',
  `name` varchar(30) NOT NULL COMMENT 'name of account',
  `acDr` bigint(20) NOT NULL DEFAULT '0' COMMENT 'debit amount',
  `acCr` bigint(20) NOT NULL DEFAULT '0'COMMENT 'credit amount',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sa_coa_ledger_chartId_nominal_index` (`chartId`,`nominal`),
  KEY `sa_coa_ledger_sa_ac_type_type_fk` (`type`),
  KEY `sa_coa_ledger_sa_coa_fk` (`chartId`),
  CONSTRAINT `sa_coa_ledger_sa_ac_type_type_fk` FOREIGN KEY (`type`) REFERENCES `sa_ac_type` (`type`)  ON DELETE CASCADE,
  CONSTRAINT `sa_coa_ledger_sa_coa_fk` FOREIGN KEY (`chartId`) REFERENCES `sa_coa` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Chart of Account structure';

CREATE TABLE `sa_coa_graph` (
  `latch` varchar(32) DEFAULT NULL,
  `origid` bigint(20) unsigned DEFAULT NULL,
  `destid` bigint(20) unsigned DEFAULT NULL,
  `weight` double DEFAULT NULL,
  `seq` bigint(20) unsigned DEFAULT NULL,
  `linkid` bigint(20) unsigned DEFAULT NULL,
  KEY `latch` (`latch`,`origid`,`destid`) USING HASH,
  KEY `latch_2` (`latch`,`destid`,`origid`) USING HASH
) ENGINE=OQGRAPH
  DEFAULT CHARSET=utf8 `data_table`='sa_coa_ledger' `origid`='prntId' `destid`='id'
  COMMENT 'oqgraph linking table';

CREATE TABLE `sa_journal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'internal id of the journal',
  `chartId` int(10) unsigned NOT NULL COMMENT 'the chart to which this journal belongs',
  `note` text COMMENT 'a note for the journal entry',
  `date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'timestamp for this journal',
  `src` VARCHAR(6) COMMENT 'user defined source of journal',
  `ref` INT(10) UNSIGNED COMMENT 'user defined reference to this journal',
  PRIMARY KEY (`id`),
  KEY `sa_journal_sa_coa_id_fk` (`chartId`),
  KEY `sa_journal_external_reference` (`src`, `ref`),
  CONSTRAINT `sa_journal_sa_coa_id_fk` FOREIGN KEY (`chartId`) REFERENCES `sa_coa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Txn Journal Header';

CREATE TABLE `sa_journal_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'internal id for entry',
  `jrnId` int(10) unsigned DEFAULT NULL COMMENT 'id if journal that this entry belongs to',
  `nominal` varchar(10) NOT NULL COMMENT 'nominal code for entry',
  `acDr` bigint(20) DEFAULT '0' COMMENT 'debit amount for entry',
  `acCr` bigint(20) DEFAULT '0' COMMENT 'credit amount for entry',
  PRIMARY KEY (`id`),
  KEY `sa_journal_entry_sa_org_id_fk` (`jrnId`),
  CONSTRAINT `sa_journal_entry_sa_jrn_id_fk` FOREIGN KEY (`jrnId`) REFERENCES `sa_journal` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Txn Journal Entry';
