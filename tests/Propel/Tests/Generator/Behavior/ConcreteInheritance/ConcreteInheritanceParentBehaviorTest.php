<?php

/*
 *	$Id: ConcreteInheritanceBehaviorTest.php 1458 2010-01-13 16:09:51Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Behavior\ConcreteInheritance;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Behavior\ConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteArticlePeer;
use Propel\Tests\Bookstore\Behavior\ConcreteAuthorPeer;
use Propel\Tests\Bookstore\Behavior\ConcreteCategory;
use Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContent;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContentPeer;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizz;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzPeer;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery;

/**
 * Tests for ConcreteInheritanceParentBehavior class
 *
 * @author    François Zaniontto
 * @version   $Revision$
 * @package   generator.behavior.concrete_inheritance
 */
class ConcreteInheritanceParentBehaviorTest extends BookstoreTestBase
{
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
