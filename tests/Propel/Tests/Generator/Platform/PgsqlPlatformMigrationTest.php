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
     * @return Platform
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
    public function testGetModifyEntityDDL($entityDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" DROP CONSTRAINT "foo1_fk2";

ALTER TABLE "foo" DROP CONSTRAINT "foo1_fk1";

DROP INDEX "bar_baz_fk";

DROP INDEX "bar_fk";

ALTER TABLE "foo" RENAME COLUMN "bar" TO "bar1";

ALTER TABLE "foo"

  ALTER COLUMN "baz" DROP NOT NULL,

  ADD "baz3" TEXT;

CREATE INDEX "bar_fk" ON "foo" ("bar1");

CREATE INDEX "baz_fk" ON "foo" ("baz3");

ALTER TABLE "foo" ADD CONSTRAINT "foo1_fk1"
    FOREIGN KEY ("bar1")
    REFERENCES "foo2" ("bar");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityFieldsDDL
     */
    public function testGetModifyEntityFieldsDDL($entityDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" RENAME COLUMN "bar" TO "bar1";

ALTER TABLE "foo" ALTER COLUMN "baz" DROP NOT NULL;

ALTER TABLE "foo" ADD "baz3" TEXT;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityFieldsDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityPrimaryKeysDDL
     */
    public function testGetModifyEntityPrimaryKeysDDL($entityDiff)
    {
        $expected = <<<END

ALTER TABLE "foo" DROP CONSTRAINT "foo_pkey";

ALTER TABLE "foo" ADD PRIMARY KEY ("id","bar");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityPrimaryKeyDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityIndicesDDL
     */
    public function testGetModifyEntityIndicesDDL($entityDiff)
    {
        $expected = <<<END

DROP INDEX "bar_fk";

CREATE INDEX "baz_fk" ON "foo" ("baz");

DROP INDEX "bar_baz_fk";

CREATE INDEX "bar_baz_fk" ON "foo" ("id","bar","baz");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityIndicesDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsDDL
     */
    public function testGetModifyEntityRelationsDDL($entityDiff)
    {
        $expected = <<<END

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk1";

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk3"
    FOREIGN KEY ("baz")
    REFERENCES "foo2" ("baz");

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk2";

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk2"
    FOREIGN KEY ("bar","id")
    REFERENCES "foo2" ("bar","id");

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyEntityRelationsSkipSqlDDL
     */
    public function testGetModifyEntityRelationsSkipSqlDDL($entityDiff)
    {
        $expected = <<<END

ALTER TABLE "foo1" DROP CONSTRAINT "foo1_fk1";

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyEntityRelationsDDL($entityDiff));
        $expected = <<<END

ALTER TABLE "foo1" ADD CONSTRAINT "foo1_fk1"
    FOREIGN KEY ("bar")
    REFERENCES "foo2" ("bar");

END;
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
        $expected = '
ALTER TABLE "foo" DROP COLUMN "bar";
';
        $this->assertEquals($expected, $this->getPlatform()->getRemoveFieldDDL($field));
    }

    /**
     * @dataProvider providerForTestGetRenameFieldDDL
     */
    public function testGetRenameFieldDDL($fromField, $toField)
    {
        $expected = '
ALTER TABLE "foo" RENAME COLUMN "bar1" TO "bar2";
';
        $this->assertEquals($expected, $this->getPlatform()->getRenameFieldDDL($fromField, $toField));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldDDL
     */
    public function testGetModifyFieldDDL($fieldDiff)
    {
        $expected = '
ALTER TABLE "foo" ALTER COLUMN "bar" TYPE DOUBLE PRECISION;
';
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($fieldDiff));
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
        $fieldDiff = FieldComparator::computeDiff($c1, $c2);
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "bar" TYPE DOUBLE PRECISION;

ALTER TABLE "foo" ALTER COLUMN "bar" SET DEFAULT -100;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($fieldDiff));
    }

    /**
     * @dataProvider providerForTestGetModifyFieldsDDL
     */
    public function testGetModifyFieldsDDL($fieldDiffs)
    {
        $expected = <<<END

ALTER TABLE "foo" ALTER COLUMN "bar1" TYPE DOUBLE PRECISION;

ALTER TABLE "foo" ALTER COLUMN "bar2" SET NOT NULL;

END;
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldsDDL($fieldDiffs));
    }

    /**
     * @dataProvider providerForTestGetAddFieldDDL
     */
    public function testGetAddFieldDDL($field)
    {
        $expected = '
ALTER TABLE "foo" ADD "bar" INTEGER;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldDDL($field));
    }

    /**
     * @dataProvider providerForTestGetAddFieldsDDL
     */
    public function testGetAddFieldsDDL($fields)
    {
        $expected = <<<END

ALTER TABLE "foo" ADD "bar1" INTEGER;

ALTER TABLE "foo" ADD "bar2" DOUBLE PRECISION DEFAULT -1 NOT NULL;

END;
        $this->assertEquals($expected, $this->getPlatform()->getAddFieldsDDL($fields));
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

        $entity = $this->getDatabaseFromSchema($schema)->getEntity('Foo');
        $c2 = $entity->getField('bar');
        $fieldDiff = FieldComparator::computeDiff($c1, $c2);
        $expected = false;
        $this->assertSame($expected, $fieldDiff);
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
        $entity = $db->getEntity('Foo');
        $c2 = $entity->getField('bar');
        $fieldDiff = FieldComparator::computeDiff($c1, $c2);
        $expected = false;
        $this->assertSame($expected, $fieldDiff);
    }

    /**
     * @dataProvider providerForTestGetModifyFieldRemoveDefaultValueDDL
     */
    public function testGetModifyFieldRemoveDefaultValueDDL($fieldDiffs)
    {
        $expected = <<<EOF

ALTER TABLE "test" ALTER COLUMN "test" DROP DEFAULT;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getModifyFieldDDL($fieldDiffs));
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
