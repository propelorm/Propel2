<?php
/*
 *  $Id: Column.php,v 1.11 2005/03/24 14:54:14 hlellelid Exp $
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

/**
 * A Class for holding data about a column used in an Application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Jon S. Stevens <jon@latchkey.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @version $Revision: 1.11 $
 * @package propel.engine.database.model
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
	 * @var string
	 */
	private $peerName;

	/**
	 * Type as defined in schema.xml
	 * @var string
	 */
	private $propelType;

	/**
	 * Type corresponding to Creole type
	 * @var int
	 */
	private $creoleType;

	/**
	 * Native PHP type
	 * @var string "string", "boolean", "int", "double"
	 */
	private $phpType;
	private $parentTable;
	private $position;
	private $isPrimaryKey = false;
	private $isNodeKey = false;
	private $nodeKeySep;
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
	private $needsTransactionInPostgres;//maybe this can be retrieved from vendorSpecificInfo?
	private $vendorSpecificInfo = array();

	/** class name to do input validation on this column */
	private $inputValidator = null;

	private $domain;

	/**
	 * Creates a new column and set the name
	 *
	 * @param name column name
	 */
	public function __construct($name = null)
	{
		$this->name = $name;
	}

	/**
	 * Return a comma delimited string listing the specified columns.
	 *
	 * @param columns Either a list of <code>Column</code> objects, or
	 * a list of <code>String</code> objects with column names.
	 */
	public function makeList($columns)
	{
		$obj = $columns[0];
		$isColumnList = ($obj instanceof Column);
		if ($isColumnList) {
			$obj = $obj->getName();
		}

		$buf = $obj;

		for ($i=1, $size=count($columns); $i < $size; $i++) {
			$obj = $columns[$i];
			if ($isColumnList) {
				$obj = $obj->getName();
			}
			$buf .= ", " . $obj;
		}

		return $buf;
	}

	/**
	 * Sets up the Column object based on the attributes that were passed to loadFromXML().
	 * @see parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$dom = $this->getAttribute("domain");
		if ($dom)  {
			$this->domain = new Domain();
			$this->domain->copy($this->getTable()->getDatabase()->getDomain($dom));
		} else {
			$this->domain = new Domain();			
			$this->domain->copy($this->getPlatform()->getDomainForType(self::DEFAULT_TYPE));
			$this->setType(strtoupper($this->getAttribute("type")));
		}
		
		//Name
		$this->name = $this->getAttribute("name");

		$this->phpName = $this->getAttribute("phpName");
		$this->phpType = $this->getAttribute("phpType");
		$this->peerName = $this->getAttribute("peerName");
		
		if (empty($this->phpType)) {
			$this->phpType = null;
		}

		// retrieves the method for converting from specified name to
		// a PHP name.
		$this->phpNamingMethod = $this->getAttribute("phpNamingMethod", $this->parentTable->getDatabase()->getDefaultPhpNamingMethod());
	   
		$this->isPrimaryKey = $this->booleanValue($this->getAttribute("primaryKey"));

		$this->isNodeKey = $this->booleanValue($this->getAttribute("nodeKey"));
		$this->nodeKeySep = $this->getAttribute("nodeKeySep", ".");
		
		$this->isNotNull = $this->booleanValue($this->getAttribute("required"), false);
		
		// Regardless of above, if this column is a primary key then it can't be null.
		if ($this->isPrimaryKey) {
			$this->isNotNull = true;
		}
		
		//AutoIncrement/Sequences
		$this->isAutoIncrement = $this->booleanValue($this->getAttribute("autoIncrement"));
		$this->isLazyLoad = $this->booleanValue($this->getAttribute("lazyLoad"));

		//Default column value.
		$this->domain->replaceDefaultValue($this->getAttribute("default"));
		$this->domain->replaceSize($this->getAttribute("size"));
		$this->domain->replaceScale($this->getAttribute("scale"));				
				
		$this->inheritanceType = $this->getAttribute("inheritance");
		$this->isInheritance = ($this->inheritanceType !== null
				&& $this->inheritanceType !== "false"); // here we are only checking for 'false', so don't
														// use boleanValue()

		$this->inputValidator = $this->getAttribute("inputValidator");
		$this->description = $this->getAttribute("description");
	}

	/**
	 * Gets domain for this column.
	 * @return Domain
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
	 * @param newDescription description for the Table
	 */
	public function setDescription($newDescription)
	{
		$this->description = $newDescription;
	}

	/**
	 * Get name to use in PHP sources
	 * @return string
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
	 * Get the Peer constant name that will identify this column.
	 * @return string
	 */
	public function getPeerName() {
		return $this->peerName;
	}

	/**
	 * Set the Peer constant name that will identify this column.
	 * @param $name string
	 */
	public function setPeerName($name) {
		$this->peerName = $name;
	}

	/**
	 * Get type to use in PHP sources.
	 * If no type has been specified, then uses results
	 * of getPhpNative().
	 *
	 * The distinction between getPhpType() and getPhpNative()
	 * is not as salient in PHP as it is in Java, but we'd like to leave open the
	 * option of specifying complex types (objects) in the schema.  While we can
	 * always cast to PHP native types, we can't cast objects (in PHP) -- hence the
	 * importance of maintaining this distinction.
	 *
	 * @return string The type name.
	 * @see getPhpNative()
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
	 * @return int value of position.
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * Get the location of this column within the table (one-based).
	 * @param int $v Value to assign to position.
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
	 * @param mixed $inhdata Inheritance or XML data.
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
	  * @return "NOT NULL" if null values are not allowed or an empty string.
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
	 * Returns the colunm type
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
	 * @return string The constant representing Creole type: e.g. "VARCHAR".
	 */
	public function getType()
	{
		return PropelTypes::getCreoleType($this->propelType);
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
	 * @return boolean
	 */
	public function isLob()
	{
		return PropelTypes::isLobType($this->propelType);
	}

	/**
	 * Utility method to see if the column is a string
	 */
	public function isString()
	{
		return (is_string($this->columnType));
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

		// Close the column.
		$result .= " />\n";

		return $result;
	}

	/**
	 * Returns the size of the column
	 * @return string
	 */
	public function getSize()
	{
		return $this->domain->getSize();
	}

	/**
	 * Set the size of the column
	 * @param string $newSize
	 */
	public function setSize($newSize)
	{
		$this->domain->setSize($newSize);
	}

	/**
	 * Returns the scale of the column
	 * @return string
	 */
	public function getScale()
	{
		return $this->domain->getScale();
	}

	/**
	 * Set the scale of the column
	 * @param string $newScale
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
	 * @return string
	 */
	 public function getDefaultSetting()
	 {
		$dflt = "";
		if ($this->getDefaultValue() !== null) {
			$dflt .= "default ";
			if (PropelTypes::isTextType($this->getType())) {
				$dflt .= '\'' . $this->getPlatform()->escapeText($this->getDefaultValue()) . '\'';
			} elseif ($this->getType() == PropelTypes::BOOLEAN) {
				$dflt .= $this->getPlatform()->getBooleanString($this->getDefaultValue());
			} else {
				$dflt .= $this->getDefaultValue();
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
	 * Get a string that will give this column a default value.
	 */
	public function getDefaultValue()
	{
		return $this->domain->getDefaultValue();
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
	 * @return string
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
	 * @return string PHP datatype used by propel.
	 */
	public function getPhpNative()
	{
		return PropelTypes::getPHPNative($this->propelType);
	}

	/**
	 * Returns true if the column's PHP native type is an
	 * boolean, int, long, float, double, string
	 */
	public function isPrimitive()
	{
		$t = $this->getPhpNative();
		return in_array($t, array("boolean", "int", "double", "string"));
	}

	/**
	 * Sets vendor specific parameter
	 */
	public function setVendorParameter($name, $value)
	{
		$this->vendorSpecificInfo[$name] = $value;
	}

	/**
	 * Sets vendor specific information to a table.
	 */
	public function setVendorSpecificInfo($info)
	{
		$this->vendorSpecificInfo = $info;
	}

	/**
	 * Retrieves vendor specific information to an index.
	 */
	public function getVendorSpecificInfo()
	{
		return $this->vendorSpecificInfo;
	}

  /**
   * Return true if column's PHP native type is an
   * boolean, int, long, float, double
   */
  public function isPrimitiveNumeric()
  {
	$t = $this->getPhpNative();
	return in_array($t, array("boolean", "int", "double"));
  }

	/**
	 * Get the platform/adapter impl.
	 *
	 * @return Platform
	 */
	public function getPlatform()
	{
		return $this->getTable()->getDatabase()->getPlatform();
	}

	/**
	 *
	 * @return string
	 */
	public function getSqlString()
	{
		$sb = "";
		$sb .= $this->getName() . " ";
		$sb .= $this->getDomain()->getSqlType();
		if ($this->getPlatform()->hasSize($this->getDomain()->getSqlType())) {
			$sb .= $this->getDomain()->printSize();
		}
		$sb .= " ";
		$sb .= $this->getDefaultSetting() . " ";
		$sb .= $this->getNotNullString() . " ";
		$sb .= $this->getAutoIncrementString();
		return $sb;
	}
}