<?php

namespace Propel\Tests\Generator\Behavior\AggregateColumn;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Behavior\AggregateColumn;
use Propel\Tests\Bookstore\Behavior\AggregateComment;
use Propel\Tests\Bookstore\Behavior\AggregateCommentQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregateCommentTableMap;

class TestableComment extends AggregateComment
{
    // overrides the parent save() to bypass behavior hooks
    public function save(ConnectionInterface $con = null)
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
    public function delete(ConnectionInterface $con = null)
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
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        return new TestableAggregateCommentQuery();
    }

    // overrides the parent basePreDelete() to bypass behavior hooks
    protected function basePreDelete(ConnectionInterface $con)
    {
        return $this->preDelete($con);
    }

    // overrides the parent basePostDelete() to bypass behavior hooks
    protected function basePostDelete($affectedRows, ConnectionInterface $con)
    {
        return $this->postDelete($affectedRows, $con);
    }

}
