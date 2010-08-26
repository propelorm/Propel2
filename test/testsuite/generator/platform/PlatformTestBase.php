<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Table.php';

/**
 * 
 * @package    generator.platform
 */
abstract class PlatformTestBase extends PHPUnit_Framework_TestCase
{
	/**
	 * Platform object.
	 *
	 * @var        Platform
	 */
	protected $platform;

	protected function setUp()
	{
		parent::setUp();

		$clazz = preg_replace('/Test$/', '', get_class($this));
		include_once dirname(__FILE__) . '/../../../../generator/lib/platform/' . $clazz . '.php';
		$this->platform = new $clazz();
	}

	/**
	 * Get the Platform object for this class
	 *
	 * @return     Platform
	 */
	protected function getPlatform()
	{
		return $this->platform;
	}

	public function providerForTestGetUniqueDDL()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->getDomain()->copy(new Domain('FOOTYPE'));
		$table->addColumn($column1);
		$column2 = new Column('bar2');
		$column2->getDomain()->copy(new Domain('BARTYPE'));
		$table->addColumn($column2);
		$index = new Unique('babar');
		$index->addColumn($column1);
		$index->addColumn($column2);
		$table->addUnique($index);

		return array(
			array($index)
		);
	}

	public function providerForTestGetIndexDDL()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->getDomain()->copy(new Domain('FOOTYPE'));
		$table->addColumn($column1);
		$column2 = new Column('bar2');
		$column2->getDomain()->copy(new Domain('BARTYPE'));
		$table->addColumn($column2);
		$index = new Index('babar');
		$index->addColumn($column1);
		$index->addColumn($column2);
		$table->addIndex($index);

		return array(
			array($index)
		);
	}

}
