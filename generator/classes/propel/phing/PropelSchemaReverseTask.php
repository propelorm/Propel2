<?php

/*
 *  $Id: PropelCreoleTransformTask.php 945 2008-01-30 02:14:46Z hans $
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

require_once 'phing/tasks/ext/pdo/PDOTask.php';
include_once 'propel/engine/GeneratorConfig.php';
include_once 'propel/engine/database/model/PropelTypes.php';

/**
 * This class generates an XML schema of an existing database from
 * the database metadata.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision: 945 $
 * @package    propel.phing
 */
class PropelSchemaReverseTask extends PDOTask {
	
	/**
	 * File to contain XML database schema.
	 * @var        PhingFIle
	 */
	protected $xmlSchema;

	/**
	 * DB encoding to use
	 * @var        string
	 */
	protected $dbEncoding = 'iso-8859-1';

	/**
	 * DB schema to use.
	 * @var        string
	 */
	protected $dbSchema;
	
	/**
	 * The datasource name (used for <database name=""> in schema.xml)
	 *
	 * @var        string
	 */
	protected $databaseName;
	
	/**
	 * DOM document produced.
	 * @var        DOMDocument
	 */
	protected $doc;

	/**
	 * The document root element.
	 * @var        DOMElement
	 */
	protected $databaseNode;

	/**
	 * Hashtable of columns that have primary keys.
	 * @var        array
	 */
	protected $primaryKeys;

	/**
	 * Whether to use same name for phpName or not.
	 * @var        boolean
	 */
	protected $samePhpName;

	/**
	 * whether to add vendor info or not
	 * @var        boolean
	 */
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
	 * An initialized GeneratorConfig object containing the converted Phing props.
	 *
	 * @var        GeneratorConfig
	 */
	private $generatorConfig;
	
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
	
	/**
	 * Gets the (optional) schema name to use.
	 *
	 * @return     string
	 */
	public function getDbSchema()
	{
		return $this->dbSchema;
	}
	
	/**
	 * Sets the name of a database schema to use (optional).
	 *
	 * @param      string $dbSchema
	 */
	public function setDbSchema($dbSchema)
	{
		$this->dbSchema = $dbSchema;
	}
	
	/**
	 * Gets the database encoding.
	 *
	 * @return     string
	 */
	public function getDbEncoding($v)
	{
		return $this->dbEncoding;
	}
	
	/**
	 * Sets the database encoding.
	 *
	 * @param      string $v
	 */
	public function setDbEncoding($v)
	{
		$this->dbEncoding = $v;
	}
	
	/**
	 * Gets the datasource name.
	 *
	 * @return     string
	 */
	public function getDatabaseName()
	{
		return $this->databaseName;
	}
	
	/**
	 * Sets the datasource name.
	 * 
	 * This will be used as the <database name=""> value in the generated schema.xml
	 *
	 * @param      string $v
	 */
	public function setDatabaseName($v)
	{
		$this->databaseName = $v;
	}
	
	/**
	 * Sets the output name for the XML file.
	 *
	 * @param    PhingFile $v
	 */
	public function setOutputFile(PhingFile $v)
	{
		$this->xmlSchema = $v;
	}

	/**
	 * Set whether to use the column name as phpName without any translation.
	 *
	 * @param    boolean $v
	 */
	public function setSamePhpName($v)
	{
		$this->samePhpName = $v;
	}
	
	/**
	 * Set whether to add vendor info to the schema.
	 *
	 * @param      boolean $v
	 */
	public function setAddVendorInfo($v)
	{
		$this->addVendorInfo = (boolean) $v;
	}

	/**
	 * Sets set validator bitfield from propel.addValidators property
	 *
	 * @param      string $v The propel.addValidators property
	 * @return     void
	 */
	public function setAddValidators($v)
	{
		// lowercase input
		$v = strtolower($v);
		// make it a bit expression
		$v = str_replace(
		array_keys(self::$validatorBitMap), self::$validatorBitMap, $v);
		// check if it's a valid boolean expression
		if (!preg_match('/[^\d|&~ ]/', $v)) {
			// eval the expression
			eval("\$v = $v;");
		} else {
			$this->log("\n\nERROR: NO VALIDATORS ADDED!\n\nThere is an error in propel.addValidators build property.\n\nAllowed tokens are: " . implode(', ', array_keys(self::$validatorBitMap)) . "\n\nAllowed operators are (like in php.ini):\n\n|    bitwise OR\n&    bitwise AND\n~    bitwise NOT\n\n", Project::MSG_ERR);
			$v = self::VALIDATORS_NONE;
		}
		$this->validatorBits = $v;
	}
	
	/**
	 * Whether to use the column name as phpName without any translation.
	 *
	 * @return     boolean
	 */
	public function isSamePhpName()
	{
		return $this->samePhpName;
	}

	/**
	 * @throws     BuildException
	 */
	public function main()
	{
		if (!$this->getDatabaseName()) {
			throw new BuildException("databaseName attribute is required for schema reverse engineering", $this->getLocation());
		}
		
		//(not yet supported) $this->log("schema : " . $this->dbSchema);
		//DocumentTypeImpl docType = new DocumentTypeImpl(null, "database", null,
		//	   "http://jakarta.apache.org/turbine/dtd/database.dtd");

		$this->doc = new DOMDocument('1.0', 'utf-8');
		$this->doc->formatOutput = true; // pretty printing

		$this->doc->appendChild($this->doc->createComment("Autogenerated by ".get_class($this)." class."));

		try {
			
			$database = $this->buildModel();
			
			$database->appendXml($this->doc);
					
			$this->log("Writing XML to file: " . $this->xmlSchema->getPath());
			$out = new FileWriter($this->xmlSchema);
			$xmlstr = $this->doc->saveXML();
			$out->write($xmlstr);
			$out->close();
			
		} catch (Exception $e) {
			$this->log("There was an error building XML from metadata: " . $e->getMessage(), Project::MSG_ERR);
		}
		
		$this->log("Schema reverse engineering finished");
	}
	
	/**
	 * Gets the GeneratorConfig object for this task or creates it on-demand.
	 * @return     GeneratorConfig
	 */
	protected function getGeneratorConfig()
	{
		if ($this->generatorConfig === null) {
			$this->generatorConfig = new GeneratorConfig();
			$this->generatorConfig->setBuildProperties($this->getProject()->getProperties()); 
		}
		return $this->generatorConfig;
	}
	
	/**
	 * Builds the model classes from the database schema.
	 * @return     Database The built-out Database (with all tables, etc.)
	 */
	protected function buildModel()
	{
		$config = $this->getGeneratorConfig();
		$con = $this->getConnection();
		
		$database = new Database($this->getDatabaseName());
		$database->setPlatform($config->getConfiguredPlatform($con));
		
		// Some defaults ...
		$database->setDefaultIdMethod(IDMethod::NATIVE);
		
		$parser = $config->getConfiguredSchemaParser($con);
		
		$parser->parse($database);
		
		return $database;
	}

}
