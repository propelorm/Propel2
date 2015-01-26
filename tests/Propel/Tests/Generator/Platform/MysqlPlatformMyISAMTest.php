<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\VendorInfo;
use Propel\Generator\Platform\MysqlPlatform;

/**
 *
 */
class MysqlPlatformMyISAMTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return MysqlPlatform
     */
    protected function getPlatform()
    {
        static $platform;

        if (!$platform) {
            $platform = new MysqlPlatform();

            $configProp['propel']['database']['adapters']['mysql']['tableType'] = 'MyISAM';
            $configProp['propel']['paths']['composerDir'] = __DIR__ . '/../../../../../';
            $config = new GeneratorConfig(__DIR__ . '/../../../../Fixtures/bookstore', $configProp);

            $platform->setGeneratorConfig($config);
        }

        return $platform;
    }

    public function testGetSequenceNameDefault()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_SEQ';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    public function testGetSequenceNameCustom()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $table->addIdMethodParameter($idMethodParameter);
        $table->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_sequence';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    /**
     * @dataProvider providerForTestGetAddTablesDDLSchema
     */
    public function testGetAddTablesDDLSchema($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- x.book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `x`.`book`;

CREATE TABLE `x`.`book`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `author_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `book_i_639136` (`title`),
    INDEX `book_fi_4444ca` (`author_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- y.author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `y`.`author`;

CREATE TABLE `y`.`author`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- x.book_summary
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `x`.`book_summary`;

CREATE TABLE `x`.`book_summary`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `book_id` INTEGER NOT NULL,
    `summary` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `book_summary_fi_23450f` (`book_id`)
) ENGINE=MyISAM;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesDDL
     */
    public function testGetAddTablesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `book`;

CREATE TABLE `book`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `author_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `book_i_639136` (`title`),
    INDEX `book_fi_ea464c` (`author_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `author`;

CREATE TABLE `author`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesSkipSQLDDL
     */
    public function testGetAddTablesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSimplePK
     */
    public function testGetAddTableDDLSimplePK($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM COMMENT='This is foo table';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLCompositePK
     */
    public function testGetAddTableDDLCompositePK($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `foo` INTEGER NOT NULL,
    `bar` INTEGER NOT NULL,
    `baz` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`foo`,`bar`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLUniqueIndex
     */
    public function testGetAddTableDDLUniqueIndex($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `foo_u_14f552` (`bar`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetAddTableDDLIndex()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <index>
            <index-column name="bar" />
        </index>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_i_14f552` (`bar`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetAddTableDDLForeignKey()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar_id" type="INTEGER" />
        <foreign-key foreignTable="bar">
            <reference local="bar_id" foreign="id" />
        </foreign-key>
    </table>
    <table name="bar">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_fi_426410` (`bar_id`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetAddTableDDLForeignKeySkipSql()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar_id" type="INTEGER" />
        <foreign-key foreignTable="bar" skipSql="true">
            <reference local="bar_id" foreign="id" />
        </foreign-key>
    </table>
    <table name="bar">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_fi_426410` (`bar_id`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetAddTableDDLEngine()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </table>
</database>
EOF;
        $platform = new MysqlPlatform();
        $platform->setTableEngineKeyword('TYPE');
        $platform->setDefaultTableEngine('MEMORY');
        $xtad = new SchemaReader($platform);
        $appData = $xtad->parseString($schema);
        $table = $appData->getDatabase()->getTable('foo');
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) TYPE=MEMORY;
";
        $this->assertEquals($expected, $platform->getAddTableDDL($table));
    }

    public function testGetAddTableDDLVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="mysql">
            <parameter name="Engine" value="InnoDB"/>
            <parameter name="Charset" value="utf8"/>
            <parameter name="AutoIncrement" value="1000"/>
        </vendor>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 CHARACTER SET='utf8';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     */
    public function testGetAddTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'Woopah.foo');
        $expected = "
CREATE TABLE `Woopah`.`foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    public function testGetDropTableDDL()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $expected = "
DROP TABLE IF EXISTS `foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     */
    public function testGetDropTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'Woopah.foo');
        $expected = "
DROP TABLE IF EXISTS `Woopah`.`foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    public function testGetColumnDDL()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->replaceSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expected = '`foo` DOUBLE(3,2) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetColumnDDLCharsetVendor()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Charset', 'greek');
        $column->addVendorInfo($vendor);
        $expected = '`foo` TEXT CHARACTER SET \'greek\'';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetColumnDDLCharsetCollation()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Collate', 'latin1_german2_ci');
        $column->addVendorInfo($vendor);
        $expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));

        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Collation', 'latin1_german2_ci');
        $column->addVendorInfo($vendor);
        $expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetColumnDDLComment()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $column->setDescription('This is column Foo');
        $expected = '`foo` INTEGER COMMENT \'This is column Foo\'';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetColumnDDLCharsetNotNull()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $column->setNotNull(true);
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Charset', 'greek');
        $column->addVendorInfo($vendor);
        $expected = '`foo` TEXT CHARACTER SET \'greek\' NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetColumnDDLCustomSqlType()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->replaceSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $column->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = '`foo` DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column = new Column('bar');
        $column->setPrimaryKey(true);
        $table->addColumn($column);
        $expected = 'PRIMARY KEY (`bar`)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column1 = new Column('bar1');
        $column1->setPrimaryKey(true);
        $table->addColumn($column1);
        $column2 = new Column('bar2');
        $column2->setPrimaryKey(true);
        $table->addColumn($column2);
        $expected = 'PRIMARY KEY (`bar1`,`bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($table)
    {
        $expected = "
ALTER TABLE `foo` DROP PRIMARY KEY;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($table)
    {
        $expected = "
ALTER TABLE `foo` ADD PRIMARY KEY (`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($table)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`, `bar2`);

CREATE INDEX `foo_index` ON `foo` (`bar1`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`, `bar2`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = "
DROP INDEX `babar` ON `foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX `babar` (`bar1`, `bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    public function testGetIndexDDLKeySize()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column1 = new Column('bar1');
        $column1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $column1->setSize(5);
        $table->addColumn($column1);
        $index = new Index('bar_index');
        $index->addColumn($column1);
        $table->addIndex($index);
        $expected = 'INDEX `bar_index` (`bar1`(5))';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    public function testGetIndexDDLFulltext()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column1 = new Column('bar1');
        $column1->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $table->addColumn($column1);
        $index = new Index('bar_index');
        $index->addColumn($column1);
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Index_type', 'FULLTEXT');
        $index->addVendorInfo($vendor);
        $table->addIndex($index);
        $expected = 'FULLTEXT INDEX `bar_index` (`bar1`)';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'UNIQUE INDEX `babar` (`bar1`, `bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetForeignKeysDDL
     */
    public function testGetAddForeignKeysDDL($table)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeysDDL($table));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetAddForeignKeyDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     */
    public function testGetAddForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetDropForeignKeyDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     */
    public function testGetDropForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     */
    public function testGetForeignKeyDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     */
    public function testGetForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getForeignKeyDDL($fk));
    }

    public function testGetCommentBlockDDL()
    {
        $expected = "
-- ---------------------------------------------------------------------
-- foo bar
-- ---------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }

    public function testAddExtraIndicesForeignKeys()
    {
        $schema = '
<database name="test1" identifierQuoting="true">
  <table name="foo">
    <behavior name="AutoAddPk"/>
    <column name="name" type="VARCHAR"/>
    <column name="subid" type="INTEGER"/>
  </table>
  <table name="bar">
    <behavior name="AutoAddPk"/>

    <column name="name" type="VARCHAR"/>
    <column name="subid" type="INTEGER"/>

    <foreign-key foreignTable="foo">
      <reference local="id" foreign="id"/>
      <reference local="subid" foreign="subid"/>
    </foreign-key>
  </table>
</database>
';

        $expectedRelationSql = "
CREATE TABLE `bar`
(
    `name` VARCHAR(255),
    `subid` INTEGER,
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`),
    INDEX `bar_fi_bb8268` (`id`, `subid`)
) ENGINE=MyISAM;
";

        $table = $this->getDatabaseFromSchema($schema)->getTable('bar');
        $relationTableSql = $this->getPlatform()->getAddTableDDL($table);

        $this->assertEquals($expectedRelationSql, $relationTableSql);
    }

}
