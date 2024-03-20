<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\AggregateColumn;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\AggregateComment;
use Propel\Tests\Bookstore\Behavior\AggregateCommentQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregateCommentTableMap;

class TestableComment extends AggregateComment
{
    // overrides the parent save() to bypass behavior hooks

    public function save(?ConnectionInterface $con = null): int
    {
        $con->beginTransaction();
        try {
            $affectedRows = $this->doSave($con);
            AggregateCommentTableMap::addInstanceToPool($this);
            $con->commit();

            return $affectedRows;
        } catch (PropelException $e) {
            $con->rollBack();

            throw $e;
        }
    }

    // overrides the parent delete() to bypass behavior hooks
    /**
     * @return void
     */
    public function delete(?ConnectionInterface $con = null): void
    {
        $con->beginTransaction();
        try {
            TestableAggregateCommentQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey())
                ->delete($con);
            $con->commit();
            $this->setDeleted(true);
        } catch (PropelException $e) {
            $con->rollBack();

            throw $e;
        }
    }
}

class TestableAggregateCommentQuery extends AggregateCommentQuery
{
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        return new TestableAggregateCommentQuery();
    }

    // overrides the parent basePreDelete() to bypass behavior hooks

    protected function basePreDelete(ConnectionInterface $con): ?int
    {
        return $this->preDelete($con);
    }

    // overrides the parent basePostDelete() to bypass behavior hooks
    protected function basePostDelete(int $affectedRows, ConnectionInterface $con): ?int
    {
        return $this->postDelete($affectedRows, $con);
    }
}
