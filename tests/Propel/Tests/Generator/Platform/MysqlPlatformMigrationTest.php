<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Platform\MysqlPlatform;

/**
 *
 */
class MysqlPlatformMigrationTest extends MysqlPlatformMigrationTestProvider
{
    protected $platform;

    /**
     * Get the Platform object for this class
     *
     * @return Platform
     */
    protected function getPlatform()
    {
        if (!$this->platform) {
            $this->platform = new MysqlPlatform();
            $configFileContent = <<<EOF
propel:
  database:
    connections:
      bookstore:
        adapter: mysql
        classname: \Propel\Runtime\Connection\DebugPDO
        dsn: mysql:host=127.0.0.1;dbname=test
        user: root
        password:
    adapters:
      mysql:
        tableType: InnoDB

  generator:
    defaultConnection: bookstore
    connections:
      - bookstore

  runtime:
    defaultConnection: bookstore
    connections:
      - bookstore
EOF;

            $configFile = sys_get_temp_dir().'/propel.yaml';
            file_put_contents($configFile, $configFileContent);
            $config = new GeneratorConfig($configFile);

            $this->platform->setGeneratorConfig($config);
        }

        return $this->platform;
    }

    /**
     * @dataProvider providerForTestGetModifyDatabaseDDL
     */
    public function testRenameTableDDL($databaseDiff)
    {
        $expected = "
# This is a fix for InnoDB in MySQL >= 4.1.x
# It \"suspends judgement\" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `foo1`;

RENAME TABLE `foo3` TO `foo4`;

ALTER TABLE `foo2`

  CHANGE `bar` `bar1` INTEGER,

  CHANGE `baz` `baz` VARCHAR(12),

  ADD `baz3` TEXT AFTER `baz`;

CREATE TABLE `foo5`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `lkdjfsh` INTEGER,
    `dfgdsgf` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
    }

    /**
     * @dataProvider providerForTestGetRenameTableDDL
     */
    public function testGetRenameTableDDL($fromName, $toName)
    {
        $expected = "
RENAME TABLE `foo1` TO `foo2`;
";
        $this->assertEquals($expected, $this->getPlatform()->getRenameTableDDL($fromName, $toName));
    }

    /**
     * @dataProvider providerForTestGetModifyTableDDL
     */
    public function testGetModifyTableDDL($tableDiff)
    {
        $expected = "
ALTER TABLE `foo` DROP FOREIGN KEY `foo1_fk_2`;

ALTER TABLE `foo` DROP FOREIGN KEY `foo1_fk_1`;

DROP INDEX `bar_baz_fk` ON `foo`;

DROP INDEX `foo1_fi_2` ON `foo`;

DROP INDEX `bar_fk` ON `foo`;

ALTER TABLE `foo`

  CHANGE `bar` `bar1` INTEGER,

  CHANGE `baz` `baz` VARCHAR(12),

  ADD `baz3` TEXT AFTER `baz`;

CREATE INDEX `bar_fk` ON `foo` (`bar1`);

CREATE INDEX `baz_fk` ON `foo` (`baz3`);

ALTER TABLE `foo` ADD CONSTRAINT `foo1_fk_1`
    FOREIGN KEY (`bar1`)
    REFERENCES `foo2` (`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableColumnsDDL
     */
    public function testGetModifyTableColumnsDDL($tableDiff)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar` `bar1` INTEGER;

ALTER TABLE `foo` CHANGE `baz` `baz` VARCHAR(12);

ALTER TABLE `foo` ADD `baz3` TEXT AFTER `baz`;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableColumnsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTablePrimaryKeysDDL
     */
    public function testGetModifyTablePrimaryKeysDDL($tableDiff)
    {
        $expected = "
ALTER TABLE `foo` DROP PRIMARY KEY;

ALTER TABLE `foo` ADD PRIMARY KEY (`id`,`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTablePrimaryKeyDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableIndicesDDL
     */
    public function testGetModifyTableIndicesDDL($tableDiff)
    {
        $expected = "
DROP INDEX `bar_fk` ON `foo`;

CREATE INDEX `baz_fk` ON `foo` (`baz`);

DROP INDEX `bar_baz_fk` ON `foo`;

CREATE INDEX `bar_baz_fk` ON `foo` (`id`, `bar`, `baz`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableIndicesDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysDDL
     */
    public function testGetModifyTableForeignKeysDDL($tableDiff)
    {
        $expected = "
ALTER TABLE `foo1` DROP FOREIGN KEY `foo1_fk_1`;

ALTER TABLE `foo1` ADD CONSTRAINT `foo1_fk_3`
    FOREIGN KEY (`baz`)
    REFERENCES `foo2` (`baz`);

ALTER TABLE `foo1` DROP FOREIGN KEY `foo1_fk_2`;

ALTER TABLE `foo1` ADD CONSTRAINT `foo1_fk_2`
    FOREIGN KEY (`bar`,`id`)
    REFERENCES `foo2` (`bar`,`id`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysSkipSqlDDL
     */
    public function testGetModifyTableForeignKeysSkipSqlDDL($tableDiff)
    {
        $expected = "
ALTER TABLE `foo1` DROP FOREIGN KEY `foo1_fk_1`;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff));
        $expected = "
ALTER TABLE `foo1` ADD CONSTRAINT `foo1_fk_1`
    FOREIGN KEY (`bar`)
    REFERENCES `foo2` (`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysSkipSql2DDL
     */
    public function testGetModifyTableForeignKeysSkipSql2DDL($tableDiff)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff));
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetRemoveColumnDDL
     */
    public function testGetRemoveColumnDDL($column)
    {
        $expected = "
ALTER TABLE `foo` DROP `bar`;
";
        $this->assertEquals($expected, $this->getPlatform()->getRemoveColumnDDL($column));
    }

    /**
     * @dataProvider providerForTestGetRenameColumnDDL
     */
    public function testGetRenameColumnDDL($fromColumn, $toColumn)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar1` `bar2` DOUBLE(2);
";
        $this->assertEquals($expected, $this->getPlatform()->getRenameColumnDDL($fromColumn, $toColumn));
    }

    /**
     * @dataProvider providerForTestGetModifyColumnDDL
     */
    public function testGetModifyColumnDDL($columnDiff)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar` `bar` DOUBLE(3);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyColumnDDL($columnDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyColumnsDDL
     */
    public function testGetModifyColumnsDDL($columnDiffs)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar1` `bar1` DOUBLE(3);

ALTER TABLE `foo` CHANGE `bar2` `bar2` INTEGER NOT NULL;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyColumnsDDL($columnDiffs));
    }

    /**
     * @dataProvider providerForTestGetAddColumnDDL
     */
    public function testGetAddColumnDDL($column)
    {
        $expected = "
ALTER TABLE `foo` ADD `bar` INTEGER AFTER `id`;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddColumnDDL($column));
    }

    /**
     * @dataProvider providerForTestGetAddColumnFirstDDL
     */
    public function testGetAddColumnFirstDDL($column)
    {
        $expected = "
ALTER TABLE `foo` ADD `bar` INTEGER FIRST;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddColumnDDL($column));
    }

    /**
     * @dataProvider providerForTestGetAddColumnsDDL
     */
    public function testGetAddColumnsDDL($columns)
    {
        $expected = "
ALTER TABLE `foo` ADD `bar1` INTEGER AFTER `id`;

ALTER TABLE `foo` ADD `bar2` DOUBLE(3,2) DEFAULT -1 NOT NULL AFTER `bar1`;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddColumnsDDL($columns));
    }

    public function testColumnRenaming()
    {
        $schema1 = '
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="bar2" type="INTEGER" />
    </table>
</database>
';
        $schema2 = '
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar_la1" type="INTEGER" />
        <column name="bar_la2" type="INTEGER" />
    </table>
</database>
';

        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);

        $diff = DatabaseComparator::computeDiff($d1, $d2);

        $tables = $diff->getModifiedTables();
        $this->assertEquals('foo', key($tables));
        $fooChanges = array_shift($tables);
        $this->assertInstanceOf('\Propel\Generator\Model\Diff\TableDiff', $fooChanges);

        $renamedColumns = $fooChanges->getRenamedColumns();

        $firstPair = array_shift($renamedColumns);
        $secondPair = array_shift($renamedColumns);

        $this->assertEquals('bar1', $firstPair[0]->getName());
        $this->assertEquals('bar_la1', $firstPair[1]->getName());

        $this->assertEquals('bar2', $secondPair[0]->getName());
        $this->assertEquals('bar_la2', $secondPair[1]->getName());
    }

    public function testTableRenaming()
    {
        $schema1 = '
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="bar2" type="INTEGER" />
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="bar2" type="INTEGER" />
    </table>
</database>
';
        $schema2 = '
<database name="test">
    <table name="foo_bla">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="bar2" type="INTEGER" />
    </table>
    <table name="foo_bla2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="bar2" type="INTEGER" />
    </table>
</database>
';

        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true);
        $renamedTables = $diff->getRenamedTables();

        $firstPair = array(key($renamedTables), current($renamedTables));
        next($renamedTables);
        $secondPair = array(key($renamedTables), current($renamedTables));

        $this->assertEquals('foo', $firstPair[0]);
        $this->assertEquals('foo_bla', $firstPair[1]);

        $this->assertEquals('foo2', $secondPair[0]);
        $this->assertEquals(
            'foo_bla2',
            $secondPair[1],
            'Table `Foo2` should not renamed to `foo_bla` since we have already renamed a table to this name.'
        );
    }
}
