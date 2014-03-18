<?php

/*
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests for More relations
 *
 * @author MArc J. Schmidt
 */
class GeneratedObjectMoreRelationTest extends TestCase
{
    /**
     * Setup schema und some default data
     */
    public function setUp()
    {
        parent::setUp();

        if (!class_exists('MoreRelationTest\Page')) {
            $schema = <<<EOF
<database name="more_relation_test" namespace="MoreRelationTest">

    <table name="more_relation_test_page" phpName="Page">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
    </table>

    <table name="more_relation_test_content" phpName="Content">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" />
        <column name="content" type="LONGVARCHAR" required="false" />
        <column name="page_id" type="INTEGER" required="false" />
        <foreign-key foreignTable="more_relation_test_page" onDelete="cascade">
          <reference local="page_id" foreign="id"/>
        </foreign-key>
    </table>

    <table name="more_relation_test_comment" phpName="Comment">
        <column name="user_id" required="true" primaryKey="true" type="INTEGER" />
        <column name="page_id" required="true" primaryKey="true" type="INTEGER" />
        <column name="comment" type="VARCHAR" size="100" />
        <foreign-key foreignTable="more_relation_test_page" onDelete="cascade">
          <reference local="page_id" foreign="id"/>
        </foreign-key>
    </table>

    <table name="more_relation_test_content_comment" phpName="ContentComment">
        <column name="id" required="true" autoIncrement="true" primaryKey="true" type="INTEGER" />
        <column name="content_id" type="INTEGER" />
        <column name="comment" type="VARCHAR" size="100" />
        <foreign-key foreignTable="more_relation_test_content" onDelete="setnull">
          <reference local="content_id" foreign="id"/>
        </foreign-key>
    </table>

</database>
EOF;

            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            $builder->build();
        }

        \MoreRelationTest\ContentCommentQuery::create()->doDeleteAll();
        \MoreRelationTest\ContentQuery::create()->doDeleteAll();
        \MoreRelationTest\CommentQuery::create()->doDeleteAll();
        \MoreRelationTest\PageQuery::create()->doDeleteAll();

        for ($i=1;$i<=2;$i++) {

            $page = new \MoreRelationTest\Page();

            $page->setTitle('Page '.$i);
            for ($j=1;$j<=3;$j++) {

                $content = new \MoreRelationTest\Content();
                $content->setTitle('Content '.$j);
                $content->setContent(str_repeat('Content', $j));
                $page->addContent($content);

                $comment = new \MoreRelationTest\Comment();
                $comment->setUserId($j);
                $comment->setComment(str_repeat('Comment', $j));
                $page->addComment($comment);

                $comment = new \MoreRelationTest\ContentComment();
                $comment->setContentId($i*$j);
                $comment->setComment(str_repeat('Comment-'.$j.', ', $j));
                $content->addContentComment($comment);

            }

            $page->save();
        }
    }

    public function testRefRelation()
    {
        /** @var $page \MoreRelationTest\Page */
        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $comments = $page->getComments();
        $count = count($comments);

        $comment = new \MoreRelationTest\Comment();
        $comment->setComment('Comment 1');
        $comment->setUserId(123);
        $comment->setPage($page);

        $this->assertCount($count + 1, $comments);
        $this->assertCount($count + 1, $page->getComments());

        //remove
        $page->removeComment($comment);
        $this->assertCount($count, $comments);
        $this->assertCount($count, $page->getComments());
    }

    public function testDuplicate()
    {
        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $pageComment = \MoreRelationTest\CommentQuery::create()->filterByPage($page)->findOne();
        $currentCount = count($page->getComments());

        \MoreRelationTest\Map\PageTableMap::clearInstancePool();
        /** @var $newPageObject \MoreRelationTest\Page */
        $newPageObject = \MoreRelationTest\PageQuery::create()->findOne(); //resets the cached comments through getComments()
        $newPageObject->addComment($pageComment);

        $this->assertCount($currentCount, $newPageObject->getComments(), 'same count as before');
    }

    /**
     * Composite PK deletion of a 1-to-n relation through set<RelationName>() and remove<RelationName>()
     * where the PK is at the same time a FK.
     */
    public function testCommentsDeletion()
    {
        $commentCollection = new ObjectCollection();
        $commentCollection->setModel('MoreRelationTest\\Comment');

        $comment = new \MoreRelationTest\Comment();
        $comment->setComment('I should be alone :-(');
        $comment->setUserId(123);

        $commentCollection[] = $comment;

        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $id = $page->getId();

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(3, $count, 'We created for each page 3 comments.');

        $page->setComments($commentCollection);
        $page->save();

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(1, $count, 'We assigned a collection of only one item.');

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId(NULL)->count();
        $this->assertEquals(0, $count, 'There should be no unassigned comment.');

        $page->removeComment($comment);

        $page->save();

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(0, $count);

        $count = \MoreRelationTest\CommentQuery::create()->filterByPageId(NULL)->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Deletion of a 1-to-n relation through set<RelationName>()
     * with onDelete=setnull
     */
    public function testContentCommentDeletion()
    {
        $commentCollection = new ObjectCollection();
        $commentCollection->setModel('MoreRelationTest\\ContentComment');

        $comment = new \MoreRelationTest\ContentComment();
        $comment->setComment('I\'m Mario');
        $commentCollection[] = $comment;

        $comment2 = new \MoreRelationTest\ContentComment();
        $comment2->setComment('I\'m Mario\'s friend');
        $commentCollection[] = $comment2;

        $content = \MoreRelationTest\ContentQuery::create()->findOne();
        $id = $content->getId();

        $count = \MoreRelationTest\ContentCommentQuery::create()->filterByContentId($id)->count();
        $this->assertEquals(1, $count, 'We created for each page 1 comments.');

        $content->setContentComments($commentCollection);
        $content->save();

        unset($content);

        $count = \MoreRelationTest\ContentCommentQuery::create()->filterByContentId($id)->count();
        $this->assertEquals(2, $count, 'We assigned a collection of two items.');

        $count = \MoreRelationTest\ContentCommentQuery::create()->filterByContentId(NULL)->count();
        $this->assertEquals(1, $count, 'There should be one unassigned contentComment.');

    }

    /**
     * Basic deletion of a 1-to-n relation through set<RelationName>().
     *
     */
    public function testContentsDeletion()
    {
        $contentCollection = new ObjectCollection();
        $contentCollection->setModel('MoreRelationTest\\Content');

        $content = new \MoreRelationTest\Content();
        $content->setTitle('I should be alone :-(');

        $contentCollection[] = $content;

        $page = \MoreRelationTest\PageQuery::create()->findOne();
        $id = $page->getId();

        $count = \MoreRelationTest\ContentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(3, $count, 'We created for each page 3 contents.');

        $page->setContents($contentCollection);
        $page->save();

        unset($page);

        $count = \MoreRelationTest\ContentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(1, $count, 'We assigned a collection of only one item.');
    }

    public function testOnDeleteCascadeNotRequired()
    {
        \MoreRelationTest\PageQuery::create()->doDeleteAll();
        \MoreRelationTest\ContentQuery::create()->doDeleteAll();

        $page = new \MoreRelationTest\Page();
        $page->setTitle('Some important Page');

        $content = new \MoreRelationTest\Content();
        $content->setTitle('Content');

        $page->addContent($content);
        $page->save();

        $this->assertEquals(1, \MoreRelationTest\ContentQuery::create()->count());

        $page->removeContent($content);
        $page->save();

        $this->assertEquals(0, \MoreRelationTest\ContentQuery::create()->count());
    }
}
