<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers\Bookstore;

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreEmptyTestBase extends BookstoreTestBase
{
    /**
     * This is run before each unit test; it empties the database.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (static::$isInitialized) {
            BookstoreDataPopulator::depopulate($this->con);
        }
    }
}
