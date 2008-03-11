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

require_once 'phing/Task.php';
include_once 'propel/engine/database/model/PropelTypes.php';

/**
 * This class generates an XML schema of an existing database from
 * Creole metadata.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Jason van Zyl <jvanzyl@periapt.com> (Torque)
 * @author     Fedor Karpelevitch <fedor.karpelevitch@barra.com> (Torque)
 * @version    $Revision$
 * @package    propel.phing
 */
class PropelCreoleTransformTask extends Task {

	/** Name of XML database schema produced. */
	protected $xmlSchema;

	/** Creole URL. */
	protected $dbUrl;

	/** Creole driver. */
	protected $dbDriver;

	/** Creole user name. */
	protected $dbUser;

	/** Creole password. */
	protected $dbPassword;

	/** DB encoding to use */
	protected $dbEncoding = 'iso-8859-1';

	/** DB schema to use. */
	protected $dbSchema;

	/** parsed DB DSN */
	protected $dsn;

	/** DOM document produced. */
	protected $doc;

	/** The document root element. */
	protected $databaseNode;

	/** Hashtable of columns that have primary keys. */
	protected $primaryKeys;

	/** Hashtable to track what table a column belongs to. */
	// doesn't seem to be used
	// protected $columnTableMap;

	/** whether to use same name for phpName or not */
	protected $samePhpName;

	/** whether to add vendor info or not */
	protected $addVendorInfo;

	/**
	 * Bitfield to switch on/off which validators will be created.
	 *
	 * @var        int
	 */
	protected $validatorBits;

	/**
	 * Collect validatorInfos to create validators.
	 *
	 * @var        int
	 */
	protected $validatorInfos;

	/**
	 * Zero bit for no validators
	 */
	const VALIDATORS_NONE = 0;

	/**
	 * Bit for maxLength validator
	 */
	const VALIDATORS_MAXLENGTH = 1;

	/**
	 * Bit for maxValue validator
	 */
	const VALIDATORS_MAXVALUE = 2;

	/**
	 * Bit for type validator
	 */
	const VALIDATORS_TYPE = 4;

	/**
	 * Bit for required validator
	 */
	const VALIDATORS_REQUIRED = 8;

	/**
	 * Bit for unique validator
	 */
	const VALIDATORS_UNIQUE = 16;

	/**
	 * Bit for all validators
	 */
	const VALIDATORS_ALL = 255;

	/**
	 * Maps validator type tokens to bits
	 *
	 * The tokens are used in the propel.addValidators property to define
	 * which validators are to be added
	 *
	 * @static
	 * @var        array
	 */
	static protected $validatorBitMap = array (
		'none' => PropelCreoleTransformTask::VALIDATORS_NONE,
		'maxlength' => PropelCreoleTransformTask::VALIDATORS_MAXLENGTH,
		'maxvalue' => PropelCreoleTransformTask::VALIDATORS_MAXVALUE,
		'type' => PropelCreoleTransformTask::VALIDATORS_TYPE,
		'required' => PropelCreoleTransformTask::VALIDATORS_REQUIRED,
		'unique' => PropelCreoleTransformTask::VALIDATORS_UNIQUE,
		'all' => PropelCreoleTransformTask::VALIDATORS_ALL,
	);

	/**
	 * Defines messages that are added to validators
	 *
	 * @static
	 * @var        array
	 */
	static protected $validatorMessages = array (
		'maxlength' => array (
			'msg' => 'The field %s must be not longer than %s characters.',
			'var' => array('colName', 'value')
	),
		'maxvalue' => array (
			'msg' => 'The field %s must be not greater than %s.',
			'var' => array('colName', 'value')
	),
		'type' => array (
			'msg' => 'The field %s is not a valid value.',
			'var' => array('colName')
	),
		'required' => array (
			'msg' => 'The field %s is required.',
			'var' => array('colName')
	),
		'unique' => array (
			'msg' => 'This %s already exists in table %s.',
			'var' => array('colName', 'tableName')
	),
	);

	public function getDbSchema()
	{
		return $this->dbSchema;
	}

	public function setDbSchema($dbSchema)
	{
		$this->dbSchema = $dbSchema;
	}

	public function setDbUrl($v)
	{
		$this->dbUrl = $v;
	}

	public function setDbDriver($v)
	{
		$this->dbDriver = $v;
	}

	public function setDbUser($v)
	{
		$this->dbUser = $v;
	}

	public function setDbPassword($v)
	{
		$this->dbPassword = $v;
	}

	public function setDbEncoding($v)
	{
		$this->dbEncoding = $v;
	}

	public function setOutputFile($v)
	{
		$this->xmlSchema = $v;
	}

	public function setSamePhpName($v)
	{
		$this->samePhpName = $v;
	}

	public function setAddVendorInfo($v)
	{
		$this->addVendorInfo = (boolean) $v;
	}

	/**
	 * Sets set validator bitfield from a comma-separated list of "validator bit" names.
	 *
	 * @param      string $v The comma-separated list of which validators to add.
	 * @return     void
	 */
	public function setAddValidators($v)
	{
		$validKeys = array_keys(self::$validatorBitMap);

		// lowercase input
		$v = strtolower($v);

		$bits = self::VALIDATORS_NONE;

		$exprs = explode(',', $v);
		foreach ($exprs as $expr) {
			$expr = trim($expr);
			if (!isset(self::$validatorBitMap[$expr])) {
				throw new BuildException("Unable to interpret validator in expression ('$v'): " . $expr);
			}
			$bits |= self::$validatorBitMap[$expr];
		}

		$this->validatorBits = $bits;
	}

	public function isSamePhpName()
	{
		return $this->samePhpName;
	}

	/**
	 * Default constructor.
	 * @return     void
	 * @throws     BuildException
	 */
	public function main()
	{
		include_once 'creole/Creole.php';
		if (!class_exists('Creole')) {
			throw new BuildException( get_class($this) . " task depends on Creole classes being on include_path. (i.e. include of 'creole/Creole.php' failed.)", $this->getLocation());
		}

		$this->log("Propel - CreoleToXMLSchema starting");
		$this->log("Your DB settings are:");
		$this->log("driver : " . ($this->dbDriver ? $this->dbDriver : "(default)"));
		$this->log("URL : " . $this->dbUrl);

		//(not yet supported) $this->log("schema : " . $this->dbSchema);
		//DocumentTypeImpl docType = new DocumentTypeImpl(null, "database", null,
		//	   "http://jakarta.apache.org/turbine/dtd/database.dtd");

		$this->doc = new DOMDocument('1.0', 'utf-8');
		$this->doc->formatOutput = true; // pretty printing

		$this->doc->appendChild($this->doc->createComment("Autogenerated by CreoleToXMLSchema!"));

		try {
			$this->generateXML();
			$this->log("Writing XML to file: " . $this->xmlSchema);
			$outFile = new PhingFile($this->xmlSchema);
			$out = new FileWriter($outFile);
			$xmlstr = $this->doc->saveXML();
			$out->write($xmlstr);
			$out->close();
		} catch (Exception $e) {
			$this->log("There was an error building XML from metadata: " . $e->getMessage(), Project::MSG_ERR);
		}
		$this->log("Propel - CreoleToXMLSchema finished");
	}

	/**
	 * Generates an XML database schema from Creole metadata.
	 *
	 * @return     void
	 * @throws     Exception a generic exception.
	 */
	public function generateXML()
	{
		// Establish db connection
		$con = $this->getConnection();

		// Get the database Metadata.
		$dbInfo = $con->getDatabaseInfo();

		// create and add the database node
		$databaseNode = $this->createDatabaseNode($dbInfo);
		$this->doc->appendChild($databaseNode);
	}

	/**
	 * Establishes a Creole database connection
	 *
	 * @return     object The connection
	 */
	protected function getConnection() {

		// Attemtp to connect to a database.
		$this->dsn = Creole::parseDSN($this->dbUrl);
		if ($this->dbUser) {
			$this->dsn["username"] = $this->dbUser;
		}
		if ($this->dbPassword) {
			$this->dsn["password"] = $this->dbPassword;
		}
		if ($this->dbDriver) {
			Creole::registerDriver($this->dsn['phptype'], $this->dbDriver);
		}
		$con = Creole::getConnection($this->dsn);
		$this->log("DB connection established");

		return $con;
	}

	/**
	 * Creates a database node
	 *
	 * @param      object $dbInfo The dbInfo for this db
	 * @return     object The database node instance
	 */
	protected function createDatabaseNode($dbInfo) {

		$this->log("Processing database");

		$node = $this->doc->createElement("database");
		$node->setAttribute("name", $dbInfo->getName());

		if ($vendorNode = $this->createVendorInfoNode($dbInfo->getVendorSpecificInfo())) {
			$node->appendChild($vendorNode);
		}

		// create and add table nodes
		foreach ($dbInfo->getTables() as $table) {
			$tableNode = $this->createTableNode($table);
			$node->appendChild($tableNode);
		}

		return $node;
	}

	/**
	 * Creates a table node
	 *
	 * @param      object $table The table
	 * @return     object The table node instance
	 */
	protected function createTableNode($table) {

		$this->log("Processing table: " . $table->toString());

		$node = $this->doc->createElement("table");
		$node->setAttribute("name", $table->getName());
		if ($this->isSamePhpName()) {
			$node->setAttribute("phpName", $table->getName());
		}
		if ($vendorNode = $this->createVendorInfoNode($table->getVendorSpecificInfo())) {
			$node->appendChild($vendorNode);
		}

		// Create and add column nodes, register column validators
		$columns = $table->getColumns();
		foreach ($columns as $column) {
			$columnNode = $this->createColumnNode($column);
			$node->appendChild($columnNode);
			$this->registerValidatorsForColumn($column);
			if ($column->isAutoIncrement()) {
				$idMethod = 'native';
			}
		}
		if (isset($idMethod)) {
			$node->setAttribute("idMethod", $idMethod);
		}

		// Create and add foreign key nodes.
		$foreignKeys = $table->getForeignKeys();
		foreach ($foreignKeys as $foreignKey) {
			$foreignKeyNode = $this->createForeignKeyNode($foreignKey);
			$node->appendChild($foreignKeyNode);
		}

		// Create and add index nodes.
		$indices =  $table->getIndices();
		foreach ($indices as $index) {
			$indexNode = $this->createIndexNode($index);
			$node->appendChild($indexNode);
		}

		// add an id-method-parameter if we have a sequence that matches table_colname_seq
		//
		//
		$pkey = $table->getPrimaryKey();
		if ($pkey) {
			$cols = $pkey->getColumns();
			if (count($cols) === 1) {
				$col = array_shift($cols);
				if ($col->isAutoIncrement()) {
					$seq_name = $table->getName().'_'.$col->getName().'_seq';
					if ($table->getDatabase()->isSequence($seq_name)) {
						$idMethodParameterNode = $this->doc->createElement("id-method-parameter");
						$idMethodParameterNode->setAttribute("value", $seq_name);
						$node->appendChild($idMethodParameterNode);
					}
				}
			}
		}


		// Create and add validator and rule nodes.
		$nodes = array();
		$tableName = $table->getName();
		if (isset($this->validatorInfos[$tableName])) {
			foreach ($this->validatorInfos[$tableName] as $colName => $rules) {
				$column = $table->getColumn($colName);
				$colName = $column->getName();
				foreach ($rules as $rule) {
					if (!isset($nodes[$colName])) {
						$nodes[$colName] = $this->createValidator($column, $rule['type']);
						$node->appendChild($nodes[$colName]);
					}
					$ruleNode = $this->createRuleNode($column, $rule);
					$nodes[$colName]->appendChild($ruleNode);
				}
			}
		}

		return $node;
	}

	/**
	 * Returns the Propel type for given Creole type.
	 *
	 * This used to be part of the PropelTypes class when Creole was an integral
	 * part of the Propel build process.  As of Propel 1.3, though, this method
	 * is only needed in this reverse-engineering code.
	 *
	 * @param      int $creoleType Creole type (e.g. CreoleTypes::CHAR)
	 * @return     string Equivalent Propel type (e.g. PropelTypes::CHAR)
	 */
	protected static function getMappedPropelType($creoleType)
	{
		static $creoleToPropelTypeMap;
		if ($creoleToPropelTypeMap === null) {
			$creoleToPropelTypeMap = array();
			$creoleToPropelTypeMap[CreoleTypes::CHAR] = PropelTypes::CHAR;
			$creoleToPropelTypeMap[CreoleTypes::VARCHAR] = PropelTypes::VARCHAR;
			$creoleToPropelTypeMap[CreoleTypes::LONGVARCHAR] = PropelTypes::LONGVARCHAR;
			$creoleToPropelTypeMap[CreoleTypes::CLOB] = PropelTypes::CLOB;
			$creoleToPropelTypeMap[CreoleTypes::NUMERIC] = PropelTypes::NUMERIC;
			$creoleToPropelTypeMap[CreoleTypes::DECIMAL] = PropelTypes::DECIMAL;
			$creoleToPropelTypeMap[CreoleTypes::TINYINT] = PropelTypes::TINYINT;
			$creoleToPropelTypeMap[CreoleTypes::SMALLINT] = PropelTypes::SMALLINT;
			$creoleToPropelTypeMap[CreoleTypes::INTEGER] = PropelTypes::INTEGER;
			$creoleToPropelTypeMap[CreoleTypes::BIGINT] = PropelTypes::BIGINT;
			$creoleToPropelTypeMap[CreoleTypes::REAL] = PropelTypes::REAL;
			$creoleToPropelTypeMap[CreoleTypes::FLOAT] = PropelTypes::FLOAT;
			$creoleToPropelTypeMap[CreoleTypes::DOUBLE] = PropelTypes::DOUBLE;
			$creoleToPropelTypeMap[CreoleTypes::BINARY] = PropelTypes::BINARY;
			$creoleToPropelTypeMap[CreoleTypes::VARBINARY] = PropelTypes::VARBINARY;
			$creoleToPropelTypeMap[CreoleTypes::LONGVARBINARY] = PropelTypes::LONGVARBINARY;
			$creoleToPropelTypeMap[CreoleTypes::BLOB] = PropelTypes::BLOB;
			$creoleToPropelTypeMap[CreoleTypes::DATE] = PropelTypes::DATE;
			$creoleToPropelTypeMap[CreoleTypes::TIME] = PropelTypes::TIME;
			$creoleToPropelTypeMap[CreoleTypes::TIMESTAMP] = PropelTypes::TIMESTAMP;
			$creoleToPropelTypeMap[CreoleTypes::BOOLEAN] = PropelTypes::BOOLEAN;
			$creoleToPropelTypeMap[CreoleTypes::YEAR] = PropelTypes::INTEGER;
		}

		if (isset($creoleToPropelTypeMap[$creoleType])) {
			return $creoleToPropelTypeMap[$creoleType];
		}
	}

	/**
	 * Creates an column node
	 *
	 * @param      object $column The Creole column
	 * @return     object The column node instance
	 */
	protected function createColumnNode($column) {

		$node = $this->doc->createElement("column");

		$table = $column->getTable();
		$colName = $column->getName();
		$colType = $column->getType();
		$colSize = $column->getSize();
		$colScale = $column->getScale();

		if ($colType === CreoleTypes::OTHER) {
			$this->log("Column [" . $table->getName() . "." . $colName . "] has a column type (".$column->getNativeType().") that Propel does not support.", Project::MSG_WARN);
		}

		$node->setAttribute("name", $colName);

		if ($this->isSamePhpName()) {
			$node->setAttribute("phpName", $colName);
		}

		$node->setAttribute("type", self::getMappedPropelType($colType));

		if ($colSize > 0 && (
		$colType == CreoleTypes::CHAR
		|| $colType == CreoleTypes::VARCHAR
		|| $colType == CreoleTypes::LONGVARCHAR
		|| $colType == CreoleTypes::DECIMAL
		|| $colType == CreoleTypes::FLOAT
		|| $colType == CreoleTypes::NUMERIC)) {
			$node->setAttribute("size", (string) $colSize);
		}

		if ($colScale > 0 && (
		$colType == CreoleTypes::DECIMAL
		|| $colType == CreoleTypes::FLOAT
		|| $colType == CreoleTypes::NUMERIC)) {
			$node->setAttribute("scale", (string) $colScale);
		}

		if (!$column->isNullable()) {
			$node->setAttribute("required", "true");
		}

		if ($column->isAutoIncrement()) {
			$node->setAttribute("autoIncrement", "true");
		}

		if (in_array($colName, $this->getTablePkCols($table))) {
			$node->setAttribute("primaryKey", "true");
		}

		if (($defValue = $column->getDefaultValue()) !== null) {
			$node->setAttribute("default", iconv($this->dbEncoding, 'utf-8', $defValue));
		}

		if ($vendorNode = $this->createVendorInfoNode($column->getVendorSpecificInfo())) {
			$node->appendChild($vendorNode);
		}

		return $node;
	}

	/**
	 * Returns the primary key columns for a table
	 *
	 * @param      object $table The table
	 * @return     array The primary keys
	 */
	protected function getTablePkCols($table) {

		static $columns = array();

		$tableName = $table->getName();
		if (!isset($columns[$tableName])) {
			$columns[$tableName] = array();
			$primaryKey = $table->getPrimaryKey();
			if ($primaryKey) {
				foreach ($primaryKey->getColumns() as $colObject) {
					$columns[$tableName][] = $colObject->getName();
				}
			}
		}
		return $columns[$tableName];
	}

	/**
	 * Creates an foreign key node
	 *
	 * @param      object $foreignKey The foreign key
	 * @return     object The foreign key node instance
	 */
	protected function createForeignKeyNode($foreignKey) {

		$node = $this->doc->createElement("foreign-key");
		if ($vendorNode = $this->createVendorInfoNode($foreignKey->getVendorSpecificInfo())) {
			$node->appendChild($vendorNode);
		}

		$refs = $foreignKey->getReferences();
		// all references must be to same table, so we can grab table from the first, foreign column
		$node->setAttribute("foreignTable", $refs[0][1]->getTable()->getName());
		$node->setAttribute("onDelete", $refs[0][2]);
		$node->setAttribute("onUpdate", $refs[0][3]);
		for ($m = 0, $size = count($refs); $m < $size; $m++) {
			$refNode = $this->doc->createElement("reference");
			$refData = $refs[$m];
			$refNode->setAttribute("local", $refData[0]->getName());
			$refNode->setAttribute("foreign", $refData[1]->getName());
			$node->appendChild($refNode);
		}

		return $node;
	}

	/**
	 * Creates an index node
	 *
	 * @param      object $index The index
	 * @return     object The index node instance
	 */
	protected function createIndexNode($index) {

		$indexType = $index->isUnique() ?  'unique' : 'index';

		$node = $this->doc->createElement($indexType);
		$node->setAttribute("name", $index->getName());

		$columns = $index->getColumns();
		foreach ($columns as $column) {
			$tableName = $column->getTable()->getName();
			$colName = $column->getName();
			$columnNode = $this->doc->createElement("{$indexType}-column");
			$columnNode->setAttribute("name", $colName);
			$node->appendChild($columnNode);
			if ($indexType == 'unique' && $this->isValidatorRequired('unique')) {
				$this->validatorInfos[$tableName][$colName][] = array('type' => 'unique');
			}
		}

		if ($vendorNode = $this->createVendorInfoNode($index->getVendorSpecificInfo())) {
			$node->appendChild($vendorNode);
		}

		return $node;
	}

	/**
	 * Checks whether to add validators of specified type or not
	 *
	 * @param      int $type The validator type constant.
	 * @return     boolean
	 */
	protected function isValidatorRequired($type) {
		return (($this->validatorBits & $type) === $type);
	}

	/**
	 * Registers column type specific validators if necessary
	 *
	 * We'll first collect the validators/rule infos and add them later on to
	 * have them appended to the table tag as a block.
	 *
	 * CreoleTypes are:
	 *
	 * 		BOOLEAN
	 * 		BIGINT, SMALLINT, TINYINT, INTEGER
	 * 		FLOAT, DOUBLE, NUMERIC, DECIMAL, REAL
	 * 		BIGINT, SMALLINT, TINYINT, INTEGER
	 * 		TEXT
	 * 		BLOB, CLOB, BINARY, VARBINARY, LONGVARBINARY
	 * 		DATE, YEAR, TIME
	 * 		TIMESTAMP
	 *
	 * We will add the following type specific validators:
	 *
	 *      for notNull columns: required validator
	 *      for unique indexes: unique validator
	 * 		for varchar types: maxLength validators (CHAR, VARCHAR, LONGVARCHAR)
	 * 		for numeric types: maxValue validators (BIGINT, SMALLINT, TINYINT, INTEGER, FLOAT, DOUBLE, NUMERIC, DECIMAL, REAL)
	 * 		for integer and timestamp types: notMatch validator with [^\d]+ (BIGINT, SMALLINT, TINYINT, INTEGER, TIMESTAMP)
	 * 		for float types: notMatch validator with [^\d\.]+ (FLOAT, DOUBLE, NUMERIC, DECIMAL, REAL)
	 *
	 * @param      object $column The Creole column
	 * @return     void
	 * @todo       find out how to evaluate the appropriate size and adjust maxValue rule values appropriate
	 * @todo       find out if float type column values must always notMatch('[^\d\.]+'), i.e. digits and point for any db vendor, language etc.
	 */
	protected function registerValidatorsForColumn($column) {

		$table = $column->getTable();
		$tableName = $table->getName();

		$colName = $column->getName();
		$colType = $column->getType();
		$colSize = $column->getSize();

		if ($this->isValidatorRequired(self::VALIDATORS_REQUIRED)) {
			$ruleInfo = array('type' => 'required');
			$this->validatorInfos[$tableName][$colName][] = $ruleInfo;
		}
		$isPrimarykeyCol = in_array($colName, $this->getTablePkCols($table));
		if ($this->isValidatorRequired(self::VALIDATORS_UNIQUE) && $isPrimarykeyCol) {
			$ruleInfo = array('type' => 'unique');
			$this->validatorInfos[$tableName][$colName][] = $ruleInfo;
		}
		if ($this->isValidatorRequired(self::VALIDATORS_MAXLENGTH) &&
		$colSize > 0 && in_array($colType, array(
		CreoleTypes::CHAR,
		CreoleTypes::VARCHAR,
		CreoleTypes::LONGVARCHAR))) {
			$ruleInfo = array('type' => 'maxLength', 'value' => $colSize);
			$this->validatorInfos[$tableName][$colName][] = $ruleInfo;
		}
		if ($this->isValidatorRequired(self::VALIDATORS_MAXVALUE) &&
		$colSize > 0 && in_array($colType, array(
		CreoleTypes::SMALLINT,
		CreoleTypes::TINYINT,
		CreoleTypes::INTEGER,
		CreoleTypes::BIGINT,
		CreoleTypes::FLOAT,
		CreoleTypes::DOUBLE,
		CreoleTypes::NUMERIC,
		CreoleTypes::DECIMAL,
		CreoleTypes::REAL))) {

			// TODO: how to evaluate the appropriate size??
			$this->log("WARNING: maxValue validator added for column $colName. You will have to adjust the size value manually.", Project::MSG_WARN);
			$ruleInfo = array('type' => 'maxValue', 'value' => $colSize);
			$this->validatorInfos[$tableName][$colName][] = $ruleInfo;
		}
		if ($this->isValidatorRequired(self::VALIDATORS_TYPE) &&
		$colSize > 0 && in_array($colType, array(
		CreoleTypes::SMALLINT,
		CreoleTypes::TINYINT,
		CreoleTypes::INTEGER,
		CreoleTypes::TIMESTAMP))) {
			$ruleInfo = array('type' => 'type', 'value' => '[^\d]+');
			$this->validatorInfos[$tableName][$colName][] = $ruleInfo;
		}
		if ($this->isValidatorRequired(self::VALIDATORS_TYPE) &&
		$colSize > 0 && in_array($colType, array(
		CreoleTypes::FLOAT,
		CreoleTypes::DOUBLE,
		CreoleTypes::NUMERIC,
		CreoleTypes::DECIMAL,
		CreoleTypes::REAL))) {
			// TODO: is this always true??
			$ruleInfo = array('type' => 'type', 'value' => '[^\d\.]+');
			$this->validatorInfos[$tableName][$colName][] = $ruleInfo;
		}
	}

	/**
	 * Creates a validator node
	 *
	 * @param      object  $column    The Creole column
	 * @param      integer $type      The validator type
	 * @return     object The validator node instance
	 */
	protected function createValidator($column, $type) {

		$node = $this->doc->createElement('validator');
		$node->setAttribute('column', $column->getName());

		return $node;
	}

	/**
	 * Creates a rule node
	 *
	 * @param      object  $column The Creole column
	 * @param      array   $rule   The rule info
	 * @return     object The rule node instance
	 */
	protected function createRuleNode($column, $rule) {

		extract($rule);

		// create message
		$colName = $column->getName();
		$tableName = $column->getTable()->getName();
		$msg = self::$validatorMessages[strtolower($type)];
		$tmp = compact($msg['var']);
		array_unshift($tmp, $msg['msg']);
		$msg = call_user_func_array('sprintf', $tmp);

		// add node
		$node = $this->doc->createElement('rule');
		$node->setAttribute('name', $type == 'type' ? 'notMatch' : $type);
		$node->setAttribute('message', $msg);

		return $node;
	}

	/**
	 * Creates a vendor info node
	 *
	 * returns false if no vendor info can or has to be added
	 *
	 * @param      array   $vendorInfo The validator info
	 * @return     object|boolean The vendor info instance or false
	 */
	protected function createVendorInfoNode($vendorInfo)
	{
		if (!$vendorInfo OR !$this->addVendorInfo) {
			return false;
		}

		$vendorNode = $this->doc->createElement("vendor");
		$vendorNode->setAttribute("type", $this->dsn["phptype"]);

		foreach ($vendorInfo as $key => $value) {
			$parameterNode = $this->doc->createElement("parameter");
			$value = iconv($this->dbEncoding, "utf-8", $value);
			$parameterNode->setAttribute("name", $key);
			$parameterNode->setAttribute("value", $value);
			$vendorNode->appendChild($parameterNode);
		}

		return $vendorNode;
	}

}
