<?php

/*
 *	$Id$
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
 * Tests for AutoAddPkBehavior class
 *
 * @author		 François Zaninotto
 * @version		$Revision: 1133 $
 * @package		generator.behavior
 */
class AutoAddPkBehaviorTest extends BookstoreTestBase 
{
	public function testDefault()
	{
		$table6 = Table6Peer::getTableMap();
		$this->assertEquals(count($table6->getColumns()), 2, 'auto_add_pk adds one column by default');
		$pks = $table6->getPrimaryKeys();
		$this->assertEquals(count($pks), 1, 'auto_add_pk adds a simple primary key by default');
		$pk = array_pop($pks);
		$this->assertEquals($pk->getName(), 'ID', 'auto_add_pk adds an id column by default');
		$this->assertEquals($pk->getType(), 'INTEGER', 'auto_add_pk adds an integer column by default');
		$this->assertTrue($pk->isPrimaryKey(), 'auto_add_pk adds a primary key column by default');
		$this->assertTrue($table6->isUseIdGenerator(), 'auto_add_pk adds an autoIncrement column by default');
	}

	public function testNoTrigger()
	{
		$table7 = Table7Peer::getTableMap();
		$this->assertEquals(count($table7->getColumns()), 2, 'auto_add_pk does not add a column when the table already has a primary key');
		$this->assertFalse(method_exists('Table7', 'getId'), 'auto_add_pk does not add an id column when the table already has a primary key');
		$pks = $table7->getPrimaryKeys();
		$pk = array_pop($pks);
		$this->assertEquals($pk->getName(), 'FOO', 'auto_add_pk does not change an existing primary key');
	}

	public function testParameters()
	{
		$table8 = Table8Peer::getTableMap();
		$this->assertEquals(count($table8->getColumns()), 3, 'auto_add_pk adds one column with custom parameters');
		$pks = $table8->getPrimaryKeys();
		$pk = array_pop($pks);
		$this->assertEquals($pk->getName(), 'IDENTIFIER', 'auto_add_pk accepts customization of pk column name');
		$this->assertEquals($pk->getType(), 'BIGINT', 'auto_add_pk accepts customization of pk column type');
		$this->assertTrue($pk->isPrimaryKey(), 'auto_add_pk adds a primary key column with custom parameters');
		$this->assertFalse($table8->isUseIdGenerator(), 'auto_add_pk accepts customization of pk column autoIncrement');
	}

	public function testForeignKey()
	{
		$t6 = new Table6();
		$t6->setTitle('foo');
		$t6->save();
		$t8 = new Table8();
		$t8->setIdentifier(1);
		$t8->setTable6($t6);
		$t8->save();
		$this->assertEquals($t8->getFooId(), $t6->getId(), 'Auto added pkeys can be used in relations');
		$t8->delete();
		$t6->delete();
	}
}