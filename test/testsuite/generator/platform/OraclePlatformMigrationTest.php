<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PlatformMigrationTestProvider.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Column.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/VendorInfo.php';

/**
 *
 * @package    generator.platform 
 */
class OraclePlatformMigrationTest extends PlatformMigrationTestProvider
{

	/**
	 * @dataProvider providerForTestGetModifyDatabaseDDL
	 */
	public function testGetModifyDatabaseDDL($schema1, $schema2)
	{
		$d1 = $this->getDatabaseFromSchema($schema1);
		$d2 = $this->getDatabaseFromSchema($schema2);
		$databaseDiff = PropelDatabaseComparator::computeDiff($d1, $d2);
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
	baz3 NVARCHAR2(2000) DEFAULT 'baz3'
);

CREATE TABLE foo5
(
	id NUMBER NOT NULL,
	lkdjfsh NUMBER,
	dfgdsgf NVARCHAR2(2000)
);

ALTER TABLE foo5
	ADD CONSTRAINT foo5_PK
	PRIMARY KEY (id);

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
ALTER TABLE foo RENAME COLUMN bar TO bar1;

ALTER TABLE foo MODIFY
(
	baz VARCHAR(12)
);

ALTER TABLE foo ADD
(
	baz3 LONGVARCHAR DEFAULT 'baz3'
);
";
		$this->assertEquals($expected, $this->getPlatform()->getModifyTableDDL($tableDiff));
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
	public function testGetRenameColumnDDL($table, $fromColumnName, $toColumnName)
	{
		$expected = "
ALTER TABLE foo RENAME COLUMN bar1 TO bar2;
";
		$this->assertEquals($expected, $this->getPlatform()->getRenameColumnDDL($table, $fromColumnName, $toColumnName));
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
ALTER TABLE foo ADD bar INTEGER;
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
	bar1 INTEGER,
	bar2 DOUBLE(3,2) DEFAULT -1 NOT NULL
);
";
		$this->assertEquals($expected, $this->getPlatform()->getAddColumnsDDL($columns));
	}
}
