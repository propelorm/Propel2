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
        $index->setEntity($this->getEntityMock('db_books'));

        $this->assertEquals('foo_idx', $index->getName());
        $this->assertFalse($index->isUnique());
        $this->assertInstanceOf('Propel\Generator\Model\Entity', $index->getEntity());
        $this->assertSame('db_books', $index->getEntityName());
        $this->assertCount(0, $index->getFields());
        $this->assertFalse($index->hasFields());
    }

    public function testSetupObject()
    {
        $index = new Index();
        $index->setEntity($this->getEntityMock('books'));
        $index->loadMapping([ 'name' => 'foo_idx' ]);

        $this->assertEquals('foo_idx', $index->getName());
    }

    /**
     * @dataProvider provideEntitySpecificAttributes
     *
     */
    public function testCreateDefaultIndexName($tableName, $maxFieldNameLength, $indexName)
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('getMaxFieldNameLength')
            ->will($this->returnValue($maxFieldNameLength))
        ;

        $table = $this->getEntityMock($tableName, [
            'common_name' => $tableName,
            'indices'     => [ new Index(), new Index() ],
            'database'    => $database,
        ]);

        $index = new Index();
        $index->setEntity($table);

        $this->assertSame($indexName, $index->getName());
    }

    public function provideEntitySpecificAttributes()
    {
        return [
            [ 'books', 64, 'books_i_no_columns' ],
            [ 'super_long_table_name', 16, 'super_long_table' ],
        ];
    }

    /**
     * @dataProvider provideFieldDefinitions
     *
     */
    public function testAddIndexedFields($columns)
    {
        $index = new Index();
        $index->setFields($columns);

        $this->assertTrue($index->hasFields());
        $this->assertCount(3, $index->getFields());

        $this->assertSame(100, $index->getFieldSize('foo'));
        $this->assertTrue($index->hasFieldSize('foo'));

        $this->assertSame(5, $index->getFieldSize('bar'));
        $this->assertTrue($index->hasFieldSize('bar'));

        $this->assertNull($index->getFieldSize('baz'));
    }

    public function provideFieldDefinitions()
    {
        $dataset[0][] = [
            $this->getFieldMock('foo', [ 'size' => 100 ]),
            $this->getFieldMock('bar', [ 'size' => 5   ]),
            $this->getFieldMock('baz', [ 'size' => 0   ]),
        ];

        $dataset[1][] = [
            [ 'name' => 'foo', 'size' => 100 ],
            [ 'name' => 'bar', 'size' => 5 ],
            [ 'name' => 'baz', 'size' => 0 ],
        ];

        return $dataset;
    }

    public function testResetFieldsSize()
    {
        $columns[] = $this->getFieldMock('foo', [ 'size' => 100 ]);
        $columns[] = $this->getFieldMock('bar', [ 'size' => 5   ]);

        $index = new Index();
        $index->setFields($columns);

        $this->assertTrue($index->hasFieldSize('foo'));
        $this->assertTrue($index->hasFieldSize('bar'));

        $index->resetFieldsSize();
        $this->assertFalse($index->hasFieldSize('foo'));
        $this->assertFalse($index->hasFieldSize('bar'));
    }

    public function testNoFieldAtFirstPosition()
    {
        $index = new Index();

        $this->assertFalse($index->hasFieldAtPosition(0, 'foo'));
    }

    /**
     * @dataProvider provideFieldAttributes
     */
    public function testNoFieldAtPositionCaseSensitivity($name, $case)
    {
        $index = new Index();
        $index->addField($this->getFieldMock('foo', [ 'size' => 5 ]));

        $this->assertFalse($index->hasFieldAtPosition(0, $name, 5, $case));
    }

    public function provideFieldAttributes()
    {
        return [
            [ 'bar', false ],
            [ 'BAR', true ],
        ];
    }

    public function testNoSizedFieldAtPosition()
    {
        $size = 5;

        $index = new Index();
        $index->addField($this->getFieldMock('foo', [ 'size' => $size ]));

        $size++;
        $this->assertFalse($index->hasFieldAtPosition(0, 'foo', $size));
    }

    public function testHasFieldAtFirstPosition()
    {
        $index = new Index();
        $index->addField($this->getFieldMock('foo', [ 'size' => 0 ]));

        $this->assertTrue($index->hasFieldAtPosition(0, 'foo'));
    }
}
