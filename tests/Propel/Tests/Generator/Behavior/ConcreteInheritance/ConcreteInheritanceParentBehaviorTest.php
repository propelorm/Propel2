<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\ConcreteInheritance;

use Propel\Tests\Bookstore\Behavior\ConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContent;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for ConcreteInheritanceParentBehavior class
 *
 * @author François Zaniontto
 *
 * @group database
 */
class ConcreteInheritanceParentBehaviorTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testHasChildObject()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();
        $content = new ConcreteContent();
        $content->save();
        $this->assertFalse($content->hasChildObject());

        $article = new ConcreteArticle();
        $article->save();
        $content = $article->getConcreteContent();
        $this->assertTrue($content->hasChildObject());
    }

    /**
     * @return void
     */
    public function testGetChildObject()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();

        $content = new ConcreteContent();
        $content->save();
        $this->assertNull($content->getChildObject());

        $article = new ConcreteArticle();
        $article->save();
        $content = $article->getConcreteContent();
        $this->assertEquals($article, $content->getChildObject());
    }
}
