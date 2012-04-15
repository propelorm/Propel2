<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Behavior;

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

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Query\Criteria;

/**
 * Tests for ConcreteInheritanceBehavior class
 *
 * @author    François Zaniontto
 * @version   $Revision$
 */
class ConcreteInheritanceBehaviorTest extends BookstoreTestBase
{
    public function testParentBehavior()
    {
        $behaviors = ConcreteContentPeer::getTableMap()->getBehaviors();
        $this->assertTrue(array_key_exists('concrete_inheritance_parent', $behaviors), 'modifyTable() gives the parent table the concrete_inheritance_parent behavior');
        $this->assertEquals('descendant_class', $behaviors['concrete_inheritance_parent']['descendant_column'], 'modifyTable() passed the descendent_column parameter to the parent behavior');
    }

    public function testModifyTableAddsParentColumn()
    {
        $contentColumns = array('id', 'title', 'category_id');
        $article = ConcreteArticlePeer::getTableMap();
        foreach ($contentColumns as $column) {
            $this->assertTrue($article->hasColumn($column), 'modifyTable() adds the columns of the parent table');
        }
        $quizz = ConcreteQuizzPeer::getTableMap();
        $this->assertEquals(3, count($quizz->getColumns()), 'modifyTable() does not add a column of the parent table if a similar column exists');
    }

    public function testModifyTableCopyDataAddsOneToOneRelationships()
    {
        $article = ConcreteArticlePeer::getTableMap();
        $this->assertTrue($article->hasRelation('ConcreteContent'), 'modifyTable() adds a relationship to the parent');
        $relation = $article->getRelation('ConcreteContent');
        $this->assertEquals(RelationMap::MANY_TO_ONE, $relation->getType(), 'modifyTable adds a one-to-one relationship');
        $content = ConcreteContentPeer::getTableMap();
        $relation = $content->getRelation('ConcreteArticle');
        $this->assertEquals(RelationMap::ONE_TO_ONE, $relation->getType(), 'modifyTable adds a one-to-one relationship');
    }

    public function testModifyTableNoCopyDataNoParentRelationship()
    {
        $quizz = ConcreteQuizzPeer::getTableMap();
        $this->assertFalse($quizz->hasRelation('ConcreteContent'), 'modifyTable() does not add a relationship to the parent when copy_data is false');
    }

    public function testModifyTableCopyDataRemovesAutoIncrement()
    {
        $content = new ConcreteContent();
        $content->save();
        $c = new Criteria;
        $c->add(ConcreteArticlePeer::ID, $content->getId());
        try {
            ConcreteArticlePeer::doInsert($c);
            $this->assertTrue(true, 'modifyTable() removed autoIncrement from copied Primary keys');
        } catch (PropelException $e) {
            $this->fail('modifyTable() removed autoIncrement from copied Primary keys');
        }
    }

    public function testModifyTableNoCopyDataKeepsAutoIncrement()
    {
        $this->setExpectedException('Propel\\Runtime\\Exception\\PropelException');
        $content = new ConcreteContent();
        $content->save();
        $c = new Criteria;
        $c->add(ConcreteQuizzPeer::ID, $content->getId());
        ConcreteQuizzPeer::doInsert($c);
    }

    public function testModifyTableAddsForeignKeys()
    {
        $article = ConcreteArticlePeer::getTableMap();
        $this->assertTrue($article->hasRelation('ConcreteCategory'), 'modifyTable() copies relationships from parent table');
    }

    public function testModifyTableAddsForeignKeysWithoutDuplicates()
    {
        $article = ConcreteAuthorPeer::getTableMap();
        $this->assertTrue($article->hasRelation('ConcreteNews'), 'modifyTable() copies relationships from parent table and removes hardcoded refPhpName');
    }

    public function testModifyTableAddsValidators()
    {
        $article = ConcreteArticlePeer::getTableMap();
        $this->assertTrue($article->getColumn('title')->hasValidators(), 'modifyTable() copies validators from parent table');
    }

    // no way to test copying of indices and uniques, except by reverse engineering the db...

    public function testParentObjectClass()
    {
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\ConcreteArticle');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContent', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Model Object to the parent object class');
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\ConcreteQuizz');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContent', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Model Object to the parent object class');
    }

    public function testParentQueryClass()
    {
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\ConcreteArticleQuery');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContentQuery', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Query Object to the parent object class');
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\ConcreteQuizzQuery');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContentQuery', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Query Object to the parent object class');
    }

    /**
     * @link http://www.propelorm.org/ticket/1262
     */
    public function testParentPeerClass()
    {
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\ConcreteArticlePeer');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContentPeer', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Peer Object to the parent object class');
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\ConcreteQuizzPeer');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContentPeer', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Peer Object to the parent object class');
    }

    public function testPreSaveCopyData()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();
        ConcreteCategoryQuery::create()->deleteAll();
        $category = new ConcreteCategory();
        $category->setName('main');
        $article = new ConcreteArticle();
        $article->setConcreteCategory($category);
        $article->save();
        $this->assertNotNull($article->getId());
        $this->assertNotNull($category->getId());
        $content = ConcreteContentQuery::create()->findPk($article->getId());
        $this->assertNotNull($content);
        $this->assertEquals($category->getId(), $content->getCategoryId());
    }

    public function testPreSaveNoCopyData()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();
        $quizz = new ConcreteQuizz();
        $quizz->save();
        $this->assertNotNull($quizz->getId());
        $content = ConcreteContentQuery::create()->findPk($quizz->getId());
        $this->assertNull($content);
    }

    public function testGetParentOrCreateNew()
    {
        $article = new ConcreteArticle();
        $content = $article->getParentOrCreate();
        $this->assertTrue($content instanceof ConcreteContent, 'getParentOrCreate() returns an instance of the parent class');
        $this->assertTrue($content->isNew(), 'getParentOrCreate() returns a new instance of the parent class if the object is new');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteArticle', $content->getDescendantClass(), 'getParentOrCreate() correctly sets the descendant_class of the parent object');
    }

    public function testGetParentOrCreateExisting()
    {
        $article = new ConcreteArticle();
        $article->save();
        ConcreteContentPeer::clearInstancePool();
        $content = $article->getParentOrCreate();
        $this->assertTrue($content instanceof ConcreteContent, 'getParentOrCreate() returns an instance of the parent class');
        $this->assertFalse($content->isNew(), 'getParentOrCreate() returns an existing instance of the parent class if the object is persisted');
        $this->assertEquals($article->getId(), $content->getId(), 'getParentOrCreate() returns the parent object related to the current object');
    }

    public function testGetParentOrCreateExistingParent()
    {
        ConcreteContentQuery::create()->deleteAll();
        ConcreteArticleQuery::create()->deleteAll();
        $content = new ConcreteContent();
        $content->save();
        $id = $content->getId();
        ConcreteContentPeer::clearInstancePool();
        $article = new ConcreteArticle();
        $article->setId($id);
        $article->save();
        $this->assertEquals($id, $article->getId(), 'getParentOrCreate() keeps manually set pk');
        $this->assertEquals(1, ConcreteContentQuery::create()->count(), 'getParentOrCreate() creates no new parent entry');
    }

    public function testGetSyncParent()
    {
        $category = new ConcreteCategory();
        $category->setName('main');
        $article = new ConcreteArticle();
        $article->setTitle('FooBar');
        $article->setConcreteCategory($category);
        $content = $article->getSyncParent();
        $this->assertEquals('FooBar', $content->getTitle(), 'getSyncParent() returns a synchronized parent object');
        $this->assertEquals($category, $content->getConcreteCategory(), 'getSyncParent() returns a synchronized parent object');
    }

    public function testPostDeleteCopyData()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();
        ConcreteCategoryQuery::create()->deleteAll();
        $category = new ConcreteCategory();
        $category->setName('main');
        $article = new ConcreteArticle();
        $article->setConcreteCategory($category);
        $article->save();
        $id = $article->getId();
        $article->delete();
        $this->assertNull(ConcreteContentQuery::create()->findPk($id), 'delete() removes the parent record as well');
    }

}
