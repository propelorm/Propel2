<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PgsqlPlatform;

/**
 * Unit test suite for Database model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DatabaseTest extends ModelTestCase
{
    public function testCreateNewDatabase()
    {
        $database = new Database('bookstore');

        $this->assertSame('bookstore', $database->getName());
        $this->assertSame('YAML', $database->getDefaultStringFormat());
        $this->assertSame('native', $database->getDefaultIdMethod());
        $this->assertSame('underscore', $database->getDefaultPhpNamingMethod());
        $this->assertEmpty($database->getTablePrefix());
        $this->assertNull($database->getParentSchema());
        $this->assertNull($database->getDomain('BOOLEAN'));
        $this->assertNull($database->getGeneratorConfig());
        $this->assertCount(0, $database->getTables());
        $this->assertSame(0, $database->countTables());
        $this->assertFalse($database->isHeavyIndexing());
        $this->assertFalse($database->getHeavyIndexing());
        $this->assertFalse($database->hasTableByPhpName('foo'));
        $this->assertNull($database->getTableByPhpName('foo'));
        $this->assertFalse($database->hasBehavior('foo'));
        $this->assertNull($database->getBehavior('foo'));
    }

    public function testSetupObject()
    {
        $database = new Database();
        $database->loadMapping(array(
            'name'                   => 'bookstore',
            'baseClass'              => 'CustomBaseObject',
            'defaultIdMethod'        => 'native',
            'defaultPhpNamingMethod' => 'underscore',
            'heavyIndexing'          => 'true',
            'tablePrefix'            => 'acme_',
            'defaultStringFormat'    => 'XML',
        ));

        $this->assertSame('bookstore', $database->getName());
        $this->assertSame('CustomBaseObject', $database->getBaseClass());
        $this->assertSame('XML', $database->getDefaultStringFormat());
        $this->assertSame('native', $database->getDefaultIdMethod());
        $this->assertSame('underscore', $database->getDefaultPhpNamingMethod());
        $this->assertSame('acme_', $database->getTablePrefix());
        $this->assertTrue($database->isHeavyIndexing());
        $this->assertTrue($database->getHeavyIndexing());
    }

    public function testDoFinalization()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
                            ->disableOriginalConstructor()->getMock();

        $schema = $this->getSchemaMock('bookstore', array(
            'generator_config' => $config
        ));

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getMaxColumnNameLength')
            ->will($this->returnValue(64))
        ;
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->with($this->equalTo('TIMESTAMP'))
            ->will($this->returnValue($this->getDomainMock('TIMESTAMP')))
        ;

        $database = new Database();
        $database->setPlatform($platform);
        $database->setParentSchema($schema);
        $database->addTable($this->getTableMock('foo'));
        $database->addTable($this->getTableMock('bar'));
        $database->doFinalInitialization();

        $this->assertCount(0, $database->getBehaviors());
        $this->assertSame(2, $database->countTables());
    }

    public function testSetParentSchema()
    {
        $database = new Database();
        $database->setParentSchema($this->getSchemaMock());

        $this->assertInstanceOf('Propel\Generator\Model\Schema', $database->getParentSchema());
    }

    public function testAddBehavior()
    {
        $behavior = $this->getBehaviorMock('foo');

        $database = new Database();

        $this->assertInstanceOf('Propel\Generator\Model\Behavior', $database->addBehavior($behavior));
        $this->assertSame($behavior, $database->getBehavior('foo'));
        $this->assertTrue($database->hasBehavior('foo'));
    }

    public function testCantAddInvalidBehavior()
    {
        $this->setExpectedException('Propel\Generator\Exception\BehaviorNotFoundException');

        $database = new Database();
        $behavior = $database->addBehavior(array('name' => 'foo'));
    }

    /**
     * @dataProvider provideBehaviors
     *
     */
    public function testAddArrayBehavior($name, $class)
    {
        $type = sprintf(
            'Propel\Generator\Behavior\%s\%sBehavior',
            $class,
            $class
        );

        $database = new Database();
        $behavior = $database->addBehavior(array('name' => $name));

        $this->assertInstanceOf($type, $behavior);
    }

    public function testGetNextTableBehavior()
    {
        $table1 = $this->getTableMock('books', array('behaviors' => array(
            $this->getBehaviorMock('foo', array(
                'is_table_modified'  => false,
                'modification_order' => 2,
            )),
            $this->getBehaviorMock('bar', array(
                'is_table_modified'  => false,
                'modification_order' => 1,
            )),
            $this->getBehaviorMock('baz', array('is_table_modified'  => true)),
        )));

        $table2 = $this->getTableMock('authors', array('behaviors' => array(
            $this->getBehaviorMock('mix', array(
                'is_table_modified'  => false,
                'modification_order' => 1,
            )),
        )));

        $database = new Database();
        $database->addTable($table1);
        $database->addTable($table2);

        $behavior = $database->getNextTableBehavior();

        $this->assertInstanceOf('Propel\Generator\Model\Behavior', $behavior);
        $this->assertSame('bar', $behavior->getName());
    }

    public function testCantGetNextTableBehavior()
    {
        $table1 = $this->getTableMock('books', array('behaviors' => array(
            $this->getBehaviorMock('foo', array('is_table_modified' => true)),
        )));

        $database = new Database();
        $database->addTable($table1);

        $behavior = $database->getNextTableBehavior();

        $this->assertNull($database->getNextTableBehavior());
    }

    public function testCantGetTable()
    {
        $database = new Database();

        $this->assertNull($database->getTable('foo'));
        $this->assertNull($database->getTableByPhpName('foo'));
        $this->assertFalse($database->hasTable('foo'));
        $this->assertFalse($database->hasTableByPhpName('foo'));
    }

    public function testAddNamespacedTable()
    {
        $table = $this->getTableMock('books', array('namespace' => '\Acme'));

        $database = new Database();
        $database->addTable($table);

        $this->assertTrue($database->hasTable('books'));
    }

    public function testAddTable()
    {
        $table = $this->getTableMock('books', array(
            'namespace' => 'Acme\Model',
        ));

        $database = new Database();
        $database->setPackage('acme');
        $database->setNamespace('Acme\Model');
        $database->addTable($table);

        $this->assertSame(1, $database->countTables());
        $this->assertCount(1, $database->getTablesForSql());

        $this->assertTrue($database->hasTable('books'));
        $this->assertTrue($database->hasTable('books', true));
        $this->assertFalse($database->hasTable('BOOKS'));
        $this->assertTrue($database->hasTableByPhpName('Books'));
        $this->assertSame($table, $database->getTable('books'));
        $this->assertSame($table, $database->getTableByPhpName('Books'));

    }

    public function testAddArrayTable()
    {
        $database = new Database();
        $database->addTable(array('name' => 'books'));
        $database->addTable(array('name' => 'authors'));
        $database->addTable(array('name' => 'categories', 'skipSql' => 'true'));
        $database->addTable(array('name' => 'publishers', 'readOnly' => 'true'));

        $this->assertTrue($database->hasTable('books'));
        $this->assertTrue($database->hasTable('books', true));
        $this->assertFalse($database->hasTable('BOOKS'));
        $this->assertTrue($database->hasTableByPhpName('Books'));
        $this->assertInstanceOf('Propel\Generator\Model\Table', $database->getTable('books'));
        $this->assertInstanceOf('Propel\Generator\Model\Table', $database->getTableByPhpName('Books'));

        // 3 tables because read only table is excluded from the count
        $this->assertSame(3, $database->countTables());

        // 3 tables because skipped sql table is excluded from the count
        $this->assertCount(3, $database->getTablesForSql());
    }

    public function testAddSameTableTwice()
    {
        $this->setExpectedException('Propel\Generator\Exception\EngineException');

        $database = new Database();
        $database->addTable(array('name' => 'authors'));
        $database->addTable(array('name' => 'authors'));
    }

    public function provideBehaviors()
    {
        return array(
            array('aggregate_column', 'AggregateColumn'),
            array('auto_add_pk', 'AutoAddPk'),
            array('concrete_inheritance', 'ConcreteInheritance'),
            array('delegate', 'Delegate'),
            array('nested_set', 'NestedSet'),
            array('query_cache', 'QueryCache'),
            array('sluggable', 'Sluggable'),
            array('sortable', 'Sortable'),
            array('timestampable', 'Timestampable'),
            array('validate', 'Validate'),
            array('versionable', 'Versionable'),
        );
    }

    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = $this->getSchemaMock('bookstore', array(
            'generator_config' => $config
        ));

        $database = new Database();
        $database->setParentSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Config\GeneratorConfig', $database->getGeneratorConfig());
    }

    public function testGetBuildProperty()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $config
            ->expects($this->once())
            ->method('getConfigProperty')
            ->with($this->equalTo('generator.database.adapters.mysql.tableType'))
            ->will($this->returnValue('InnoDB'))
        ;

        $schema = $this->getSchemaMock('bookstore', array(
            'generator_config' => $config
        ));

        $database = new Database();
        $database->setParentSchema($schema);

        $this->assertSame('InnoDB', $database->getBuildProperty('generator.database.adapters.mysql.tableType'));
    }

    public function testAddArrayDomain()
    {
        $copiedDomain = $this->getDomainMock('original');

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getDomainForType')
            ->will($this->returnValue($copiedDomain))
        ;

        $database = new Database();
        $database->setPlatform($platform);

        $domain1  = $database->addDomain(array('name' => 'foo'));

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $domain1);
        $this->assertSame($domain1, $database->getDomain('foo'));
        $this->assertNull($database->getDomain('baz'));
    }

    public function testAddDomain()
    {
        $domain1 = $this->getDomainMock('foo');
        $domain2 = $this->getDomainMock('bar');

        $database = new Database();
        $database->addDomain($domain1);
        $database->addDomain($domain2);

        $this->assertSame($domain1, $database->getDomain('foo'));
        $this->assertSame($domain2, $database->getDomain('bar'));
        $this->assertNull($database->getDomain('baz'));
    }

    public function testSetInvalidDefaultStringFormat()
    {
        $this->setExpectedException('Propel\Generator\Exception\InvalidArgumentException');

        $database = new Database();
        $database->setDefaultStringFormat('FOO');
    }

    /**
     * @dataProvider provideSupportedFormats
     *
     */
    public function testSetDefaultStringFormat($format)
    {
        $database = new Database();
        $database->setDefaultStringFormat($format);

        $this->assertSame(strtoupper($format), $database->getDefaultStringFormat());
    }

    public function provideSupportedFormats()
    {
        return array(
            array('xml'),
            array('yaml'),
            array('json'),
            array('csv'),
        );
    }

    public function testSetHeavyIndexing()
    {
        $database = new Database();
        $database->setHeavyIndexing(true);

        $this->assertTrue($database->isHeavyIndexing());
        $this->assertTrue($database->getHeavyIndexing());
    }

    public function testSetBaseClasses()
    {
        $database = new Database();
        $database->setBaseClass('CustomBaseObject');

        $this->assertSame('CustomBaseObject', $database->getBaseClass());
    }

    public function testSetDefaultIdMethod()
    {
        $database = new Database();
        $database->setDefaultIdMethod('native');

        $this->assertSame('native', $database->getDefaultIdMethod());
    }

    public function testSetDefaultPhpNamingMethodStrategy()
    {
        $database = new Database();
        $database->setDefaultPhpNamingMethod('foo');

        $this->assertSame('foo', $database->getDefaultPhpNamingMethod());
    }

    public function testAddTableWithSameNameOnDifferentSchema()
    {
        $db = new Database();
        $db->setPlatform(new PgsqlPlatform());

        $t1 = new Table('t1');
        $db->addTable($t1);
        $this->assertEquals('t1', $t1->getName());

        $t1b = new Table('t1');
        $t1b->setSchema('bis');
        $db->addTable($t1b);
        $this->assertEquals('bis.t1', $t1b->getName());
    }
}