<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\collection;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Runtime\Propel;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for OnDemandIterator.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class OnDemandIteratorTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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
