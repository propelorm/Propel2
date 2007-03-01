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

require_once 'propel/engine/database/model/XMLElement.php';
include_once 'propel/engine/EngineException.php';
include_once 'propel/engine/database/model/PropelTypes.php';
include_once 'propel/engine/database/model/Inheritance.php';
include_once 'propel/engine/database/model/Domain.php';
include_once 'propel/engine/database/model/ColumnDefaultValue.php';

/**
 * A Class for holding data about a column used in an Application.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author     Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author     Jon S. Stevens <jon@latchkey.com> (Torque)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author     Byron Foster <byron_foster@yahoo.com> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class Column extends XMLElement {

	const DEFAULT_TYPE = "VARCHAR";

	private $name;
	private $description;
	private $phpName = null;
	private $phpNamingMethod;
	private $isNotNull = false;
	private $size;

	/**
	 * The name to use for the Peer constant that identifies this column.
	 * (Will be converted to all-uppercase in the templates.)
	 * @var        string
	 */
	private $peerName;

	/**
	 * Type as defined in schema.xml
	 * @var        string
	 */
	private $propelType;

	/**
	 * Type corresponding to Creole type
	 * @var        int
	 */
	private $creoleType;

	/**
	 * Native PHP type (scalar or class name)
	 * @var        string "string", "boolean", "int", "double"
	 */
	private $phpType;
	private $parentTable;
	private $position;
	private $isPrimaryKey = false;
	private $isNodeKey = false;
	private $nodeKeySep;
	private $isNestedSetLeftKey = false;
	private $isNestedSetRightKey = false;
	private $isUnique = false;
	private $isAutoIncrement = false;
	private $isLazyLoad = false;
	private $defaultValue;
	private $referrers;
	// only one type is supported currently, which assumes the
	// column either contains the classnames or a key to
	// classnames specified in the schema.  Others may be
	// supported later.
	private $inheritanceType;
	private $isInheritance;
	private $isEnumeratedClasses;
	private $inheritanceList;
	private $needsTransactionInPostgres; //maybe this can be retrieved from vendorSpecificInfo

	/** class name to do input validation on this column */
	private $inputValidator = null;
	
	/**
	 * @var        Domain The domain object associated with this Column.
	 */
	private $domain;

	/**
	 * Creates a new column and set the name
	 *
	 * @param      name column name
	 */
	public function __construct($name = null)
	{
		$this->name = $name;
	}

	/**
	 * Return a comma delimited string listing the specified columns.
	 *
	 * @param      columns Either a list of <code>Column</code> objects, or
	 * a list of <code>String</code> objects with column names.
	 * @deprecated Use the DDLBuilder->getColumnList() method instead; this will be removed in 1.3
	 */
	public static function makeList($columns, Platform $platform)
	{
		$list = array();
		foreach ($columns as $col) {
			if ($col instanceof Column) {
				$col = $col->getName();
			}
			$list[] = $platform->quoteIdentifier($col);
		}
		return implode(", ", $list);
	}

	/**
	 * Sets up the Column object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		try {
			$dom = $this->getAttribute("domain");
			if ($dom)  {
				$this->domain = new Domain();
				$this->domain->copy($this->getTable()->getDatabase()->getDomain($dom));
			} else {
				$this->domain = new Domain();
				$this->domain->copy($this->getPlatform()->getDomainForType(self::DEFAULT_TYPE));
				$this->setType(strtoupper($this->getAttribute("type")));
			}

			$this->name = $this->getAttribute("name");
			$this->phpName = $this->getAttribute("phpName");
			$this->phpType = $this->getAttribute("phpType");
			
			$this->peerName = $this->getAttribute("peerName");

			// retrieves the method for converting from specified name to a PHP name, defaulting to parent tables default method
			$this->phpNamingMethod = $this->getAttribute("phpNamingMethod", $this->parentTable->getDatabase()->getDefaultPhpNamingMethod());

			$this->isPrimaryKey = $this->booleanValue($this->getAttribute("primaryKey"));

			$this->isNodeKey = $this->booleanValue($this->getAttribute("nodeKey"));
			$this->nodeKeySep = $this->getAttribute("nodeKeySep", ".");

			$this->isNestedSetLeftKey = $this->booleanValue($this->getAttribute("nestedSetLeftKey"));
			$this->isNestedSetRightKey = $this->booleanValue($this->getAttribute("nestedSetRightKey"));

			$this->isNotNull = ($this->booleanValue($this->getAttribute("required"), false) || $this->isPrimaryKey); // primary keys are required

			//AutoIncrement/Sequences
			$this->isAutoIncrement = $this->booleanValue($this->getAttribute("autoIncrement"));
			$this->isLazyLoad = $this->booleanValue($this->getAttribute("lazyLoad"));

			// Add type, size information to associated Domain object
			$this->domain->replaceSqlType($this->getAttribute("sqlType"));
			$this->domain->replaceSize($this->getAttribute("size"));
			$this->domain->replaceScale($this->getAttribute("scale"));
			
			$defval = $this->getAttribute("defaultValue", $this->getAttribute("default"));
			if ($defval !== null) {
				$this->domain->setDefaultValue(new ColumnDefaultValue($defval, ColumnDefaultValue::TYPE_VALUE));
			} elseif ($this->getAttribute("defaultExpr") !== null) {
				$this->domain->setDefaultValue(new ColumnDefaultValue($this->getAttribute("defaultExpr"), ColumnDefaultValue::TYPE_EXPR));
			}

			$this->inheritanceType = $this->getAttribute("inheritance");
			$this->isInheritance = ($this->inheritanceType !== null
			&& $this->inheritanceType !== "false"); // here we are only checking for 'false', so don't
			// use boleanValue()

			$this->inputValidator = $this->getAttribute("inputValidator");
			$this->description = $this->getAttribute("description");
		} catch (Exception $e) {
			throw new EngineException("Error setting up column " . var_export($this->getAttribute("name"), true) . ": " . $e->getMessage());
		}
	}

	/**
	 * Gets domain for this column.
	 * @return     Domain
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Returns table.column
	 */
	public function getFullyQualifiedName()
	{
		return ($this->parentTable->getName() . '.' . name);
	}

	/**
	 * Get the name of the column
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the name of the column
	 */
	public function setName($newName)
	{
		$this->name = $newName;
	}

	/**
	 * Get the description for the Table
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set the description for the Table
	 *
	 * @param      newDescription description for the Table
	 */
	public function setDescription($newDescription)
	{
		$this->description = $newDescription;
	}

	/**
	 * Get name to use in PHP sources
	 * @return     string
	 */
	public function getPhpName()
	{
		if ($this->phpName === null) {
			$inputs = array();
			$inputs[] = $this->name;
			$inputs[] = $this->phpNamingMethod;
			try {
				$this->phpName = NameFactory::generateName(NameFactory::PHP_GENERATOR, $inputs);
			} catch (EngineException $e) {
				print $e->getMessage() . "\n";
				print $e->getTraceAsString();
			}
		}
		return $this->phpName;
	}

	/**
	 * Set name to use in PHP sources
	 */
	public function setPhpName($phpName)
	{
		$this->phpName = $phpName;
	}

	/**
	 * Get studly version of PHP name.
	 *
	 * The studly name is the PHP name with the first character lowercase.
	 *
	 * @return    string
	 */
	public function getStudlyPhpName()
	{
		$phpname = $this->getPhpName();
		if (strlen($phpname) > 1) {
			return strtolower(substr($phpname, 0, 1)) . substr($phpname, 1);
		} else { // 0 or 1 chars (I suppose that's rare)
			return strtolower($phpname);
		}
	}

	/**
	 * Get the Peer constant name that will identify this column.
	 * @return     string
	 */
	public function getPeerName() {
		return $this->peerName;
	}

	/**
	 * Set the Peer constant name that will identify this column.
	 * @param      $name string
	 */
	public function setPeerName($name) {
		$this->peerName = $name;
	}

	/**
	 * Get type to use in PHP sources.
	 * 
	 * If no type has been specified, then uses results of getPhpNative().
	 *
	 * @return     string The type name.
	 * @see        getPhpNative()
	 */
	public function getPhpType()
	{
		if ($this->phpType !== null) {
			return $this->phpType;
		}
		return $this->getPhpNative();
	}

	/**
	 * Get the location of this column within the table (one-based).
	 * @return     int value of position.
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * Get the location of this column within the table (one-based).
	 * @param      int $v Value to assign to position.
	 */
	public function setPosition($v)
	{
		$this->position = $v;
	}

	/**
	 * Set the parent Table of the column
	 */
	public function setTable(Table $parent)
	{
		$this->parentTable = $parent;
	}

	/**
	 * Get the parent Table of the column
	 */
	public function getTable()
	{
		return $this->parentTable;
	}

	/**
	 * Returns the Name of the table the column is in
	 */
	public function getTableName()
	{
		return $this->parentTable->getName();
	}

	/**
	 * Adds a new inheritance definition to the inheritance list and set the
	 * parent column of the inheritance to the current column
	 * @param      mixed $inhdata Inheritance or XML data.
	 */
	public function addInheritance($inhdata)
	{
		if ($inhdata instanceof Inheritance) {
			$inh = $inhdata;
			$inh->setColumn($this);
			if ($this->inheritanceList === null) {
				$this->inheritanceList = array();
				$this->isEnumeratedClasses = true;
			}
			$this->inheritanceList[] = $inh;
			return $inh;
		} else {
			$inh = new Inheritance();
			$inh->loadFromXML($inhdata);
			return $this->addInheritance($inh);
		}
	}

	/**
	 * Get the inheritance definitions.
	 */
	public function getChildren()
	{
		return $this->inheritanceList;
	}

	/**
	 * Determine if this column is a normal property or specifies a
	 * the classes that are represented in the table containing this column.
	 */
	public function isInheritance()
	{
		return $this->isInheritance;
	}

	/**
	 * Determine if possible classes have been enumerated in the xml file.
	 */
	public function isEnumeratedClasses()
	{
		return $this->isEnumeratedClasses;
	}

	/**
	 * Return the isNotNull property of the column
	 */
	public function isNotNull()
	{
		return $this->isNotNull;
	}

	/**
	 * Set the isNotNull property of the column
	 */
	public function setNotNull($status)
	{
		$this->isNotNull = (boolean) $status;
	}

	/**
	 * Return NOT NULL String for this column
	 *
	 * @return     "NOT NULL" if null values are not allowed or an empty string.
	 */
	public function getNotNullString()
	{
		return $this->getTable()->getDatabase()->getPlatform()->getNullString($this->isNotNull());
	}

	/**
	 * Set if the column is a primary key or not
	 */
	public function setPrimaryKey($pk)
	{
		$this->isPrimaryKey = (boolean) $pk;
	}

	/**
	 * Return true if the column is a primary key
	 */
	public function isPrimaryKey()
	{
		return $this->isPrimaryKey;
	}

	/**
	 * Set if the column is the node key of a tree
	 */
	public function setNodeKey($nk)
	{
		$this->isNodeKey = (boolean) $nk;
	}

	/**
	 * Return true if the column is a node key of a tree
	 */
	public function isNodeKey()
	{
		return $this->isNodeKey;
	}

	/**
	 * Set if the column is the node key of a tree
	 */
	public function setNodeKeySep($sep)
	{
		$this->nodeKeySep = (string) $sep;
	}

	/**
	 * Return true if the column is a node key of a tree
	 */
	public function getNodeKeySep()
	{
		return $this->nodeKeySep;
	}

	/**
	 * Set if the column is the nested set left key of a tree
	 */
	public function setNestedSetLeftKey($nslk)
	{
		$this->isNestedSetLeftKey = (boolean) $nslk;
	}

	/**
	 * Return true if the column is a nested set key of a tree
	 */
	public function isNestedSetLeftKey()
	{
		return $this->isNestedSetLeftKey;
	}

	/**
	 * Set if the column is the nested set right key of a tree
	 */
	public function setNestedSetRightKey($nsrk)
	{
		$this->isNestedSetRightKey = (boolean) $nsrk;
	}

	/**
	 * Return true if the column is a nested set right key of a tree
	 */
	public function isNestedSetRightKey()
	{
		return $this->isNestedSetRightKey;
	}

	/**
	 * Set true if the column is UNIQUE
	 */
	public function setUnique($u)
	{
		$this->isUnique = $u;
	}

	/**
	 * Get the UNIQUE property
	 */
	public function isUnique()
	{
		return $this->isUnique;
	}

	/**
	 * Return true if the column requires a transaction in Postgres
	 */
	public function requiresTransactionInPostgres()
	{
		return $this->needsTransactionInPostgres;
	}

	/**
	 * Utility method to determine if this column is a foreign key.
	 */
	public function isForeignKey()
	{
		return ($this->getForeignKey() !== null);
	}

	/**
	 * Determine if this column is a foreign key that refers to the
	 * same table as another foreign key column in this table.
	 */
	public function isMultipleFK()
	{
		$fk = $this->getForeignKey();
		if ($fk !== null) {
			$fks = $this->parentTable->getForeignKeys();
			for ($i=0, $len=count($fks); $i < $len; $i++) {
				if ($fks[$i]->getForeignTableName() === $fk->getForeignTableName()
				&& !in_array($this->name, $fks[$i]->getLocalColumns()) ) {
					return true;
				}
			}
		}

		// No multiple foreign keys.
		return false;
	}

	/**
	 * get the foreign key object for this column
	 * if it is a foreign key or part of a foreign key
	 */
	public function getForeignKey()
	{
		return $this->parentTable->getForeignKey($this->name);
	}

	/**
	 * Utility method to get the related table of this column if it is a foreign
	 * key or part of a foreign key
	 */
	public function getRelatedTableName()
	{
		$fk = $this->getForeignKey();
		return ($fk === null ? null : $fk->getForeignTableName());
	}


	/**
	 * Utility method to get the related column of this local column if this
	 * column is a foreign key or part of a foreign key.
	 */
	public function getRelatedColumnName()
	{
		$fk = $this->getForeignKey();
		if ($fk === null) {
			return null;
		} else {
			$m = $fk->getLocalForeignMapping();
			$c = @$m[$this->name];
			if ($c === null) {
				return null;
			} else {
				return $c;
			}
		}
	}

	/**
	 * Adds the foreign key from another table that refers to this column.
	 */
	public function addReferrer(ForeignKey $fk)
	{
		if ($this->referrers === null) {
			$this->referrers = array();
		}
		$this->referrers[] = $fk;
	}

	/**
	 * Get list of references to this column.
	 */
	public function getReferrers()
	{
		if ($this->referrers === null) {
			$this->referrers = array();
		}
		return $this->referrers;
	}

	/**
	 * Sets the colunm type
	 */
	public function setType($propelType)
	{
		$this->domain = new Domain();
		$this->domain->copy($this->getPlatform()->getDomainForType($propelType));

		$this->propelType = $propelType;
		if ($propelType == PropelTypes::VARBINARY|| $propelType == PropelTypes::LONGVARBINARY || $propelType == PropelTypes::BLOB) {
			$this->needsTransactionInPostgres = true;
		}
	}

	/**
	 * Returns the column Creole type as a string.
	 * @return     string The constant representing Creole type: e.g. "VARCHAR".
	 */
	public function getType()
	{
		return PropelTypes::getCreoleType($this->propelType);
	}

	/**
	 * Returns the column Creole type as a string.
	 * @return     string The constant representing Creole type: e.g. "VARCHAR".
	 */
	public function getPDOType()
	{
		return PropelTypes::getPDOType($this->propelType);
	}

	/**
	 * Returns the column type as given in the schema as an object
	 */
	public function getPropelType()
	{
		return $this->propelType;
	}

	/**
	 * Utility method to know whether column needs Blob/Lob handling.
	 * @return     boolean
	 */
	public function isLobType()
	{
		return PropelTypes::isLobType($this->propelType);
	}

	/**
	 * Utility method to see if the column is text type.
	 */
	public function isTextType()
	{
		return PropelTypes::isTextType($this->propelType);
	}
	
	/**
	 * Utility method to know whether column is a temporal column.
	 * @return     boolean
	 */
	public function isTemporalType()
	{
		return PropelTypes::isTemporalType($this->propelType);
	}
	
	/**
	 * String representation of the column. This is an xml representation.
	 */
	public function toString()
	{
		$result = "	<column name=\"" . $this->name . '"';
		if ($this->phpName !== null) {
			$result .= " phpName=\"" . $this->phpName . '"';
		}
		if ($this->isPrimaryKey) {
			$result .= " primaryKey=\"" . ($this->isPrimaryKey ? "true" : "false"). '"';
		}

		if ($this->isNotNull) {
			$result .= " required=\"true\"";
		} else {
			$result .= " required=\"false\"";
		}

		$result .= " type=\"" . $this->propelType . '"';

		if ($this->domain->getSize() !== null) {
			$result .= " size=\"" . $this->domain->getSize() . '"';
		}

		if ($this->domain->getScale() !== null) {
			$result .= " scale=\"" . $this->domain->getScale() . '"';
		}

		if ($this->domain->getDefaultValue() !== null) {
			$result .= " default=\"" . $this->domain->getDefaultValue() . '"';
		}

		if ($this->isInheritance()) {
			$result .= " inheritance=\"" . $this->inheritanceType
			. '"';
		}

		if ($this->isNodeKey()) {
			$result .= " nodeKey=\"true\"";
			if ($this->getNodeKeySep() !== null) {
				$result .= " nodeKeySep=\"" . $this->nodeKeySep . '"';
			}
		}

		// Close the column.
		$result .= " />\n";

		return $result;
	}

	/**
	 * Returns the size of the column
	 * @return     string
	 */
	public function getSize()
	{
		return $this->domain->getSize();
	}

	/**
	 * Set the size of the column
	 * @param      string $newSize
	 */
	public function setSize($newSize)
	{
		$this->domain->setSize($newSize);
	}

	/**
	 * Returns the scale of the column
	 * @return     string
	 */
	public function getScale()
	{
		return $this->domain->getScale();
	}

	/**
	 * Set the scale of the column
	 * @param      string $newScale
	 */
	public function setScale($newScale)
	{
		$this->domain->setScale($newScale);
	}

	/**
	 * Return the size in brackets for use in an sql
	 * schema if the type is String.  Otherwise return an empty string
	 */
	public function printSize()
	{
		return $this->domain->printSize();
	}

	/**
	 * Return a string that will give this column a default value.
	 * @return     string
	 */
	public function getDefaultSetting()
	{
		$dflt = "";
		$defaultValue = $this->getDefaultValue();
		if ($defaultValue !== null) {
			$dflt .= "default ";
			
			if ($this->getDefaultValue()->isExpression()) {
				$dflt .= $this->getDefaultValue()->getValue();
			} else {
				if ($this->isTextType()) {
					$dflt .= $this->getPlatform()->quote($defaultValue->getValue());
				} elseif ($this->getType() == PropelTypes::BOOLEAN) {
					$dflt .= $this->getPlatform()->getBooleanString($defaultValue->getValue());
				} else {
					$dflt .= $defaultValue->getValue();
				}
			}
		}
		return $dflt;
	}

	/**
	 * Set a string that will give this column a default value.
	 */
	public function setDefaultValue($def)
	{
		$this->domain->setDefaultValue($def);
	}

	/**
	 * Get the default value object for this column.
	 * @return     ColumnDefaultValue
	 * @see        Domain::getDefaultValue()
	 */
	public function getDefaultValue()
	{
		return $this->domain->getDefaultValue();
	}

	/**
	 * Get the default value suitable for use in PHP.
	 * @return     mixed
	 * @see        Domain::getPhpDefaultValue()
	 */
	public function getPhpDefaultValue()
	{
		return $this->domain->getPhpDefaultValue();
	}

	/**
	 * Returns the class name to do input validation
	 */
	public function getInputValidator()
	{
		return $this->inputValidator;
	}

	/**
	 * Return auto increment/sequence string for the target database. We need to
	 * pass in the props for the target database!
	 */
	public function isAutoIncrement()
	{
		return $this->isAutoIncrement;
	}

	/**
	 * Return auto increment/sequence string for the target database. We need to
	 * pass in the props for the target database!
	 */
	public function isLazyLoad()
	{
		return $this->isLazyLoad;
	}

	/**
	 * Gets the auto-increment string.
	 * @return     string
	 */
	public function getAutoIncrementString()
	{
		if ($this->isAutoIncrement()&& IDMethod::NATIVE === $this->getTable()->getIdMethod()) {
			return $this->getPlatform()->getAutoIncrement();
		} elseif ($this->isAutoIncrement()) {
			throw new EngineException("You have specified autoIncrement for column '" . $this->name . "' but you have not specified idMethod=\"native\" for table '" . $this->getTable()->getName() . "'.");
		}
		return "";
	}

	/**
	 * Set the auto increment value.
	 * Use isAutoIncrement() to find out if it is set or not.
	 */
	public function setAutoIncrement($value)
	{
		$this->isAutoIncrement = (boolean) $value;
	}

	/**
	 * Set the column type from a string property
	 * (normally a string from an sql input file)
	 *
	 * @deprecated Do not use; this will be removed in next release.
	 */
	public function setTypeFromString($typeName, $size)
	{
		$tn = strtoupper($typeName);
		$this->setType($tn);

		if ($size !== null) {
			$this->size = $size;
		}

		if (strpos($tn, "CHAR") !== false) {
			$this->domain->setType(PropelTypes::VARCHAR);
		} elseif (strpos($tn, "INT") !== false) {
			$this->domain->setType(PropelTypes::INTEGER);
		} elseif (strpos($tn, "FLOAT") !== false) {
			$this->domain->setType(PropelTypes::FLOAT);
		} elseif (strpos($tn, "DATE") !== false) {
			$this->domain->setType(PropelTypes::DATE);
		} elseif (strpos($tn, "TIME") !== false) {
			$this->domain->setType(PropelTypes::TIMESTAMP);
		} else if (strpos($tn, "BINARY") !== false) {
			$this->domain->setType(PropelTypes::LONGVARBINARY);
		} else {
			$this->domain->setType(PropelTypes::VARCHAR);
		}
	}

	/**
	 * Return a string representation of the native PHP type which corresponds
	 * to the Creole type of this column. Use in the generation of Base objects.
	 *
	 * @return     string PHP datatype used by propel.
	 */
	public function getPhpNative()
	{
		return PropelTypes::getPhpNative($this->propelType);
	}

	/**
	 * Returns true if the column's PHP native type is an boolean, int, long, float, double, string.
	 * @return     boolean
	 * @see        PropelTypes::isPhpPrimitiveType()
	 */
	public function isPhpPrimitiveType()
	{
		return PropelTypes::isPhpPrimitiveType($this->getPhpType());
	}

	/**
	 * Return true if column's PHP native type is an boolean, int, long, float, double.
	 * @return     boolean
	 * @see        PropelTypes::isPhpPrimitiveNumericType()
	 */
	public function isPhpPrimitiveNumericType()
	{
		return PropelTypes::isPhpPrimitiveNumericType($this->getPhpType());
	}
	
	/**
	 * Returns true if the column's PHP native type is a class name.
	 * @return     boolean
	 * @see        PropelTypes::isPhpObjectType()
	 */
	public function isPhpObjectType()
	{
		return PropelTypes::isPhpObjectType($this->getPhpType());
	}
	
	/**
	 * Get the platform/adapter impl.
	 *
	 * @return     Platform
	 */
	public function getPlatform()
	{
		return $this->getTable()->getDatabase()->getPlatform();
	}

	/**
	 *
	 * @return     string
	 * @deprecated Use DDLBuilder->getColumnDDL() instead; this will be removed in 1.3
	 */
	public function getSqlString()
	{
		$sb = "";
		$sb .= $this->getPlatform()->quoteIdentifier($this->getName()) . " ";
		$sb .= $this->getDomain()->getSqlType();
		if ($this->getPlatform()->hasSize($this->getDomain()->getSqlType())) {
			$sb .= $this->getDomain()->printSize();
		}
		$sb .= " ";
		$sb .= $this->getDefaultSetting() . " ";
		$sb .= $this->getNotNullString() . " ";
		$sb .= $this->getAutoIncrementString();
		return trim($sb);
	}
}
