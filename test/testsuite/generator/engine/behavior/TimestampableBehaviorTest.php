<?php

/*
 *  $Id: TimestampableBehaviorTest.php 1133 2009-09-16 13:35:12Z francois $
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
 * Tests for TimestampableBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision: 1133 $
 * @package    generator.engine.behavior
 */
class TimestampableBehaviorTest extends BookstoreTestBase 
{
  public function testParameters()
  {
    $table2 = Table2Peer::getTableMap();
    $this->assertEquals(count($table2->getColumns()), 4, 'Timestampable adds two columns by default');
    $this->assertTrue(method_exists('Table2', 'getCreatedAt'), 'Timestamplable adds a created_at column by default');
    $this->assertTrue(method_exists('Table2', 'getUpdatedAt'), 'Timestamplable adds an updated_at column by default');
    $table1 = Table1Peer::getTableMap();
    $this->assertEquals(count($table1->getColumns()), 4, 'Timestampable does not add two columns when add_column is false');
    $this->assertTrue(method_exists('Table1', 'getCreatedOn'), 'Timestamplable allows customization of create_column name');
    $this->assertTrue(method_exists('Table1', 'getUpdatedOn'), 'Timestamplable allows customization of update_column name');
  }
  
  public function testPreSave()
  {
    $t1 = new Table2();
    $this->assertNull($t1->getUpdatedAt());
    $tsave = time();
    $t1->save();
    $this->assertEquals($t1->getUpdatedAt('U'), $tsave, 'Timestampable sets updated_column to time() on creation');
    sleep(1);
    $tupdate = time();
    $t1->save();
    $this->assertEquals($t1->getUpdatedAt('U'), $tupdate, 'Timestampable changes updated_column to time() on update');    
  }

  public function testPreInsert()
  {
    $t1 = new Table2();
    $this->assertNull($t1->getCreatedAt());
    $tsave = time();
    $t1->save();
    $this->assertEquals($t1->getCreatedAt('U'), $tsave, 'Timestampable sets created_column to time() on creation');
    sleep(1);
    $tupdate = time();
    $t1->save();
    $this->assertEquals($t1->getCreatedAt('U'), $tsave, 'Timestampable does not update created_column on update');    
  }
}
