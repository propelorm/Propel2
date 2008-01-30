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
 * This is the base class for any builder class that is using the data model.
 *
 * This could be extended by classes that build SQL DDL, PHP classes, configuration
 * files, input forms, etc.
 *
 * The GeneratorConfig needs to be set on this class in order for the builders
 * to be able to access the propel generator build properties.  You should be
 * safe if you always use the GeneratorConfig to get a configured builder class
 * anyway.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder
 */
abstract class DataModelBuilder {
	
	/**
	 * The current table.
	 * @var        Table
	 */
	private $table;

	/**
	 * The generator config object holding build properties, etc.
	 *
	 * @var        GeneratorConfig
	 */
	private $generatorConfig;
	
	/**
	 * An array of warning messages that can be retrieved for display (e.g. as part of phing build process).
	 * @var        array string[]
	 */
	private $warnings = array();

	/**
	 * Creates new instance of DataModelBuilder subclass.
	 * @param      Table $table The Table which we are using to build [OM, DDL, etc.].
	 */
	public function __construct(Table $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Gets the GeneratorConfig object.
	 *
	 * @return     GeneratorConfig
	 */
	public function getGeneratorConfig()
	{
		return $this->generatorConfig;
	}
	
	/**
	 * Get a specific [name transformed] build property.
	 * 
	 * @param      string $name
	 * @return     string
	 */
	public function getBuildProperty($name)
	{
		if ($this->getGeneratorConfig()) {
			return $this->getGeneratorConfig()->getBuildProperty($name);
		}
		return null; // just to be explicit
	}
	
	/**
	 * Sets the GeneratorConfig object.
	 *
	 * @param     GeneratorConfig $v
	 */
	public function setGeneratorConfig(GeneratorConfig $v)
	{
		$this->generatorConfig = $v;
	}
	
	/**
	 * Sets the table for this builder.
	 * @param     Table $table
	 */
	public function setTable(Table $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Returns the current Table object.
	 * @return     Table
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Convenience method to returns the Platform class for this table (database).
	 * @return     Platform
	 */
	public function getPlatform()
	{
		if ($this->getTable() && $this->getTable()->getDatabase()) {
			return $this->getTable()->getDatabase()->getPlatform();
		}
	}

	/**
	 * Convenience method to returns the database for current table.
	 * @return     Database
	 */
	public function getDatabase()
	{
		if ($this->getTable()) {
			return $this->getTable()->getDatabase();
		}
	}
	
	/**
	 * Pushes a message onto the stack of warnings.
	 * @param      string $msg The warning message.
	 */
	protected function warn($msg)
	{
		$this->warnings[] = $msg;
	}

	/**
	 * Gets array of warning messages.
	 * @return     array string[]
	 */
	public function getWarnings()
	{
		return $this->warnings;
	}

	/**
	 * Wraps call to Platform->quoteIdentifier() with a check to see whether quoting is enabled.
	 *
	 * All subclasses should call this quoteIdentifier() method rather than calling the Platform
	 * method directly.  This method is used by both DataSQLBuilder and DDLBuilder, and potentially
	 * in the OM builders also, which is why it is defined in this class.
	 *
	 * @param      string $text The text to quote.
	 * @return     string Quoted text.
	 */
	public function quoteIdentifier($text)
	{
		if (!$this->getBuildProperty('disableIdentifierQuoting')) {
			return $this->getPlatform()->quoteIdentifier($text);
		}
		return $text;
	}

	/**
	 * Returns the name of the current class being built, with a possible prefix.
	 * @return     string
	 * @see        OMBuilder#getClassname()
	 */
	public function prefixClassname($identifier)
	{
		return $this->getBuildProperty('classPrefix') . $identifier;
	}
	
	/**
	 * Returns the name of the current table being built, with a possible prefix.
	 * @return     string
	 */
	public function prefixTablename($identifier)
	{
		return $this->getBuildProperty('tablePrefix') . $identifier;
	}

	/**
	 * A name to use for creating a sequence if one is not specified.
	 */
	public function getSequenceName()
	{
		$table = $this->getTable();
		static $longNamesMap = array();
		$result = null;
		if ($table->getIdMethod() == IDMethod::NATIVE) {
			$idMethodParams = $table->getIdMethodParameters();
			if ($idMethodParams === null) {
				$maxIdentifierLength = $table->getDatabase()->getPlatform()->getMaxColumnNameLength();
				if (strlen($table->getName() . "_SEQ") > $maxIdentifierLength) {
					if (!isset($longNamesMap[$table->getName()])) {
						$longNamesMap[$table->getName()] = strval(count($longNamesMap) + 1);
					}
					$result = substr($table->getName(), 0, $maxIdentifierLength - strlen("_SEQ_" . $longNamesMap[$table->getName()])) . "_SEQ_" . $longNamesMap[$table->getName()];
				}
				else {
					$result = $table->getName() . "_SEQ";
				}
			} else {
				$result = $idMethodParams[0]->getValue();
			}
		}
		return $result;
	}

	/**
	* A Name to use for the serials (dependant sequence in PostgreSQL)
	*/
	public function getSerialName()
	{
		$table = $this->getTable();

		if ($table->getIdMethod() != IDMethod::NATIVE || !$table->hasAutoIncrementPrimaryKey()) {
			return null;
		}
		foreach ($table->getPrimaryKey() as $col) {
			if ($col->isAutoIncrement()) {
				return $table->getName() . '_' . $col->getName() . '_seq';
			}
		}
		return null;
	}
}
