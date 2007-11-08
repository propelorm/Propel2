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

/**
 * ColumnMap is used to model a column of a table in a database.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups, but instead
 * are used by the MapBuilder classes that were generated for your datamodel. The
 * MapBuilder that was created for your datamodel build a representation of your
 * database by creating instances of the DatabaseMap, TableMap, ColumnMap, etc.
 * classes. See propel/templates/om/php5/MapBuilder.tpl and the classes generated
 * by that template for your datamodel to further understand how these are put
 * together.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @version    $Revision$
 * @package    propel.map
 */
class ColumnMap {

	/** @var        string Propel type of the column. */
	private $type;

	/** Size of the column. */
	private $size = 0;

	/** Is it a primary key? */
	private $pk = false;

	/** Is null value allowed ?*/
	private $notNull = false;

	/** The default value for this column. */
	private $defaultValue;

	/** Name of the table that this column is related to. */
	private $relatedTableName = "";

	/** Name of the column that this column is related to. */
	private $relatedColumnName = "";

	/** The TableMap for this column. */
	private $table;

	/** The name of the column. */
	private $columnName;

	/** The php name of the column. */
	private $phpName;

	/** validators for this column */
	private $validators = array();

	/**
	 * Constructor.
	 *
	 * @param      string $name The name of the column.
	 * @param      TableMap containingTable TableMap of the table this column is in.
	 */
	public function __construct($name, TableMap $containingTable)
	{
		$this->columnName = $name;
		$this->table = $containingTable;
	}

	/**
	 * Gets column name (DEPRECATED).
	 * @return     string
	 * @deprecated Use getName() instead.
	 */
	public function getColumnName()
	{
		return $this->getName();
	}

	/**
	 * Get the name of a column.
	 *
	 * @return     string A String with the column name.
	 */
	public function getName()
	{
		return $this->columnName;
	}

	/**
	 * Get the name of a column.
	 *
	 * @return     string A String with the column name.
	 */
	public function getPhpName()
	{
		return $this->phpName;
	}

	/**
	 * Set the php anme of this column.
	 *
	 * @param      string $phpName A string representing the PHP name.
	 * @return     void
	 */
	public function setPhpName($phpName)
	{
		$this->phpName = $phpName;
	}

	/**
	 * Get the table name + column name.
	 *
	 * @return     string A String with the full column name.
	 */
	public function getFullyQualifiedName()
	{
		return $this->table->getName() . "." . $this->columnName;
	}

	/**
	 * Get the table map this column belongs to.
	 * @return     TableMap
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Get the name of the table this column is in.
	 *
	 * @return     string A String with the table name.
	 */
	public function getTableName()
	{
		return $this->table->getName();
	}

	/**
	 * Get the Propel type of this column.
	 *
	 * @return     string A string representing the Propel type (e.g. PropelColumnTypes::DATE).
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set the Propel type of this column.
	 *
	 * @param      string $type A string representing the Propel type (e.g. PropelColumnTypes::DATE).
	 * @return     void
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Get the PHP type of this column.
	 *
	 * @return     int The PDO::PARMA_* value
	 */
	 /*
	public function getPhpType()
	{
		return PropelColumnTypes::getPhpType($this->type);
	}
	*/
	/**
	 * Get the PDO type of this column.
	 *
	 * @return     int The PDO::PARMA_* value
	 */
	public function getPdoType()
	{
		return PropelColumnTypes::getPdoType($this->type);
	}

	/**
	 * Whether this is a BLOB, LONGVARBINARY, or VARBINARY.
	 * @return     boolean
	 */
	public function isLob()
	{
		return ($this->type == PropelColumnTypes::BLOB || $this->type == PropelColumnTypes::VARBINARY || $this->type == PropelColumnTypes::LONGVARBINARY);
	}

	/**
	 * Whether this is a DATE/TIME/TIMESTAMP column that is post-epoch (1970).
	 *
	 * PHP cannot handle pre-epoch timestamps well -- hence the need to differentiate
	 * between epoch and pre-epoch timestamps.
	 *
	 * @return     boolean
	 * @deprecated Propel supports non-epoch dates
	 */
	public function isEpochTemporal()
	{
		return ($this->type == PropelColumnTypes::TIMESTAMP || $this->type == PropelColumnTypes::DATE || $this->type == PropelColumnTypes::TIME);
	}

	/**
	 * Whether this column is numeric (int, decimal, bigint etc).
	 * @return     boolean
	 */
	public function isNumeric()
	{
		return ($this->type == PropelColumnTypes::NUMERIC || $this->type == PropelColumnTypes::DECIMAL || $this->type == PropelColumnTypes::TINYINT || $this->type == PropelColumnTypes::SMALLINT || $this->type == PropelColumnTypes::INTEGER || $this->type == PropelColumnTypes::BIGINT || $this->type == PropelColumnTypes::REAL || $this->type == PropelColumnTypes::FLOAT || $this->type == PropelColumnTypes::DOUBLE);
	}

	/**
	 * Whether this is a DATE/TIME/TIMESTAMP column.
	 *
	 * @return     boolean
	 * @since      1.3
	 */
	public function isTemporal()
	{
		return ($this->type == PropelColumnTypes::TIMESTAMP || $this->type == PropelColumnTypes::DATE || $this->type == PropelColumnTypes::TIME || $this->type == PropelColumnTypes::BU_DATE  || $this->type == PropelColumnTypes::BU_TIMESTAMP);
	}

	/**
	 * Whether this column is a text column (varchar, char, longvarchar).
	 * @return     boolean
	 */
	public function isText()
	{
		return ($this->type == PropelColumnTypes::VARCHAR || $this->type == PropelColumnTypes::LONGVARCHAR || $this->type == PropelColumnTypes::CHAR);
	}

	/**
	 * Set the size of this column.
	 *
	 * @param      int $size An int specifying the size.
	 * @return     void
	 */
	public function setSize($size)
	{
		$this->size = $size;
	}

	/**
	 * Set if this column is a primary key or not.
	 *
	 * @param      boolean $pk True if column is a primary key.
	 * @return     void
	 */
	public function setPrimaryKey($pk)
	{
		$this->pk = $pk;
	}

	/**
	 * Set if this column may be null.
	 *
	 * @param      boolean nn True if column may be null.
	 * @return     void
	 */
	public function setNotNull($nn)
	{
		$this->notNull = $nn;
	}

	/**
	 * Gets the default value for this column.
	 * @return     mixed String or NULL
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * Set the foreign key for this column.
	 *
	 * @param      string tableName The name of the table that is foreign.
	 * @param      string columnName The name of the column that is foreign.
	 * @return     void
	 */
	public function setForeignKey($tableName, $columnName)
	{
		if ($tableName && $columnName) {
			$this->relatedTableName = $tableName;
			$this->relatedColumnName = $columnName;
		} else {
			$this->relatedTableName = "";
			$this->relatedColumnName = "";
		}
	}

	public function addValidator($validator)
	{
	  $this->validators[] = $validator;
	}

	public function hasValidators()
	{
	  return count($this->validators) > 0;
	}

	public function getValidators()
	{
	  return $this->validators;
	}

	/**
	 * Get the size of this column.
	 *
	 * @return     int An int specifying the size.
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Is this column a primary key?
	 *
	 * @return     boolean True if column is a primary key.
	 */
	public function isPrimaryKey()
	{
		return $this->pk;
	}

	/**
	 * Is null value allowed ?
	 *
	 * @return     boolean True if column may not be null.
	 */
	public function isNotNull()
	{
		return ($this->notNull || $this->isPrimaryKey());
	}

	/**
	 * Is this column a foreign key?
	 *
	 * @return     boolean True if column is a foreign key.
	 */
	public function isForeignKey()
	{
		if ($this->relatedTableName) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the table.column that this column is related to.
	 *
	 * @return     string A String with the full name for the related column.
	 */
	public function getRelatedName()
	{
		return $this->relatedTableName . "." . $this->relatedColumnName;
	}

	/**
	 * Get the table name that this column is related to.
	 *
	 * @return     string A String with the name for the related table.
	 */
	public function getRelatedTableName()
	{
		return $this->relatedTableName;
	}

	/**
	 * Get the column name that this column is related to.
	 *
	 * @return     string A String with the name for the related column.
	 */
	public function getRelatedColumnName()
	{
		return $this->relatedColumnName;
	}

	/**
	 * Performs DB-specific ignore case, but only if the column type necessitates it.
	 * @param      string $str The expression we want to apply the ignore case formatting to (e.g. the column name).
	 * @param      DBAdapter $db
	 */
	public function ignoreCase($str, DBAdapter $db)
	{
		if ($this->isText()) {
			return $db->ignoreCase($str);
		} else {
			return $str;
		}
	}
}
