<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Configuration;
use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeEntityMap;

use Propel\Runtime\Map\FieldMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Map\Exception\ForeignKeyNotFoundException;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for EntityMap.
 *
 * @author François Zaninotto
 */
class FieldMapTest extends TestCaseFixtures
{
    /**
     * @var DatabaseMap
     */
    protected $databaseMap;

    /**
     * @var string
     */
    protected $fieldName;
    /**
     * @var EntityMap
     */
    protected $tmap;

    /**
     * @var FieldMap
     */
    protected $cmap;

    protected function setUp()
    {
        parent::setUp();
        $this->dmap = new DatabaseMap('foodb');
        Configuration::getCurrentConfiguration()->registerDatabase($this->dmap);
        $this->tmap = $this->getMockForAbstractClass(EntityMap::class, ['foo', 'foodb', Configuration::getCurrentConfiguration()]);
        $this->fieldName = 'barName';
        $this->cmap = new FieldMap($this->fieldName, $this->tmap);
    }

    protected function tearDown()
    {
        // nothing to do for now
        parent::tearDown();
    }

    public function testConstructor()
    {
        $this->assertEquals($this->fieldName, $this->cmap->getName(), 'constructor sets the column name');
        $this->assertEquals($this->tmap, $this->cmap->getEntity(), 'Constructor sets the table map');
        $this->assertNull($this->cmap->getType(), 'A new column map has no type');
    }

    public function testPhpName()
    {
        $this->assertEquals($this->fieldName, $this->cmap->getName(), 'name is empty until set');
        $this->assertEquals('bar_name', $this->cmap->getColumnName());
        $this->cmap->setName('fooBar');
        $this->assertEquals('fooBar', $this->cmap->getName(), '');
        $this->assertEquals('foo_bar', $this->cmap->getColumnName());
    }

    public function testType()
    {
        $this->assertNull($this->cmap->getType(), 'type is empty until set');
        $this->cmap->setType('FooBar');
        $this->assertEquals('FooBar', $this->cmap->getType(), 'type is set by setType()');
    }

    public function tesSize()
    {
        $this->assertEquals(0, $this->cmap->getSize(), 'size is empty until set');
        $this->cmap->setSize(123);
        $this->assertEquals(123, $this->cmap->getSize(), 'size is set by setSize()');
    }

    public function testPrimaryKey()
    {
        $this->assertFalse($this->cmap->isPrimaryKey(), 'primaryKey is false by default');
        $this->cmap->setPrimaryKey(true);
        $this->assertTrue($this->cmap->isPrimaryKey(), 'primaryKey is set by setPrimaryKey()');
    }

    public function testNotNull()
    {
        $this->assertFalse($this->cmap->isNotNull(), 'notNull is false by default');
        $this->cmap->setNotNull(true);
        $this->assertTrue($this->cmap->isNotNull(), 'notNull is set by setPrimaryKey()');
    }

    public function testDefaultValue()
    {
        $this->assertNull($this->cmap->getDefaultValue(), 'defaultValue is empty until set');
        $this->cmap->setDefaultValue('FooBar');
        $this->assertEquals('FooBar', $this->cmap->getDefaultValue(), 'defaultValue is set by setDefaultValue()');
    }

    public function testGetForeignKey()
    {
        $this->assertFalse($this->cmap->isForeignKey(), 'foreignKey is false by default');
        try {
            $this->cmap->getRelatedEntity();
            $this->fail('getRelatedEntity throws an exception when called on a column with no foreign key');
        } catch (ForeignKeyNotFoundException $e) {
            $this->assertTrue(true, 'getRelatedEntity throws an exception when called on a column with no foreign key');
        }
        try {
            $this->cmap->getRelatedField();
            $this->fail('getRelatedField throws an exception when called on a column with no foreign key');
        } catch (ForeignKeyNotFoundException $e) {
            $this->assertTrue(true, 'getRelatedField throws an exception when called on a column with no foreign key');
        }
        $relatedTmap = $this->getMockForAbstractClass(EntityMap::class, ['foo2', 'foodb', Configuration::getCurrentConfiguration()]);
        // required to let the database map use the foreign EntityMap
        $relatedCmap = $relatedTmap->addField('BAR2', 'Bar2', 'INTEGER');
        $this->cmap->setForeignKey('foo2', 'BAR2');
        $this->assertTrue($this->cmap->isForeignKey(), 'foreignKey is true after setting the foreign key via setForeignKey()');
        $this->assertEquals($relatedTmap, $this->cmap->getRelatedEntity(), 'getRelatedEntity returns the related EntityMap object');
        $this->assertEquals($relatedCmap, $this->cmap->getRelatedField(), 'getRelatedField returns the related FieldMap object');
    }

    public function testGetRelation()
    {
        $bookEntity = BookEntityMap::getEntityMap();
        $titleField = $bookEntity->getField('TITLE');
        $this->assertNull($titleField->getRelation(), 'getRelation() returns null for non-foreign key columns');
        $publisherField = $bookEntity->getField('publisherId');
        $this->assertEquals($publisherField->getRelation(), $bookEntity->getRelation('publisher'), 'getRelation() returns the RelationMap object for this foreign key');
        $bookstoreEntity = BookstoreEmployeeEntityMap::getEntityMap();
        $supervisorField = $bookstoreEntity->getField('supervisorId');
        $this->assertEquals($supervisorField->getRelation(), $supervisorField->getRelation('supervisor'), 'getRelation() returns the RelationMap object even whit ha specific refPhpName');

    }

    public function testNormalizeName()
    {
        $this->assertEquals('', FieldMap::normalizeName(''), 'normalizeFieldName() returns an empty string when passed an empty string');
        $this->assertEquals('bar', FieldMap::normalizeName('bar'), 'normalizeFieldName() uppercases the input');
        $this->assertEquals('bar_baz', FieldMap::normalizeName('bar_baz'), 'normalizeFieldName() does not mind underscores');
        $this->assertEquals('BAR', FieldMap::normalizeName('FOO.BAR'), 'normalizeFieldName() removes table prefix');
        $this->assertEquals('BAR', FieldMap::normalizeName('BAR'), 'normalizeFieldName() leaves normalized column names unchanged');
        $this->assertEquals('bar_baz', FieldMap::normalizeName('foo.bar_baz'), 'normalizeFieldName() can do all the above at the same time');
    }

    public function testIsPrimaryString()
    {
        $bookEntity = BookEntityMap::getEntityMap();
        $idField = $bookEntity->getField('ID');
        $titleField = $bookEntity->getField('TITLE');
        $isbnField = $bookEntity->getField('ISBN');

        $this->assertFalse($idField->isPrimaryString(), 'isPrimaryString() returns false by default.');
        $this->assertTrue($titleField->isPrimaryString(), 'isPrimaryString() returns true if set in schema.');
        $this->assertFalse($isbnField->isPrimaryString(), 'isPrimaryString() returns false if not set in schema.');

        $titleField->setPrimaryString(false);
        $this->assertFalse($titleField->isPrimaryString(), 'isPrimaryString() returns false if unset.');

        $titleField->setPrimaryString(true);
        $this->assertTrue($titleField->isPrimaryString(), 'isPrimaryString() returns true if set.');
    }
}
