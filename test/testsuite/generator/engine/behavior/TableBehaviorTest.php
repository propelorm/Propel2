<?php
/*
 *  $Id: TableBehaviorTest.php 1169 2009-09-28 20:07:02Z francois $
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

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Tests the table structure behavior hooks.
 *
 * @author     Francois Zaninotto
 * @package    generator.engine.behavior
 */
class TableBehaviorTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		parent::setUp();
		set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
		require_once 'behavior/map/Table3TableMap.php';
		require_once 'behavior/Table3Peer.php';
	}

  public function testModifyTable()
  {
    $t = Table3Peer::getTableMap();
    $this->assertTrue($t->hasColumn('test'), 'modifyTable hook is called when building the model structure');
  }
}
