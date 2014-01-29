<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveRecord;

use Propel\Tests\Bookstore\Book;
use Propel\Tests\TestCase;

/**
 * Test class for ActiveRecord.
 *
 * @author François Zaninotto
 */
class ActiveRecordTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        include_once(__DIR__.'/ActiveRecordTestClasses.php');
    }

    public function testGetVirtualColumns()
    {
        $b = new TestableActiveRecord();
        $this->assertEquals(array(), $b->getVirtualColumns(), 'getVirtualColumns() returns an empty array for new objects');
        $b->virtualColumns = array('foo' => 'bar');
        $this->assertEquals(array('foo' => 'bar'), $b->getVirtualColumns(), 'getVirtualColumns() returns an associative array of virtual columns');
    }

    public function testHasVirtualColumn()
    {
        $b = new TestableActiveRecord();
        $this->assertFalse($b->hasVirtualColumn('foo'), 'hasVirtualColumn() returns false if the virtual column is not set');
        $b->virtualColumns = array('foo' => 'bar');
        $this->assertTrue($b->hasVirtualColumn('foo'), 'hasVirtualColumn() returns true if the virtual column is set');
        $b->virtualColumns = array('foo' => null);
        $this->assertTrue($b->hasVirtualColumn('foo'), 'hasVirtualColumn() returns true if the virtual column is set and has NULL value');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testGetVirtualColumnWrongKey()
    {
        $b = new TestableActiveRecord();
        $b->getVirtualColumn('foo');
    }

    public function testGetVirtualColumn()
    {
        $b = new TestableActiveRecord();
        $b->virtualColumns = array('foo' => 'bar');
        $this->assertEquals('bar', $b->getVirtualColumn('foo'), 'getVirtualColumn() returns a virtual column value based on its key');
    }

    public function testSetVirtualColumn()
    {
        $b = new TestableActiveRecord();
        $b->setVirtualColumn('foo', 'bar');
        $this->assertEquals('bar', $b->getVirtualColumn('foo'), 'setVirtualColumn() sets a virtual column value based on its key');
        $b->setVirtualColumn('foo', 'baz');
        $this->assertEquals('baz', $b->getVirtualColumn('foo'), 'setVirtualColumn() can modify the value of an existing virtual column');
        $this->assertEquals($b, $b->setVirtualColumn('foo', 'bar'), 'setVirtualColumn() returns the current object');
    }
}
