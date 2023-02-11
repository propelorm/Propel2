<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Diff\ColumnComparator;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\PlatformInterface;

class PgsqlPlatformMigrationTest extends PlatformMigrationTestProvider
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
     * @dataProvider providerForTestGetModifyDatabaseDDL
     *
     * @return void
     */
    public function testGetModifyDatabaseDDL($databaseDiff)
    {
        $expected = <<<END

BEGIN;

DROP TABLE IF EXISTS "foo1" CASCADE;

ALTER TABLE "foo3" RENAME TO "foo4";

CREATE TABLE "foo5"
(
    "id" serial NOT NULL,
    "lkdjfsh" INTEGER,
    "dfgdsgf" TEXT,
    PRIMARY KEY ("id")
);

ALTER TABLE "foo2" RENAME COLUMN "bar" TO "bar1";

ALTER TABLE "foo2"

  ALTER COLUMN "baz" DROP NOT NULL,

  ADD "baz3" TEXT;

COMMIT;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
    }

    /**
     * @dataProvider providerForTestGetRenameTableDDL
     *
     * @return void
     */
    public function testGetRenameTableDDL($fromName, $toName)
    {
        $expected = '
ALTER TABLE "foo1" RENAME TO "foo2";
';
        $this->assertEquals($expected, $this->getPlatform()->getRenameTableDDL($fromName, $toName));
    }

    /**
     * @dataProvider providerForTestGetModifyTableDDL
     *
     * @return void
     */
    public function testGetModifyTableDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" DROP CONSTRAINT "foo1_fk_2";

ALTER TABLE "foo" DROP CONSTRAINT "foo1_fk_1";

DROP INDEX "bar_baz_fk";

DROP INDEX "bar_fk";

ALTER TABLE "foo" RENAME COLUMN "bar" TO "bar1";

ALTER TABLE "foo"

  ALTER COLUMN "baz" DROP NOT NULL,

  ADD "baz3" TEXT;

CREATE INDEX "bar_fk" ON "foo" ("bar1");

CREATE INDEX "baz_fk" ON "foo" ("baz3");

ALTER TABLE "foo" ADD CONSTRAINT "foo1_fk_1"
    FOREIGN KEY ("bar1")
    REFERENCES "foo2" ("bar");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableColumnsDDL
     *
     * @return void
     */
    public function testGetModifyTableColumnsDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" RENAME COLUMN "bar" TO "bar1";

ALTER TABLE "foo" ALTER COLUMN "baz" DROP NOT NULL;

ALTER TABLE "foo" ADD "baz3" TEXT;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableColumnsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTablePrimaryKeysDDL
     *
     * @return void
     */
    public function testGetModifyTablePrimaryKeysDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" DROP CONSTRAINT "foo_pkey";

ALTER TABLE "foo" ADD PRIMARY KEY ("id","bar");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTablePrimaryKeyDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableIndicesDDL
     *
     * @return void
     */
    public function testGetModifyTableIndicesDDL($tableDiff)
    {
        $expected = <<<END

DROP INDEX "bar_fk";

ALTER TABLE "foo" DROP CONSTRAINT "bax_unique";

CREATE INDEX "baz_fk" ON "foo" ("baz");

ALTER TABLE "foo" ADD CONSTRAINT "bax_bay_unique" UNIQUE ("bax","bay");

DROP INDEX "bar_baz_fk";

CREATE INDEX "bar_baz_fk" ON "foo" ("id","bar","baz");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableIndicesDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysDDL
     *
     * @return void
     */
    public function testGetModifyTableForeignKeysDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk_1";

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk_3"
    FOREIGN KEY ("baz")
    REFERENCES "foo2" ("baz");

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk_2";

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk_2"
    FOREIGN KEY ("bar","id")
    REFERENCES "foo2" ("bar","id");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysSkipSqlDDL
     *
     * @return void
     */
    public function testGetModifyTableForeignKeysSkipSqlDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk_1";

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff));
        $expected = <<<END

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk_1"
    FOREIGN KEY ("bar")
    REFERENCES "foo2" ("bar");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff->getReverseDiff()));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysSkipSql2DDL
     *
     * @return void
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
     *
     * @return void
     */
    public function testGetRemoveColumnDDL($column)
    {
        $expected = '
ALTER TABLE "foo" DROP COLUMN "bar";
';
        $this->assertEquals($expected, $this->getPlatform()->getRemoveColumnDDL($column));
    }

    /**
     * @dataProvider providerForTestGetRenameColumnDDL
     *
     * @return void
     */
    public function testGetRenameColumnDDL($fromColumn, $toColumn)
    {
        $expected = '
ALTER TABLE "foo" RENAME COLUMN "bar1" TO "bar2";
';
        $this->assertEquals($expected, $this->getPlatform()->getRenameColumnDDL($fromColumn, $toColumn));
    }

    /**
     * @dataProvider providerForTestGetModifyColumnDDL
     *
     * @return void
     */
    public function testGetModifyColumnDDL($columnDiff)
    {
        $expected = '
ALTER TABLE "foo" ALTER COLUMN "bar" TYPE DOUBLE PRECISION;
';
        $this->assertEquals($expected, $this->getPlatform()->getModifyColumnDDL($columnDiff));
    }

    /**
     * @return void
     */
    public function testGetModifyColumnDDLWithChangedTypeAndDefault()
    {
        $t1 = new Table('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('bar');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addColumn($c1);
        $t2 = new Table('foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Column('bar');
        $c2->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceSize(3);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(-100, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c2);
        $columnDiff = ColumnComparator::computeDiff($c1, $c2);
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "bar" TYPE DOUBLE PRECISION;

ALTER TABLE "foo" ALTER COLUMN "bar" SET DEFAULT -100;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyColumnDDL($columnDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyColumnsDDL
     *
     * @return void
     */
    public function testGetModifyColumnsDDL($columnDiffs)
    {
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "bar1" TYPE DOUBLE PRECISION;

ALTER TABLE "foo" ALTER COLUMN "bar2" SET NOT NULL;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyColumnsDDL($columnDiffs));
    }

    /**
     * @dataProvider providerForTestGetAddColumnDDL
     *
     * @return void
     */
    public function testGetAddColumnDDL($column)
    {
        $expected = '
ALTER TABLE "foo" ADD "bar" INTEGER;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddColumnDDL($column));
    }

    /**
     * @dataProvider providerForTestGetAddColumnsDDL
     *
     * @return void
     */
    public function testGetAddColumnsDDL($columns)
    {
        $expected = <<<END

ALTER TABLE "foo" ADD "bar1" INTEGER;

ALTER TABLE "foo" ADD "bar2" DOUBLE PRECISION DEFAULT -1 NOT NULL;

END;
        $this->assertEquals($expected, $this->getPlatform()->getAddColumnsDDL($columns));
    }

    /**
     * @return void
     */
    public function testGetModifyColumnDDLWithVarcharWithoutSize()
    {
        $t1 = new Table('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('bar');
        $c1->setTable($t1);
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(null);
        $c1->getDomain()->replaceScale(null);
        $t1->addColumn($c1);

        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="VARCHAR"/>
    </table>
</database>
EOF;

        $table = $this->getDatabaseFromSchema($schema)->getTable('foo');
        $c2 = $table->getColumn('bar');
        $columnDiff = ColumnComparator::computeDiff($c1, $c2);
        $expected = false;
        $this->assertSame($expected, $columnDiff);
    }

    /**
     * @return void
     */
    public function testGetModifyColumnDDLWithVarcharWithoutSizeAndPlatform()
    {
        $t1 = new Table('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('bar');
        $c1->setTable($t1);
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(null);
        $c1->getDomain()->replaceScale(null);
        $t1->addColumn($c1);

        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar"/>
    </table>
</database>
EOF;

        $xtad = new SchemaReader(null);
        $appData = $xtad->parseString($schema);
        $db = $appData->getDatabase();
        $table = $db->getTable('foo');
        $c2 = $table->getColumn('bar');
        $columnDiff = ColumnComparator::computeDiff($c1, $c2);
        $expected = false;
        $this->assertSame($expected, $columnDiff);
    }

    /**
     * @dataProvider providerForTestGetModifyColumnRemoveDefaultValueDDL
     *
     * @return void
     */
    public function testGetModifyColumnRemoveDefaultValueDDL($columnDiffs)
    {
        $expected = <<<EOF

ALTER TABLE "test" ALTER COLUMN "test" DROP DEFAULT;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getModifyColumnDDL($columnDiffs));
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysSkipSql3DDL
     *
     * @return void
     */
    public function testGetModifyTableForeignKeysSkipSql3DDL($databaseDiff)
    {
        $this->assertFalse($databaseDiff);
    }

    /**
     * @dataProvider providerForTestGetModifyTableForeignKeysSkipSql4DDL
     *
     * @return void
     */
    public function testGetModifyTableForeignKeysSkipSql4DDL($databaseDiff)
    {
        $this->assertFalse($databaseDiff);
    }

    /**
     * @dataProvider providerForTestMigrateToUUIDColumn
     *
     * @return void
     */
    public function testMigrateToUUIDColumn($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "id" TYPE uuid USING id::uuid;

ALTER TABLE "foo" ALTER COLUMN "id" SET DEFAULT vendor_specific_uuid_generator_function();

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableColumnsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestMigrateToUuidBinColumn
     *
     * @return void
     */
    public function testMigrateToUuidBinColumn($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "id" TYPE BYTEA USING NULL;

ALTER TABLE "foo" ALTER COLUMN "id" SET DEFAULT vendor_specific_uuid_generator_function();

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyTableColumnsDDL($tableDiff));
    }
}
