<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Util;

use Propel\Generator\Util\SchemaValidator;
use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Tests\TestCase;

class SchemaValidatorTest extends TestCase
{
    protected function getSchemaForTable($entity)
    {
        $database = new Database();
        $database->addEntity($entity);

        $schema = new Schema();
        $schema->addDatabase($database);

        return $schema;
    }

    public function testValidateReturnsTrueForEmptySchema()
    {
        $schema = new Schema();
        $validator = new SchemaValidator($schema);
        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsTrueForValidSchema()
    {
        $xmlSchema = <<<EOF
<database name="bookstore">
    <table name="book">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);

        $database = $builder->getDatabase();

        $schema = new Schema();
        $schema->addDatabase($database);

        $validator = new SchemaValidator($schema);
        $this->assertTrue($validator->validate());
    }

    public function testDatabasePackageName()
    {

        $schema = <<<EOF
<database name="bookstore" package="my.sub-directory">
    <entity name="book">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
    </entity>
</database>
EOF;
        $dom = new \DomDocument('1.0', 'UTF-8');
        $dom->loadXML($schema);

        $this->assertTrue($dom->schemaValidate(__DIR__.'/../../../../../resources/xsd/database.xsd'));
    }

    public function testValidateReturnsFalseWhenTwoTablesHaveSamePhpName()
    {
        $field1 = new Field('id');
        $field1->setPrimaryKey(true);

        $entity1 = new Entity('Bar');
        $entity1->addField($field1);
        $entity2 = new Entity('Bar2');
        $entity2->addField($field1);

        $database = new Database();
        $database->addEntity($entity1);
        $database->addEntity($entity2);
        $entity2->setName('Bar');

        $schema = new Schema();
        $schema->addDatabase($database);

        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Entity "Bar" declares a name already used in another entity', $validator->getErrors()[0]);
    }

    public function testValidateReturnsTrueWhenTwoTablesHaveSamePhpNameInDifferentNamespaces()
    {
        $field1 = new Field('id');
        $field1->setPrimaryKey(true);

        $entity1 = new Entity('foo');
        $entity1->addField($field1);
        $entity1->setNamespace('Foo');

        $field2 = new Field('id');
        $field2->setPrimaryKey(true);

        $entity2 = new Entity('Foo');
        $entity2->addField($field2);
        $entity2->setNamespace('Bar');

        $database = new Database();
        $database->addEntity($entity1);
        $database->addEntity($entity2);

        $schema = new Schema();
        $schema->addDatabase($database);

        $validator = new SchemaValidator($schema);

        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsFalseWhenTableHasNoPk()
    {
        $entity = new Entity('foo');

        $schema = $this->getSchemaForTable($entity);
        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Entity "foo" does not have a primary key defined. Propel requires all entities to have a primary key.', $validator->getErrors()[0]);
    }

    public function testValidateReturnsTrueWhenTableHasNoPkButIsAView()
    {
        $entity = new Entity('foo');
        $entity->setSkipSql(true);

        $schema = $this->getSchemaForTable($entity);
        $validator = new SchemaValidator($schema);

        $this->assertTrue($validator->validate());
    }

    public function testValidateReturnsFalseWhenTableHasAReservedName()
    {
        $field1 = new Field('id');
        $field1->setPrimaryKey(true);

        $entity = new Entity('tableName');
        $entity->addField($field1);
        $entity->setTableName('TABLE_NAME');
        $schema = $this->getSchemaForTable($entity);
        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Entity "tableName" uses a reserved keyword as tableName', $validator->getErrors()[0]);
    }

    public function testValidateReturnsFalseWhenTwoColumnsHaveSamePhpName()
    {
        $field1 = new Field('foo');
        $field1->setPrimaryKey(true);
        $field2 = new Field('Foo');

        $entity = new Entity('foo_table');
        $entity->addField($field1);
        $entity->addField($field2);
        $field2->setName('foo');

        $schema = $this->getSchemaForTable($entity);

        $validator = new SchemaValidator($schema);

        $this->assertFalse($validator->validate());
        $this->assertContains('Field "foo" declares a name already used in entity "foo_table"', $validator->getErrors()[0]);
    }
}
