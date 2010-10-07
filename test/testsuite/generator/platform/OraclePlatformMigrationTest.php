<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PlatformMigrationTestProvider.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/platform/OraclePlatform.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Column.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/VendorInfo.php';

/**
 *
 * @package    generator.platform 
 */
class OraclePlatformMigrationTest extends PlatformMigrationTestProvider
{
	/**
	 * Get the Platform object for this class
	 *
	 * @return     Platform
	 */
	protected function getPlatform()
	{
		return new OraclePlatform();
	}

	/**
	 * @dataProvider providerForTestGetModifyDatabaseDDL
	 */
	public function testGetModifyDatabaseDDL($databaseDiff)
	{
		$expected = "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';

DROP TABLE foo1 CASCADE CONSTRAINTS;

DROP SEQUENCE foo1_SEQ;

ALTER TABLE foo3 RENAME TO foo4;

ALTER TABLE foo2 RENAME COLUMN bar TO bar1;

ALTER TABLE foo2 MODIFY
(
	baz NVARCHAR2(12)
);

ALTER TABLE foo2 ADD
(
	baz3 NVARCHAR2(2000)
);

CREATE TABLE foo5
(
	id NUMBER NOT NULL,
	lkdjfsh NUMBER,
	dfgdsgf NVARCHAR2(2000)
);

ALTER TABLE foo5 ADD CONSTRAINT foo5_PK PRIMARY KEY (id);

CREATE SEQUENCE foo5_SEQ
	INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyDatabaseDDL($databaseDiff));
	}
	
	/**
	 * @dataProvider providerForTestGetRenameTableDDL
	 */
	public function testGetRenameTableDDL($fromName, $toName)
	{
		$expected = "
ALTER TABLE foo1 RENAME TO foo2;
";
		$this->assertEquals($expected, $this->getPlatform()->getRenameTableDDL($fromName, $toName));
	}

	/**
	 * @dataProvider providerForTestGetModifyTableDDL
	 */
	public function testGetModifyTableDDL($tableDiff)
	{
		$expected = "
ALTER TABLE foo DROP CONSTRAINT foo1_FK_2;

ALTER TABLE foo DROP CONSTRAINT foo1_FK_1;

DROP INDEX bar_baz_FK;

DROP INDEX bar_FK;

ALTER TABLE foo RENAME COLUMN bar TO bar1;

ALTER TABLE foo MODIFY
(
	baz NVARCHAR2(12)
);

ALTER TABLE foo ADD
(
	baz3 NVARCHAR2(2000)
);

CREATE INDEX bar_FK ON foo (bar1);

CREATE INDEX baz_FK ON foo (baz3);

ALTER TABLE foo ADD CONSTRAINT foo1_FK_1
	FOREIGN KEY (bar1) REFERENCES foo2 (bar);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyTableDDL($tableDiff));
	}
	
	/**
	 * @dataProvider providerForTestGetModifyTableColumnsDDL
	 */
	public function testGetModifyTableColumnsDDL($tableDiff)
	{
		$expected = "
ALTER TABLE foo RENAME COLUMN bar TO bar1;

ALTER TABLE foo MODIFY
(
	baz NVARCHAR2(12)
);

ALTER TABLE foo ADD
(
	baz3 NVARCHAR2(2000)
);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyTableColumnsDDL($tableDiff));
	}

	/**
	 * @dataProvider providerForTestGetModifyTablePrimaryKeysDDL
	 */
	public function testGetModifyTablePrimaryKeysDDL($tableDiff)
	{
		$expected = "
ALTER TABLE foo DROP CONSTRAINT foo_PK;

ALTER TABLE foo ADD CONSTRAINT foo_PK PRIMARY KEY (id,bar);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyTablePrimaryKeyDDL($tableDiff));
	}
	
	/**
	 * @dataProvider providerForTestGetModifyTableIndicesDDL
	 */
	public function testGetModifyTableIndicesDDL($tableDiff)
	{
		$expected = "
DROP INDEX bar_FK;

CREATE INDEX baz_FK ON foo (baz);

DROP INDEX bar_baz_FK;

CREATE INDEX bar_baz_FK ON foo (id,bar,baz);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyTableIndicesDDL($tableDiff));
	}

	/**
	 * @dataProvider providerForTestGetModifyTableForeignKeysDDL
	 */
	public function testGetModifyTableForeignKeysDDL($tableDiff)
	{
		$expected = "
ALTER TABLE foo1 DROP CONSTRAINT foo1_FK_1;

ALTER TABLE foo1 ADD CONSTRAINT foo1_FK_3
	FOREIGN KEY (baz) REFERENCES foo2 (baz);

ALTER TABLE foo1 DROP CONSTRAINT foo1_FK_2;

ALTER TABLE foo1 ADD CONSTRAINT foo1_FK_2
	FOREIGN KEY (bar,id) REFERENCES foo2 (bar,id);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyTableForeignKeysDDL($tableDiff));
	}
	
	/**
	 * @dataProvider providerForTestGetRemoveColumnDDL
	 */
	public function testGetRemoveColumnDDL($column)
	{
		$expected = "
ALTER TABLE foo DROP COLUMN bar;
";
		$this->assertEquals($expected, $this->getPlatform()->getRemoveColumnDDL($column));
	}

	/**
	 * @dataProvider providerForTestGetRenameColumnDDL
	 */
	public function testGetRenameColumnDDL($fromColumn, $toColumn)
	{
		$expected = "
ALTER TABLE foo RENAME COLUMN bar1 TO bar2;
";
		$this->assertEquals($expected, $this->getPlatform()->getRenameColumnDDL($fromColumn, $toColumn));
	}
	
	/**
	 * @dataProvider providerForTestGetModifyColumnDDL
	 */
	public function testGetModifyColumnDDL($columnDiff)
	{
		$expected = "
ALTER TABLE foo MODIFY bar DOUBLE(3);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyColumnDDL($columnDiff));
	}

	/**
	 * @dataProvider providerForTestGetModifyColumnsDDL
	 */
	public function testGetModifyColumnsDDL($columnDiffs)
	{
		$expected = "
ALTER TABLE foo MODIFY
(
	bar1 DOUBLE(3),
	bar2 INTEGER NOT NULL
);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyColumnsDDL($columnDiffs));
	}
	
	/**
	 * @dataProvider providerForTestGetAddColumnDDL
	 */
	public function testGetAddColumnDDL($column)
	{
		$expected = "
ALTER TABLE foo ADD bar NUMBER;
";
		$this->assertEquals($expected, $this->getPlatform()->getAddColumnDDL($column));
	}

	/**
	 * @dataProvider providerForTestGetAddColumnsDDL
	 */
	public function testGetAddColumnsDDL($columns)
	{
		$expected = "
ALTER TABLE foo ADD
(
	bar1 NUMBER,
	bar2 FLOAT(3,2) DEFAULT -1 NOT NULL
);
";
		$this->assertEquals($expected, $this->getPlatform()->getAddColumnsDDL($columns));
	}
}
