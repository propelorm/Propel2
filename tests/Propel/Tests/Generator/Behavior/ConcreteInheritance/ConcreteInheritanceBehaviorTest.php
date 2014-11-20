<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Behavior\ConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteArticleTableMap;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteAuthorTableMap;
use Propel\Tests\Bookstore\Behavior\ConcreteCategory;
use Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContent;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteContentTableMap;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizz;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteQuizzTableMap;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery;

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\ActiveQuery\Criteria;

use Propel\Generator\Util\QuickBuilder;

/**
 * Tests for ConcreteInheritanceBehavior class
 *
 * @author François Zaniontto
 *
 * @group database
 */
class ConcreteInheritanceBehaviorTest extends BookstoreTestBase
{
    public function setUp()
    {
        parent::setUp();

        if (!class_exists('ConcreteContentSetPkQuery')) {
            $schema = <<<EOF
<database name="concrete_content_set_pk">
    <table name="concrete_content_set_pk" allowPkInsert="true">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
        <index>
            <index-column name="title" />
        </index>
    </table>
    <table name="concrete_article_set_pk" allowPkInsert="true">
        <column name="body" type="longvarchar" />
        <column name="author_id" required="false" type="INTEGER" />
        <behavior name="concrete_inheritance">
            <parameter name="extends" value="concrete_content_set_pk" />
            <parameter name="copy_data_to_child" value="title" />
        </behavior>
    </table>
</database>
EOF;

            QuickBuilder::buildSchema($schema);
        }
    }

    public function testCopyToChild()
    {
        $article = new \ConcreteArticleSetPk();
        $this->assertTrue(method_exists($article, 'getSyncParent'));
        $this->assertTrue(method_exists($article, 'syncParentToChild'));
        $parent = $article->getSyncParent();
        $parent->setTitle('test title');
        $article->syncParentToChild($parent);
        $this->assertEquals('test title', $parent->getTitle());
        $article->save();
        $this->assertEquals('test title', $article->getTitle());
    }

    public function testParentBehavior()
    {
        $behaviors = ConcreteContentTableMap::getTableMap()->getBehaviors();
        $this->assertTrue(array_key_exists('concrete_inheritance_parent', $behaviors), 'modifyTable() gives the parent table the concrete_inheritance_parent behavior');
        $this->assertEquals('descendant_class', $behaviors['concrete_inheritance_parent']['descendant_column'], 'modifyTable() passed the descendant_column parameter to the parent behavior');
    }

    public function testModifyTableAddsParentColumn()
    {
        $contentColumns = array('id', 'title', 'category_id');
        $article = ConcreteArticleTableMap::getTableMap();
        foreach ($contentColumns as $column) {
            $this->assertTrue($article->hasColumn($column), 'modifyTable() adds the columns of the parent table');
        }
        $quizz = ConcreteQuizzTableMap::getTableMap();
        $this->assertEquals(3, count($quizz->getColumns()), 'modifyTable() does not add a column of the parent table if a similar column exists');
    }

    public function testModifyTableCopyDataAddsOneToOneRelationships()
    {
        $article = ConcreteArticleTableMap::getTableMap();
        $this->assertTrue($article->hasRelation('ConcreteContent'), 'modifyTable() adds a relationship to the parent');
        $relation = $article->getRelation('ConcreteContent');
        $this->assertEquals(RelationMap::MANY_TO_ONE, $relation->getType(), 'modifyTable adds a one-to-one relationship');
        $content = ConcreteContentTableMap::getTableMap();
        $relation = $content->getRelation('ConcreteArticle');
        $this->assertEquals(RelationMap::ONE_TO_ONE, $relation->getType(), 'modifyTable adds a one-to-one relationship');
    }

    public function testModifyTableNoCopyDataNoParentRelationship()
    {
        $quizz = ConcreteQuizzTableMap::getTableMap();
        $this->assertFalse($quizz->hasRelation('ConcreteContent'), 'modifyTable() does not add a relationship to the parent when copy_data is false');
    }

    public function testModifyTableCopyDataRemovesAutoIncrement()
    {
        $content = new ConcreteContent();
        $content->save();
        $c = new Criteria;
        $c->add(ConcreteArticleTableMap::COL_ID, $content->getId());
        try {
            ConcreteArticleTableMap::doInsert($c);
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
        $c->add(ConcreteQuizzTableMap::COL_ID, $content->getId());
        ConcreteQuizzTableMap::doInsert($c);
    }

    public function testModifyTableAddsForeignKeys()
    {
        $article = ConcreteArticleTableMap::getTableMap();
        $this->assertTrue($article->hasRelation('ConcreteCategory'), 'modifyTable() copies relationships from parent table');
    }

    public function testModifyTableAddsForeignKeysWithoutDuplicates()
    {
        $article = ConcreteAuthorTableMap::getTableMap();
        $this->assertTrue($article->hasRelation('ConcreteNews'), 'modifyTable() copies relationships from parent table and removes hardcoded refPhpName');
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
        ConcreteContentTableMap::clearInstancePool();
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
        ConcreteContentTableMap::clearInstancePool();
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

    public function testGetParentOrCreateNewWithPK()
    {
        \ConcreteContentSetPkQuery::create()->deleteAll();
        \ConcreteArticleSetPkQuery::create()->deleteAll();
        $article = new \ConcreteArticleSetPk();
        $article->setId(5);
        $content = $article->getParentOrCreate();
        $this->assertEquals(5, $article->getId(), 'getParentOrCreate() keeps manually set pk');
        $this->assertTrue($content instanceof \ConcreteContentSetPk, 'getParentOrCreate() returns an instance of the parent class');
        $this->assertTrue($content->isNew(), 'getParentOrCreate() returns a new instance of the parent class if the object is new');
        $this->assertEquals(5,$content->getId(), 'getParentOrCreate() returns a instance of the parent class with pk set');
        $this->assertEquals('ConcreteArticleSetPk', $content->getDescendantClass(), 'getParentOrCreate() correctly sets the descendant_class of the parent object');
    }

    public function testSetPKOnNewObject()
    {
        \ConcreteContentSetPkQuery::create()->deleteAll();
        \ConcreteArticleSetPkQuery::create()->deleteAll();
        $article = new \ConcreteArticleSetPk();
        $article->setId(2);
        $article->save();
        $this->assertEquals(2, $article->getId(), 'getParentOrCreate() keeps manually set pk after save');
        $this->assertEquals(1, \ConcreteContentSetPkQuery::create()->count(), 'getParentOrCreate() creates a parent entry');
        $articledb = \ConcreteArticleSetPkQuery::create()->findOneById(2);
        $this->assertEquals(2, $articledb->getId(), 'getParentOrCreate() keeps manually set pk after save and reload from db');
    }

    public function testSetPKOnNewObjectWithPkAlreadyInParentTable()
    {
        \ConcreteContentSetPkQuery::create()->deleteAll();
        \ConcreteArticleSetPkQuery::create()->deleteAll();
        try {
            $article = new \ConcreteArticleSetPk();
            $article->setId(4);
            $article->save();
            $article = new \ConcreteArticleSetPk();
            $article->setId(4);
            $article->save();
            $this->fail('getParentOrCreate() returns a new parent object on new child objects with pk set');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'getParentOrCreate() returns a new parent object on new child objects with pk set');
        }
    }

    public function testSetPkAllowPkInsertIsFalse()
    {
        ConcreteContentQuery::create()->deleteAll();
        ConcreteArticleQuery::create()->deleteAll();
        try {
            $article = new ConcreteArticle();
            $article->setId(4);
            $article->save();
            $this->fail('SetPk fails when allowPkInsert is false');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'SetPk fails when allowPkInsert is false');
        }
    }

}
