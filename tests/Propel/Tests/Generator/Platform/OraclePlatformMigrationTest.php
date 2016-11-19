<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Platform\OraclePlatform;

/**
 *
 */
class OraclePlatformMigrationTest extends PlatformMigrationTestProvider
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

    /**
     * @dataProvider providerForTestGetModifyDatabaseDDL
     */
    public function testGetModifyDatabaseDDL($databaseDiff)
    {
        $expected = "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';

DROP TABLE foo1 CASCADE CONSTRAINTS;

DROP SEQUENCE foo1_SEQ;

ALTER TABLE foo3 RENAME TO foo4;

CREATE TABLE foo5
(
    id NUMBER NOT NULL,
    lkdjfsh NUMBER,
    dfgdsgf NVARCHAR2(2000)
);

ALTER TABLE foo5 ADD CONSTRAINT foo5_pk PRIMARY KEY (id);

CREATE SEQUENCE foo5_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

ALTER TABLE foo2 RENAME COLUMN bar TO bar1;

ALTER TABLE foo2

  MODIFY
(
    baz NVARCHAR2(12)
),

  ADD
(
    baz3 NVARCHAR2(2000)
);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
    }

    /**
     * @dataProvider providerForTestGetRenameEntityDDL
     */
    public function testGetRenameEntityDDL($fromName, $toName)
    {
        $expected = "
ALTER TABLE foo1 RENAME TO foo2;
";
        $this->assertEquals($expected, $this->getPlatform()->getRenameEntityDDL($fromName, $toName));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityDDL
     */
    public function testGetModifyEntityDDL($tableDiff)
    {
        $expected = "
ALTER TABLE foo DROP CONSTRAINT foo1_fk_2;

ALTER TABLE foo DROP CONSTRAINT foo1_fk_1;

DROP INDEX bar_baz_fk;

DROP INDEX bar_fk;

ALTER TABLE foo RENAME COLUMN bar TO bar1;

ALTER TABLE foo

  MODIFY
(
    baz NVARCHAR2(12)
),

  ADD
(
    baz3 NVARCHAR2(2000)
);

CREATE INDEX bar_fk ON foo (bar1);

CREATE INDEX baz_fk ON foo (baz3);

ALTER TABLE foo ADD CONSTRAINT foo1_fk_1
    FOREIGN KEY (bar1) REFERENCES foo2 (bar);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityFieldsDDL
     */
    public function testGetModifyEntityFieldsDDL($tableDiff)
    {
        $expected = "
ALTER TABLE foo RENAME COLUMN bar TO bar1;

ALTER TABLE foo MODIFY
(
    baz NVARCHAR2(12)
);

ALTER TABLE foo ADD
(
    baz3 NVARCHAR2(2000)
);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityFieldsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityPrimaryKeysDDL
     */
    public function testGetModifyEntityPrimaryKeysDDL($tableDiff)
    {
        $expected = "
ALTER TABLE foo DROP CONSTRAINT foo_pk;

ALTER TABLE foo ADD CONSTRAINT foo_pk PRIMARY KEY (id,bar);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityPrimaryKeyDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityIndicesDDL
     */
    public function testGetModifyEntityIndicesDDL($tableDiff)
    {
        $expected = "
DROP INDEX bar_fk;

CREATE INDEX baz_fk ON foo (baz);

DROP INDEX bar_baz_fk;

CREATE INDEX bar_baz_fk ON foo (id,bar,baz);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityIndicesDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsDDL
     */
    public function testGetModifyEntityRelationsDDL($tableDiff)
    {
        $expected = "
ALTER TABLE foo1 DROP CONSTRAINT foo1_fk_1;

ALTER TABLE foo1 ADD CONSTRAINT foo1_fk_3
    FOREIGN KEY (baz) REFERENCES foo2 (baz);

ALTER TABLE foo1 DROP CONSTRAINT foo1_fk_2;

ALTER TABLE foo1 ADD CONSTRAINT foo1_fk_2
    FOREIGN KEY (bar,id) REFERENCES foo2 (bar,id);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSqlDDL
     */
    public function testGetModifyEntityRelationsSkipSqlDDL($tableDiff)
    {
        $expected = "
ALTER TABLE foo1 DROP CONSTRAINT foo1_fk_1;
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff));
        $expected = "
ALTER TABLE foo1 ADD CONSTRAINT foo1_fk_1
    FOREIGN KEY (bar) REFERENCES foo2 (bar);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSql2DDL
     */
    public function testGetModifyEntityRelationsSkipSql2DDL($tableDiff)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff));
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetRemoveFieldDDL
     */
    public function testGetRemoveFieldDDL($column)
    {
        $expected = "
ALTER TABLE foo DROP COLUMN bar;
";
        $this->assertEquals($expected, $this->getPlatform()->getRemoveFieldDDL($column));
    }

    /**
     * @dataProvider providerForTestGetRenameFieldDDL
     */
    public function testGetRenameFieldDDL($fromColumn, $toColumn)
    {
        $expected = "
ALTER TABLE foo RENAME COLUMN bar1 TO bar2;
";
        $this->assertEquals($expected, $this->getPlatform()->getRenameFieldDDL($fromColumn, $toColumn));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldDDL
     */
    public function testGetModifyFieldDDL($columnDiff)
    {
        $expected = "
ALTER TABLE foo MODIFY bar FLOAT(3);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($columnDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldsDDL
     */
    public function testGetModifyFieldsDDL($columnDiffs)
    {
        $expected = "
ALTER TABLE foo MODIFY
(
    bar1 FLOAT(3),
    bar2 INTEGER NOT NULL
);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldsDDL($columnDiffs));
    }

    /**
     * @dataProvider providerForTestGetAddFieldDDL
     */
    public function testGetAddFieldDDL($column)
    {
        $expected = "
ALTER TABLE foo ADD bar NUMBER;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldDDL($column));
    }

    /**
     * @dataProvider providerForTestGetAddFieldsDDL
     */
    public function testGetAddFieldsDDL($columns)
    {
        $expected = "
ALTER TABLE foo ADD
(
    bar1 NUMBER,
    bar2 FLOAT(3,2) DEFAULT -1 NOT NULL
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldsDDL($columns));
    }

    public function testGetModifyDatabaseWithBlockStorageDDL()
    {
        $schema1 = <<<EOF
<database name="test">
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="blooopoo" type="INTEGER" />
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
    <entity name="foo3">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="yipee" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test">
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="CLOB" />
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
    <entity name="foo4">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="yipee" type="INTEGER" />
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
    <entity name="foo5">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="lkdjfsh" type="INTEGER" />
        <field name="dfgdsgf" type="CLOB" />
        <index name="lkdjfsh_IDX">
            <index-column name="lkdjfsh"/>
            <vendor type="oracle">
                <parameter name="PCTFree" value="20"/>
                <parameter name="InitTrans" value="4"/>
                <parameter name="MinExtents" value="1"/>
                <parameter name="MaxExtents" value="99"/>
                <parameter name="PCTIncrease" value="0"/>
                <parameter name="Tablespace" value="L_128K"/>
            </vendor>
        </index>
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
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $databaseDiff = DatabaseComparator::computeDiff($d1, $d2);
        $expected = "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';

DROP TABLE foo1 CASCADE CONSTRAINTS;

DROP SEQUENCE foo1_SEQ;

DROP TABLE foo3 CASCADE CONSTRAINTS;

DROP SEQUENCE foo3_SEQ;

CREATE TABLE foo4
(
    id NUMBER NOT NULL,
    yipee NUMBER
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

ALTER TABLE foo4 ADD CONSTRAINT foo4_pk PRIMARY KEY (id)
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

CREATE SEQUENCE foo4_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

CREATE TABLE foo5
(
    id NUMBER NOT NULL,
    lkdjfsh NUMBER,
    dfgdsgf CLOB
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

ALTER TABLE foo5 ADD CONSTRAINT foo5_pk PRIMARY KEY (id)
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

CREATE SEQUENCE foo5_SEQ
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;

CREATE INDEX lkdjfsh_IDX ON foo5 (lkdjfsh)
PCTFREE 20
INITRANS 4
STORAGE
(
    MINEXTENTS 1
    MAXEXTENTS 99
    PCTINCREASE 0
)
TABLESPACE L_128K;

ALTER TABLE foo2 RENAME COLUMN bar TO bar1;

ALTER TABLE foo2

  MODIFY
(
    baz NVARCHAR2(12)
),

  ADD
(
    baz3 CLOB
);
";
        $this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
    }

}
