<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Exception\BehaviorNotFoundException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Util\VfsTrait;

/**
 * Unit test suite for Database model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DatabaseTest extends ModelTestCase
{
    use VfsTrait;

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testSetupObject()
    {
        $database = new Database();
        $database->loadMapping([
            'name' => 'bookstore',
            'baseClass' => 'CustomBaseObject',
            'baseQueryClass' => 'CustomBaseQueryObject',
            'defaultIdMethod' => 'native',
            'defaultPhpNamingMethod' => 'underscore',
            'heavyIndexing' => 'true',
            'tablePrefix' => 'acme_',
            'defaultStringFormat' => 'XML',
        ]);

        $this->assertSame('bookstore', $database->getName());
        $this->assertSame('CustomBaseObject', $database->getBaseClass());
        $this->assertSame('CustomBaseQueryObject', $database->getBaseQueryClass());
        $this->assertSame('XML', $database->getDefaultStringFormat());
        $this->assertSame('native', $database->getDefaultIdMethod());
        $this->assertSame('underscore', $database->getDefaultPhpNamingMethod());
        $this->assertSame('acme_', $database->getTablePrefix());
        $this->assertTrue($database->isHeavyIndexing());
        $this->assertTrue($database->getHeavyIndexing());
    }

    /**
     * @return void
     */
    public function testDoFinalization()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
                            ->disableOriginalConstructor()->getMock();

        $schema = $this->getSchemaMock('bookstore', [
            'generator_config' => $config,
        ]);

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getMaxColumnNameLength')
            ->will($this->returnValue(64));
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->with($this->equalTo('TIMESTAMP'))
            ->will($this->returnValue($this->getDomainMock('TIMESTAMP')));

        $database = new Database();
        $database->setPlatform($platform);
        $database->setParentSchema($schema);
        $database->addTable($this->getTableMock('foo'));
        $database->addTable($this->getTableMock('bar'));
        $database->doFinalInitialization();

        $this->assertCount(0, $database->getBehaviors());
        $this->assertSame(2, $database->countTables());
    }

    /**
     * @return void
     */
    public function testSetParentSchema()
    {
        $database = new Database();
        $database->setParentSchema($this->getSchemaMock());

        $this->assertInstanceOf('Propel\Generator\Model\Schema', $database->getParentSchema());
    }

    /**
     * @return void
     */
    public function testAddBehavior()
    {
        $behavior = $this->getBehaviorMock('foo');

        $database = new Database();

        $this->assertInstanceOf('Propel\Generator\Model\Behavior', $database->addBehavior($behavior));
        $this->assertSame($behavior, $database->getBehavior('foo'));
        $this->assertTrue($database->hasBehavior('foo'));
    }

    /**
     * @return void
     */
    public function testCantAddInvalidBehavior()
    {
        $this->expectException(BehaviorNotFoundException::class);

        $database = new Database();
        $behavior = $database->addBehavior(['name' => 'foo']);
    }

    /**
     * @dataProvider provideBehaviors
     *
     * @return void
     */
    public function testAddArrayBehavior($name, $class)
    {
        $type = sprintf(
            'Propel\Generator\Behavior\%s\%sBehavior',
            $class,
            $class
        );

        $database = new Database();
        $behavior = $database->addBehavior(['name' => $name]);

        $this->assertInstanceOf($type, $behavior);
    }

    /**
     * @return void
     */
    public function testGetNextTableBehavior()
    {
        $table1 = $this->getTableMock('books', [
        'behaviors' => [
            $this->getBehaviorMock('foo', [
                'is_table_modified' => false,
                'modification_order' => 2,
            ]),
            $this->getBehaviorMock('bar', [
                'is_table_modified' => false,
                'modification_order' => 1,
            ]),
            $this->getBehaviorMock('baz', ['is_table_modified' => true]),
        ]]);

        $table2 = $this->getTableMock('authors', [
        'behaviors' => [
            $this->getBehaviorMock('mix', [
                'is_table_modified' => false,
                'modification_order' => 1,
            ]),
        ]]);

        $database = new Database();
        $database->addTable($table1);
        $database->addTable($table2);

        $behavior = $database->getNextTableBehavior();

        $this->assertInstanceOf('Propel\Generator\Model\Behavior', $behavior);
        $this->assertSame('bar', $behavior->getName());
    }

    /**
     * @return void
     */
    public function testCantGetNextTableBehavior()
    {
        $table1 = $this->getTableMock('books', [
        'behaviors' => [
            $this->getBehaviorMock('foo', ['is_table_modified' => true]),
        ]]);

        $database = new Database();
        $database->addTable($table1);

        $behavior = $database->getNextTableBehavior();

        $this->assertNull($database->getNextTableBehavior());
    }

    /**
     * @return void
     */
    public function testCantGetTable()
    {
        $database = new Database();

        $this->assertNull($database->getTable('foo'));
        $this->assertNull($database->getTableByPhpName('foo'));
        $this->assertFalse($database->hasTable('foo'));
        $this->assertFalse($database->hasTableByPhpName('foo'));
    }

    /**
     * @return void
     */
    public function testAddNamespacedTable()
    {
        $table = $this->getTableMock('books', ['namespace' => '\Acme']);

        $database = new Database();
        $database->addTable($table);

        $this->assertTrue($database->hasTable('books'));
    }

    /**
     * @return void
     */
    public function testAddTable()
    {
        $table = $this->getTableMock('books', [
            'namespace' => 'Acme\Model',
        ]);

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

    /**
     * @return void
     */
    public function testAddArrayTable()
    {
        $database = new Database();
        $database->addTable(['name' => 'books']);
        $database->addTable(['name' => 'authors']);
        $database->addTable(['name' => 'categories', 'skipSql' => 'true']);
        $database->addTable(['name' => 'publishers', 'readOnly' => 'true']);

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

    /**
     * @return void
     */
    public function testAddSameTableTwice()
    {
        $this->expectException(EngineException::class);

        $database = new Database();
        $database->addTable(['name' => 'authors']);
        $database->addTable(['name' => 'authors']);
    }

    public function provideBehaviors()
    {
        return [
            ['aggregate_column', 'AggregateColumn'],
            ['auto_add_pk', 'AutoAddPk'],
            ['concrete_inheritance', 'ConcreteInheritance'],
            ['delegate', 'Delegate'],
            ['nested_set', 'NestedSet'],
            ['query_cache', 'QueryCache'],
            ['sluggable', 'Sluggable'],
            ['sortable', 'Sortable'],
            ['timestampable', 'Timestampable'],
            ['validate', 'Validate'],
            ['versionable', 'Versionable'],
        ];
    }

    /**
     * @return void
     */
    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = $this->getSchemaMock('bookstore', [
            'generator_config' => $config,
        ]);

        $database = new Database();
        $database->setParentSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Config\GeneratorConfig', $database->getGeneratorConfig());
    }

    /**
     * @return void
     */
    public function testGetBuildProperty()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $config
            ->expects($this->once())
            ->method('getConfigProperty')
            ->with($this->equalTo('generator.database.adapters.mysql.tableType'))
            ->will($this->returnValue('InnoDB'));

        $schema = $this->getSchemaMock('bookstore', [
            'generator_config' => $config,
        ]);

        $database = new Database();
        $database->setParentSchema($schema);

        $this->assertSame('InnoDB', $database->getBuildProperty('generator.database.adapters.mysql.tableType'));
    }

    /**
     * @return void
     */
    public function testAddArrayDomain()
    {
        $copiedDomain = $this->getDomainMock('original');

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getDomainForType')
            ->will($this->returnValue($copiedDomain));

        $database = new Database();
        $database->setPlatform($platform);

        $domain1 = $database->addDomain(['name' => 'foo']);

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $domain1);
        $this->assertSame($domain1, $database->getDomain('foo'));
        $this->assertNull($database->getDomain('baz'));
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testSetInvalidDefaultStringFormat()
    {
        $this->expectException(InvalidArgumentException::class);

        $database = new Database();
        $database->setDefaultStringFormat('FOO');
    }

    /**
     * @dataProvider provideSupportedFormats
     *
     * @return void
     */
    public function testSetDefaultStringFormat($format)
    {
        $database = new Database();
        $database->setDefaultStringFormat($format);

        $this->assertSame(strtoupper($format), $database->getDefaultStringFormat());
    }

    public function provideSupportedFormats()
    {
        return [
            ['xml'],
            ['yaml'],
            ['json'],
            ['csv'],
        ];
    }

    /**
     * @return void
     */
    public function testSetHeavyIndexing()
    {
        $database = new Database();
        $database->setHeavyIndexing(true);

        $this->assertTrue($database->isHeavyIndexing());
        $this->assertTrue($database->getHeavyIndexing());
    }

    /**
     * return array
     */
    public function baseClassDataProvider(): array
    {
        return [
            // [<Class name>, <Expected class name>, <message>]]
            ['\CustomBaseQueryObject', '\CustomBaseQueryObject', 'Setter should set base query class'],
            ['CustomBaseQueryObject', '\CustomBaseQueryObject', 'Setter should set absolute namespace of base query class'],
        ];
    }

    /**
     * @dataProvider baseClassDataProvider
     *
     * @return void
     */
    public function testSetBaseClass(string $className, string $expectedClassName, string $message)
    {
        $database = new Database();
        $database->setBaseClass($className);

        $this->assertSame($expectedClassName, $database->getBaseClass(), $message);
    }

    /**
     * @dataProvider baseClassDataProvider
     *
     * @return void
     */
    public function testSetBaseQueryClass(string $className, string $expectedClassName, string $message)
    {
        $database = new Database();
        $database->setBaseQueryClass($className);

        $this->assertSame($expectedClassName, $database->getBaseQueryClass(), $message);
    }

    /**
     * @return void
     */
    public function testSetDefaultIdMethod()
    {
        $database = new Database();
        $database->setDefaultIdMethod('native');

        $this->assertSame('native', $database->getDefaultIdMethod());
    }

    /**
     * @return void
     */
    public function testSetDefaultPhpNamingMethodStrategy()
    {
        $database = new Database();
        $database->setDefaultPhpNamingMethod('foo');

        $this->assertSame('foo', $database->getDefaultPhpNamingMethod());
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testAutoNamespaceToDatabaseSchemaName()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  generator:
      schema:
          autoNamespace: true
EOF;

        $configFile = $this->newFile('propel.yml', $yamlConf);

        $schema = 'TestSchema';
        $config = new GeneratorConfig($configFile->url());
        $platform = new MysqlPlatform();
        $parentSchema = new Schema($platform);
        $parentSchema->setGeneratorConfig($config);

        $db = new Database();
        $db->setPlatform($platform);
        $db->setParentSchema($parentSchema);
        $db->setSchema($schema);

        $this->assertEquals($schema, $db->getNamespace());
    }

    /**
     * @return array
     */
    public function combinedNamespaceDataProvider(): array
    {
        // [<Database namespace>, <Table namespace>, <Combined namespace>, <Message>]
        return [
            [null, null, null, 'No namespaces should leave table namespace empty'],
            ['Le\\Database', null, 'Le\\Database', 'No table namespace should use database namespace'],
            [null, 'Il\\Table', 'Il\\Table', 'No database namespace should result in unchanged table namespace'],
            ['Le\\Database', '\\Il\\Table', 'Il\\Table', 'Absolute table namespace should superseed database namespace'],
            ['Le\\Database', 'Il\\Table', 'Le\\Database\\Il\\Table', 'Relative table namespace should be apended to database namespace'],
            ['Le\\Database', 'Le\\Database', 'Le\\Database\\Le\\Database', 'Same relative namespace on database and table should be doubled'],
            ['Le\\Database', '\\Le\\Database', 'Le\\Database', 'Same absolute namespace on database and table should be used only once (as all absolute namespaces)'],
        ];
    }

    /**
     * @dataProvider combinedNamespaceDataProvider
     *
     * @param string|null $databaseNamespace
     * @param string|null $tableNamespace
     * @param string|null $expectedNamespace
     * @param string $message
     *
     * @return void
     */
    public function testCombineNamespace($databaseNamespace, $tableNamespace, $expectedNamespace, $message)
    {
        $database = new Database();

        if ($databaseNamespace !== null) {
            $database->setNamespace($databaseNamespace);
        }

        $table = new Table('');
        if ($tableNamespace !== null) {
            $table->setNamespace($tableNamespace);
        }

        $database->addTable($table);
        $combinedNamespace = $table->getNamespace();

        $this->assertEquals($expectedNamespace, $combinedNamespace, $message);
    }
}
