<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\AggregateField;

use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Behavior\AggregateField;
use Propel\Tests\Bookstore\Behavior\AggregateComment;
use Propel\Tests\Bookstore\Behavior\AggregateCommentQuery;
use Propel\Tests\Bookstore\Behavior\AggregatePost;
use Propel\Tests\Bookstore\Behavior\AggregatePostQuery;
use Propel\Tests\Bookstore\Behavior\Base\BaseAggregatePollRepository;
use Propel\Tests\Bookstore\Behavior\Base\BaseAggregatePostRepository;
use Propel\Tests\Bookstore\Behavior\Map\AggregatePostEntityMap;
use Propel\Tests\Bookstore\Behavior\AggregateItem;
use Propel\Tests\Bookstore\Behavior\AggregateItemQuery;
use Propel\Tests\Bookstore\Behavior\AggregatePoll;
use Propel\Tests\Bookstore\Behavior\AggregatePollQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;


/**
 * Tests for AggregateFieldBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class AggregateFieldBehaviorTest extends BookstoreTestBase
{
    public function testCompute()
    {
        AggregateCommentQuery::create()->deleteAll();
        AggregatePostQuery::create()->deleteAll();

        /** @var BaseAggregatePostRepository $aggregatePostRepository */
        $aggregatePostRepository = $this->getConfiguration()->getRepository(AggregatePostEntityMap::ENTITY_CLASS);

        $post = new AggregatePost();
        $post->save();
        $this->assertEquals(0, $aggregatePostRepository->computeCommentsCount($post), 'The compute method returns 0 for objects with no related objects');
        $comment1 = new AggregateComment();
        $comment1->setAggregatePost($post);
        $comment1->save();
        $this->assertEquals(1, $aggregatePostRepository->computeCommentsCount($post), 'The compute method computes the aggregate function on related objects');
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save();
        $this->assertEquals(2, $aggregatePostRepository->computeCommentsCount($post), 'The compute method computes the aggregate function on related objects');
        $comment1->delete();
        $this->assertEquals(1, $aggregatePostRepository->computeCommentsCount($post), 'The compute method computes the aggregate function on related objects');
    }

    public function testUpdate()
    {
        AggregateCommentQuery::create()->deleteAll();
        AggregatePostQuery::create()->deleteAll();
        $post = new AggregatePost();
        $post->save();

        /** @var BaseAggregatePostRepository $aggregatePostRepository */
        $aggregatePostRepository = $this->getConfiguration()->getRepository(AggregatePostEntityMap::ENTITY_CLASS);

        $comment = new AggregateComment();
        $comment->setAggregatePost($post);
        $comment->save();

        $aggregatePostRepository->updateCommentsCount($post);
        $this->assertEquals(1, $post->getCommentsCount(), 'The update method updates the aggregate column');
        $comment->delete();
        $this->assertEquals(0, $post->getCommentsCount(), 'The update method updates the aggregate column');
    }

    public function testCreateRelated()
    {
        AggregateCommentQuery::create()->deleteAll();
        AggregatePostQuery::create()->deleteAll();
        /** @var BaseAggregatePostRepository $aggregatePostRepository */
        $aggregatePostRepository = $this->getConfiguration()->getRepository(AggregatePostEntityMap::ENTITY_CLASS);

        $post = new AggregatePost();
        $post->save();
        $comment1 = new AggregateComment();
        $comment1->save();
        $this->assertNull($post->getCommentsCount(), 'Adding a new foreign object does not update the aggregate column');

        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save();

        $this->assertEquals(1, $post->getCommentsCount(), 'Adding a new related object updates the aggregate column');
        $comment3 = new AggregateComment();
        $comment3->setAggregatePost($post);
        $comment3->save();
        $this->assertEquals(2, $post->getCommentsCount(), 'Adding a new related object updates the aggregate column');
    }

    public function testUpdateRelated()
    {
        /** @var AggregatePoll $poll */
        /** @var AggregateItem $item1 */
        /** @var AggregateItem $item2 */
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getVotesCount());
        $item1->setScore(10);
        $item1->save();
        $this->assertEquals(17, $poll->getTotalScore(), 'Updating a related object updates the aggregate column');
        $this->assertEquals(2, $poll->getVotesCount());
    }

    public function testDeleteRelated()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getVotesCount());
        $item1->delete();
        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $this->assertEquals(1, $poll->getVotesCount());
        $item2->delete();
        $this->assertNull($poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $this->assertEquals(0, $poll->getVotesCount());
    }

    public function testUpdateRelatedWithQuery()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getVotesCount());

        AggregateItemQuery::create()
            ->update(array('score' => 4), true);

        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query updates the aggregate column');
        $this->assertEquals(2, $poll->getVotesCount());
    }

    public function testUpdateRelatedWithQueryUsingAlias()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getVotesCount());
        AggregateItemQuery::create()
            ->setEntityAlias('foo', true)
            ->update(array('score' => 4), true);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query using alias updates the aggregate column');
        $this->assertEquals(2, $poll->getVotesCount());
    }

    public function testDeleteRelatedWithQuery()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getVotesCount());
        AggregateItemQuery::create()
            ->deleteAll(true);
        $this->assertNull($poll->getTotalScore(), 'Deleting related objects with a query updates the aggregate column');
        $this->assertEquals(0, $poll->getVotesCount());
    }

    public function testDeleteRelatedWithQueryUsingAlias()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(2, $poll->getVotesCount());

        if ($this->runningOnSQLite()) {
            $this->markTestSkipped('Not executed on sqlite');
        }

        AggregateItemQuery::create()
            ->setEntityAlias('foo', true)
            ->filterById($item1->getId())
            ->delete(true);
        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting related objects with a query using alias updates the aggregate column');
        $this->assertEquals(1, $poll->getVotesCount());
    }

    public function testRemoveRelation()
    {
        AggregateCommentQuery::create()->deleteAll();
        AggregatePostQuery::create()->deleteAll();
        $post = new AggregatePost();
        $post->save();
        $comment1 = new AggregateComment();
        $comment1->setAggregatePost($post);
        $comment1->save();
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save();
        $this->assertEquals(2, $post->getCommentsCount());
        $comment2->setAggregatePost(null);
        $comment2->save();
        $this->assertEquals(1, $post->getCommentsCount(), 'Removing a relation changes the related object aggregate column');
    }

    public function testReplaceRelation()
    {
        AggregateCommentQuery::create()->deleteAll();
        AggregatePostQuery::create()->deleteAll();
        $post1 = new AggregatePost();
        $post1->save();
        $post2 = new AggregatePost();
        $post2->save();
        $comment = new AggregateComment();
        $comment->setAggregatePost($post1);
        $comment->save();
        $this->assertEquals(1, $post1->getCommentsCount());
        $this->assertNull($post2->getCommentsCount());
        $comment->setAggregatePost($post2);
        $comment->save();
        $this->assertEquals(0, $post1->getCommentsCount(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(1, $post2->getCommentsCount(), 'Replacing a relation changes the related object aggregate column');
    }

    protected function populatePoll()
    {
        AggregateItemQuery::create()->deleteAll();
        AggregatePollQuery::create()->deleteAll();
        $poll = new AggregatePoll();
        $poll->save();

        $item1 = new AggregateItem();
        $item1->setScore(12);
        $item1->setAggregatePoll($poll);
        $item1->save();

        $item2 = new AggregateItem();
        $item2->setScore(7);
        $item2->setAggregatePoll($poll);
        $item2->save();

        return array($poll, $item1, $item2);
    }

}
