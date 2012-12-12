<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\DatabaseDiff;

/**
 * MySql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class CubridPlatform extends DefaultPlatform
{

	/**
  	* @var boolean whether the identifier quoting is enabled
  	*/
 	protected $isIdentifierQuotingEnabled = true;

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'SHORT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'NUMERIC'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'STRING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BIT VARYING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'BIT VARYING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'BIT VARYING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'CLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'STRING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'STRING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'SHORT'));
    }

    public function getAutoIncrement()
    {
        return 'AUTO_INCREMENT';
    }

    public function getMaxColumnNameLength()
    {
		// according to http://www.cubrid.org/wiki_tutorials/entry/cubrid-rdbms-size-limits
        return 254;
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

	/**
	* Returns the DDL SQL to add the tables of a database
	* together with index and foreign keys
	*
	* @return string
	*/
	public function getAddTablesDDL(Database $database)
	{
		$fks = '';
		$ret = $this->getBeginDDL();
		$definedTables = [];

		foreach ($database->getTablesForSql() as $table) {
			$definedTables[] = $table->getName();

			$ret .= $this->getCommentBlockDDL($table->getName());

			// before dropping the table, drop all its child tables
			// but only if child tables haven't been defined here.
			foreach ($table->getReferrers() as $fk) {
				if (!in_array($fk->getTable()->getName(), $definedTables)) {
					$ret .= $this->getDropTableDDL($fk->getTable());
				}
			}

			$ret .= $this->getDropTableDDL($table);
			$ret .= $this->getAddTableDDL($table);
			$ret .= $this->getAddIndicesDDL($table);

			$fks .= $this->getCommentBlockDDL($table->getName() . ' Foreign Key Definition');
			$fks .= $this->getAddForeignKeysDDL($table);
		}

		// add foreign key definition at the very end to avoid
		// "The class 'table_name' referred by the foreign key does not exist" error
		// since in CUBRID there is no way to turn off foreign keys which is a bad practice anyway
		$ret .= $fks;
		$ret .= $this->getEndDDL();

		//echo $ret;

		return $ret;
	}

    public function getDropTableDDL(Table $table)
    {
        return "DROP TABLE IF EXISTS " . $this->quoteIdentifier($table->getName()) . ";";
    }

    /**
     * Builds the DDL SQL to rename a table
     * @return string
     */
    public function getRenameTableDDL($fromTableName, $toTableName)
    {
        $pattern = "RENAME TABLE %s TO %s;";

        return sprintf($pattern,
            $this->quoteIdentifier($fromTableName),
            $this->quoteIdentifier($toTableName)
        );
    }

	/**
  	* Returns if the RDBMS-specific SQL type has a size attribute.
	* The list presented below do have size attribute
  	*
  	* @param  string  $sqlType the SQL type
  	* @return boolean True if the type has a size attribute
  	*/
    public function hasSize($sqlType)
    {
        return in_array($sqlType, array(
            'NUMERIC',
            'DECIMAL',
            'FLOAT',
            'REAL',
            'BIT',
            'BIT VARYING',
            'CHAR',
            'VARCHAR'
        ));
    }
}
