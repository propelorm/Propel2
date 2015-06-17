<?php

/*
 *	$Id: ConcreteInheritanceBehaviorTest.php 1458 2010-01-13 16:09:51Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Runtime\Event\SaveEvent;
use Propel\Tests\BookstoreSchemas\Base\BaseSecondHandBookRepository;
use Propel\Tests\BookstoreSchemas\Book;
use Propel\Tests\BookstoreSchemas\Map\SecondHandBookEntityMap;
use Propel\Tests\BookstoreSchemas\SecondHandBook;
use Propel\Tests\BookstoreSchemas\Map\BookEntityMap;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Tests for ConcreteInheritanceBehavior class
 *
 * @author François Zaniontto
 *
 * @group database
 */
class ConcreteInheritanceBehaviorWithSchemaTest extends TestCaseFixturesDatabase
{
    public function testParentBehaviorWithSchemas()
    {
        $behaviors = $this->getConfiguration()->getEntityMap(BookEntityMap::ENTITY_CLASS)->getBehaviors();
        $this->assertTrue(array_key_exists('concrete_inheritance_parent', $behaviors), 'modifyEntity() gives the parent table the concrete_inheritance_parent behavior');
        $this->assertEquals('descendantClass', $behaviors['concrete_inheritance_parent']['descendant_field'], 'modifyEntity() passed the descendant_column parameter to the parent behavior');
    }

    public function testGetParentOrCreateNewWithSchemas()
    {
        $second_hand_book = new SecondHandBook();

        /** @var BaseSecondHandBookRepository $repository */
        $repository = $this->getConfiguration()->getRepository(SecondHandBookEntityMap::ENTITY_CLASS);

        $event = new SaveEvent($this->getConfiguration()->getSession(), $this->getConfiguration()->getEntityMap(SecondHandBookEntityMap::ENTITY_CLASS), [$second_hand_book]);
        $repository->preSave($event);
        $book = $second_hand_book->getBook();

        $this->assertTrue($book instanceof Book, 'getParentOrCreate() returns an instance of the parent class');
        $this->assertTrue($this->getConfiguration()->getSession()->isNew($book), 'getParentOrCreate() returns a new instance of the parent class if the object is new');
        $this->assertEquals('Propel\Tests\BookstoreSchemas\SecondHandBook', $book->getDescendantClass(), 'getParentOrCreate() correctly sets the descendant_class of the parent object');
    }

}
