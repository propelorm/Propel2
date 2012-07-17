<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Index;

/**
 * Unit test suite for the Index model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class IndexTest extends ModelTestCase
{
    public function testCreateNamedIndex()
    {
        $index = new Index('foo_idx');
        $index->setTable($this->getTableMock('db_books'));

        $this->assertEquals('foo_idx', $index->getName());
        $this->assertFalse($index->isUnique());
        $this->assertInstanceOf('Propel\Generator\Model\Table', $index->getTable());
        $this->assertSame('db_books', $index->getTableName());
        $this->assertCount(0, $index->getColumns());
        $this->assertFalse($index->hasColumns());
    }

    public function testSetupObject()
    {
        $index = new Index();
        $index->setTable($this->getTableMock('books'));
        $index->loadMapping(array('name' => 'foo_idx'));

        $this->assertEquals('foo_idx', $index->getName());
    }

    /**
     * @dataProvider provideTableSpecificAttributes
     *
     */
    public function testCreateDefaultIndexName($tableName, $maxColumnNameLength, $indexName)
    {
        $table = $this->getTableMock($tableName, array(
            'common_name' => $tableName,
            'indices'     => array(new Index(), new Index()),
            'database'    => $this->getDatabaseMock('bookstore', array(
                'platform' => $this->getPlatformMock(true, array(
                    'max_column_name_length' => $maxColumnNameLength,
                )),
            )),
        ));

        $index = new Index();
        $index->setTable($table);

        $this->assertSame($indexName, $index->getName());
    }

    public function provideTableSpecificAttributes()
    {
        return array(
            array('books', 64, 'books_I_3'),
            array('super_long_table_name', 16, 'super_long_t_I_3'),
        );
    }

    /**
     * @dataProvider provideColumnDefinitions
     *
     */
    public function testAddIndexedColumns($columns)
    {
        $index = new Index();
        $index->setColumns($columns);

        $this->assertTrue($index->hasColumns());
        $this->assertCount(3, $index->getColumns());

        $this->assertSame(100, $index->getColumnSize('foo'));
        $this->assertTrue($index->hasColumnSize('foo'));

        $this->assertSame(5, $index->getColumnSize('bar'));
        $this->assertTrue($index->hasColumnSize('bar'));

        $this->assertNull($index->getColumnSize('baz'));
    }

    public function provideColumnDefinitions()
    {
        $dataset[0][] = array(
            $this->getColumnMock('foo', array('size' => 100)),
            $this->getColumnMock('bar', array('size' => 5)),
            $this->getColumnMock('baz', array('size' => 0)),
        );

        $dataset[1][] = array(
            array('name' => 'foo', 'size' => 100),
            array('name' => 'bar', 'size' => 5),
            array('name' => 'baz', 'size' => 0),
        );

        return $dataset;
    }

    public function testResetColumnsSize()
    {
        $columns[] = $this->getColumnMock('foo', array('size' => 100));
        $columns[] = $this->getColumnMock('bar', array('size' => 5));

        $index = new Index();
        $index->setColumns($columns);

        $this->assertTrue($index->hasColumnSize('foo'));
        $this->assertTrue($index->hasColumnSize('bar'));

        $index->resetColumnsSize();
        $this->assertFalse($index->hasColumnSize('foo'));
        $this->assertFalse($index->hasColumnSize('bar'));
    }

    public function testNoColumnAtFirstPosition()
    {
        $index = new Index();

        $this->assertFalse($index->hasColumnAtPosition(0, 'foo'));
    }

    /**
     * @dataProvider provideColumnAttributes
     */
    public function testNoColumnAtPositionCaseSensitivity($name, $case)
    {
        $index = new Index();
        $index->addColumn($this->getColumnMock('foo', array('size' => 5)));

        $this->assertFalse($index->hasColumnAtPosition(0, $name, 5, $case));
    }

    public function provideColumnAttributes()
    {
        return array(
            array('bar', false),
            array('BAR', true),
        );
    }

    public function testNoSizedColumnAtPosition()
    {
        $size = 5;

        $index = new Index();
        $index->addColumn($this->getColumnMock('foo', array('size' => $size)));

        $size++;
        $this->assertFalse($index->hasColumnAtPosition(0, 'foo', $size));
    }

    public function testHasColumnAtFirstPosition()
    {
        $index = new Index();
        $index->addColumn($this->getColumnMock('foo', array('size' => 0)));

        $this->assertTrue($index->hasColumnAtPosition(0, 'foo'));
    }
}
