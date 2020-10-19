<?php

declare(strict_types=1);

/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license   BSD-3-Clause See LICENSE.md
 */
namespace SAccounts\Doctrine;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Base table and procedure generation
 *
 * NB. This uses the sql scripts is src/sql as input to the up and down methods.
 * That way, this stays in sync with the canonical source for the database
 */
class Version20180428092520 extends AbstractMigration
{
    /**
     * @var string
     */
    private $sqlSrcDir;

    public function __construct(Version $version)
    {
        parent::__construct($version);
        $this->connection->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');
        $this->sqlSrcDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/sql/mariadb';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->createTables();
        $this->createProcs();
        $this->createTriggers();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->dropProcs();
        $this->dropTables();
    }

    protected function createTables(): void
    {
        $sql = file_get_contents($this->sqlSrcDir . '/build-tables.sql');

        $matches = [];
        preg_match_all('/DROP TABLE .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }

        $matches = [];
        preg_match_all('/CREATE TABLE .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }

        $matches = [];
        preg_match_all('/INSERT.*\);/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }
    }

    protected function createProcs(): void
    {
        $sql = file_get_contents($this->sqlSrcDir . '/build-procs.sql');

        $matches = [];
        preg_match_all('/DROP FUNCTION .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }

        $matches = [];
        preg_match_all('/DROP PROCEDURE .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }

        $matches = [];
        preg_match_all('!CREATE DEFINER .*//!isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }
    }

    protected function createTriggers(): void
    {
        $sql = file_get_contents($this->sqlSrcDir . '/build-triggers.sql');

        $matches = [];
        preg_match_all('/DROP TRIGGER .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }

        $matches = [];
        preg_match_all('!CREATE DEFINER .*//!isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }
    }

    protected function dropProcs(): void
    {
        $sql = file_get_contents($this->sqlSrcDir . '/drop-procs.sql');

        $matches = [];
        preg_match_all('/DROP FUNCTION .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }

        $matches = [];
        preg_match_all('/DROP PROCEDURE .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }
    }

    protected function dropTables(): void
    {
        $sql = file_get_contents($this->sqlSrcDir . '/drop-tables.sql');

        $matches = [];
        preg_match_all('/DROP TABLE .*;/isU', $sql, $matches);

        foreach ($matches as $statement) {
            $this->addSql($statement);
        }
    }
}
