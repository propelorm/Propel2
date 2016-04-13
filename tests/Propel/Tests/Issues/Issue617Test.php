<?php

namespace Propel\Tests\Issues;

use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\Helpers\PlatformDatabaseBuildTimeBase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel/issues/617.
 * Since the build property `addVendorInfo` is per default not set (= false), the `MysqlSchemaParser` **did**
 * not return the `Engine` of the table. Since we depend on that information in `MysqlPlatform`,
 * we really need that kind of information.
 *
 * @group mysql
 * @group database
 */
class Issue617Test extends PlatformDatabaseBuildTimeBase
{
    /**
     * Contains the builder instance of the updated schema (removed FK)
     * @var QuickBuilder
     */
    private $updatedBuilder;

    protected function setUp()
    {
        parent::setUp();
        $this->removeTables();
    }

    protected function tearDown()
    {
        $this->removeTables();
        parent::tearDown();
    }

    /**
     * Remove issue617 tables.
     */
    public function removeTables()
    {
        $this->con->query('DROP TABLE IF EXISTS `issue617_user`');
        $this->con->query('DROP TABLE IF EXISTS `issue617_group`');
    }

    /**
     * Setups the initial schema.
     */
    private function setupInitSchema()
    {
        /*
         * Create issue617 tables with foreign keys
         */
        $schema = '
<database name="bookstore" identifierQuoting="true">
<table name="issue617_user">
  <vendor type="mysql">
    <parameter name="Engine" value="InnoDB"/>
    <parameter name="Charset" value="utf8"/>
  </vendor>
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="full_name" type="VARCHAR" size="50" required="true" />

  <!-- this column (and FK) will be removed from schema, but not from DB on migrate -->
  <column name="group_id" type="INTEGER" />
  <foreign-key foreignTable="issue617_group" onDelete="setnull">
    <reference local="group_id" foreign="id" />
  </foreign-key>
</table>

<table name="issue617_group">
  <vendor type="mysql">
    <parameter name="Engine" value="InnoDB"/>
    <parameter name="Charset" value="utf8"/>
  </vendor>
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="name" type="VARCHAR" size="50" required="true" />
</table>
</database>
';

        $builder = new QuickBuilder();
        $builder->setIdentifierQuoting(true);
        $builder->setPlatform($this->database->getPlatform());
        $builder->setSchema($schema);

        $diff = DatabaseComparator::computeDiff($this->database, $builder->getDatabase());
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $expected = '
CREATE TABLE `issue617_user`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(50) NOT NULL,
    `group_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `issue617_user_fi_5936b3` (`group_id`),
    CONSTRAINT `issue617_user_fk_5936b3`
        FOREIGN KEY (`group_id`)
        REFERENCES `issue617_group` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET=\'utf8\';

CREATE TABLE `issue617_group`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARACTER SET=\'utf8\';
';

        $this->assertContains($expected, $sql);
        $this->updateSchema($builder->getDatabase());

    }

    /**
     * Drop the foreign key in the `_user` table and check whether it generates
     * the correct `DROP` SQL.
     */
    private function dropForeignKey()
    {
        $this->readDatabase();
        $updatedSchema = '
<database name="reverse-bookstore" identifierQuoting="true">
<table name="issue617_user">
  <vendor type="mysql">
    <parameter name="Engine" value="InnoDB"/>
    <parameter name="Charset" value="utf8"/>
  </vendor>
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="full_name" type="VARCHAR" size="50" required="true" />
</table>

<table name="issue617_group">
  <vendor type="mysql">
    <parameter name="Engine" value="InnoDB"/>
    <parameter name="Charset" value="utf8"/>
  </vendor>
  <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
  <column name="name" type="VARCHAR" size="50" required="true" />
</table>
</database>
';

        $this->updatedBuilder = new QuickBuilder();
        $this->updatedBuilder->setIdentifierQuoting(true);
        $this->updatedBuilder->setPlatform($this->database->getPlatform());
        $this->updatedBuilder->setSchema($updatedSchema);

        $diff = DatabaseComparator::computeDiff($this->database, $this->updatedBuilder->getDatabase());
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $expected = '
ALTER TABLE `issue617_user` DROP FOREIGN KEY `issue617_user_fk_5936b3`;

DROP INDEX `issue617_user_fi_5936b3` ON `issue617_user`;

ALTER TABLE `issue617_user`

  DROP `group_id`;
';

        $this->assertContains($expected, $sql);
        $this->updateSchema($this->updatedBuilder->getDatabase());
    }

    /*
     * Checks if FKs are really deleted.
     */
    private function checkDeletedFk()
    {
        $this->readDatabase();
        $diff = DatabaseComparator::computeDiff($this->database, $this->updatedBuilder->getDatabase());
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $expected = 'issue617_user';

        $this->assertNotContains($expected, $sql);
    }

    /**
     * Checks if a changed schema with removed FK does really delete the FK.
     * Based on a real use-case, reverse classes and `computeDiff`.
     */
    public function testDropForeignKey()
    {
        $this->readDatabase();

        $this->setupInitSchema();
        $this->dropForeignKey();
        $this->checkDeletedFk();

    }
}
