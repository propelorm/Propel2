<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\collection;

use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use UnexpectedValueException;

/**
 * Test class for Collection.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class CollectionTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testClone()
    {
        $col = new Collection(['Bar1']);
        $colCloned = clone $col;
        $colCloned[] = 'Bar2';

        $this->assertCount(1, $col);
        $this->assertCount(2, $colCloned);
    }

    /**
     * @return void
     */
    public function testArrayAccess()
    {
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertEquals('bar1', $col[0], 'Collection allows access via $foo[$index]');
        $this->assertEquals('bar2', $col[1], 'Collection allows access via $foo[$index]');
        $this->assertEquals('bar3', $col[2], 'Collection allows access via $foo[$index]');
    }

    /**
     * @return void
     */
    public function testGetData()
    {
        $col = new Collection();
        $this->assertEquals([], $col->getData(), 'getData() returns an empty array for empty collections');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertEquals($data, $col->getData(), 'getData() returns the collection data');
        $col[0] = 'bar4';
        $this->assertEquals('bar1', $data[0], 'getData() returns a copy of the collection data');
    }

    /**
     * @return void
     */
    public function testSetData()
    {
        $col = new Collection();
        $col->setData([]);
        $this->assertEquals([], $col->getArrayCopy(), 'setData() can set data to an empty array');

        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection();
        $col->setData($data);
        $this->assertEquals($data, $col->getArrayCopy(), 'setData() sets the collection data');
    }

    /**
     * @return void
     */
    public function testIsEmpty()
    {
        $col = new Collection();
        $this->assertTrue($col->isEmpty(), 'isEmpty() returns true on an empty collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertFalse($col->isEmpty(), 'isEmpty() returns false on a non empty collection');
    }

    /**
     * @return void
     */
    public function testCallIteratorMethods()
    {
        $methods = ['getPosition', 'isFirst', 'isLast', 'isOdd', 'isEven'];
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $it = $col->getIterator();
        foreach ($it as $item) {
            foreach ($methods as $method) {
                $this->assertNotNull(
                    $it->$method(),
                    $method . '() returns a not null value'
                );
            }
        }
    }

    /**
     * @return void
     */
    public function testNestedIteration()
    {
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $sequence = '';
        foreach ($col as $i => $element) {
            $sequence .= "i$i;";
            foreach ($col as $k => $element2) {
                $sequence .= "k$k;";
            }
        }
        $this->assertEquals('i0;k0;k1;k2;i1;k0;k1;k2;i2;k0;k1;k2;', $sequence);
    }

    /**
     * @return void
     */
    public function testGet()
    {
        $col = new Collection(['foo', 'bar']);
        $this->assertEquals('foo', $col->get(0), 'get() returns an element from its key');
    }

    /**
     * @return void
     */
    public function testGetUnknownOffset()
    {
        $this->expectException(UnexpectedValueException::class);

        $col = new Collection();
        $bar = $col->get('foo');
    }

    /**
     * @return void
     */
    public function testPop()
    {
        $col = new Collection();
        $this->assertNull($col->pop(), 'pop() returns null on an empty collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertEquals('bar3', $col->pop(), 'pop() returns the last element of the collection');
        $this->assertEquals(['bar1', 'bar2'], $col->getData(), 'pop() removes the last element of the collection');
    }

    /**
     * @return void
     */
    public function testShift()
    {
        $col = new Collection();
        $this->assertNull($col->shift(), 'shift() returns null on an empty collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertEquals('bar1', $col->shift(), 'shift() returns the first element of the collection');
        $this->assertEquals(['bar2', 'bar3'], $col->getData(), 'shift() removes the first element of the collection');
    }

    /**
     * @return void
     */
    public function testPrepend()
    {
        $col = new Collection();
        $this->assertEquals(1, $col->prepend('a'), 'prepend() returns 1 on an empty collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertEquals(4, $col->prepend('bar4'), 'prepend() returns the new number of elements in the collection when adding a variable');
        $this->assertEquals(['bar4', 'bar1', 'bar2', 'bar3'], $col->getData(), 'prepend() adds new element to the beginning of the collection');
    }

    /**
     * @return void
     */
    public function testSet()
    {
        $col = new Collection();
        $col->set(4, 'bar');
        $this->assertEquals([4 => 'bar'], $col->getData(), 'set() adds an element to the collection with a key');

        $col = new Collection();
        $col->set(null, 'foo');
        $col->set(null, 'bar');
        $this->assertEquals(['foo', 'bar'], $col->getData(), 'set() adds an element to the collection without a key');
    }

    /**
     * @return void
     */
    public function testRemove()
    {
        $col = new Collection();
        $col[0] = 'bar';
        $col[1] = 'baz';
        $col->remove(1);
        $this->assertEquals(['bar'], $col->getData(), 'remove() removes an element from its key');
    }

    /**
     * @return void
     */
    public function testRemoveUnknownOffset()
    {
        $this->expectException(UnexpectedValueException::class);

        $col = new Collection();
        $col->remove(2);
    }

    /**
     * @return void
     */
    public function testClear()
    {
        $col = new Collection();
        $col->clear();
        $this->assertEquals([], $col->getData(), 'clear() empties the collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $col->clear();
        $this->assertEquals([], $col->getData(), 'clear() empties the collection');
    }

    /**
     * @return void
     */
    public function testContains()
    {
        $col = new Collection();
        $this->assertFalse($col->contains('foo_1'), 'contains() returns false on an empty collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertTrue($col->contains('bar1'), 'contains() returns true when the key exists');
        $this->assertFalse($col->contains('bar4'), 'contains() returns false when the key does not exist');
    }

    /**
     * @return void
     */
    public function testSearch()
    {
        $col = new Collection();
        $this->assertFalse($col->search('bar1'), 'search() returns false on an empty collection');
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $this->assertEquals(1, $col->search('bar2'), 'search() returns the key when the element exists');
        $this->assertFalse($col->search('bar4'), 'search() returns false when the element does not exist');
    }

    /**
     * @return void
     */
    public function testSerializable()
    {
        $data = ['bar1', 'bar2', 'bar3'];
        $col = new Collection($data);
        $col->setModel('Foo');
        $serializedCol = serialize($col);

        $col2 = unserialize($serializedCol);
        $this->assertEquals($col, $col2, 'Collection is serializable');
    }

    /**
     * @database
     *
     * @return void
     */
    public function testGetWriteConnection()
    {
        $col = new Collection();
        $col->setModel('\Propel\Tests\Bookstore\Book');
        $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        $this->assertEquals($con, $col->getWriteConnection(), 'getWriteConnection() returns a write connection for the collection model');
    }

    /**
     * @return void
     */
    public function testGetConnectionNoModel()
    {
        $this->expectException(BadMethodCallException::class);

        $col = new Collection();
        $col->getConnection();
    }

    /**
     * @return void
     */
    public function testDiffWithEmptyCollectionReturnsCurrentCollection()
    {
        $col1 = new Collection();
        $col2 = new Collection();

        $b = new Book();
        $col1[] = $b;

        $result = $col1->diff($col2);

        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $result);
        $this->assertEquals(1, count($result));
        $this->assertSame($b, $result[0]);
    }

    /**
     * @return void
     */
    public function testDiffWithEmptyCollections()
    {
        $col1 = new Collection();
        $col2 = new Collection();

        $result = $col1->diff($col2);

        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $result);
        $this->assertEquals(0, count($result));
    }

    /**
     * @return void
     */
    public function testDiffWithASimilarCollectionReturnsAnEmptyCollection()
    {
        $col1 = new Collection();
        $col2 = new Collection();

        $b = new Book();
        $col1[] = $b;
        $col2[] = $b;

        $result = $col1->diff($col2);

        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $result);
        $this->assertEquals(0, count($result));
    }

    /**
     * @return void
     */
    public function testDiffWithNonEmptyCollectionReturnsObjectsInTheFirstCollectionWhichAreNotInTheSecondCollection()
    {
        $col1 = new Collection();
        $col2 = new Collection();

        $b = new Book();
        $b1 = new Book();
        $col1[] = $b;
        $col1[] = $b1;
        $col2[] = $b;

        $result = $col1->diff($col2);

        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $result);
        $this->assertEquals(1, count($result));
        $this->assertSame($b1, $result[0]);
    }

    /**
     * @return void
     */
    public function testDiffWithACollectionHavingObjectsNotPresentInTheFirstCollection()
    {
        $col1 = new Collection();
        $col2 = new Collection();

        $b = new Book();
        $col2[] = $b;

        $result = $col1->diff($col2);

        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $result);
        $this->assertEquals(0, count($result));
    }
}
