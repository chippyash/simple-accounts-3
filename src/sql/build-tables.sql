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
  `type` varchar(10) NOT NULL,
  `value` smallint(6) NOT NULL,
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Account type enumeration';

INSERT INTO sa_ac_type (type, value) VALUES ('ASSET', 11);
INSERT INTO sa_ac_type (type, value) VALUES ('BANK', 27);
INSERT INTO sa_ac_type (type, value) VALUES ('CR', 5);
INSERT INTO sa_ac_type (type, value) VALUES ('CUSTOMER', 44);
INSERT INTO sa_ac_type (type, value) VALUES ('DR', 3);
INSERT INTO sa_ac_type (type, value) VALUES ('DUMMY', 0);
INSERT INTO sa_ac_type (type, value) VALUES ('EQUITY', 645);
INSERT INTO sa_ac_type (type, value) VALUES ('EXPENSE', 77);
INSERT INTO sa_ac_type (type, value) VALUES ('INCOME', 389);
INSERT INTO sa_ac_type (type, value) VALUES ('LIABILITY', 133);
INSERT INTO sa_ac_type (type, value) VALUES ('REAL', 1);
INSERT INTO sa_ac_type (type, value) VALUES ('SUPPLIER', 1157);

CREATE TABLE `sa_coa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sa_coa_name_uindex` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='A Chart of Account';

CREATE TABLE `sa_coa_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prntId` int(10) unsigned NOT NULL DEFAULT 0,
  `chartId` int(10) unsigned DEFAULT NULL,
  `nominal` char(10) NOT NULL,
  `type` varchar(10) DEFAULT NULL,
  `name` varchar(30) NOT NULL,
  `acDr` bigint(20) NOT NULL DEFAULT '0',
  `acCr` bigint(20) NOT NULL DEFAULT '0',
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
) ENGINE=OQGRAPH DEFAULT CHARSET=utf8 `data_table`='sa_coa_ledger' `origid`='prntId' `destid`='id';

CREATE TABLE `sa_journal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chartId` int(10) unsigned NOT NULL,
  `note` text,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `ref` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sa_journal_sa_coa_id_fk` (`chartId`),
  CONSTRAINT `sa_journal_sa_coa_id_fk` FOREIGN KEY (`chartId`) REFERENCES `sa_coa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Txn Journal Header';

CREATE TABLE `sa_journal_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `jrnId` int(10) unsigned DEFAULT NULL,
  `nominal` varchar(10) NOT NULL,
  `acDr` bigint(20) DEFAULT '0',
  `acCr` bigint(20) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sa_journal_entry_sa_org_id_fk` (`jrnId`),
  CONSTRAINT `sa_journal_entry_sa_jrn_id_fk` FOREIGN KEY (`jrnId`) REFERENCES `sa_journal` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Txn Journal Entry';
