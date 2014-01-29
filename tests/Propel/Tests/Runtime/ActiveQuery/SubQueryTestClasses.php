<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Bookstore\BookQuery;

class TestableBookQuery extends BookQuery
{
    public function configureSelectColumns()
    {
        return parent::configureSelectColumns();
    }
}
