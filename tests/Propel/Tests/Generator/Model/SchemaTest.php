<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Schema;

/**
 * Unit test suite for the Schema model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class SchemaTest extends ModelTestCase
{
    public function testCreateNewSchema()
    {
        $schema = new Schema();
        $this->assertCount(0, $schema->getDatabases());
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    public function testJoinMultipleSchemasWithSameEntityTwice()
    {
        $booksEntity = $this->getEntityMock('Books');

        $database1 = $this->getDatabaseMock('bookstore');
        $database1
            ->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue(array($booksEntity)))
        ;
        $database1
            ->expects($this->any())
            ->method('hasEntityByFullClassName')
            ->will($this->returnValue(true))
        ;


        $database2 = $this->getDatabaseMock('bookstore');
        $database2
            ->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue(array(
                $booksEntity,
                $this->getEntityMock('Authors'),
            )))
        ;
        $database2
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('Books'))
            ->will($this->returnValue(true))
        ;
        $database2
            ->expects($this->any())
            ->method('hasEntityByFullClassName')
            ->will($this->returnValue(true))
        ;

        $subSchema1 = new Schema();
        $subSchema1->addDatabase($database1);

        $schema = new Schema();
        $schema->addDatabase($database2);

        $this->setExpectedException('Propel\Generator\Exception\EngineException');

        $schema->joinSchemas(array($subSchema1));
    }

    public function testJoinMultipleSchemasWithSameDatabase()
    {
        $behavior = $this->getBehaviorMock('sluggable');
        $behavior
            ->expects($this->any())
            ->method('hasBehavior')
            ->will($this->returnValue(false))
        ;

        $entities[] = $this->getEntityMock('Books');
        $entities[] = $this->getEntityMock('Authors');

        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('countEntities')
            ->will($this->returnValue(count($entities)))
        ;
        $database
            ->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue($entities))
        ;
        $database
            ->expects($this->any())
            ->method('getBehaviors')
            ->will($this->returnValue(array($behavior)))
        ;

        $subSchema1 = new Schema();
        $subSchema1->addDatabase($database);

        $schema = new Schema();
        $schema->addDatabase($database);

        $schema->joinSchemas(array($subSchema1));

        $this->assertCount(1, $schema->getDatabases(false));
        $this->assertSame(2, $schema->countEntities());
    }

    public function testJoinMultipleSchemasWithoutEntities()
    {
        $subSchema1 = new Schema();
        $subSchema1->addDatabase(array('name' => 'bookstore'));
        $subSchema1->addDatabase(array('name' => 'shoestore'));

        $subSchema2 = new Schema();
        $subSchema2->addDatabase(array('name' => 'surfstore'));

        $schema = new Schema();
        $schema->addDatabase(array('name' => 'skatestore'));

        $schema->joinSchemas(array($subSchema1, $subSchema2));

        $this->assertCount(4, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertTrue($schema->hasDatabase('surfstore'));
        $this->assertTrue($schema->hasDatabase('skatestore'));
    }

    public function testGetFirstDatabase()
    {
        $schema = new Schema();
        $db = $schema->addDatabase(array('name' => 'bookstore'));

        $this->assertSame($db, $schema->getDatabase());
    }

    public function testGetDatabase()
    {
        $schema = new Schema();
        $db1 = $schema->addDatabase(array('name' => 'bookstore'));
        $db2 = $schema->addDatabase(array('name' => 'shoestore'));

        $this->assertSame($db2, $schema->getDatabase('shoestore', false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
    }

    public function testGetNoDatabase()
    {
        $schema = new Schema();

        $this->assertNull($schema->getDatabase('shoestore', false));
    }

    public function testAddArrayDatabase()
    {
        $config = $this
            ->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $config
            ->expects($this->any())
            ->method('getConfiguredPlatform')
            ->with($this->equalTo(null), $this->equalTo('bookstore'))
            ->will($this->returnValue($this->getPlatformMock()))
        ;

        $schema = new Schema();
        $schema->setGeneratorConfig($config);
        $schema->addDatabase(array('name' => 'bookstore'));

        $this->assertCount(1, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    public function testAddArrayDatabaseWithDefaultPlatform()
    {
        $schema = new Schema();
        $schema->addDatabase(array('name' => 'bookstore'));

        $this->assertCount(1, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    public function testAddDatabase()
    {
        $database1 = $this->getDatabaseMock('bookstore');
        $database2 = $this->getDatabaseMock('shoestore');
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = new Schema();
        $schema->setGeneratorConfig($config);
        $schema->addDatabase($database1);
        $schema->addDatabase($database2);

        $this->assertCount(2, $schema->getDatabases(false));
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertTrue($schema->hasMultipleDatabases());
    }

    public function testSetName()
    {
        $schema = new Schema();
        $schema->setName('bookstore-schema');

        $this->assertSame('bookstore-schema', $schema->getName());
        $this->assertSame('bookstore', $schema->getShortName());
    }

    public function testSetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = new Schema();
        $schema->setGeneratorConfig($config);

        $this->assertSame($config, $schema->getGeneratorConfig());
    }
}
