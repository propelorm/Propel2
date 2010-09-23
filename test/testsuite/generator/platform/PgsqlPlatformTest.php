<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PlatformTestBase.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Database.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Table.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/VendorInfo.php';

/**
 *
 * @package    generator.platform 
 */
class PgsqlPlatformTest extends PlatformTestBase
{
	public function testGetSequenceNameDefault()
	{
		$table = new Table('foo');
		$table->setIdMethod(IDMethod::NATIVE);
		$col = new Column('bar');
		$col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
		$col->setAutoIncrement(true);
		$table->addColumn($col);
		$expected = 'foo_bar_seq';
		$this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
	}

	public function testGetSequenceNameCustom()
	{
		$table = new Table('foo');
		$table->setIdMethod(IDMethod::NATIVE);
		$idMethodParameter = new IdMethodParameter();
		$idMethodParameter->setValue('foo_sequence');
		$table->addIdMethodParameter($idMethodParameter);
		$table->setIdMethod(IDMethod::NATIVE);
		$col = new Column('bar');
		$col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
		$col->setAutoIncrement(true);
		$table->addColumn($col);
		$expected = 'foo_sequence';
		$this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
	}

	/**
	 * @dataProvider providerForTestGetAddTableDDLSimplePK
	 */
	public function testGetAddTableDDLSimplePK($schema)
	{
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

CREATE TABLE "foo"
(
	"id" serial NOT NULL,
	"bar" VARCHAR(255) NOT NULL,
	PRIMARY KEY ("id")
);

COMMENT ON TABLE "foo" IS 'This is foo table';

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetAddTableDDLCompositePK
	 */
	public function testGetAddTableDDLCompositePK($schema)
	{
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

CREATE TABLE "foo"
(
	"foo" INTEGER NOT NULL,
	"bar" INTEGER NOT NULL,
	"baz" VARCHAR(255) NOT NULL,
	PRIMARY KEY ("foo","bar")
);

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetAddTableDDLUniqueIndex
	 */
	public function testGetAddTableDDLUniqueIndex($schema)
	{
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

CREATE TABLE "foo"
(
	"id" serial NOT NULL,
	"bar" INTEGER,
	PRIMARY KEY ("id"),
	CONSTRAINT "foo_U_1" UNIQUE ("bar")
);

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	public function testGetAddTableWithSchemaDDL()
	{
		$schema = <<<EOF
<database name="test">
	<table name="foo">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<vendor type="pgsql">
			<parameter name="schema" value="Woopah"/>
		</vendor>
	</table>
</database>
EOF;
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

SET search_path TO "Woopah";

CREATE TABLE "foo"
(
	"id" serial NOT NULL,
	PRIMARY KEY ("id")
);

SET search_path TO public;

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	public function testGetAddTableWithSequenceDDL()
	{
		$schema = <<<EOF
<database name="test">
	<table name="foo">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<id-method-parameter value="my_custom_sequence_name"/>
	</table>
</database>
EOF;
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

CREATE SEQUENCE "my_custom_sequence_name";

CREATE TABLE "foo"
(
	"id" INTEGER NOT NULL,
	PRIMARY KEY ("id")
);

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	public function testGetAddTableWithColumnCommentsDDL()
	{
		$schema = <<<EOF
<database name="test">
	<table name="foo">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" description="identifier column"/>
		<column name="bar" type="INTEGER" description="your name here"/>
	</table>
</database>
EOF;
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

CREATE TABLE "foo"
(
	"id" serial NOT NULL,
	"bar" INTEGER,
	PRIMARY KEY ("id")
);

COMMENT ON COLUMN "foo"."id" IS 'identifier column';

COMMENT ON COLUMN "foo"."bar" IS 'your name here';

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getAddTableDDL($table));
	}

	public function testGetDropTableDDL()
	{
		$table = new Table('foo');
		$expected = "
DROP TABLE \"foo\" CASCADE;
";
		$this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
	}

	public function testGetDropTableWithSchemaDDL()
	{
		$schema = <<<EOF
<database name="test">
	<table name="foo">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<vendor type="pgsql">
			<parameter name="schema" value="Woopah"/>
		</vendor>
	</table>
</database>
EOF;
		$table = $this->getTableFromSchema($schema);
		$expected = <<<EOF

SET search_path TO "Woopah";

DROP TABLE "foo" CASCADE;

SET search_path TO public;

EOF;
		$this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
	}

	public function testGetDropTableWithSequenceDDL()
	{
		$table = new Table('foo');
		$idMethodParameter = new IdMethodParameter();
		$idMethodParameter->setValue('foo_sequence');
		$table->addIdMethodParameter($idMethodParameter);
		$table->setIdMethod(IDMethod::NATIVE);
		$expected = "
DROP TABLE \"foo\" CASCADE;

DROP SEQUENCE \"foo_sequence\";
";
		$this->assertEquals($expected, $this->getPlatform()->getDropTableDDL($table));
	}

	public function testGetColumnDDL()
	{
		$c = new Column('foo');
		$c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
		$c->getDomain()->replaceScale(2);
		$c->getDomain()->replaceSize(3);
		$c->setNotNull(true);
		$c->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
		$expected = '"foo" DOUBLE PRECISION(3,2) DEFAULT 123 NOT NULL';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($c));
	}
	
	public function testGetColumnDDLAutoIncrement()
	{
		$database = new Database();
		$database->setPlatform($this->getPlatform());
		$table = new Table('foo_table');
		$table->setIdMethod(IDMethod::NATIVE);
		$database->addTable($table);
		$column = new Column('foo');
		$column->getDomain()->copy($this->getPlatform()->getDomainForType(PropelTypes::BIGINT));
		$column->setAutoIncrement(true);
		$table->addColumn($column);
		$expected = '"foo" bigserial';
		$this->assertEquals($expected, $this->getPlatform()->getColumnDDL($column));
	}

	public function testGetPrimaryKeyDDLSimpleKey()
	{
		$table = new Table('foo');
		$column = new Column('bar');
		$column->setPrimaryKey(true);
		$table->addColumn($column);
		$expected = 'PRIMARY KEY ("bar")';
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
		$expected = 'PRIMARY KEY ("bar1","bar2")';
		$this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testAddIndexDDL($index)
	{
		$expected = "
CREATE INDEX \"babar\" ON \"foo\" (\"bar1\",\"bar2\");
";
		$this->assertEquals($expected, $this->getPLatform()->getAddIndexDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetIndicesDDL
	 */
	public function testAddIndicesDDL($table)
	{
		$expected = "
CREATE INDEX \"babar\" ON \"foo\" (\"bar1\",\"bar2\");

CREATE INDEX \"foo_index\" ON \"foo\" (\"bar1\");
";
		$this->assertEquals($expected, $this->getPLatform()->getAddIndicesDDL($table));
	}
	
	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testDropIndexDDL($index)
	{
		$expected = "
DROP INDEX \"babar\";
";
		$this->assertEquals($expected, $this->getPLatform()->getDropIndexDDL($index));
	}
	
	/**
	 * @dataProvider providerForTestGetIndexDDL
	 */
	public function testGetIndexDDL($index)
	{
		$expected = 'INDEX "babar" ("bar1","bar2")';
		$this->assertEquals($expected, $this->getPLatform()->getIndexDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetUniqueDDL
	 */
	public function testGetUniqueDDL($index)
	{
		$expected = 'CONSTRAINT "babar" UNIQUE ("bar1","bar2")';
		$this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
	}

	/**
	 * @dataProvider providerForTestGetForeignKeysDDL
	 */
	public function testGetAddForeignKeysDDL($table)
	{
		$expected = "
ALTER TABLE \"foo\" ADD CONSTRAINT \"foo_bar_FK\"
	FOREIGN KEY (\"bar_id\")
	REFERENCES \"bar\" (\"id\")
	ON DELETE CASCADE;

ALTER TABLE \"foo\" ADD CONSTRAINT \"foo_baz_FK\"
	FOREIGN KEY (\"baz_id\")
	REFERENCES \"baz\" (\"id\")
	ON DELETE SET NULL;
";
		$this->assertEquals($expected, $this->getPLatform()->getAddForeignKeysDDL($table));
	}

	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetDropForeignKeyDDL($fk)
	{
		$expected = "
ALTER TABLE \"foo\" DROP CONSTRAINT \"foo_bar_FK\";
";
		$this->assertEquals($expected, $this->getPLatform()->getDropForeignKeyDDL($fk));
	}
	
	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetAddForeignKeyDDL($fk)
	{
		$expected = "
ALTER TABLE \"foo\" ADD CONSTRAINT \"foo_bar_FK\"
	FOREIGN KEY (\"bar_id\")
	REFERENCES \"bar\" (\"id\")
	ON DELETE CASCADE;
";
		$this->assertEquals($expected, $this->getPLatform()->getAddForeignKeyDDL($fk));
	}
	
	/**
	 * @dataProvider providerForTestGetForeignKeyDDL
	 */
	public function testGetForeignKeyDDL($fk)
	{
		$expected = "CONSTRAINT \"foo_bar_FK\"
	FOREIGN KEY (\"bar_id\")
	REFERENCES \"bar\" (\"id\")
	ON DELETE CASCADE";
		$this->assertEquals($expected, $this->getPLatform()->getForeignKeyDDL($fk));
	}


}
