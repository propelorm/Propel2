<?php
/*
 *  $Id: ObjectBehaviorTest.php 1169 2009-09-28 20:07:02Z francois $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests the generated Object behavior hooks.
 *
 * @author     Francois Zaninotto
 * @package    generator.engine.behavior
 */
class ObjectBehaviorTest extends BookstoreTestBase
{
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
    $this->assertTrue($t->postSaveIsAfterSave, 'postSave hook is called after save');
    $t->postSave = 0;
    $t->setTitle('foo');
    $t->save();
    $this->assertEquals($t->postSave, 1, 'postSave hook is called on object modification');
  }
  
  public function testPreInsert()
  {
    $t = new Table3();
    $t->preInsert = 0;
    $t->save();
    $this->assertEquals($t->preInsert, 1, 'preInsert hook is called on object insertion');
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
    $this->assertTrue($t->postUpdateIsAfterSave, 'postUpdate hook is called after save');
  }
  
  public function testPreDelete()
  {
    $t = new Table3();
    $t->save();
    $this->preDelete = 0;
    $t->delete();
    $this->assertEquals($t->preDelete, 1, 'preDelete hook is called on object deletion');
    $this->assertTrue($t->preDeleteIsBeforeDelete, 'preDelete hook is called before deletion');
  }
  
  public function testPostDelete()
  {
    $t = new Table3();
    $t->save();
    $this->postDelete = 0;
    $t->delete();
    $this->assertEquals($t->postDelete, 1, 'postDelete hook is called on object deletion');
    $this->assertFalse($t->postDeleteIsBeforeDelete, 'postDelete hook is called before deletion');
  }
  
  public function testObjectMethods()
  {
    $t = new Table3();
    $this->assertTrue(method_exists($t, 'hello'), 'objectMethods hook is called when adding methods');
    $this->assertEquals($t->hello(), 'hello', 'objectMethods hook is called when adding methods');
  }
  
  public function testObjectFilter()
  {
    $t = new Table3();
    $this->assertTrue(class_exists('testObjectFilter'), 'objectFilter hook allows complete manipulation of the generated script');
  }
}
