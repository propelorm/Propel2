<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\collection;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\PropelQuery;

/**
 * Test class for OnDemandIterator.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class OnDemandIteratorTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
    }

    public function testInstancePoolingDisabled()
    {
        Propel::enableInstancePooling();
        $books = PropelQuery::from('\Propel\Tests\Bookstore\Book')
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find($this->con);
        foreach ($books as $book) {
            $this->assertFalse(Propel::isInstancePoolingEnabled());
        }
    }

    public function testInstancePoolingReenabled()
    {
        Propel::enableInstancePooling();
        $books = PropelQuery::from('\Propel\Tests\Bookstore\Book')
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find($this->con);
        foreach ($books as $book) {
        }
        $this->assertTrue(Propel::isInstancePoolingEnabled());

        Propel::disableInstancePooling();
        $books = PropelQuery::from('\Propel\Tests\Bookstore\Book')
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find($this->con);
        foreach ($books as $book) {
        }
        $this->assertFalse(Propel::isInstancePoolingEnabled());
        Propel::enableInstancePooling();
    }

}
