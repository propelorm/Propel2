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
 * Tests the generated queries for array column types filters
 *
 * @author     Francois Zaninotto
 * @package    generator.builder.om
 */
class GeneratedQueryArrayColumnTypeTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		if (!class_exists('ComplexColumnTypeEntity11')) {
			$schema = <<<EOF
<database name="generated_object_complex_type_test_11">
	<table name="complex_column_type_entity_11">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="tags" type="ARRAY" />
	</table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
			$e0 = new ComplexColumnTypeEntity11();
			$e0->save();
			$e1 = new ComplexColumnTypeEntity11();
			$e1->setTags(array('foo', 'bar', 'baz'));
			$e1->save();
			$e2 = new ComplexColumnTypeEntity11();
			$e2->setTags(array('bar'));
			$e2->save();
			$e3 = new ComplexColumnTypeEntity11();
			$e3->setTags(array('bar23'));
			$e3->save();
		}
	}

	public function testColumnHydration()
	{
		$e = ComplexColumnTypeEntity11Query::create()->orderById()->offset(1)->findOne();
		$this->assertEquals(array('foo', 'bar', 'baz'), $e->getTags(), 'array columns are correctly hydrated');
	}
	
	public function testColumnIsSearchableByElement()
	{
		$e = ComplexColumnTypeEntity11Query::create()->filterByTags('bar')->orderById()->find();
		$this->assertEquals(array('foo', 'bar', 'baz'), $e[0]->getTags(), 'array columns are searchable by element');
		$this->assertEquals(array('bar'), $e[1]->getTags(), 'array columns are searchable by element');
		$this->assertEquals(2, $e->count(), 'array columns do not return false positives');
		$e = ComplexColumnTypeEntity11Query::create()->filterByTags('bar23')->findOne();
		$this->assertEquals(array('bar23'), $e->getTags(), 'array columns are searchable by element');
	}
	
	public function testColumnIsSearchableByElementUsingContains()
	{
		$e = ComplexColumnTypeEntity11Query::create()
			->filterByTags('bar', Criteria::CONTAINS)
			->orderById()
			->find();
		$this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::CONTAINS');
		$this->assertEquals(array('foo', 'bar', 'baz'), $e[0]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS');
		$this->assertEquals(array('bar'), $e[1]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS');
	}
	
	public function testColumnIsSearchableByElementUsingNotContains()
	{
		$e = ComplexColumnTypeEntity11Query::create()
			->filterByTags('bar', Criteria::NOT_CONTAINS)
			->orderById()
			->find();
		$this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::NOT_CONTAINS');
		$this->assertEquals(array(), $e[0]->getTags(), 'array columns are searchable by element using Criteria::NOT_CONTAINS');
		$this->assertEquals(array('bar23'), $e[1]->getTags(), 'array columns are searchable by element using Criteria::NOT_CONTAINS');
	}
}