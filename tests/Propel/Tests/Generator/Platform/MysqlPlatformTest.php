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
use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\VendorInfo;
use Propel\Generator\Platform\MysqlPlatform;

/**
 *
 */
class MysqlPlatformTest extends PlatformTestProvider
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

            $configProp['propel']['database']['adapters']['mysql']['tableType'] = 'InnoDB';
            $config = new GeneratorConfig(__DIR__ . '/../../../../Fixtures/bookstore', $configProp);

            $platform->setGeneratorConfig($config);
        }

        return $platform;
    }

    public function testGetSequenceNameDefault()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_SEQ';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($entity));
    }

    public function testGetSequenceNameCustom()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(IdMethod::NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $entity->addIdMethodParameter($idMethodParameter);
        $entity->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_sequence';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDLSchema
     */
    public function testGetAddEntitiesDDLSchema($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until tables are set.
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
    CONSTRAINT `book_fk_85f9bd`
        FOREIGN KEY (`author_id`)
        REFERENCES `y`.`author` (`id`)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
    CONSTRAINT `book_summary_fk_6eb6da`
        FOREIGN KEY (`book_id`)
        REFERENCES `x`.`book` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDL
     */
    public function testGetAddEntitiesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until tables are set.
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
    CONSTRAINT `book_fk_c83a02`
        FOREIGN KEY (`author_id`)
        REFERENCES `author` (`id`)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesSkipSQLDDL
     */
    public function testGetAddEntitiesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSimplePK
     */
    public function testGetAddEntityDDLSimplePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='This is foo table';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLCompositePK
     */
    public function testGetAddEntityDDLCompositePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `foo` INTEGER NOT NULL,
    `bar` INTEGER NOT NULL,
    `baz` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`foo`,`bar`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLUniqueIndex
     */
    public function testGetAddEntityDDLUniqueIndex($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `foo_u_14f552` (`bar`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLIndex()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <index>
            <index-field name="bar" />
        </index>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_i_14f552` (`bar`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLRelation()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar_id" type="INTEGER" />
        <foreign-key foreignEntity="bar">
            <reference local="bar_id" foreign="id" />
        </foreign-key>
    </entity>
    <entity name="bar">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`),
    CONSTRAINT `foo_fk_d56443`
        FOREIGN KEY (`bar_id`)
        REFERENCES `bar` (`id`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLRelationSkipSql()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar_id" type="INTEGER" />
        <foreign-key foreignEntity="bar" skipSql="true">
            <reference local="bar_id" foreign="id" />
        </foreign-key>
    </entity>
    <entity name="bar">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLEngine()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
</database>
EOF;
        $platform = new MysqlPlatform();
        $platform->setEntityEngineKeyword('TYPE');
        $platform->setDefaultEntityEngine('MEMORY');
        $xtad = new SchemaReader();
        $appData = $xtad->parseString($schema);
        $database = $appData->getDatabase();
        $database->setPlatform($platform);
        $entity = $database->getEntity('Foo');
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) TYPE=MEMORY;
";
        $this->assertEquals($expected, $platform->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="mysql">
            <parameter name="Engine" value="InnoDB"/>
            <parameter name="Charset" value="utf8"/>
            <parameter name="AutoIncrement" value="1000"/>
        </vendor>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 CHARACTER SET='utf8';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetAddEntityDDLSchema($schema)
    {
        $entity = $this->getEntityFromSchema($schema, 'Foo');
        $expected = "
CREATE TABLE `Woopah`.`foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetDropEntityDDL()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $expected = "
DROP TABLE IF EXISTS `foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetDropEntityDDLSchema($schema)
    {
        $entity = $this->getEntityFromSchema($schema, 'Foo');
        $expected = "
DROP TABLE IF EXISTS `Woopah`.`foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetFieldDDL()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $field->getDomain()->replaceScale(2);
        $field->getDomain()->replaceSize(3);
        $field->setNotNull(true);
        $field->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expected = '`foo` DOUBLE(3,2) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetFieldDDLCharsetVendor()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Charset', 'greek');
        $field->addVendorInfo($vendor);
        $expected = '`foo` TEXT CHARACTER SET \'greek\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetFieldDDLCharsetCollation()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Collate', 'latin1_german2_ci');
        $field->addVendorInfo($vendor);
        $expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));

        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Collation', 'latin1_german2_ci');
        $field->addVendorInfo($vendor);
        $expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetFieldDDLComment()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $field->setDescription('This is field Foo');
        $expected = '`foo` INTEGER COMMENT \'This is field Foo\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetFieldDDLCharsetNotNull()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $field->setNotNull(true);
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Charset', 'greek');
        $field->addVendorInfo($vendor);
        $expected = '`foo` TEXT CHARACTER SET \'greek\' NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetFieldDDLCustomSqlType()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $field->getDomain()->replaceScale(2);
        $field->getDomain()->replaceSize(3);
        $field->setNotNull(true);
        $field->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $field->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = '`foo` DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field = new Field('bar');
        $field->setPrimaryKey(true);
        $entity->addField($field);
        $expected = 'PRIMARY KEY (`bar`)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field1 = new Field('bar1');
        $field1->setPrimaryKey(true);
        $entity->addField($field1);
        $field2 = new Field('bar2');
        $field2->setPrimaryKey(true);
        $entity->addField($field2);
        $expected = 'PRIMARY KEY (`bar1`,`bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($entity)
    {
        $expected = "
ALTER TABLE `foo` DROP PRIMARY KEY;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($entity)
    {
        $expected = "
ALTER TABLE `foo` ADD PRIMARY KEY (`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($entity)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`, `bar2`);

CREATE INDEX `foo_index` ON `foo` (`bar1`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($entity));
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
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field1 = new Field('bar1');
        $field1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $field1->setSize(5);
        $entity->addField($field1);
        $index = new Index('bar_index');
        $index->addField($field1);
        $entity->addIndex($index);
        $expected = 'INDEX `bar_index` (`bar1`(5))';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    public function testGetIndexDDLFulltext()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field1 = new Field('bar1');
        $field1->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $entity->addField($field1);
        $index = new Index('bar_index');
        $index->addField($field1);
        $vendor = new VendorInfo('mysql');
        $vendor->setParameter('Index_type', 'FULLTEXT');
        $index->addVendorInfo($vendor);
        $entity->addIndex($index);
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
     * @dataProvider providerForTestGetRelationsDDL
     */
    public function testGetAddRelationsDDL($entity)
    {
        $expected = "
ALTER TABLE `foo` ADD CONSTRAINT `foo_bar_fk`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`)
    ON DELETE CASCADE;

ALTER TABLE `foo` ADD CONSTRAINT `foo_baz_fk`
    FOREIGN KEY (`baz_id`)
    REFERENCES `baz` (`id`)
    ON DELETE SET NULL;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationsDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetAddRelationDDL($fk)
    {
        $expected = "
ALTER TABLE `foo` ADD CONSTRAINT `foo_bar_fk`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`)
    ON DELETE CASCADE;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetAddRelationSkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetDropRelationDDL($fk)
    {
        $expected = "
ALTER TABLE `foo` DROP CONSTRAINT `foo_bar_fk`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetDropRelationSkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetRelationDDL($fk)
    {
        $expected = "CONSTRAINT `foo_bar_fk`
    FOREIGN KEY (`bar_id`)
    REFERENCES `bar` (`id`)
    ON DELETE CASCADE";
        $this->assertEquals($expected, $this->getPlatform()->getRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetRelationSkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getRelationDDL($fk));
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

    public function testAddExtraIndicesRelations()
    {
        $schema = '
<database name="test1" identifierQuoting="true">
  <entity name="foo">
    <behavior name="AutoAddPK"/>
    <field name="name" type="VARCHAR"/>
    <field name="subid" type="INTEGER"/>
  </entity>
  <entity name="bar">
    <behavior name="AutoAddPK"/>

    <field name="name" type="VARCHAR"/>
    <field name="subid" type="INTEGER"/>

    <foreign-key foreignEntity="foo">
      <reference local="id" foreign="id"/>
      <reference local="subid" foreign="subid"/>
    </foreign-key>
  </entity>
</database>
';

        $expectedRelationSql = "
CREATE TABLE `bar`
(
    `name` VARCHAR(255),
    `subid` INTEGER,
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`),
    CONSTRAINT `bar_fk_bb8268`
        FOREIGN KEY (`id`,`subid`)
        REFERENCES `foo` (`id`,`subid`)
) ENGINE=InnoDB;
";

        $entity = $this->getDatabaseFromSchema($schema)->getEntity('Bar');
        $relationEntitySql = $this->getPlatform()->getAddEntityDDL($entity);

        $this->assertEquals($expectedRelationSql, $relationEntitySql);
    }

    public function testGetAddEntityDDLComplexPK()
    {
        $schema   = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER"/>
        <field name="second_id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="third_id" primaryKey="true" type="INTEGER" />
        <field name="bar" type="VARCHAR" size="255" />
    </entity>
</database>
EOF;
        $entity    = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL,
    `second_id` INTEGER NOT NULL AUTO_INCREMENT,
    `third_id` INTEGER NOT NULL,
    `bar` VARCHAR(255),
    PRIMARY KEY (`second_id`,`id`,`third_id`)
) ENGINE=InnoDB;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testVendorOptionsQuoting()
    {

        $schema   = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER"/>
        <vendor type="mysql">
            <parameter name="AutoIncrement" value="100" />
            <parameter name="AvgRowLength" value="50" />
            <parameter name="Charset" value="utf8" />
            <parameter name="Checksum" value="1" />
            <parameter name="Collate" value="utf8_unicode_ci" />
            <parameter name="Connection" value="mysql://foo@bar.host:9306/federated/test_table" />
            <parameter name="DataDirectory" value="/tmp/mysql-foo-table/" />
            <parameter name="DelayKeyWrite" value="1" />
            <parameter name="IndexDirectory" value="/tmp/mysql-foo-table-idx/" />
            <parameter name="InsertMethod" value="LAST" />
            <parameter name="KeyBlockSize" value="5" />
            <parameter name="MaxRows" value="5000" />
            <parameter name="MinRows" value="0" />
            <parameter name="Pack_Keys" value="DEFAULT" />
            <parameter name="PackKeys" value="1" />
            <parameter name="RowFormat" value="COMPRESSED" />
            <parameter name="Union" value="other_table" />
        </vendor>
    </entity>
</database>
EOF;
        $entity    = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 AVG_ROW_LENGTH=50 CHARACTER SET='utf8' CHECKSUM=1 COLLATE='utf8_unicode_ci' CONNECTION='mysql://foo@bar.host:9306/federated/test_table' DATA DIRECTORY='/tmp/mysql-foo-table/' DELAY_KEY_WRITE=1 INDEX DIRECTORY='/tmp/mysql-foo-table-idx/' INSERT_METHOD=LAST KEY_BLOCK_SIZE=5 MAX_ROWS=5000 MIN_ROWS=0 PACK_KEYS=DEFAULT PACK_KEYS=1 ROW_FORMAT=COMPRESSED UNION='other_table';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }
}

