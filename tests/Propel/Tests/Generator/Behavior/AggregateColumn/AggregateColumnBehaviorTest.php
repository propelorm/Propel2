<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\AggregateColumn;

use Propel\Tests\Bookstore\Behavior\AggregateComment;
use Propel\Tests\Bookstore\Behavior\AggregateCommentQuery;
use Propel\Tests\Bookstore\Behavior\AggregateItem;
use Propel\Tests\Bookstore\Behavior\AggregateItemQuery;
use Propel\Tests\Bookstore\Behavior\AggregatePoll;
use Propel\Tests\Bookstore\Behavior\AggregatePollQuery;
use Propel\Tests\Bookstore\Behavior\AggregatePost;
use Propel\Tests\Bookstore\Behavior\AggregatePostQuery;
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
