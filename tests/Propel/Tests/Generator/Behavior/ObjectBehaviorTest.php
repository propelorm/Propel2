<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Tests\Bookstore\Behavior\Table3;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests the generated Object behavior hooks.
 *
 * @author Francois Zaninotto
 */
class ObjectBehaviorTest extends BookstoreTestBase
{
    public static function setUpBeforeClass()
    {
        //prevent issue with wrong DSN
        self::$isInitialized = false;
        parent::setUpBeforeClass();
    }

    public function testObjectAttributes()
    {
        $t = new Table3();
        $this->assertEquals($t->customAttribute, 1, 'objectAttributes hook is called when adding attributes');
    }

    public function testPreSave()
    {
        $t = new Table3();
        $t->preSave = 0;
        $t->save();
        $this->assertEquals($t->preSave, 1, 'preSave hook is called on object insertion');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->preSaveBuilder, 'preSave hook is called with the object builder as parameter');
        $this->assertFalse($t->preSaveIsAfterSave, 'preSave hook is called before save');
        $t->preSave = 0;
        $t->setTitle('foo');
        $t->save();
        $this->assertEquals($t->preSave, 1, 'preSave hook is called on object modification');
    }

    public function testPostSave()
    {
        $t = new Table3();
        $t->postSave = 0;
        $t->save();
        $this->assertEquals($t->postSave, 1, 'postSave hook is called on object insertion');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->postSaveBuilder, 'postSave hook is called with the object builder as parameter');
        $this->assertTrue($t->postSaveIsAfterSave, 'postSave hook is called after save');
        $t->postSave = 0;
        $t->setTitle('foo');
        $t->save();
        $this->assertEquals($t->postSave, 1, 'postSave hook is called on object modification');
    }

    public function testObjectBuilderPreInsert()
    {
        $t = new Table3();
        $t->preInsert = 0;
        $t->save();
        $this->assertEquals($t->preInsert, 1, 'preInsert hook is called on object insertion');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->preInsertBuilder, 'preInsert hook is called with the object builder as parameter');
        $this->assertFalse($t->preInsertIsAfterSave, 'preInsert hook is called before save');
        $t->preInsert = 0;
        $t->setTitle('foo');
        $t->save();
        $this->assertEquals($t->preInsert, 0, 'preInsert hook is not called on object modification');
    }

    public function testPostInsert()
    {
        $t = new Table3();
        $t->postInsert = 0;
        $t->save();
        $this->assertEquals($t->postInsert, 1, 'postInsert hook is called on object insertion');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->postInsertBuilder, 'postInsert hook is called with the object builder as parameter');
        $this->assertTrue($t->postInsertIsAfterSave, 'postInsert hook is called after save');
        $t->postInsert = 0;
        $t->setTitle('foo');
        $t->save();
        $this->assertEquals($t->postInsert, 0, 'postInsert hook is not called on object modification');
    }

    public function testPreUpdate()
    {
        $t = new Table3();
        $t->preUpdate = 0;
        $t->save();
        $this->assertEquals($t->preUpdate, 0, 'preUpdate hook is not called on object insertion');
        $t->preUpdate = 0;
        $t->setTitle('foo');
        $t->save();
        $this->assertEquals($t->preUpdate, 1, 'preUpdate hook is called on object modification');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->preUpdateBuilder, 'preUpdate hook is called with the object builder as parameter');
        $this->assertFalse($t->preUpdateIsAfterSave, 'preUpdate hook is called before save');
    }

    public function testPostUpdate()
    {
        $t = new Table3();
        $t->postUpdate = 0;
        $t->save();
        $this->assertEquals($t->postUpdate, 0, 'postUpdate hook is not called on object insertion');
        $t->postUpdate = 0;
        $t->setTitle('foo');
        $t->save();
        $this->assertEquals($t->postUpdate, 1, 'postUpdate hook is called on object modification');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->postUpdateBuilder, 'postUpdate hook is called with the object builder as parameter');
        $this->assertTrue($t->postUpdateIsAfterSave, 'postUpdate hook is called after save');
    }

    public function testPreDelete()
    {
        $t = new Table3();
        $t->save();
        $this->preDelete = 0;
        $t->delete();
        $this->assertEquals($t->preDelete, 1, 'preDelete hook is called on object deletion');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->preDeleteBuilder, 'preDelete hook is called with the object builder as parameter');
        $this->assertTrue($t->preDeleteIsBeforeDelete, 'preDelete hook is called before deletion');
    }

    public function testPostDelete()
    {
        $t = new Table3();
        $t->save();
        $this->postDelete = 0;
        $t->delete();
        $this->assertEquals($t->postDelete, 1, 'postDelete hook is called on object deletion');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->postDeleteBuilder, 'postDelete hook is called with the object builder as parameter');
        $this->assertFalse($t->postDeleteIsBeforeDelete, 'postDelete hook is called before deletion');
    }

    public function testObjectMethods()
    {
        $t = new Table3();
        $this->assertTrue(method_exists($t, 'hello'), 'objectMethods hook is called when adding methods');
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', $t->hello(), 'objectMethods hook is called with the object builder as parameter');
    }

    public function testObjectCall()
    {
        $t = new Table3();
        $this->assertEquals('bar', $t->foo(), 'objectCall hook is called when building the magic __call()');
    }

    public function testObjectFilter()
    {
        $t = new Table3();
        $this->assertTrue(class_exists('Propel\Tests\Bookstore\Behavior\Base\testObjectFilter'),
            'objectFilter hook allows complete manipulation of the generated script'
        );
        $this->assertEquals('Propel\Generator\Builder\Om\ObjectBuilder', \Propel\Tests\Bookstore\Behavior\Base\testObjectFilter::FOO,
            'objectFilter hook is called with the object builder as parameter'
        );
    }
}
