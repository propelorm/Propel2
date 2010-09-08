<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'builder/sql/DDLBuilder.php';

/**
 * The SQL DDL-building class for SQLite.
 *
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.builder.sql.pgsql
 */
class SqliteDDLBuilder extends DDLBuilder
{

	/**
	 *
	 * @see        parent::addColumns()
	 */
	protected function addTable(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		$script .= "
-----------------------------------------------------------------------------
-- ".$table->getName()."
-----------------------------------------------------------------------------
";

		$script .= $platform->getDropTableDDL($table);

		$script .= "

CREATE TABLE ".$this->quoteIdentifier($table->getName())."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			$lines[] = $platform->getColumnDDL($col);
		}

		if ($table->hasPrimaryKey() && count($table->getPrimaryKey()) > 1) {
			$lines[] = $platform->getPrimaryKeyDDL($table);
		}
		
		foreach ($table->getUnices() as $unique ) {
			$lines[] = $platform->getUniqueDDL($unique);
		}

		$sep = ",
	";
		$script .= implode($sep, $lines);
		$script .= "
);
";
	}

}
