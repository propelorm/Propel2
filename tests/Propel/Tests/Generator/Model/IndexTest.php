<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testSetupObject()
    {
        $index = new Index();
        $index->setTable($this->getTableMock('books'));
        $index->loadMapping([ 'name' => 'foo_idx' ]);

        $this->assertEquals('foo_idx', $index->getName());
    }

    /**
     * @dataProvider provideTableSpecificAttributes
     *
     * @return void
     */
    public function testCreateDefaultIndexName($tableName, $maxColumnNameLength, $indexName)
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('getMaxColumnNameLength')
            ->will($this->returnValue($maxColumnNameLength));

        $table = $this->getTableMock($tableName, [
            'common_name' => $tableName,
            'indices' => [ new Index(), new Index() ],
            'database' => $database,
        ]);

        $index = new Index();
        $index->setTable($table);

        $this->assertSame($indexName, $index->getName());
    }

    public function provideTableSpecificAttributes()
    {
        return [
            [ 'books', 64, 'books_i_no_columns' ],
            [ 'super_long_table_name', 16, 'super_long_table' ],
        ];
    }

    /**
     * @dataProvider provideColumnDefinitions
     *
     * @return void
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
        $dataset[0][] = [
            $this->getColumnMock('foo', [ 'size' => 100 ]),
            $this->getColumnMock('bar', [ 'size' => 5   ]),
            $this->getColumnMock('baz', [ 'size' => 0   ]),
        ];

        $dataset[1][] = [
            [ 'name' => 'foo', 'size' => 100 ],
            [ 'name' => 'bar', 'size' => 5 ],
            [ 'name' => 'baz', 'size' => 0 ],
        ];

        return $dataset;
    }

    /**
     * @return void
     */
    public function testResetColumnsSize()
    {
        $columns[] = $this->getColumnMock('foo', [ 'size' => 100 ]);
        $columns[] = $this->getColumnMock('bar', [ 'size' => 5   ]);

        $index = new Index();
        $index->setColumns($columns);

        $this->assertTrue($index->hasColumnSize('foo'));
        $this->assertTrue($index->hasColumnSize('bar'));

        $index->resetColumnsSize();
        $this->assertFalse($index->hasColumnSize('foo'));
        $this->assertFalse($index->hasColumnSize('bar'));
    }

    /**
     * @return void
     */
    public function testNoColumnAtFirstPosition()
    {
        $index = new Index();

        $this->assertFalse($index->hasColumnAtPosition(0, 'foo'));
    }

    /**
     * @dataProvider provideColumnAttributes
     *
     * @return void
     */
    public function testNoColumnAtPositionCaseSensitivity($name, $case)
    {
        $index = new Index();
        $index->addColumn($this->getColumnMock('foo', [ 'size' => 5 ]));

        $this->assertFalse($index->hasColumnAtPosition(0, $name, 5, $case));
    }

    public function provideColumnAttributes()
    {
        return [
            [ 'bar', false ],
            [ 'BAR', true ],
        ];
    }

    /**
     * @return void
     */
    public function testNoSizedColumnAtPosition()
    {
        $size = 5;

        $index = new Index();
        $index->addColumn($this->getColumnMock('foo', [ 'size' => $size ]));

        $size++;
        $this->assertFalse($index->hasColumnAtPosition(0, 'foo', $size));
    }

    /**
     * @return void
     */
    public function testHasColumnAtFirstPosition()
    {
        $index = new Index();
        $index->addColumn($this->getColumnMock('foo', [ 'size' => 0 ]));

        $this->assertTrue($index->hasColumnAtPosition(0, 'foo'));
    }
}
