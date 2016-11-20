<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveRecord;

use Propel\Tests\Bookstore\ActiveBook;
use Propel\Tests\TestCase;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for ActiveRecord.
 *
 * @author François Zaninotto
 */
class ActiveRecordTest extends TestCaseFixtures
{
    public function testGetVirtualColumns()
    {
        $b = new ActiveBook();
        $this->assertEquals(array(), $b->getVirtualFields(), 'getVirtualFields() returns an empty array for new objects');
        $b->foo = 'bar';
        $this->assertEquals(array('foo' => 'bar'), $b->getVirtualFields(), 'getVirtualFields() returns an associative array of virtual columns');
    }

    public function testHasVirtualColumn()
    {
        $b = new ActiveBook();
        $this->assertFalse($b->hasVirtualField('foo'), 'hasVirtualField() returns false if the virtual column is not set');
        $b->foo = 'bar';
        $this->assertTrue($b->hasVirtualField('foo'), 'hasVirtualField() returns true if the virtual column is set');
        $b->foo = null;
        $this->assertTrue($b->hasVirtualField('foo'), 'hasVirtualField() returns true if the virtual column is set and has NULL value');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetVirtualColumnWrongKey()
    {
        $b = new ActiveBook();
        $b->getVirtualField('foo');
    }

    public function testGetVirtualColumn()
    {
        $b = new ActiveBook();
        $b->foo = 'bar';
        $this->assertEquals('bar', $b->getVirtualField('foo'), 'getVirtualField() returns a virtual column value based on its key');
    }

    public function testSetVirtualColumn()
    {
        $b = new ActiveBook();
        $b->setVirtualColumn('foo', 'bar');
        $this->assertEquals('bar', $b->getVirtualField('foo'), 'setVirtualColumn() sets a virtual column value based on its key');
        $b->setVirtualColumn('foo', 'baz');
        $this->assertEquals('baz', $b->getVirtualField('foo'), 'setVirtualColumn() can modify the value of an existing virtual column');
        $this->assertEquals($b, $b->setVirtualColumn('foo', 'bar'), 'setVirtualColumn() returns the current object');
    }
}
