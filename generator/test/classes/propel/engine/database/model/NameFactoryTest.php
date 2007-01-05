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

require_once 'classes/propel/BaseTestCase.php';
include_once 'propel/engine/database/model/NameFactory.php';
include_once 'propel/engine/platform/MysqlPlatform.php';
include_once 'propel/engine/database/model/AppData.php';

/**
 * <p>Unit tests for class <code>NameFactory</code> and known
 * <code>NameGenerator</code> implementations.</p>
 *
 * <p>To add more tests, add entries to the <code>ALGORITHMS</code>,
 * <code>INPUTS</code>, and <code>OUTPUTS</code> arrays, and code to
 * the <code>makeInputs()</code> method.</p>
 *
 * <p>This test assumes that it's being run using the MySQL database
 * adapter, <code>DBMM</code>.  MySQL has a column length limit of 64
 * characters.</p>
 *
 * @author     <a href="mailto:dlr@collab.net">Daniel Rall</a>
 * @version    $Id$
 */
class NameFactoryTest extends BaseTestCase {

	/** The database to mimic in generating the SQL. */
	const DATABASE_TYPE = "mysql";

	/**
	 * The list of known name generation algorithms, specified as the
	 * fully qualified class names to <code>NameGenerator</code>
	 * implementations.
	 */
	private static $ALGORITHMS = array(NameFactory::CONSTRAINT_GENERATOR, NameFactory::PHP_GENERATOR);

	/**
	 * Two dimensional arrays of inputs for each algorithm.
	 */
	private static $INPUTS = array();


	/**
	 * Given the known inputs, the expected name outputs.
	 */
	private static $OUTPUTS = array();

	/**
	 * Used as an input.
	 */
	private $database;

	/**
	 * Creates a new instance.
	 *
	 */
	public function __construct() {

		self::$INPUTS = array(
				array( array(self::makeString(61), "I", 1),
						array(self::makeString(61), "I", 2),
						array(self::makeString(65), "I", 3),
						array(self::makeString(4), "FK", 1),
						array(self::makeString(5), "FK", 2)
					),
				array(
						array("MY_USER", NameGenerator::CONV_METHOD_UNDERSCORE),
						array("MY_USER", NameGenerator::CONV_METHOD_PHPNAME),
						array("MY_USER", NameGenerator::CONV_METHOD_NOCHANGE)
					)
				);


		self::$OUTPUTS = array(
						array(
							self::makeString(60) . "_I_1",
							self::makeString(60) . "_I_2",
							self::makeString(60) . "_I_3",
							self::makeString(4) . "_FK_1",
							self::makeString(5) . "_FK_2"),
						array("MyUser", "MYUSER", "MY_USER")
					);

	}

	/**
	 * Creates a string of the specified length consisting entirely of
	 * the character <code>A</code>.  Useful for simulating table
	 * names, etc.
	 *
	 * @param      int $len the number of characters to include in the string
	 * @return     a string of length <code>len</code> with every character an 'A'
	 */
	private static function makeString($len) {
		$buf = "";
		for ($i = 0; $i < $len; $i++) {
			$buf .= 'A';
		}
		return $buf;
	}

	/** Sets up the Propel model. */
	public function setUp()
	{
		$appData = new AppData(new MysqlPlatform());
		$this->database = new Database();
		$appData->addDatabase($this->database);
	}

	/**
	 * @throws     Exception on fail
	 */
	public function testNames() {
		for ($algoIndex = 0; $algoIndex < count(self::$ALGORITHMS); $algoIndex++) {
			$algo = self::$ALGORITHMS[$algoIndex];
			$algoInputs = self::$INPUTS[$algoIndex];
			for ($i = 0; $i < count($algoInputs); $i++) {
				$inputs = $this->makeInputs($algo, $algoInputs[$i]);
				$generated = NameFactory::generateName($algo, $inputs);
				$expected = self::$OUTPUTS[$algoIndex][$i];
				$this->assertEquals($expected, $generated, 0, "Algorithm " . $algo . " failed to generate an unique name");
			}
		}
	}

	/**
	 * Creates the list of arguments to pass to the specified type of
	 * <code>NameGenerator</code> implementation.
	 *
	 * @param      algo The class name of the <code>NameGenerator</code> to
	 * create an argument list for.
	 * @param      inputs The (possibly partial) list inputs from which to
	 * generate the final list.
	 * @return     the list of arguments to pass to the <code>NameGenerator</code>
	 */
	private function makeInputs($algo, $inputs)
	{
		if (NameFactory::CONSTRAINT_GENERATOR == $algo) {
			array_unshift($inputs, $this->database);
		}
		return $inputs;
	}

}
