<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;

class GoodBookQuery extends BookQuery
{
    public function filterByIsGood(): GoodBookQuery
    {
        return $this->filterByTitle('good');
    }
}

class ExceptionOnMergeAuthorQuery extends AuthorQuery
{
    public function mergeWith(Criteria $criteria, $operator = null)
    {
        throw new \Exception('nodontmerge');
    }
}
