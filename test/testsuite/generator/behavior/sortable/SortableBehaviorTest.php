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
 * Tests for SortableBehavior class
 *
 * @author		Massimiliano Arione
 * @version		$Revision$
 * @package		generator.engine.behavior
 */
class SortableBehaviorTest extends BookstoreTestBase
{

	public function testParameters()
	{
		$table11 = Table11Peer::getTableMap();
		$this->assertEquals(count($table11->getColumns()), 3, 'Sortable adds one columns by default');
		$this->assertTrue(method_exists('Table11', 'getRank'), 'Sortable adds a rank column by default');
		$table12 = Table12Peer::getTableMap();
		$this->assertEquals(count($table12->getColumns()), 4, 'Sortable does not add a column when add_column is false');
		$this->assertTrue(method_exists('Table12', 'getPosition'), 'Sortable allows customization of rank_column name');
	}

}
