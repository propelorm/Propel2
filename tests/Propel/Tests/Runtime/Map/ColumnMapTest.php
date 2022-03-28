<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\Exception\ForeignKeyNotFoundException;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for TableMap.
 *
 * @author François Zaninotto
 */
class ColumnMapTest extends TestCaseFixtures
{
    /**
     * @var string
     */
    protected const COLUMN_NAME = 'bar';

    /**
     * @var string
     */
    protected const PHP_NAME = 'php_bar';

    /**
     * @var string
     */
    protected const TYPE = 'type';

    protected $databaseMap;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dmap = new DatabaseMap('foodb');
        $this->tmap = new TableMap('foo', $this->dmap);
        $this->cmap = new ColumnMap(static::COLUMN_NAME, $this->tmap, static::PHP_NAME, static::TYPE);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        // nothing to do for now
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testConstructor()
    {
        $this->assertEquals(static::COLUMN_NAME, $this->cmap->getName(), 'constructor sets the column name');
        $this->assertEquals($this->tmap, $this->cmap->getTable(), 'Constructor sets the table map');
        $this->assertEquals(static::PHP_NAME, $this->cmap->getPhpName(), 'constructor sets the php name');
        $this->assertEquals(static::TYPE, $this->cmap->getType(), 'constructor sets the type');
    }

    /**
     * @return void
     */
    public function testPhpName()
    {
        $this->cmap->setPhpName('FooBar');
        $this->assertEquals('FooBar', $this->cmap->getPhpName(), 'phpName is set by setPhpName()');
    }

    /**
     * @return void
     */
    public function testType()
    {
        $this->cmap->setType('FooBar');
        $this->assertEquals('FooBar', $this->cmap->getType(), 'type is set by setType()');
    }

    /**
     * @return void
     */
    public function tesSize()
    {
        $this->assertEquals(0, $this->cmap->getSize(), 'size is empty until set');
        $this->cmap->setSize(123);
        $this->assertEquals(123, $this->cmap->getSize(), 'size is set by setSize()');
    }

    /**
     * @return void
     */
    public function testPrimaryKey()
    {
        $this->assertFalse($this->cmap->isPrimaryKey(), 'primaryKey is false by default');
        $this->cmap->setPrimaryKey(true);
        $this->assertTrue($this->cmap->isPrimaryKey(), 'primaryKey is set by setPrimaryKey()');
    }

    /**
     * @return void
     */
    public function testNotNull()
    {
        $this->assertFalse($this->cmap->isNotNull(), 'notNull is false by default');
        $this->cmap->setNotNull(true);
        $this->assertTrue($this->cmap->isNotNull(), 'notNull is set by setPrimaryKey()');
    }

    /**
     * @return void
     */
    public function testDefaultValue()
    {
        $this->assertNull($this->cmap->getDefaultValue(), 'defaultValue is empty until set');
        $this->cmap->setDefaultValue('FooBar');
        $this->assertEquals('FooBar', $this->cmap->getDefaultValue(), 'defaultValue is set by setDefaultValue()');
    }

    /**
     * @return void
     */
    public function testGetForeignKey()
    {
        $this->assertFalse($this->cmap->isForeignKey(), 'foreignKey is false by default');
        try {
            $this->cmap->getRelatedTable();
            $this->fail('getRelatedTable throws an exception when called on a column with no foreign key');
        } catch (ForeignKeyNotFoundException $e) {
            $this->assertTrue(true, 'getRelatedTable throws an exception when called on a column with no foreign key');
        }
        try {
            $this->cmap->getRelatedColumn();
            $this->fail('getRelatedColumn throws an exception when called on a column with no foreign key');
        } catch (ForeignKeyNotFoundException $e) {
            $this->assertTrue(true, 'getRelatedColumn throws an exception when called on a column with no foreign key');
        }
        $relatedTmap = $this->dmap->addTable('foo2');
        // required to let the database map use the foreign TableMap
        $relatedCmap = $relatedTmap->addColumn('BAR2', 'Bar2', 'INTEGER');
        $this->cmap->setForeignKey('foo2', 'BAR2');
        $this->assertTrue($this->cmap->isForeignKey(), 'foreignKey is true after setting the foreign key via setForeignKey()');
        $this->assertEquals($relatedTmap, $this->cmap->getRelatedTable(), 'getRelatedTable returns the related TableMap object');
        $this->assertEquals($relatedCmap, $this->cmap->getRelatedColumn(), 'getRelatedColumn returns the related ColumnMap object');
    }

    /**
     * @return void
     */
    public function testGetRelation()
    {
        $bookTable = BookTableMap::getTableMap();
        $titleColumn = $bookTable->getColumn('TITLE');
        $this->assertNull($titleColumn->getRelation(), 'getRelation() returns null for non-foreign key columns');
        $publisherColumn = $bookTable->getColumn('PUBLISHER_ID');
        $this->assertEquals($publisherColumn->getRelation(), $bookTable->getRelation('Publisher'), 'getRelation() returns the RelationMap object for this foreign key');
        $bookstoreTable = BookstoreEmployeeTableMap::getTableMap();
        $supervisorColumn = $bookstoreTable->getColumn('SUPERVISOR_ID');
        $this->assertEquals($supervisorColumn->getRelation(), $supervisorColumn->getRelation('Supervisor'), 'getRelation() returns the RelationMap object even whit ha specific refPhpName');
    }

    /**
     * @return void
     */
    public function testNormalizeName()
    {
        $this->assertEquals('', ColumnMap::normalizeName(''), 'normalizeColumnName() returns an empty string when passed an empty string');
        $this->assertEquals('BAR', ColumnMap::normalizeName('bar'), 'normalizeColumnName() uppercases the input');
        $this->assertEquals('BAR_BAZ', ColumnMap::normalizeName('bar_baz'), 'normalizeColumnName() does not mind underscores');
        $this->assertEquals('BAR', ColumnMap::normalizeName('FOO.BAR'), 'normalizeColumnName() removes table prefix');
        $this->assertEquals('BAR', ColumnMap::normalizeName('BAR'), 'normalizeColumnName() leaves normalized column names unchanged');
        $this->assertEquals('BAR_BAZ', ColumnMap::normalizeName('foo.bar_baz'), 'normalizeColumnName() can do all the above at the same time');
    }

    /**
     * @return void
     */
    public function testIsPrimaryString()
    {
        $bookTable = BookTableMap::getTableMap();
        $idColumn = $bookTable->getColumn('ID');
        $titleColumn = $bookTable->getColumn('TITLE');
        $isbnColumn = $bookTable->getColumn('ISBN');

        $this->assertFalse($idColumn->isPrimaryString(), 'isPrimaryString() returns false by default.');
        $this->assertTrue($titleColumn->isPrimaryString(), 'isPrimaryString() returns true if set in schema.');
        $this->assertFalse($isbnColumn->isPrimaryString(), 'isPrimaryString() returns false if not set in schema.');

        $titleColumn->setPrimaryString(false);
        $this->assertFalse($titleColumn->isPrimaryString(), 'isPrimaryString() returns false if unset.');

        $titleColumn->setPrimaryString(true);
        $this->assertTrue($titleColumn->isPrimaryString(), 'isPrimaryString() returns true if set.');
    }
}
