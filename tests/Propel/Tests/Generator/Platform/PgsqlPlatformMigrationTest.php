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
use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\FieldComparator;
use Propel\Generator\Platform\PgsqlPlatform;

/**
 *
 */
class PgsqlPlatformMigrationTest extends PlatformMigrationTestProvider
{

    /**
     * Get the Platform object for this class
     *
     * @return PgsqlPlatform
     */
    protected function getPlatform()
    {
        return new PgsqlPlatform();
    }

    /**
     * @dataProvider providerForTestGetModifyDatabaseDDL
     */
    public function testGetModifyDatabaseDDL($databaseDiff)
    {
        $expected = <<<END

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

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
    }

    /**
     * @dataProvider providerForTestGetRenameEntityDDL
     */
    public function testGetRenameEntityDDL($fromName, $toName)
    {
        $expected = '
ALTER TABLE "foo1" RENAME TO "foo2";
';
        $this->assertEquals($expected, $this->getPlatform()->getRenameEntityDDL($fromName, $toName));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityDDL
     */
    public function testGetModifyEntityDDL($tableDiff)
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
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityFieldsDDL
     */
    public function testGetModifyEntityFieldsDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" RENAME COLUMN "bar" TO "bar1";

ALTER TABLE "foo" ALTER COLUMN "baz" DROP NOT NULL;

ALTER TABLE "foo" ADD "baz3" TEXT;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityFieldsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityPrimaryKeysDDL
     */
    public function testGetModifyEntityPrimaryKeysDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" DROP CONSTRAINT "foo_pkey";

ALTER TABLE "foo" ADD PRIMARY KEY ("id","bar");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityPrimaryKeyDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityIndicesDDL
     */
    public function testGetModifyEntityIndicesDDL($tableDiff)
    {
        $expected = <<<END

DROP INDEX "bar_fk";

CREATE INDEX "baz_fk" ON "foo" ("baz");

DROP INDEX "bar_baz_fk";

CREATE INDEX "bar_baz_fk" ON "foo" ("id","bar","baz");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityIndicesDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsDDL
     */
    public function testGetModifyEntityRelationsDDL($tableDiff)
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
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSqlDDL
     */
    public function testGetModifyEntityRelationsSkipSqlDDL($tableDiff)
    {
        $expected = <<<END

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk_1";

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($tableDiff));
        $expected = <<<END

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk_1"
    FOREIGN KEY ("bar")
    REFERENCES "foo2" ("bar");

END;
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
        $expected = '
ALTER TABLE "foo" DROP COLUMN "bar";
';
        $this->assertEquals($expected, $this->getPlatform()->getRemoveFieldDDL($column));
    }

    /**
     * @dataProvider providerForTestGetRenameFieldDDL
     */
    public function testGetRenameFieldDDL($fromColumn, $toColumn)
    {
        $expected = '
ALTER TABLE "foo" RENAME COLUMN "bar1" TO "bar2";
';
        $this->assertEquals($expected, $this->getPlatform()->getRenameFieldDDL($fromColumn, $toColumn));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldDDL
     */
    public function testGetModifyFieldDDL($columnDiff)
    {
        $expected = '
ALTER TABLE "foo" ALTER COLUMN "bar" TYPE DOUBLE PRECISION;
';
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($columnDiff));
    }

    public function testGetModifyFieldDDLWithChangedTypeAndDefault()
    {
        $t1 = new Entity('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);
        $t2 = new Entity('foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field('bar');
        $c2->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceSize(3);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(-100, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);
        $columnDiff = FieldComparator::computeDiff($c1, $c2);
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "bar" TYPE DOUBLE PRECISION;

ALTER TABLE "foo" ALTER COLUMN "bar" SET DEFAULT -100;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($columnDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldsDDL
     */
    public function testGetModifyFieldsDDL($columnDiffs)
    {
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "bar1" TYPE DOUBLE PRECISION;

ALTER TABLE "foo" ALTER COLUMN "bar2" SET NOT NULL;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldsDDL($columnDiffs));
    }

    /**
     * @dataProvider providerForTestGetAddFieldDDL
     */
    public function testGetAddFieldDDL($column)
    {
        $expected = '
ALTER TABLE "foo" ADD "bar" INTEGER;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldDDL($column));
    }

    /**
     * @dataProvider providerForTestGetAddFieldsDDL
     */
    public function testGetAddFieldsDDL($columns)
    {
        $expected = <<<END

ALTER TABLE "foo" ADD "bar1" INTEGER;

ALTER TABLE "foo" ADD "bar2" DOUBLE PRECISION DEFAULT -1 NOT NULL;

END;
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldsDDL($columns));
    }

    public function testGetModifyFieldDDLWithVarcharWithoutSize()
    {
        $t1 = new Entity('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar');
        $c1->setEntity($t1);
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(null);
        $c1->getDomain()->replaceScale(null);
        $t1->addField($c1);

        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="VARCHAR" />
    </entity>
</database>
EOF;

        $table = $this->getDatabaseFromSchema($schema)->getEntity('foo');
        $c2 = $table->getField('bar');
        $columnDiff = FieldComparator::computeDiff($c1, $c2);
        $expected = false;
        $this->assertSame($expected, $columnDiff);
    }

public function testGetModifyFieldDDLWithVarcharWithoutSizeAndPlatform()
    {
        $t1 = new Entity('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar');
        $c1->setEntity($t1);
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(null);
        $c1->getDomain()->replaceScale(null);
        $t1->addField($c1);

        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar"/>
    </entity>
</database>
EOF;

        $xtad = new SchemaReader(null);
        $appData = $xtad->parseString($schema);
        $db = $appData->getDatabase();
        $table = $db->getEntity('foo');
        $c2 = $table->getField('bar');
        $columnDiff = FieldComparator::computeDiff($c1, $c2);
        $expected = false;
        $this->assertSame($expected, $columnDiff);
    }

    /**
     * @dataProvider providerForTestGetModifyFieldRemoveDefaultValueDDL
     */
    public function testGetModifyFieldRemoveDefaultValueDDL($columnDiffs)
    {
        $expected = <<<EOF

ALTER TABLE "test" ALTER COLUMN "test" DROP DEFAULT;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($columnDiffs));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSql3DDL
     */
    public function testGetModifyEntityRelationsSkipSql3DDL($databaseDiff)
    {
        $this->assertFalse($databaseDiff);
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSql4DDL
     */
    public function testGetModifyEntityRelationsSkipSql4DDL($databaseDiff)
    {
        $this->assertFalse($databaseDiff);
    }

}
