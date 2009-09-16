<?php

/*
 *  $Id$
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
require_once 'propel/engine/database/model/Column.php';
include_once 'propel/engine/platform/MysqlPlatform.php';

/**
 * Tests for package handling.
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision$
 */
class ColumnTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests static Column::makeList() method.
	 * @deprecated - Column::makeList() is deprecated and set to be removed in 1.3
	 */
	public function testMakeList()
	{
		$expected = "`Column0`, `Column1`, `Column2`, `Column3`, `Column4`";
		$objArray = array();
		for ($i=0; $i<5; $i++) {
			$c = new Column();
			$c->setName("Column" . $i);
			$objArray[] = $c;
		}

		$list = Column::makeList($objArray, new MySQLPlatform());
		$this->assertEquals($expected, $list, sprintf("Expected '%s' match, got '%s' ", var_export($expected, true), var_export($list,true)));

		$strArray = array();
		for ($i=0; $i<5; $i++) {
			$strArray[] = "Column" . $i;
		}

		$list = Column::makeList($strArray, new MySQLPlatform());
		$this->assertEquals($expected, $list, sprintf("Expected '%s' match, got '%s' ", var_export($expected, true), var_export($list,true)));

	}

}
