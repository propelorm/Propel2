<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\PlatformInterface;

class PgsqlPlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return \Propel\Generator\Platform\PgsqlPlatform
     */
    protected function getPlatform(): PlatformInterface
    {
        return new PgsqlPlatform();
    }

    /**
     * @return void
     */
    public function testGetSequenceNameDefault()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);
        $col = new Column('bar');
        $col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $col->setAutoIncrement(true);
        $table->addColumn($col);
        $expected = 'foo_bar_seq';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    /**
     * @return void
     */
    public function testGetSequenceNameCustom()
    {
        $table = new Table('foo');
        $table->setIdMethod(IdMethod::NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $table->addIdMethodParameter($idMethodParameter);
        $table->setIdMethod(IdMethod::NATIVE);
        $col = new Column('bar');
        $col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $col->setAutoIncrement(true);
        $table->addColumn($col);
        $expected = 'foo_sequence';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    /**
     * @dataProvider providerForTestGetAddTablesDDL
     *
     * @return void
     */
    public function testGetAddTablesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

BEGIN;

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "book" CASCADE;

CREATE TABLE "book"
(
    "id" serial NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "author_id" INTEGER,
    PRIMARY KEY ("id")
);

CREATE INDEX "book_i_639136" ON "book" ("title");

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "author" CASCADE;

CREATE TABLE "author"
(
    "id" serial NOT NULL,
    "first_name" VARCHAR(100),
    "last_name" VARCHAR(100),
    PRIMARY KEY ("id")
);

ALTER TABLE "book" ADD CONSTRAINT "book_fk_ea464c"
    FOREIGN KEY ("author_id")
    REFERENCES "author" ("id");

COMMIT;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesSkipSQLDDL
     *
     * @return void
     */
    public function testGetAddTablesDDLSkipSQL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @return void
     */
    public function testGetAddTablesDDLSchemasVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="table1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </table>
    <table name="table2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
    </table>
    <table name="table3">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <vendor type="pgsql">
            <parameter name="schema" value="Yipee"/>
        </vendor>
    </table>
</database>
EOF;
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

BEGIN;

CREATE SCHEMA "Woopah";

CREATE SCHEMA "Yipee";

-----------------------------------------------------------------------
-- table1
-----------------------------------------------------------------------

SET search_path TO "Woopah";

DROP TABLE IF EXISTS "table1" CASCADE;

SET search_path TO public;

SET search_path TO "Woopah";

CREATE TABLE "table1"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

-----------------------------------------------------------------------
-- table2
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "table2" CASCADE;

CREATE TABLE "table2"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- table3
-----------------------------------------------------------------------

SET search_path TO "Yipee";

DROP TABLE IF EXISTS "table3" CASCADE;

SET search_path TO public;

SET search_path TO "Yipee";

CREATE TABLE "table3"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

COMMIT;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTablesDDLSchema
     *
     * @return void
     */
    public function testGetAddTablesDDLSchemas($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

BEGIN;

-----------------------------------------------------------------------
-- x.book
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "x"."book" CASCADE;

CREATE TABLE "x"."book"
(
    "id" serial NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "author_id" INTEGER,
    PRIMARY KEY ("id")
);

CREATE INDEX "book_i_639136" ON "x"."book" ("title");

-----------------------------------------------------------------------
-- y.author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "y"."author" CASCADE;

CREATE TABLE "y"."author"
(
    "id" serial NOT NULL,
    "first_name" VARCHAR(100),
    "last_name" VARCHAR(100),
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- x.book_summary
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "x"."book_summary" CASCADE;

CREATE TABLE "x"."book_summary"
(
    "id" serial NOT NULL,
    "book_id" INTEGER NOT NULL,
    "summary" TEXT NOT NULL,
    PRIMARY KEY ("id")
);

ALTER TABLE "x"."book" ADD CONSTRAINT "book_fk_4444ca"
    FOREIGN KEY ("author_id")
    REFERENCES "y"."author" ("id");

ALTER TABLE "x"."book_summary" ADD CONSTRAINT "book_summary_fk_23450f"
    FOREIGN KEY ("book_id")
    REFERENCES "x"."book" ("id")
    ON DELETE CASCADE;

COMMIT;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTablesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSimplePK
     *
     * @return void
     */
    public function testGetAddTableDDLSimplePK($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("id")
);

COMMENT ON TABLE "foo" IS 'This is foo table';

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLCompositePK
     *
     * @return void
     */
    public function testGetAddTableDDLCompositePK($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "foo" INTEGER NOT NULL,
    "bar" INTEGER NOT NULL,
    "baz" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("foo","bar")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLUniqueIndex
     *
     * @return void
     */
    public function testGetAddTableDDLUniqueIndex($schema)
    {
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id"),
    CONSTRAINT "foo_u_14f552" UNIQUE ("bar")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetAddTableDDLSchemaVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

SET search_path TO "Woopah";

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     *
     * @return void
     */
    public function testGetAddTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'Woopah.foo');
        $expected = <<<EOF

CREATE TABLE "Woopah"."foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetAddTableDDLSequence()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <id-method-parameter value="my_custom_sequence_name"/>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

CREATE SEQUENCE "my_custom_sequence_name";

CREATE TABLE "foo"
(
    "id" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetAddTableDDLColumnComments()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" description="identifier column"/>
        <column name="bar" type="INTEGER" description="your name here"/>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id")
);

COMMENT ON COLUMN "foo"."id" IS 'identifier column';

COMMENT ON COLUMN "foo"."bar" IS 'your name here';

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetDropTableDDL()
    {
        $table = new Table('foo');
        $expected = '
DROP TABLE IF EXISTS "foo" CASCADE;
';
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetDropTableDDLSchemaVendor()
    {
        $schema = <<<EOF
<database name="test">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </table>
</database>
EOF;
        $table = $this->getTableFromSchema($schema);
        $expected = <<<EOF

SET search_path TO "Woopah";

DROP TABLE IF EXISTS "foo" CASCADE;

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddTableDDLSchema
     *
     * @return void
     */
    public function testGetDropTableDDLSchema($schema)
    {
        $table = $this->getTableFromSchema($schema, 'Woopah.foo');
        $expected = <<<EOF

DROP TABLE IF EXISTS "Woopah"."foo" CASCADE;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetDropTableWithSequenceDDL()
    {
        $table = new Table('foo');
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $table->addIdMethodParameter($idMethodParameter);
        $table->setIdMethod(IdMethod::NATIVE);
        $expected = '
DROP TABLE IF EXISTS "foo" CASCADE;

DROP SEQUENCE "foo_sequence";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
    }

    /**
     * @return void
     */
    public function testGetColumnDDL()
    {
        $c = new Column('foo');
        $c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c->getDomain()->replaceScale(2);
        $c->getDomain()->replaceSize(3);
        $c->setNotNull(true);
        $c->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expected = '"foo" DOUBLE PRECISION DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($c));
    }

    /**
     * @return void
     */
    public function testGetColumnDDLAutoIncrement()
    {
        $database = new Database();
        $database->setPlatform($this->getPlatform());
        $table = new Table('foo_table');
        $table->setIdMethod(IdMethod::NATIVE);
        $database->addTable($table);
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType(PropelTypes::BIGINT));
        $column->setAutoIncrement(true);
        $table->addColumn($column);
        $expected = '"foo" bigserial';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    /**
     * @return void
     */
    public function testGetColumnDDLCustomSqlType()
    {
        $column = new Column('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->replaceSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $column->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = '"foo" DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
    }

    /**
     * @return void
     */
    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $table = new Table('foo');
        $column = new Column('bar');
        $column->setPrimaryKey(true);
        $table->addColumn($column);
        $expected = 'PRIMARY KEY ("bar")';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    /**
     * @return void
     */
    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $table = new Table('foo');
        $column1 = new Column('bar1');
        $column1->setPrimaryKey(true);
        $table->addColumn($column1);
        $column2 = new Column('bar2');
        $column2->setPrimaryKey(true);
        $table->addColumn($column2);
        $expected = 'PRIMARY KEY ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     *
     * @return void
     */
    public function testGetDropPrimaryKeyDDL($table)
    {
        $expected = '
ALTER TABLE "foo" DROP CONSTRAINT "foo_pkey";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     *
     * @return void
     */
    public function testGetAddPrimaryKeyDDL($table)
    {
        $expected = '
ALTER TABLE "foo" ADD PRIMARY KEY ("bar");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     *
     * @return void
     */
    public function testAddIndexDDL($index)
    {
        $expected = '
CREATE INDEX "babar" ON "foo" ("bar1","bar2");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueIndexDDL
     *
     * @param \Propel\Generator\Model\Unique $index
     *
     * @return void
     */
    public function testAddUniqueIndexDDL(Unique $index): void
    {
        $expected = '
ALTER TABLE "foo" ADD CONSTRAINT "babar" UNIQUE ("bar1");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     *
     * @return void
     */
    public function testAddIndicesDDL($table)
    {
        $expected = '
CREATE INDEX "babar" ON "foo" ("bar1","bar2");

CREATE INDEX "foo_index" ON "foo" ("bar1");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     *
     * @return void
     */
    public function testDropIndexDDL($index)
    {
        $expected = '
DROP INDEX "babar";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     *
     * @return void
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX "babar" ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     *
     * @return void
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'CONSTRAINT "babar" UNIQUE ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetForeignKeysDDL
     *
     * @return void
     */
    public function testGetAddForeignKeysDDL($table)
    {
        $expected = '
ALTER TABLE "foo" ADD CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE;

ALTER TABLE "foo" ADD CONSTRAINT "foo_baz_fk"
    FOREIGN KEY ("baz_id")
    REFERENCES "baz" ("id")
    ON DELETE SET NULL;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeysDDL($table));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     *
     * @return void
     */
    public function testGetAddForeignKeyDDL($fk)
    {
        $expected = '
ALTER TABLE "foo" ADD CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     *
     * @return void
     */
    public function testGetAddForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     *
     * @return void
     */
    public function testGetDropForeignKeyDDL($fk)
    {
        $expected = '
ALTER TABLE "foo" DROP CONSTRAINT "foo_bar_fk";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     *
     * @return void
     */
    public function testGetDropForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeyDDL
     *
     * @return void
     */
    public function testGetForeignKeyDDL($fk)
    {
        $expected = 'CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE';
        $this->assertEquals($expected, $this->getPlatform()->getForeignKeyDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetForeignKeySkipSqlDDL
     *
     * @return void
     */
    public function testGetForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getForeignKeyDDL($fk));
    }

    /**
     * @return void
     */
    public function testGetCommentBlockDDL()
    {
        $expected = "
-----------------------------------------------------------------------
-- foo bar
-----------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }

    /**
     * @return void
     */
    public function assertCreateTableMatches(string $expected, $schema, ?string $tableName = 'foo' )
    {
        $table = $this->getTableFromSchema($schema, $tableName);
        $this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
    }

    /**
     * @dataProvider providerForTestCreateSchemaWithUuidColumns
     *
     * @return void
     */
    public function testCreateSchemaWithUuidColumns($schema)
    {
        $expected = <<< 'EOT'

CREATE TABLE "foo"
(
    "uuid" uuid DEFAULT vendor_specific_default() NOT NULL,
    "other_uuid" uuid,
    PRIMARY KEY ("uuid")
);

EOT;
        $this->assertCreateTableMatches($expected, $schema);
    }

    /**
     * @dataProvider providerForTestCreateSchemaWithUuidBinaryColumns
     *
     * @return void
     */
    public function testCreateSchemaWithUuidBinaryColumns($schema)
    {
        $expected = <<< 'EOT'

CREATE TABLE "foo"
(
    "uuid-bin" BYTEA DEFAULT vendor_specific_default() NOT NULL,
    "other_uuid-bin" BYTEA,
    PRIMARY KEY ("uuid-bin")
);

EOT;
        $this->assertCreateTableMatches($expected, $schema);
    }
}
