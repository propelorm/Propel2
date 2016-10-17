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
use Propel\Generator\Model\Database;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\PgsqlPlatform;

/**
 *
 */
class PgsqlPlatformTest extends PlatformTestProvider
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

    public function testGetSequenceNameDefault()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(IdMethod::NATIVE);
        $col = new Field('bar');
        $col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $col->setAutoIncrement(true);
        $entity->addField($col);
        $expected = 'foo_bar_seq';
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
        $col = new Field('bar');
        $col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $col->setAutoIncrement(true);
        $entity->addField($col);
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

ALTER TABLE "book" ADD CONSTRAINT "book_fk_c83a02"
    FOREIGN KEY ("author_id")
    REFERENCES "author" ("id");

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesSkipSQLDDL
     */
    public function testGetAddEntitiesDDLSkipSQL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    public function testGetAddEntitiesDDLSchemasVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="table1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </entity>
    <entity name="table2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
    <entity name="table3">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Yipee"/>
        </vendor>
    </entity>
</database>
EOF;
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

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

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDLSchema
     */
    public function testGetAddEntitiesDDLSchemas($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

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

ALTER TABLE "x"."book" ADD CONSTRAINT "book_fk_85f9bd"
    FOREIGN KEY ("author_id")
    REFERENCES "y"."author" ("id");

ALTER TABLE "x"."book_summary" ADD CONSTRAINT "book_summary_fk_6eb6da"
    FOREIGN KEY ("book_id")
    REFERENCES "x"."book" ("id")
    ON DELETE CASCADE;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSimplePK
     */
    public function testGetAddEntityDDLSimplePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("id")
);

COMMENT ON TABLE "foo" IS 'This is foo table';

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLCompositePK
     */
    public function testGetAddEntityDDLCompositePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "foo" INTEGER NOT NULL,
    "bar" INTEGER NOT NULL,
    "baz" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("foo","bar")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLUniqueIndex
     */
    public function testGetAddEntityDDLUniqueIndex($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id"),
    CONSTRAINT "foo_u_14f552" UNIQUE ("bar")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLSchemaVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

SET search_path TO "Woopah";

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetAddEntityDDLSchema($schema)
    {
        $entity = $this->getEntityFromSchema($schema, 'Foo');
        $expected = <<<EOF

CREATE TABLE "Woopah"."foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLSequence()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <id-method-parameter value="my_custom_sequence_name"/>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE SEQUENCE "my_custom_sequence_name";

CREATE TABLE "foo"
(
    "id" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLFieldComments()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" description="identifier column"/>
        <field name="bar" type="INTEGER" description="your name here"/>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
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
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetDropEntityDDL()
    {
        $entity = new Entity('foo');
        $expected = '
DROP TABLE IF EXISTS "foo" CASCADE;
';
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetDropEntityDDLSchemaVendor()
    {
        $schema = <<<EOF
<database name="test">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

SET search_path TO "Woopah";

DROP TABLE IF EXISTS "foo" CASCADE;

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetDropEntityDDLSchema($schema)
    {
        $entity = $this->getEntityFromSchema($schema, 'Foo');
        $expected = <<<EOF

DROP TABLE IF EXISTS "Woopah"."foo" CASCADE;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetDropEntityWithSequenceDDL()
    {
        $entity = new Entity('foo');
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $entity->addIdMethodParameter($idMethodParameter);
        $entity->setIdMethod(IdMethod::NATIVE);
        $expected = '
DROP TABLE IF EXISTS "foo" CASCADE;

DROP SEQUENCE "foo_sequence";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetFieldDDL()
    {
        $c = new Field('foo');
        $c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c->getDomain()->replaceScale(2);
        $c->getDomain()->replaceSize(3);
        $c->setNotNull(true);
        $c->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expected = '"foo" DOUBLE PRECISION DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($c));
    }

    public function testGetFieldDDLAutoIncrement()
    {
        $database = new Database();
        $database->setPlatform($this->getPlatform());
        $entity = new Entity('foo_entity');
        $entity->setIdMethod(IdMethod::NATIVE);
        $database->addEntity($entity);
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType(PropelTypes::BIGINT));
        $field->setAutoIncrement(true);
        $entity->addField($field);
        $expected = '"foo" bigserial';
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
        $expected = '"foo" DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $entity = new Entity('foo');
        $field = new Field('bar');
        $field->setPrimaryKey(true);
        $entity->addField($field);
        $expected = 'PRIMARY KEY ("bar")';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $entity = new Entity('foo');
        $field1 = new Field('bar1');
        $field1->setPrimaryKey(true);
        $entity->addField($field1);
        $field2 = new Field('bar2');
        $field2->setPrimaryKey(true);
        $entity->addField($field2);
        $expected = 'PRIMARY KEY ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($entity)
    {
        $expected = '
ALTER TABLE "foo" DROP CONSTRAINT "foo_pkey";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($entity)
    {
        $expected = '
ALTER TABLE "foo" ADD PRIMARY KEY ("bar");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = '
CREATE INDEX "babar" ON "foo" ("bar1","bar2");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($entity)
    {
        $expected = '
CREATE INDEX "babar" ON "foo" ("bar1","bar2");

CREATE INDEX "foo_index" ON "foo" ("bar1");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
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
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX "babar" ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'CONSTRAINT "babar" UNIQUE ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetRelationsDDL
     */
    public function testGetAddRelationsDDL($entity)
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
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationsDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetAddRelationDDL($fk)
    {
        $expected = '
ALTER TABLE "foo" ADD CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE;
';
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
        $expected = '
ALTER TABLE "foo" DROP CONSTRAINT "foo_bar_fk";
';
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
        $expected = 'CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE';
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

}
