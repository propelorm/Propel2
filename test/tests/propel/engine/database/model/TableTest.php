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
include_once 'propel/engine/database/transform/XmlToAppData.php';
include_once 'propel/engine/platform/MysqlPlatform.php';

/**
 * Tests for package handling.
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision$
 */
class TableTest extends PHPUnit_Framework_TestCase {

	private $xmlToAppData;
	private $appData;

	/**
	 * test if the tables get the package name from the properties file
	 *
	 */
	public function testIdMethodHandling() {
		$this->xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);

		//$this->appData = $this->xmlToAppData->parseFile(dirname(__FILE__) . "/tabletest-schema.xml");
		$this->appData = $this->xmlToAppData->parseFile("etc/schema/tabletest-schema.xml");

		$db = $this->appData->getDatabase("iddb");
		$expected = IDMethod::NATIVE;
		$result = $db->getDefaultIdMethod();
		$this->assertEquals($expected, $result);

		$table2 = $db->getTable("table_native");
		$expected = IDMethod::NATIVE;
		$result = $table2->getIdMethod();
		$this->assertEquals($expected, $result);

		$table = $db->getTable("table_none");
		$expected = IDMethod::NO_ID_METHOD;
		$result = $table->getIdMethod();
		$this->assertEquals($expected, $result);
	}
}
