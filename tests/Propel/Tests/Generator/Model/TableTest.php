<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\DefaultPlatform;

use Propel\Tests\Helpers\DummyPlatforms;
use Propel\Tests\Helpers\NoSchemaPlatform;
use Propel\Tests\Helpers\SchemaPlatform;

use \DOMDocument;

/**
 * Tests for Table model class
 *
 * @author     Martin Poeschl (mpoeschl@marmot.at)
 */
class TableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test if the tables get the package name from the properties file
     *
     */
    public function testIdMethodHandling()
    {
        $schemaReader = new SchemaReader();
        $xmlSchema = <<<EOF
<database name="iddb" defaultIdMethod="native">
  <table name="table_native">
    <column name="table_a_id" required="true" autoIncrement="true" primaryKey="true" type="INTEGER" />
    <column name="col_a" type="CHAR" size="5" />
  </table>
  <table name="table_none" idMethod="none">
    <column name="table_a_id" required="true" primaryKey="true" type="INTEGER" />
    <column name="col_a" type="CHAR" size="5" />
  </table>
</database>
EOF;
        $schema = $schemaReader->parseString($xmlSchema);

        $db = $schema->getDatabase("iddb");
        $this->assertEquals(IdMethod::NATIVE, $db->getDefaultIdMethod());

        $table1 = $db->getTable("table_native");
        $this->assertEquals(IdMethod::NATIVE, $table1->getIdMethod());

        $table2 = $db->getTable("table_none");
        $this->assertEquals(IdMethod::NO_ID_METHOD, $table2->getIdMethod());
    }

    public function testGeneratorConfig()
    {
        $schemaReader = new SchemaReader();
        $xmlSchema = <<<EOF
<database name="test1">
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
  </table>
</database>
EOF;
        $schema = $schemaReader->parseString($xmlSchema);
        $table = $schema->getDatabase('test1')->getTable('table1');
        $config = new GeneratorConfig();
        $config->setBuildProperties(array('propel.foo.bar.class' => 'bazz'));
        $table->getDatabase()->getSchema()->setGeneratorConfig($config);
        $this->assertThat($table->getGeneratorConfig(), $this->isInstanceOf('\Propel\Generator\Config\GeneratorConfig'), 'getGeneratorConfig() returns an instance of the generator configuration');
        $this->assertEquals($table->getGeneratorConfig()->getBuildProperty('fooBarClass'), 'bazz', 'getGeneratorConfig() returns the instance of the generator configuration used in the platform');
    }

    public function testAddBehavior()
    {
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $config = new GeneratorConfig();
        $config->setBuildProperties(array(
            'propel.platform.class' => 'propel.engine.platform.DefaultPlatform',
            'propel.behavior.timestampable.class' => '\Propel\Generator\Behavior\TimestampableBehavior'
        ));
        $schemaReader->setGeneratorConfig($config);
        $xmlSchema = <<<EOF
<database name="test1">
  <table name="table1">
    <behavior name="timestampable" />
    <column name="id" type="INTEGER" primaryKey="true" />
  </table>
</database>
EOF;
        $schema = $schemaReader->parseString($xmlSchema);
        $table = $schema->getDatabase('test1')->getTable('table1');
        $this->assertThat($table->getBehavior('timestampable'), $this->isInstanceOf('\Propel\Generator\Behavior\Timestampable\TimestampableBehavior'), 'addBehavior() uses the behavior class defined in build.properties');
    }

    /**
     * @expectedException \Propel\Generator\Exception\EngineException
     */
    public function testUniqueColumnName()
    {
        $schemaReader = new SchemaReader();
        $xmlSchema = <<<EOF
<database name="columnTest" defaultIdMethod="native">
    <table name="columnTestTable">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
        <column name="title" type="VARCHAR" required="true" description="Book Title" />
        <column name="title" type="VARCHAR" required="true" description="Book Title" />
    </table>
</database>
EOF;
        // Parsing file with duplicate column names in one table throws exception
        $schema = $schemaReader->parseString($xmlSchema);
    }

    /**
     * @expectedException \Propel\Generator\Exception\EngineException
     */
    public function testUniqueTableName()
    {
        $schemaReader = new SchemaReader();
        $xmlSchema = <<<EOF
<database name="columnTest" defaultIdMethod="native">
    <table name="columnTestTable">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
        <column name="title" type="VARCHAR" required="true" description="Book Title" />
    </table>
    <table name="columnTestTable">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id" />
        <column name="title" type="VARCHAR" required="true" description="Book Title" />
    </table>
</database>
EOF;
        // Parsing file with duplicate table name throws exception
        $schema = $schemaReader->parseString($xmlSchema);
    }

    public function providerForTestHasColumn()
    {
        $table = new Table();
        $column = new Column('Foo');
        $table->addColumn($column);

        return array(
            array($table, $column)
        );
    }

    /**
     * @dataProvider providerForTestHasColumn
     */
    public function testHasColumn($table, $column)
    {
        $this->assertTrue($table->hasColumn('Foo'));
        $this->assertFalse($table->hasColumn('foo'));
        $this->assertFalse($table->hasColumn('FOO'));
    }

    /**
     * @dataProvider providerForTestHasColumn
     */
    public function testHasColumnCaseInsensitive($table, $column)
    {
        $this->assertTrue($table->hasColumn('Foo', true));
        $this->assertTrue($table->hasColumn('foo', true));
        $this->assertTrue($table->hasColumn('FOO', true));
    }

    /**
     * @dataProvider providerForTestHasColumn
     */
    public function testGetColumn($table, $column)
    {
        $this->assertEquals($column, $table->getColumn('Foo'));
        $this->assertNull($table->getColumn('foo'));
        $this->assertNull($table->getColumn('FOO'));
    }

    /**
     * @dataProvider providerForTestHasColumn
     */
    public function testGetColumnCaseInsensitive($table, $column)
    {
        $this->assertEquals($column, $table->getColumn('Foo', true));
        $this->assertEquals($column, $table->getColumn('foo', true));
        $this->assertEquals($column, $table->getColumn('FOO', true));
    }

    /**
     * @dataProvider providerForTestHasColumn
     */
    public function testRemoveColumnFromObject($table, $column)
    {
        $table->removeColumn($column);
        $this->assertFalse($table->hasColumn('Foo'));
    }

    /**
     * @dataProvider providerForTestHasColumn
     */
    public function testRemoveColumnFromName($table, $column)
    {
        $table->removeColumn($column->getName());
        $this->assertFalse($table->hasColumn('Foo'));
    }

    public function testRemoveColumnFixesPositions()
    {
        $table = new Table();
        $col1 = new Column('Foo1');
        $table->addColumn($col1);
        $col2 = new Column('Foo2');
        $table->addColumn($col2);
        $col3 = new Column('Foo3');
        $table->addColumn($col3);
        $this->assertEquals(1, $col1->getPosition());
        $this->assertEquals(2, $col2->getPosition());
        $this->assertEquals(3, $col3->getPosition());
        $this->assertEquals(array(0, 1, 2), array_keys($table->getColumns()));
        $table->removeColumn($col2);
        $this->assertEquals(1, $col1->getPosition());
        $this->assertEquals(2, $col3->getPosition());
        $this->assertEquals(array(0, 1), array_keys($table->getColumns()));
    }

    public function testQualifiedName()
    {
        $table = new Table();
        $table->setSchema("foo");
        $table->setCommonName("bar");
        $this->assertEquals($table->getName(), "bar");
        $this->assertEquals($table->getCommonName(), "bar");
        $database = new Database();
        $database->addTable($table);
        $database->setPlatform(new NoSchemaPlatform());
        $this->assertEquals($table->getName(), "bar");
        $database->setPlatform(new SchemaPlatform());
        $this->assertEquals($table->getName(), "foo.bar");
    }

    public function testRemoveValidatorForColumn()
    {
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $xmlSchema = <<<EOF
<database name="test">
  <table name="table1">
    <column name="id" primaryKey="true" />
    <column name="title1" type="VARCHAR" />
    <validator column="title1">
      <rule name="minLength" value="4" message="Username must be at least 4 characters !" />
    </validator>
  </table>
</database>
EOF;
        $schema = $schemaReader->parseString($xmlSchema);
        $table1 = $schema->getDatabase('test')->getTable('table1');
        $title1Column = $table1->getColumn('title1');
        $this->assertNotNull($title1Column->getValidator());
        $table1->removeValidatorForColumn('title1');
        $this->assertNull($title1Column->getValidator());
    }

    public function testTableNamespaceAcrossDatabase()
    {
        $xmlSchema1 = <<<EOF
<database name="DB1" namespace="NS1">
  <table name="table1">
    <column name="id" primaryKey="true" />
    <column name="title1" type="VARCHAR" />
  </table>
</database>
EOF;
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $schema1 = $schemaReader->parseString($xmlSchema1);
        $xmlSchema2 = <<<EOF
<database name="DB1" namespace="NS2">
  <table name="table2">
    <column name="id" primaryKey="true" />
    <column name="title1" type="VARCHAR" />
  </table>
</database>
EOF;
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $schema2 = $schemaReader->parseString($xmlSchema2);
        $schema1->joinSchemas(array($schema2));
        $this->assertEquals('NS1', $schema1->getDatabase('DB1')->getTable('table1')->getNamespace());
        $this->assertEquals('NS2', $schema1->getDatabase('DB1')->getTable('table2')->getNamespace());
    }

    public function testSetNamespaceSetsPackageWhenBuildPropertySet()
    {
        $xmlSchema = <<<EOF
<database name="DB">
  <table name="table" namespace="NS">
    <column name="id" primaryKey="true" />
    <column name="title1" type="VARCHAR" />
  </table>
</database>
EOF;
        $config = new GeneratorConfig();
        $config->setBuildProperties(array('propel.namespace.autoPackage' => 'true'));
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $schemaReader->setGeneratorConfig($config);
        $table = $schemaReader->parseString($xmlSchema)->getDatabase('DB')->getTable('table');
        $this->assertEquals('NS', $table->getPackage());
    }

    public function testSetNamespaceSetsCompletePackageWhenBuildPropertySet()
    {
        $xmlSchema = <<<EOF
<database name="DB" namespace="NS1">
  <table name="table" namespace="NS2">
    <column name="id" primaryKey="true" />
    <column name="title1" type="VARCHAR" />
  </table>
</database>
EOF;
        $config = new GeneratorConfig();
        $config->setBuildProperties(array('propel.namespace.autoPackage' => 'true'));
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $schemaReader->setGeneratorConfig($config);
        $table = $schemaReader->parseString($xmlSchema)->getDatabase('DB')->getTable('table');
        $this->assertEquals('NS1.NS2', $table->getPackage());
    }

    public function testSetPackageOverridesNamespaceAutoPackage()
    {
        $xmlSchema = <<<EOF
<database name="DB" namespace="NS1">
  <table name="table" namespace="NS2" package="foo">
    <column name="id" primaryKey="true" />
    <column name="title1" type="VARCHAR" />
  </table>
</database>
EOF;
        $config = new GeneratorConfig();
        $config->setBuildProperties(array('propel.namespace.autoPackage' => 'true'));
        $schemaReader = new SchemaReader(new DefaultPlatform());
        $schemaReader->setGeneratorConfig($config);
        $table = $schemaReader->parseString($xmlSchema)->getDatabase('DB')->getTable('table');
        $this->assertEquals('foo', $table->getPackage());
    }

    public function testAppendXmlPackage()
    {
        $xmlSchema = <<<EOF
<?xml version="1.0"?>
<table name="test" package="test/package"/>
EOF;

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;

        $table = new Table('test');
        $table->setPackage('test/package');
        $table->appendXml($doc);

        $xmlstr = trim($doc->saveXML());
        $this->assertSame($xmlSchema, $xmlstr);
    }

    public function testAppendXmlNamespace()
    {
        $xmlSchema = <<<EOF
<?xml version="1.0"?>
<table name="test" namespace="\\testNs"/>
EOF;

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;

        $table = new Table('test');
        $table->setNamespace('\testNs');
        $table->appendXml($doc);

        $xmlstr = trim($doc->saveXML());
        $this->assertSame($xmlSchema, $xmlstr);

        $xmlSchema = <<<EOF
<?xml version="1.0"?>
<table name="test" namespace="\\testNs" package="testPkg"/>
EOF;

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
        $table->setPackage('testPkg');
        $table->appendXml($doc);

        $xmlstr = trim($doc->saveXML());
        $this->assertSame($xmlSchema, $xmlstr);
    }

    public function testAppendXmlNamespaceWithAutoPackage()
    {
        $xmlSchema = <<<EOF
<?xml version="1.0"?>
<table name="test" namespace="\\testNs"/>
EOF;

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;

        $config = new GeneratorConfig();
        $config->setBuildProperties(array('propel.namespace.autoPackage' => 'true'));

        $schema = new Schema();
        $schema->setGeneratorConfig($config);

        $db = new Database('testDb');
        $db->setMappingSchema($schema);

        $table = new Table('test');
        $table->setDatabase($db);
        $table->setNamespace('\testNs');
        $table->appendXml($doc);

        $xmlstr = trim($doc->saveXML());
        $this->assertSame($xmlSchema, $xmlstr);

        $xmlSchema = <<<EOF
<?xml version="1.0"?>
<table name="test" namespace="\\testNs" package="testPkg"/>
EOF;

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
        $table->setPackage('testPkg');
        $table->appendXml($doc);

        $xmlstr = trim($doc->saveXML());
        $this->assertSame($xmlSchema, $xmlstr);
    }

    public function testIsCrossRefAttribute()
    {
        $schemaReader = new SchemaReader();
        $xmlSchema = <<<EOF
<database name="iddb" defaultIdMethod="native">
    <table name="table_native">
        <column name="table_a_id" required="true" primaryKey="true" type="INTEGER" />
        <column name="col_a" type="CHAR" size="5" />
    </table>
    <table name="table_is_cross_ref_true" isCrossRef="true">
        <column name="table_a_id" required="true" primaryKey="true" type="INTEGER" />
        <column name="col_a" type="CHAR" size="5" />
    </table>
    <table name="table_is_cross_ref_false" isCrossRef="false">
        <column name="table_a_id" required="true" primaryKey="true" type="INTEGER" />
        <column name="col_a" type="CHAR" size="5" />
    </table>
</database>
EOF;
        $schema = $schemaReader->parseString($xmlSchema);

        $db = $schema->getDatabase("iddb");

        $table1 = $db->getTable("table_native");
        $this->assertFalse($table1->getIsCrossRef());

        $table2 = $db->getTable("table_is_cross_ref_true");
        $this->assertTrue($table2->getIsCrossRef());

        $table3 = $db->getTable("table_is_cross_ref_false");
        $this->assertFalse($table3->getIsCrossRef());
    }
}