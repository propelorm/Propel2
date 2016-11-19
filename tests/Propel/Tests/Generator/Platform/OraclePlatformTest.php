<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\OraclePlatform;

/**
 *
 */
class OraclePlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return OraclePlatform
     */
    protected function getPlatform()
    {
        return new OraclePlatform();
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
     * @dataProvider providerForTestGetAddEntitiesDDL
     */
    public function testGetAddEntitiesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

DROP TABLE book CASCADE CONSTRAINTS;

DROP SEQUENCE book_SEQ;

CREATE TABLE book
(
    id NUMBER NOT NULL,
    title NVARCHAR2(255) NOT NULL,
    author_id NUMBER
);

ALTER TABLE book ADD CONSTRAINT book_pk PRIMARY KEY (id);

CREATE SEQUENCE book_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

CREATE INDEX book_i_639136 ON book (title);

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE author CASCADE CONSTRAINTS;

DROP SEQUENCE author_SEQ;

CREATE TABLE author
(
    id NUMBER NOT NULL,
    first_name NVARCHAR2(100),
    last_name NVARCHAR2(100)
);

ALTER TABLE author ADD CONSTRAINT author_pk PRIMARY KEY (id);

CREATE SEQUENCE author_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

-----------------------------------------------------------------------
-- Foreign Keys
-----------------------------------------------------------------------

ALTER TABLE book ADD CONSTRAINT book_fk_b97a1a
    FOREIGN KEY (author_id) REFERENCES author (id);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesSkipSQLDDL
     */
    public function testGetAddEntitiesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSimplePK
     */
    public function testGetAddEntityDDLSimplePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
-- This is foo table
CREATE TABLE foo
(
    id NUMBER NOT NULL,
    bar NVARCHAR2(255) NOT NULL
);

ALTER TABLE foo ADD CONSTRAINT foo_pk PRIMARY KEY (id);

CREATE SEQUENCE foo_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
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
CREATE TABLE foo
(
    foo NUMBER NOT NULL,
    bar NUMBER NOT NULL,
    baz NVARCHAR2(255) NOT NULL
);

ALTER TABLE foo ADD CONSTRAINT foo_pk PRIMARY KEY (foo,bar);
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
CREATE TABLE foo
(
    id NUMBER NOT NULL,
    bar NUMBER,
    CONSTRAINT foo_u_14f552 UNIQUE (bar)
);

ALTER TABLE foo ADD CONSTRAINT foo_pk PRIMARY KEY (id);

CREATE SEQUENCE foo_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetDropEntityDDL()
    {
        $entity = new Entity('foo');
        $expected = "
DROP TABLE foo CASCADE CONSTRAINTS;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetDropTableWithSequenceDDL()
    {
        $entity = new Entity('foo');
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $entity->addIdMethodParameter($idMethodParameter);
        $entity->setIdMethod(IdMethod::NATIVE);
        $expected = "
DROP TABLE foo CASCADE CONSTRAINTS;

DROP SEQUENCE foo_sequence;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetColumnDDLCustomSqlType()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->replaceSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $column->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = 'foo DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetPrimaryKeyDDLSimpleKey($entity)
    {
        $expected ='CONSTRAINT foo_pk PRIMARY KEY (bar)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    public function testGetPrimaryKeyDDLLongTableName()
    {
        $entity = new Entity('this_table_has_a_very_long_name');
        $column = new Field('bar');
        $column->setPrimaryKey(true);
        $entity->addField($column);
        $expected = 'CONSTRAINT this_table_has_a_very_long__pk PRIMARY KEY (bar)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $entity = new Entity('foo');
        $column1 = new Field('bar1');
        $column1->setPrimaryKey(true);
        $entity->addField($column1);
        $column2 = new Field('bar2');
        $column2->setPrimaryKey(true);
        $entity->addField($column2);
        $expected = 'CONSTRAINT foo_pk PRIMARY KEY (bar1,bar2)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($entity)
    {
        $expected = "
ALTER TABLE foo DROP CONSTRAINT foo_pk;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($entity)
    {
        $expected = "
ALTER TABLE foo ADD CONSTRAINT foo_pk PRIMARY KEY (bar);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($entity)
    {
        $expected = "
CREATE INDEX babar ON foo (bar1,bar2);

CREATE INDEX foo_index ON foo (bar1);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = "
CREATE INDEX babar ON foo (bar1,bar2);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = "
DROP INDEX babar;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX babar (bar1,bar2)';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'CONSTRAINT babar UNIQUE (bar1,bar2)';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetRelationsDDL
     */
    public function testGetAddRelationsDDL($entity)
    {
        $expected = "
ALTER TABLE foo ADD CONSTRAINT foo_bar_fk
    FOREIGN KEY (bar_id) REFERENCES bar (id)
    ON DELETE CASCADE;

ALTER TABLE foo ADD CONSTRAINT foo_baz_fk
    FOREIGN KEY (baz_id) REFERENCES baz (id)
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
ALTER TABLE foo ADD CONSTRAINT foo_bar_fk
    FOREIGN KEY (bar_id) REFERENCES bar (id)
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
ALTER TABLE foo DROP CONSTRAINT foo_bar_fk;
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
        $expected = "CONSTRAINT foo_bar_fk
    FOREIGN KEY (bar_id) REFERENCES bar (id)
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
-----------------------------------------------------------------------
-- foo bar
-----------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }

    public function testGetOracleBlockStorageDDL()
    {
        $schema = <<<EOF
<database name="test" schema="x">
    <entity name="book">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="title" type="VARCHAR" size="255" required="true" />
        <index>
            <index-column name="title" />
            <vendor type="oracle">
                <parameter name="PCTFree" value="20"/>
                <parameter name="InitTrans" value="4"/>
                <parameter name="MinExtents" value="1"/>
                <parameter name="MaxExtents" value="99"/>
                <parameter name="PCTIncrease" value="0"/>
                <parameter name="Tablespace" value="IL_128K"/>
            </vendor>
        </index>
        <field name="author_id" type="INTEGER"/>
        <relation target="author" foreignSchema="y">
            <reference local="author_id" foreign="id" />
        </relation>
        <vendor type="oracle">
            <parameter name="PCTFree" value="20"/>
            <parameter name="InitTrans" value="4"/>
            <parameter name="MinExtents" value="1"/>
            <parameter name="MaxExtents" value="99"/>
            <parameter name="PCTIncrease" value="0"/>
            <parameter name="Tablespace" value="L_128K"/>
            <parameter name="PKPCTFree" value="20"/>
            <parameter name="PKInitTrans" value="4"/>
            <parameter name="PKMinExtents" value="1"/>
            <parameter name="PKMaxExtents" value="99"/>
            <parameter name="PKPCTIncrease" value="0"/>
            <parameter name="PKTablespace" value="IL_128K"/>
        </vendor>
    </entity>
    <entity name="author" schema="y">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="first_name" type="VARCHAR" size="100" />
        <field name="last_name" type="VARCHAR" size="100" />
        <vendor type="oracle">
            <parameter name="PCTFree" value="20"/>
            <parameter name="InitTrans" value="4"/>
            <parameter name="MinExtents" value="1"/>
            <parameter name="MaxExtents" value="99"/>
            <parameter name="PCTIncrease" value="0"/>
            <parameter name="Tablespace" value="L_128K"/>
            <parameter name="PKPCTFree" value="20"/>
            <parameter name="PKInitTrans" value="4"/>
            <parameter name="PKMinExtents" value="1"/>
            <parameter name="PKMaxExtents" value="99"/>
            <parameter name="PKPCTIncrease" value="0"/>
            <parameter name="PKTablespace" value="IL_128K"/>
        </vendor>
    </entity>
</database>
EOF;
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

DROP TABLE book CASCADE CONSTRAINTS;

DROP SEQUENCE book_SEQ;

CREATE TABLE book
(
    id NUMBER NOT NULL,
    title NVARCHAR2(255) NOT NULL,
    author_id NUMBER
)
PCTFREE 20
INITRANS 4
STORAGE
(
    MINEXTENTS 1
    MAXEXTENTS 99
    PCTINCREASE 0
)
TABLESPACE L_128K;

ALTER TABLE book ADD CONSTRAINT book_pk PRIMARY KEY (id)
USING INDEX
PCTFREE 20
INITRANS 4
STORAGE
(
    MINEXTENTS 1
    MAXEXTENTS 99
    PCTINCREASE 0
)
TABLESPACE IL_128K;

CREATE SEQUENCE book_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

CREATE INDEX book_i_639136 ON book (title)
PCTFREE 20
INITRANS 4
STORAGE
(
    MINEXTENTS 1
    MAXEXTENTS 99
    PCTINCREASE 0
)
TABLESPACE IL_128K;

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE author CASCADE CONSTRAINTS;

DROP SEQUENCE author_SEQ;

CREATE TABLE author
(
    id NUMBER NOT NULL,
    first_name NVARCHAR2(100),
    last_name NVARCHAR2(100)
)
PCTFREE 20
INITRANS 4
STORAGE
(
    MINEXTENTS 1
    MAXEXTENTS 99
    PCTINCREASE 0
)
TABLESPACE L_128K;

ALTER TABLE author ADD CONSTRAINT author_pk PRIMARY KEY (id)
USING INDEX
PCTFREE 20
INITRANS 4
STORAGE
(
    MINEXTENTS 1
    MAXEXTENTS 99
    PCTINCREASE 0
)
TABLESPACE IL_128K;

CREATE SEQUENCE author_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

-----------------------------------------------------------------------
-- Foreign Keys
-----------------------------------------------------------------------

ALTER TABLE book ADD CONSTRAINT book_fk_82ae3e
    FOREIGN KEY (author_id) REFERENCES author (id);

EOF;

        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

}
