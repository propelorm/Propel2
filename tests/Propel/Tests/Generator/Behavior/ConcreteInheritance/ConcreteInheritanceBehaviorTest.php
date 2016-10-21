<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Runtime\Event\SaveEvent;
use Propel\Tests\Bookstore\Behavior\Base\BaseConcreteArticleRepository;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteArticleEntityMap;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteAuthorEntityMap;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteContentEntityMap;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteQuizzEntityMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Behavior\ConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteCategory;
use Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContent;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizz;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery;
use Propel\Runtime\Map\RelationMap;
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
        if (!class_exists('ConcreteContentSetPkQuery')) {
            parent::setUp();

            $schema = <<<EOF
<database name="concrete_content_set_pk" activeRecord="true">
    <entity name="concrete_content_set_pk" allowPkInsert="true">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <index>
            <index-field name="title" />
        </index>
    </entity>
    <entity name="concrete_article_set_pk" allowPkInsert="true">
        <field name="body" type="longvarchar" />
        <field name="author_id" required="false" type="INTEGER" />
        <behavior name="concrete_inheritance">
            <parameter name="extends" value="ConcreteContentSetPk" />
        </behavior>
    </entity>
</database>
EOF;

            QuickBuilder::buildSchema($schema);
        } else {
            $this->configuration = QuickBuilder::$configuration;
        }
    }

    public function testParentBehavior()
    {
        $behaviors = $this->getConfiguration()->getEntityMap(ConcreteContentEntityMap::ENTITY_CLASS)->getBehaviors();
        $this->assertTrue(array_key_exists('concrete_inheritance_parent', $behaviors), 'modifyEntity() gives the parent table the concrete_inheritance_parent behavior');
        $this->assertEquals('descendantClass', $behaviors['concrete_inheritance_parent']['descendant_field'], 'modifyEntity() passed the descendant_Field parameter to the parent behavior');
    }

    public function testmodifyEntityAddsParentField()
    {
        $contentFields = array('id', 'title', 'concreteCategoryId');
        $article = $this->getConfiguration()->getEntityMap(ConcreteArticleEntityMap::ENTITY_CLASS);
        foreach ($contentFields as $field) {
            $this->assertTrue($article->hasField($field), 'modifyEntity() adds the Fields of the parent table');
        }
        $quizz = $this->getConfiguration()->getEntityMap(ConcreteQuizzEntityMap::ENTITY_CLASS);
        $this->assertEquals(3, count($quizz->getFields()), 'modifyEntity() does not add a field of the parent table if a similar field exists');
    }

    public function testmodifyEntityCopyDataAddsOneToOneRelationships()
    {
        $article = $this->getConfiguration()->getEntityMap(ConcreteArticleEntityMap::ENTITY_CLASS);
        $this->assertTrue($article->hasRelation('concreteContent'), 'modifyEntity() adds a relationship to the parent');
        $relation = $article->getRelation('concreteContent');
        $this->assertEquals(RelationMap::MANY_TO_ONE, $relation->getType(), 'modifyEntity adds a one-to-one relationship');
        $content = $this->getConfiguration()->getEntityMap(ConcreteContentEntityMap::ENTITY_CLASS);
        $relation = $content->getRelation('concreteArticle');
        $this->assertEquals(RelationMap::ONE_TO_ONE, $relation->getType(), 'modifyEntity adds a one-to-one relationship');
    }

    public function testmodifyEntityNoCopyDataNoParentRelationship()
    {
        $quizz = $this->getConfiguration()->getEntityMap(ConcreteQuizzEntityMap::ENTITY_CLASS);
        $this->assertFalse($quizz->hasRelation('concreteContent'), 'modifyEntity() does not add a relationship to the parent when copy_data is false');
    }

    public function testmodifyEntityCopyDataRemovesAutoIncrement()
    {
        ConcreteArticleQuery::create()->deleteAll();
        $article = new ConcreteArticle();
        $article->save();
        $this->assertGreaterThan(0, $article->getConcreteContent()->getId());
        $this->assertEquals($article->getConcreteContent()->getId(), $article->getId());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     * @expectedExceptionMessage Cannot insert a value for auto-increment primary key (id)
     */
    public function testmodifyEntityNoCopyDataKeepsAutoIncrement()
    {
        ConcreteQuizzQuery::create()->deleteAll();
        $quizz = new ConcreteQuizz();
        $quizz->setId(5);
        $quizz->save();
    }

    public function testmodifyEntityAddsForeignKeys()
    {
        $article = $this->getConfiguration()->getEntityMap(ConcreteArticleEntityMap::ENTITY_CLASS);
        $this->assertTrue($article->hasRelation('concreteCategory'), 'modifyEntity() copies relationships from parent table');
    }

    public function testmodifyEntityAddsForeignKeysWithoutDuplicates()
    {
        $article = $this->getConfiguration()->getEntityMap(ConcreteAuthorEntityMap::ENTITY_CLASS);
        $this->assertTrue($article->hasRelation('concreteNews'), 'modifyEntity() copies relationships from parent table and removes hardcoded refName');
    }

    // no way to test copying of indices and uniques, except by reverse engineering the db...

    public function testParentObjectClass()
    {
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\ConcreteArticle');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContent', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Model Object to the parent object class');
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\ConcreteQuizz');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContent', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Model Object to the parent object class');
    }

    public function testParentQueryClass()
    {
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\BaseConcreteArticleQuery');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContentQuery', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Query Object to the parent object class');
        $r = new \ReflectionClass('Propel\Tests\Bookstore\Behavior\Base\BaseConcreteQuizzQuery');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteContentQuery', $r->getParentClass()->getName(), 'concrete_inheritance changes the parent class of the Query Object to the parent object class');
    }

    public function testPreSaveCopyData()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();
        ConcreteCategoryQuery::create()->deleteAll();

        $this->markTestSkipped('Problem in copying data');

        $category = new ConcreteCategory();
        $category->setName('main');
        $article = new ConcreteArticle();
        $article->setConcreteCategory($category);
        $article->save();

        $this->assertNotNull($article->getId());
        $this->assertNotNull($category->getId());
        $content = ConcreteContentQuery::create()->findPk($article->getId());
        $this->assertNotNull($content);
        $this->assertEquals($category->getId(), $content->getConcreteCategory()->getId());
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

    public function testPreSaveNew()
    {
        $article = new ConcreteArticle();

        /** @var BaseConcreteArticleRepository $repository */
        $repository = $this->getConfiguration()->getRepository(ConcreteArticleEntityMap::ENTITY_CLASS);

        $event = new SaveEvent($this->getConfiguration()->getSession(), $this->getConfiguration()->getEntityMap(ConcreteArticleEntityMap::ENTITY_CLASS), [$article]);
        $repository->preSave($event);
        $content = $article->getConcreteContent();

        $this->assertTrue($content instanceof ConcreteContent, 'preSave() sets an instance of the parent class');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($content), 'preSave() sets a new instance of the parent class if the object is new');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ConcreteArticle', $content->getDescendantClass(), 'preSave() correctly sets the descendant_class of the parent object');
    }

    public function testPreSaveExisting()
    {
        $article = new ConcreteArticle();
        $article->save();

        /** @var BaseConcreteArticleRepository $repository */
        $repository = $this->getConfiguration()->getRepository(ConcreteArticleEntityMap::ENTITY_CLASS);

        $event = new SaveEvent($this->getConfiguration()->getSession(), $this->getConfiguration()->getEntityMap(ConcreteArticleEntityMap::ENTITY_CLASS), [$article]);
        $repository->preSave($event);
        $content = $article->getConcreteContent();

        $this->assertTrue($content instanceof ConcreteContent, 'preSave() returns an instance of the parent class');
        $this->assertFalse($content->isNew(), 'preSave() returns an existing instance of the parent class if the object is persisted');
        $this->assertEquals($article->getId(), $content->getId(), 'preSave() returns the parent object related to the current object');
    }

    public function testPreSaveExistingParent()
    {
        ConcreteContentQuery::create()->deleteAll();
        ConcreteArticleQuery::create()->deleteAll();

        $content = new ConcreteContent();
        $content->save();
        $this->assertGreaterThan(0, $content->getId());
        $id = $content->getId();
        $this->getConfiguration()->getSession()->clearFirstLevelCache();

        $article = new ConcreteArticle();
        $article->setConcreteContent($content);
        $article->save();

        $this->assertEquals($id, $content->getId());
        $this->assertEquals($id, $article->getId(), 'preSave() keeps manually set pk');
        $this->assertEquals(1, ConcreteContentQuery::create()->count(), 'preSave() creates no new parent entry');
    }

    public function testPostDeleteCopyData()
    {
        ConcreteArticleQuery::create()->deleteAll();
        ConcreteQuizzQuery::create()->deleteAll();
        ConcreteContentQuery::create()->deleteAll();
        ConcreteCategoryQuery::create()->deleteAll();

        $this->markTestSkipped('Problem in copying data');

        $category = new ConcreteCategory();
        $category->setName('main');
        $article = new ConcreteArticle();
        $article->setConcreteCategory($category);
        $article->save();
        $id = $article->getId();
        $article->delete();
        $this->assertNull(ConcreteContentQuery::create()->findPk($id), 'delete() removes the parent record as well');
    }

    public function testSetPKOnNewObject()
    {
        \ConcreteContentSetPkQuery::create()->deleteAll();
        \ConcreteArticleSetPkQuery::create()->deleteAll();

        $article = new \ConcreteArticleSetPk();
        $article->setId(2);
        $article->setTitle('Test');
        $article->save();

        $this->assertEquals(2, $article->getConcreteContentSetPk()->getId());
        $this->assertEquals(2, $article->getId(), 'preSave() keeps manually set pk after save');
        $this->assertEquals(1, \ConcreteContentSetPkQuery::create()->count(), 'preSave() creates a parent entry');
        $this->assertEquals(2, \ConcreteContentSetPkQuery::create()->findOne()->getId(), 'preSave() creates a parent entry');

        $articledb = \ConcreteArticleSetPkQuery::create()->findOneById(2);
        $this->assertEquals(2, $article->getId(), 'preSave() keeps manually set pk after save and reload from db');
        $this->assertEquals('Test', $articledb->getTitle());
        $this->assertEquals(2, $articledb->getId(), 'preSave() keeps manually set pk after save and reload from db');
    }

    /**
     * @expectedException \Propel\Runtime\Persister\Exception\UniqueConstraintException
     * @expectedExceptionMessage Unique constraint failure for field id in entity ConcreteContentSetPk
     */
    public function testSetPKOnNewObjectWithPkAlreadyInParentTable()
    {
        \ConcreteContentSetPkQuery::create()->deleteAll();
        \ConcreteArticleSetPkQuery::create()->deleteAll();

        $article = new \ConcreteArticleSetPk();
        $article->setId(4);
        $article->save();

        $article = new \ConcreteArticleSetPk();
        $article->setId(4);
        $article->save();
    }

    public function testSetPkAllowPkInsertIsFalse()
    {
        ConcreteContentQuery::create()->deleteAll();
        ConcreteArticleQuery::create()->deleteAll();

        $content = new ConcreteContent();
        $content->save();
        $content = new ConcreteContent();
        $content->save();
        $content = new ConcreteContent();
        $content->save();

        $article = new ConcreteArticle();
        $article->setId(1);
        $article->save();

        $this->assertGreaterThan(1, $article->getId());
    }
}
