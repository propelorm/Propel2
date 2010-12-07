<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../../runtime/lib/Propel.php';

/**
 * Tests the generated objects for array column types accessor & mutator
 *
 * @author     Francois Zaninotto
 * @package    generator.builder.om
 */
class GeneratedObjectArrayColumnTypeTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		if (!class_exists('ComplexColumnTypeEntity2')) {
			$schema = <<<EOF
<database name="generated_object_complex_type_test_2">
	<table name="complex_column_type_entity_2">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="tags" type="ARRAY" />
	</table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
		}
	}
	
	public function testColumnGetterDefaultValue()
	{
		$e = new ComplexColumnTypeEntity2();
		$this->assertEquals(array(), $e->getTags(), 'array columns return an empty array by default');
	}
	
	public function testColumnSetterArrayValue()
	{
		$e = new ComplexColumnTypeEntity2();
		$value = array('foo', 1234);
		$e->setTags($value);
		$this->assertEquals($value, $e->getTags(), 'array columns can store arrays');
	}
	
	public function testColumnSetterResetValue()
	{
		$e = new ComplexColumnTypeEntity2();
		$value = array('foo', 1234);
		$e->setTags($value);
		$e->setTags(array());
		$this->assertEquals(array(), $e->getTags(), 'object columns can be reset');
	}
	
	public function testValueIsPersisted($value='')
	{
		$e = new ComplexColumnTypeEntity2();
		$value = array('foo', 1234);
		$e->setTags($value);
		$e->save();
		ComplexColumnTypeEntity2Peer::clearInstancePool();
		$e = ComplexColumnTypeEntity2Query::create()->findOne();
		$this->assertEquals($value, $e->getTags(), 'array columns are persisted');
	}
}