<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Schema;

/**
 * Unit test suite for the Schema model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class SchemaTest extends ModelTestCase
{
    /**
     * @return void
     */
    public function testCreateNewSchema()
    {
        $platform = $this->getPlatformMock();

        $schema = new Schema($platform);
        $this->assertSame($platform, $schema->getPlatform());
        $this->assertCount(0, $schema->getDatabases());
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    /**
     * @return void
     */
    public function testJoinMultipleSchemasWithSameTableTwice()
    {
        $booksTable = $this->getTableMock('books');

        $database1 = $this->getDatabaseMock('bookstore');
        $database1
            ->expects($this->any())
            ->method('getTables')
            ->will($this->returnValue([$booksTable]));

        $database2 = $this->getDatabaseMock('bookstore');
        $database2
            ->expects($this->any())
            ->method('getTables')
            ->will($this->returnValue([
                $booksTable,
                $this->getTableMock('authors'),
            ]));
        $database2
            ->expects($this->any())
            ->method('getTable')
            ->with($this->equalTo('books'))
            ->will($this->returnValue($booksTable));

        $subSchema1 = new Schema($this->getPlatformMock());
        $subSchema1->addDatabase($database1);

        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase($database2);

        $this->expectException(EngineException::class);

        $schema->joinSchemas([$subSchema1]);
    }

    /**
     * @return void
     */
    public function testJoinMultipleSchemasWithSameDatabase()
    {
        $behavior = $this->getBehaviorMock('sluggable');

        $tables[] = $this->getTableMock('books');
        $tables[] = $this->getTableMock('authors');

        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('countTables')
            ->will($this->returnValue(count($tables)));
        $database
            ->expects($this->any())
            ->method('getTables')
            ->will($this->returnValue($tables));
        $database
            ->expects($this->any())
            ->method('getBehaviors')
            ->will($this->returnValue([$behavior]));

        $subSchema1 = new Schema($this->getPlatformMock());
        $subSchema1->addDatabase($database);

        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase($database);

        $schema->joinSchemas([$subSchema1]);

        $this->assertCount(1, $schema->getDatabases(false));
        $this->assertSame(2, $schema->countTables());
    }

    /**
     * @return void
     */
    public function testJoinMultipleSchemasWithoutTables()
    {
        $subSchema1 = new Schema($this->getPlatformMock());
        $subSchema1->addDatabase(['name' => 'bookstore']);
        $subSchema1->addDatabase(['name' => 'shoestore']);

        $subSchema2 = new Schema($this->getPlatformMock());
        $subSchema2->addDatabase(['name' => 'surfstore']);

        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase(['name' => 'skatestore']);

        $schema->joinSchemas([$subSchema1, $subSchema2]);

        $this->assertCount(4, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertTrue($schema->hasDatabase('surfstore'));
        $this->assertTrue($schema->hasDatabase('skatestore'));
    }

    /**
     * @return void
     */
    public function testGetFirstDatabase()
    {
        $schema = new Schema($this->getPlatformMock());
        $db = $schema->addDatabase(['name' => 'bookstore']);

        $this->assertSame($db, $schema->getDatabase());
    }

    /**
     * @return void
     */
    public function testGetDatabase()
    {
        $schema = new Schema($this->getPlatformMock());
        $db1 = $schema->addDatabase(['name' => 'bookstore']);
        $db2 = $schema->addDatabase(['name' => 'shoestore']);

        $this->assertSame($db2, $schema->getDatabase('shoestore', false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
    }

    /**
     * @return void
     */
    public function testGetNoDatabase()
    {
        $schema = new Schema($this->getPlatformMock());

        $this->assertNull($schema->getDatabase('shoestore', false));
    }

    /**
     * @return void
     */
    public function testAddArrayDatabase()
    {
        $config = $this
            ->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $config
            ->expects($this->any())
            ->method('getConfiguredPlatform')
            ->with($this->equalTo(null), $this->equalTo('bookstore'))
            ->will($this->returnValue($this->getPlatformMock()));

        $schema = new Schema($this->getPlatformMock());
        $schema->setGeneratorConfig($config);
        $schema->addDatabase(['name' => 'bookstore']);

        $this->assertCount(1, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    /**
     * @return void
     */
    public function testAddArrayDatabaseWithDefaultPlatform()
    {
        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase(['name' => 'bookstore']);

        $this->assertCount(1, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    /**
     * @return void
     */
    public function testAddDatabase()
    {
        $database1 = $this->getDatabaseMock('bookstore');
        $database2 = $this->getDatabaseMock('shoestore');
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = new Schema($this->getPlatformMock());
        $schema->setGeneratorConfig($config);
        $schema->addDatabase($database1);
        $schema->addDatabase($database2);

        $this->assertCount(2, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertTrue($schema->hasMultipleDatabases());
    }

    /**
     * @return void
     */
    public function testSetName()
    {
        $schema = new Schema();
        $schema->setName('bookstore-schema');

        $this->assertSame('bookstore-schema', $schema->getName());
        $this->assertSame('bookstore', $schema->getShortName());
    }

    /**
     * @return void
     */
    public function testSetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = new Schema();
        $schema->setGeneratorConfig($config);

        $this->assertSame($config, $schema->getGeneratorConfig());
    }
}
