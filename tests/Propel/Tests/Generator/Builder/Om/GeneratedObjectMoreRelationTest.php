<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use MoreRelationTest\Comment;
use MoreRelationTest\CommentQuery;
use MoreRelationTest\Content;
use MoreRelationTest\ContentComment;
use MoreRelationTest\ContentCommentQuery;
use MoreRelationTest\ContentQuery;
use MoreRelationTest\Map\PageTableMap;
use MoreRelationTest\Page;
use MoreRelationTest\PageQuery;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
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
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists('MoreRelationTest\Page')) {
            $schema = <<<EOF
<database name="more_relation_test" namespace="MoreRelationTest">

    <table name="more_relation_test_page" phpName="Page">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="more_relation_test_content" phpName="Content">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100"/>
        <column name="content" type="LONGVARCHAR" required="false"/>
        <column name="page_id" type="INTEGER" required="false"/>
        <foreign-key foreignTable="more_relation_test_page" onDelete="cascade">
          <reference local="page_id" foreign="id"/>
        </foreign-key>
    </table>

    <table name="more_relation_test_comment" phpName="Comment">
        <column name="user_id" required="true" primaryKey="true" type="INTEGER"/>
        <column name="page_id" required="true" primaryKey="true" type="INTEGER"/>
        <column name="comment" type="VARCHAR" size="100"/>
        <foreign-key foreignTable="more_relation_test_page" onDelete="cascade">
          <reference local="page_id" foreign="id"/>
        </foreign-key>
    </table>

    <table name="more_relation_test_content_comment" phpName="ContentComment">
        <column name="id" required="true" autoIncrement="true" primaryKey="true" type="INTEGER"/>
        <column name="content_id" type="INTEGER"/>
        <column name="comment" type="VARCHAR" size="100"/>
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

        ContentCommentQuery::create()->doDeleteAll();
        ContentQuery::create()->doDeleteAll();
        CommentQuery::create()->doDeleteAll();
        PageQuery::create()->doDeleteAll();

        for ($i = 1; $i <= 2; $i++) {
            $page = new Page();

            $page->setTitle('Page ' . $i);
            for ($j = 1; $j <= 3; $j++) {
                $content = new Content();
                $content->setTitle('Content ' . $j);
                $content->setContent(str_repeat('Content', $j));
                $page->addContent($content);

                $comment = new Comment();
                $comment->setUserId($j);
                $comment->setComment(str_repeat('Comment', $j));
                $page->addComment($comment);

                $comment = new ContentComment();
                $comment->setContentId($i * $j);
                $comment->setComment(str_repeat('Comment-' . $j . ', ', $j));
                $content->addContentComment($comment);
            }

            $page->save();
        }
    }

    /**
     * @return void
     */
    public function testRefRelation()
    {
        /** @var \MoreRelationTest\Page $page */
        $page = PageQuery::create()->findOne();
        $comments = $page->getComments();
        $count = count($comments);

        $comment = new Comment();
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

    /**
     * @return void
     */
    public function testDuplicate()
    {
        $page = PageQuery::create()->findOne();
        $pageComment = CommentQuery::create()->filterByPage($page)->findOne();
        $currentCount = count($page->getComments());

        PageTableMap::clearInstancePool();
        /** @var \MoreRelationTest\Page $newPageObject */
        $newPageObject = PageQuery::create()->findOne(); //resets the cached comments through getComments()
        $newPageObject->addComment($pageComment);

        $this->assertCount($currentCount, $newPageObject->getComments(), 'same count as before');
    }

    /**
     * Composite PK deletion of a 1-to-n relation through set<RelationName>() and remove<RelationName>()
     * where the PK is at the same time a FK.
     *
     * @return void
     */
    public function testCommentsDeletion()
    {
        $commentCollection = new ObjectCollection();
        $commentCollection->setModel('MoreRelationTest\\Comment');

        $comment = new Comment();
        $comment->setComment('I should be alone :-(');
        $comment->setUserId(123);

        $commentCollection[] = $comment;

        $page = PageQuery::create()->findOne();
        $id = $page->getId();

        $count = CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(3, $count, 'We created for each page 3 comments.');

        $page->setComments($commentCollection);
        $page->save();

        $count = CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(1, $count, 'We assigned a collection of only one item.');

        $count = CommentQuery::create()->filterByPageId(null)->count();
        $this->assertEquals(0, $count, 'There should be no unassigned comment.');

        $page->removeComment($comment);

        $page->save();

        $count = CommentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(0, $count);

        $count = CommentQuery::create()->filterByPageId(null)->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Deletion of a 1-to-n relation through set<RelationName>()
     * with onDelete=setnull
     *
     * @return void
     */
    public function testContentCommentDeletion()
    {
        $commentCollection = new ObjectCollection();
        $commentCollection->setModel('MoreRelationTest\\ContentComment');

        $comment = new ContentComment();
        $comment->setComment('I\'m Mario');
        $commentCollection[] = $comment;

        $comment2 = new ContentComment();
        $comment2->setComment('I\'m Mario\'s friend');
        $commentCollection[] = $comment2;

        $content = ContentQuery::create()->findOne();
        $id = $content->getId();

        $count = ContentCommentQuery::create()->filterByContentId($id)->count();
        $this->assertEquals(1, $count, 'We created for each page 1 comments.');

        $content->setContentComments($commentCollection);
        $content->save();

        unset($content);

        $count = ContentCommentQuery::create()->filterByContentId($id)->count();
        $this->assertEquals(2, $count, 'We assigned a collection of two items.');

        $count = ContentCommentQuery::create()->filterByContentId(null)->count();
        $this->assertEquals(1, $count, 'There should be one unassigned contentComment.');
    }

    /**
     * Basic deletion of a 1-to-n relation through set<RelationName>().
     *
     * @return void
     */
    public function testContentsDeletion()
    {
        $contentCollection = new ObjectCollection();
        $contentCollection->setModel('MoreRelationTest\\Content');

        $content = new Content();
        $content->setTitle('I should be alone :-(');

        $contentCollection[] = $content;

        $page = PageQuery::create()->findOne();
        $id = $page->getId();

        $count = ContentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(3, $count, 'We created for each page 3 contents.');

        $page->setContents($contentCollection);
        $page->save();

        unset($page);

        $count = ContentQuery::create()->filterByPageId($id)->count();
        $this->assertEquals(1, $count, 'We assigned a collection of only one item.');
    }

    /**
     * @return void
     */
    public function testOnDeleteCascadeNotRequired()
    {
        PageQuery::create()->doDeleteAll();
        ContentQuery::create()->doDeleteAll();

        $page = new Page();
        $page->setTitle('Some important Page');

        $content = new Content();
        $content->setTitle('Content');

        $page->addContent($content);
        $page->save();

        $this->assertEquals(1, ContentQuery::create()->count());

        $page->removeContent($content);
        $page->save();

        $this->assertEquals(0, ContentQuery::create()->count());
    }
}
