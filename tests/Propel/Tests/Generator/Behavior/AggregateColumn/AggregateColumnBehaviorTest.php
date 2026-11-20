<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\AggregateColumn;

use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Behavior\AggregateComment;
use Propel\Tests\Bookstore\Behavior\AggregateCommentQuery;
use Propel\Tests\Bookstore\Behavior\AggregateItem;
use Propel\Tests\Bookstore\Behavior\AggregateItemQuery;
use Propel\Tests\Bookstore\Behavior\AggregateOrder;
use Propel\Tests\Bookstore\Behavior\AggregateOrderItem;
use Propel\Tests\Bookstore\Behavior\AggregateOrderItemQuery;
use Propel\Tests\Bookstore\Behavior\AggregateOrderQuery;
use Propel\Tests\Bookstore\Behavior\AggregatePoll;
use Propel\Tests\Bookstore\Behavior\AggregatePollQuery;
use Propel\Tests\Bookstore\Behavior\AggregatePost;
use Propel\Tests\Bookstore\Behavior\AggregatePostQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregateItemTableMap;
use Propel\Tests\Bookstore\Behavior\Map\AggregateOrderItemTableMap;
use Propel\Tests\Bookstore\Behavior\Map\AggregateOrderTableMap;
use Propel\Tests\Bookstore\Behavior\Map\AggregatePollTableMap;
use Propel\Tests\Bookstore\Behavior\Map\AggregatePostTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for AggregateColumnBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class AggregateColumnBehaviorTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        include_once(__DIR__ . '/AggregateColumnsBehaviorTestClasses.php');
    }

    /**
     * @return void
     */
    public function testParameters()
    {
        $postTable = AggregatePostTableMap::getTableMap();
        $this->assertEquals(count($postTable->getColumns()), 2, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\AggregatePost', 'getNbComments'));
    }

    /**
     * @return void
     */
    public function testCompute()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $this->assertEquals(0, $post->computeNbComments($this->con), 'The compute method returns 0 for objects with no related objects');
        $comment1 = new AggregateComment();
        $comment1->setAggregatePost($post);
        $comment1->save($this->con);
        $this->assertEquals(1, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(2, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
        $comment1->delete($this->con);
        $this->assertEquals(1, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $comment = new TestableComment();
        $comment->setAggregatePost($post);
        $comment->save($this->con);
        $this->assertNull($post->getNbComments());
        $post->updateNbComments($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'The update method updates the aggregate column');
        $comment->delete($this->con);
        $this->assertEquals(1, $post->getNbComments());
        $post->updateNbComments($this->con);
        $this->assertEquals(0, $post->getNbComments(), 'The update method updates the aggregate column');
    }

    /**
     * @return void
     */
    public function testCreateRelated()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $comment1 = new AggregateComment();
        $comment1->save($this->con);
        $this->assertNull($post->getNbComments(), 'Adding a new foreign object does not update the aggregate column');
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'Adding a new related object updates the aggregate column');
        $comment3 = new AggregateComment();
        $comment3->setAggregatePost($post);
        $comment3->save($this->con);
        $this->assertEquals(2, $post->getNbComments(), 'Adding a new related object updates the aggregate column');
    }

    /**
     * @return void
     */
    public function testUpdateRelated()
    {
        [$poll, $item1, $item2] = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getNbVotes());
        $item1->setScore(10);
        $item1->save($this->con);
        $this->assertEquals(17, $poll->getTotalScore(), 'Updating a related object updates the aggregate column');
        $this->assertEquals(2, $poll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testDeleteRelated()
    {
        [$poll, $item1, $item2] = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getNbVotes());
        $item1->delete($this->con);
        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $this->assertEquals(1, $poll->getNbVotes());
        $item2->delete($this->con);
        $this->assertNull($poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $this->assertEquals(0, $poll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testUpdateRelatedWithQuery()
    {
        [$poll, $item1, $item2] = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getNbVotes());
        AggregateItemQuery::create()
            ->update(['Score' => 4], $this->con);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query updates the aggregate column');
        $this->assertEquals(2, $poll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testUpdateRelatedWithQueryUsingAlias()
    {
        [$poll, $item1, $item2] = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getNbVotes());
        AggregateItemQuery::create()
            ->setModelAlias('foo', true)
            ->update(['Score' => 4], $this->con);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query using alias updates the aggregate column');
        $this->assertEquals(2, $poll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testDeleteRelatedWithQuery()
    {
        [$poll, $item1, $item2] = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getNbVotes());
        AggregateItemQuery::create()
            ->deleteAll($this->con);
        $this->assertNull($poll->getTotalScore(), 'Deleting related objects with a query updates the aggregate column');
        $this->assertEquals(0, $poll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testDeleteRelatedWithQueryUsingAlias()
    {
        [$poll, $item1, $item2] = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getNbVotes());

        if ($this->runningOnSQLite()) {
            $this->markTestSkipped('Not executed on sqlite');
        }

        AggregateItemQuery::create()
            ->setModelAlias('foo', true)
            ->filterById($item1->getId())
            ->delete($this->con);
        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting related objects with a query using alias updates the aggregate column');
        $this->assertEquals(1, $poll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testRemoveRelation()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $comment1 = new AggregateComment();
        $comment1->setAggregatePost($post);
        $comment1->save($this->con);
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(2, $post->getNbComments());
        $comment2->setAggregatePost(null);
        $comment2->save($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'Removing a relation changes the related object aggregate column');
    }

    /**
     * @return void
     */
    public function testReplaceRelation()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post1 = new AggregatePost();
        $post1->save($this->con);
        $post2 = new AggregatePost();
        $post2->save($this->con);
        $comment = new AggregateComment();
        $comment->setAggregatePost($post1);
        $comment->save($this->con);
        $this->assertEquals(1, $post1->getNbComments());
        $this->assertNull($post2->getNbComments());
        $comment->setAggregatePost($post2);
        $comment->save($this->con);
        $this->assertEquals(0, $post1->getNbComments(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(1, $post2->getNbComments(), 'Replacing a relation changes the related object aggregate column');
    }

    /**
     * @return void
     */
    public function testCascadeSaveCreateAndNoOpSave(): void
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);

        $post = new TestablePost();
        $post->addAggregateComment(new AggregateComment());
        $post->addAggregateComment(new AggregateComment());
        $affectedRows = $post->save($this->con);

        $this->assertEquals(3, $affectedRows, 'Cascade-inserting 1 post and 2 comments affects 3 rows');
        $this->assertFalse($post->isModified(), 'Parent object must not remain modified after cascade save()');
        $this->assertEquals(2, $post->getNbComments());
        $this->assertEquals(2, $post->postSaveNbComments, 'postSave() hook sees computed aggregate value');
        $this->assertEquals(1, $post->computeCallCount, 'Aggregate query runs once after saving all children');

        AggregatePostTableMap::clearInstancePool();
        $reloadedPost = AggregatePostQuery::create()->findPk($post->getId(), $this->con);
        $this->assertEquals(2, $reloadedPost->getNbComments(), 'Cascade save persists aggregate column to database');

        $post->computeCallCount = 0;
        $post->save($this->con);
        $this->assertEquals(0, $post->computeCallCount, 'Saving an unmodified parent with loaded children skips compute query');
    }

    /**
     * @return void
     */
    public function testCascadeSaveMultipleAggregatesUpdateAndHydratedRemove(): void
    {
        AggregateItemQuery::create()->deleteAll($this->con);
        AggregatePollQuery::create()->deleteAll($this->con);

        $poll = new AggregatePoll();
        $item1 = new AggregateItem();
        $item1->setScore(12);
        $poll->addAggregateItem($item1);
        $item2 = new AggregateItem();
        $item2->setScore(7);
        $poll->addAggregateItem($item2);
        $poll->save($this->con);

        $this->assertFalse($poll->isModified());
        AggregatePollTableMap::clearInstancePool();
        $reloadedPoll = AggregatePollQuery::create()->findPk($poll->getId(), $this->con);
        $this->assertEquals(19, $reloadedPoll->getTotalScore());
        $this->assertEquals(2, $reloadedPoll->getNbVotes());

        $item1->setScore(10);
        $affectedRows = $poll->save($this->con);
        $this->assertEquals(2, $affectedRows, 'Cascade-updating 1 child and its parent aggregate affects 2 rows');
        $this->assertFalse($poll->isModified());
        AggregatePollTableMap::clearInstancePool();
        $reloadedPoll = AggregatePollQuery::create()->findPk($poll->getId(), $this->con);
        $this->assertEquals(17, $reloadedPoll->getTotalScore(), 'Cascade-saving modified child persists updated aggregate');
        $this->assertEquals(2, $reloadedPoll->getNbVotes());

        // Clear BOTH parent and child pools to test DB-hydrated children whose back-reference is lazy (null)
        AggregatePollTableMap::clearInstancePool();
        AggregateItemTableMap::clearInstancePool();
        $hydratedPoll = AggregatePollQuery::create()->findPk($poll->getId(), $this->con);
        $hydratedItems = $hydratedPoll->getAggregateItems(null, $this->con);
        $hydratedItem1 = $this->findById($hydratedItems, $item1->getId());
        $hydratedPoll->removeAggregateItem($hydratedItem1);
        $hydratedPoll->save($this->con);

        $this->assertFalse($hydratedPoll->isModified());
        $this->assertEquals(7, $hydratedPoll->getTotalScore(), 'Removing DB-hydrated nullable-FK child updates in-memory aggregate');
        $this->assertEquals(1, $hydratedPoll->getNbVotes());

        AggregatePollTableMap::clearInstancePool();
        $reloadedPoll = AggregatePollQuery::create()->findPk($poll->getId(), $this->con);
        $this->assertEquals(7, $reloadedPoll->getTotalScore(), 'Removing DB-hydrated nullable-FK child persists updated aggregate');
        $this->assertEquals(1, $reloadedPoll->getNbVotes());
    }

    /**
     * @return void
     */
    public function testCascadeSaveDeleteRequiredChildWithDisabledInstancePooling(): void
    {
        AggregateOrderItemQuery::create()->deleteAll($this->con);
        AggregateOrderQuery::create()->deleteAll($this->con);

        $order = new AggregateOrder();
        $orderItem1 = new AggregateOrderItem();
        $orderItem1->setPrice(100);
        $order->addAggregateOrderItem($orderItem1);
        $orderItem2 = new AggregateOrderItem();
        $orderItem2->setPrice(50);
        $order->addAggregateOrderItem($orderItem2);
        $order->save($this->con);

        $this->assertFalse($order->isModified());
        AggregateOrderTableMap::clearInstancePool();
        AggregateOrderItemTableMap::clearInstancePool();

        Propel::disableInstancePooling();
        try {
            $hydratedOrder = AggregateOrderQuery::create()->findPk($order->getId(), $this->con);
            $hydratedOrderItems = $hydratedOrder->getAggregateOrderItems(null, $this->con);
            $hydratedOrderItem1 = $this->findById($hydratedOrderItems, $orderItem1->getId());
            $hydratedOrder->removeAggregateOrderItem($hydratedOrderItem1);
            $hydratedOrder->save($this->con);

            $this->assertFalse($hydratedOrder->isModified());
            $this->assertEquals(50, $hydratedOrder->getTotalPrice(), 'In-memory parent reflects cascade-deleted required child even with pooling disabled');

            $reloadedOrder = AggregateOrderQuery::create()->findPk($order->getId(), $this->con);
            $this->assertEquals(50, $reloadedOrder->getTotalPrice(), 'Database reflects cascade-deleted required child');
        } finally {
            Propel::enableInstancePooling();
        }
    }

    /**
     * Finds a hydrated model in a collection by primary key (independent of database row order).
     *
     * @param iterable<object> $items
     * @param int $id
     *
     * @return mixed
     */
    protected function findById(iterable $items, int $id)
    {
        foreach ($items as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        $this->fail(sprintf('Item with ID %d not found in collection.', $id));
    }

    protected function populatePoll()
    {
        AggregateItemQuery::create()->deleteAll($this->con);
        AggregatePollQuery::create()->deleteAll($this->con);
        $poll = new AggregatePoll();
        $poll->save($this->con);
        $item1 = new AggregateItem();
        $item1->setScore(12);
        $item1->setAggregatePoll($poll);
        $item1->save($this->con);
        $item2 = new AggregateItem();
        $item2->setScore(7);
        $item2->setAggregatePoll($poll);
        $item2->save($this->con);

        return [$poll, $item1, $item2];
    }
}
