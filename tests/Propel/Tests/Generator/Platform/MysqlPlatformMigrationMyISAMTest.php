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
use Propel\Generator\Platform\MysqlPlatform;

/**
 *
 */
class MysqlPlatformMigrationMyISAMTest extends PlatformMigrationTestProvider
{
    protected $platform;

    /**
     * Get the Platform object for this class
     *
     * @return MysqlPlatform
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
        tableType: MyISAM

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
    public function testRenameEntityDDL($databaseDiff)
    {
        $expected = "
# This is a fix for InnoDB in MySQL >= 4.1.x
# It \"suspends judgement\" for fkey relationships until tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `foo1`;

RENAME TABLE `foo3` TO `foo4`;

ALTER TABLE `foo2`

  CHANGE `bar` `bar1` INTEGER,

  CHANGE `baz` `baz` VARCHAR(12),

  ADD
(
    `baz3` TEXT
);

CREATE TABLE `foo5`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `lkdjfsh` INTEGER,
    `dfgdsgf` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
    }

    /**
     * @dataProvider providerForTestGetRenameEntityDDL
     */
    public function testGetRenameEntityDDL($fromName, $toName)
    {
        $expected = "
RENAME TABLE `foo1` TO `foo2`;
";
        $this->assertEquals($expected, $this->getPlatform()->getRenameEntityDDL($fromName, $toName));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityDDL
     */
    public function testGetModifyEntityDDL($entityDiff)
    {
        $expected = "
DROP INDEX `bar_baz_fk` ON `foo`;

DROP INDEX `bar_fk` ON `foo`;

ALTER TABLE `foo`

  CHANGE `bar` `bar1` INTEGER,

  CHANGE `baz` `baz` VARCHAR(12),

  ADD
(
    `baz3` TEXT
);

CREATE INDEX `bar_fk` ON `foo` (`bar1`);

CREATE INDEX `baz_fk` ON `foo` (`baz3`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityFieldsDDL
     */
    public function testGetModifyEntityFieldsDDL($entityDiff)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar` `bar1` INTEGER;

ALTER TABLE `foo` CHANGE `baz` `baz` VARCHAR(12);

ALTER TABLE `foo` ADD
(
    `baz3` TEXT
);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityFieldsDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityPrimaryKeysDDL
     */
    public function testGetModifyEntityPrimaryKeysDDL($entityDiff)
    {
        $expected = "
ALTER TABLE `foo` DROP PRIMARY KEY;

ALTER TABLE `foo` ADD PRIMARY KEY (`id`,`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityPrimaryKeyDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityIndicesDDL
     */
    public function testGetModifyEntityIndicesDDL($entityDiff)
    {
        $expected = "
DROP INDEX `bar_fk` ON `foo`;

CREATE INDEX `baz_fk` ON `foo` (`baz`);

DROP INDEX `bar_baz_fk` ON `foo`;

CREATE INDEX `bar_baz_fk` ON `foo` (`id`, `bar`, `baz`);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityIndicesDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsDDL
     */
    public function testGetModifyEntityRelationsDDL($entityDiff)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSqlDDL
     */
    public function testGetModifyEntityRelationsSkipSqlDDL($entityDiff)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff));
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSql2DDL
     */
    public function testGetModifyEntityRelationsSkipSql2DDL($entityDiff)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff));
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetRemoveFieldDDL
     */
    public function testGetRemoveFieldDDL($field)
    {
        $expected = "
ALTER TABLE `foo` DROP `bar`;
";
        $this->assertEquals($expected, $this->getPlatform()->getRemoveFieldDDL($field));
    }

    /**
     * @dataProvider providerForTestGetRenameFieldDDL
     */
    public function testGetRenameFieldDDL($fromField, $toField)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar1` `bar2` DOUBLE(2);
";
        $this->assertEquals($expected, $this->getPlatform()->getRenameFieldDDL($fromField, $toField));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldDDL
     */
    public function testGetModifyFieldDDL($fieldDiff)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar` `bar` DOUBLE(3);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($fieldDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldsDDL
     */
    public function testGetModifyFieldsDDL($fieldDiffs)
    {
        $expected = "
ALTER TABLE `foo` CHANGE `bar1` `bar1` DOUBLE(3);

ALTER TABLE `foo` CHANGE `bar2` `bar2` INTEGER NOT NULL;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldsDDL($fieldDiffs));
    }

    /**
     * @dataProvider providerForTestGetAddFieldDDL
     */
    public function testGetAddFieldDDL($field)
    {
        $expected = "
ALTER TABLE `foo` ADD `bar` INTEGER;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldDDL($field));
    }

    /**
     * @dataProvider providerForTestGetAddFieldsDDL
     */
    public function testGetAddFieldsDDL($fields)
    {
        $expected = "
ALTER TABLE `foo` ADD
(
    `bar1` INTEGER,
    `bar2` DOUBLE(3,2) DEFAULT -1 NOT NULL
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldsDDL($fields));
    }
}
