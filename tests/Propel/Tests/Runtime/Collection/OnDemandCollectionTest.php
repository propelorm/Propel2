<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Collection;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\OnDemandCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\PropelQuery;

/**
 * Test class for OnDemandCollection.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class OnDemandCollectionTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
        Propel::disableInstancePooling();
        $this->books = PropelQuery::from('\Propel\Tests\Bookstore\Book')->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)->find();
    }

    protected function tearDown()
    {
        $this->books = null;
        parent::tearDown();
        Propel::enableInstancePooling();
    }

    public function testSetFormatter()
    {
        $this->assertTrue($this->books instanceof OnDemandCollection);
        $this->assertEquals(4, count($this->books));
    }

    public function testKeys()
    {
        $i = 0;
        foreach ($this->books as $key => $book) {
            $this->assertEquals($i, $key);
            $i++;
        }
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testoffsetExists()
    {
        $this->books->offsetExists(2);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testoffsetGet()
    {
        $this->books->offsetGet(2);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testoffsetSet()
    {
        $this->books->offsetSet(2, 'foo');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testoffsetUnset()
    {
        $this->books->offsetUnset(2);
    }

    public function testToArray()
    {
        $this->assertNotEquals(array(), $this->books->toArray());
        // since the code from toArray comes from ObjectCollection, we'll assume it's good
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testFromArray()
    {
        $this->books->fromArray(array());
    }

}
