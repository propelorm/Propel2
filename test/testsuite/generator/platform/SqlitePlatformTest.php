<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/DefaultPlatformTest.php';

/**
 * 
 * @package    generator.platform
 */
class SqlitePlatformTest extends DefaultPlatformTest
{
	/**
	 * @var        PDO The PDO connection to SQLite DB.
	 */
	private $pdo;

	protected function setUp()
	{
		parent::setUp();
		$this->pdo = new PDO("sqlite::memory:");

	}

	public function tearDown()
	{
		 parent::tearDown();
	}

	public function testQuoteConnected()
	{
		$p = $this->getPlatform();
		$p->setConnection($this->pdo);

		$unquoted = "Naughty ' string";
		$quoted = $p->quote($unquoted);

		$expected = "'Naughty '' string'";
		$this->assertEquals($expected, $quoted);
	}

	public function testGetColumnDDL()
	{
		$c = new Column('foo');
		$c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
		$c->getDomain()->replaceScale(2);
		$c->getDomain()->replaceSize(3);
		$c->setNotNull(true);
		$c->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
		$expected = '[foo] DOUBLE(3,2) DEFAULT 123 NOT NULL';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($c));
	}

	public function testGetPrimaryKeyDDLSimpleKey()
	{
		$table = new Table('foo');
		$column = new Column('bar');
		$column->setPrimaryKey(true);
		$table->addColumn($column);
		$expected = 'PRIMARY KEY ([bar])';
		$this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
	}

	public function testGetPrimaryKeyDDLCompositeKey()
	{
		$table = new Table('foo');
		$column1 = new Column('bar1');
		$column1->setPrimaryKey(true);
		$table->addColumn($column1);
		$column2 = new Column('bar2');
		$column2->setPrimaryKey(true);
		$table->addColumn($column2);
		$expected = 'PRIMARY KEY ([bar1],[bar2])';
		$this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testGetIndexDDL($index)
	{
		$expected = 'INDEX [babar] ON [foo] ([bar1],[bar2])';
		$this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetUniqueDDL
	 */
	public function testGetUniqueDDL($index)
	{
		$expected = 'UNIQUE ([bar1],[bar2])';
		$this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
	}

}
