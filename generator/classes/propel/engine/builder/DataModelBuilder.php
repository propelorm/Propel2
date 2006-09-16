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
 * This class has a static method to return the correct builder subclass identified by
 * a given key.  Note that in order for this factory method to work, the properties have to have
 * been loaded first.  Usage should look something like this (from within a AbstractProelDataModelTask subclass):
 *
 * <code>
 * DataModelBuilder::setBuildProperties($this->getPropelProperties());
 * $builder = DataModelBuilder::builderFactory($table, 'peer');
 * // $builder (by default) instanceof PHP5ComplexPeerBuilder
 * </code>
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder
 */
abstract class DataModelBuilder {

	// --------------------------------------------------------------
	// Static properties & methods
	// --------------------------------------------------------------

	/**
	 * Build properties (after they've been transformed from "propel.some.name" => "someName").
	 * @var array string[]
	 */
	private static $buildProperties = array();

	private static $cache = array();
	
	/**
	 * Sets the [name transformed] build properties to use.
	 * @param array Property values keyed by [transformed] prop names.
	 */
	public static function setBuildProperties($props)
	{
		self::$buildProperties = $props;
	}

	/**
	 * Get a specific [name transformed] build property.
	 * @param string $name
	 * @return string
	 */
	public static function getBuildProperty($name)
	{
		return isset(self::$buildProperties[$name]) ? self::$buildProperties[$name] : null;
	}

	/**
	 * Imports and returns the classname of the builder class for specified 'type'.
	 * @param $type The "key" for class to load.
	 * @return string The unqualified classname.
	 */
	public static function getBuilderClass($type)
	{
		if (empty(self::$buildProperties)) {
		    throw new BuildException("Cannot determine builder class when no build properties have been loaded (hint: Did you call DataModelBuilder::setBuildProperties(\$props) first?)");
		}
		$propname = 'builder' . ucfirst(strtolower($type)) . 'Class';
		$classpath = self::getBuildProperty($propname);

		if (empty($classpath)) {
			throw new BuildException("Unable to find class path for '$propname' property.");
		}

		// This is a slight hack to workaround camel case inconsistencies for the DDL classes.
		// Basically, we want to turn ?.?.?.sqliteDDLBuilder into ?.?.?.SqliteDDLBuilder
		$lastdotpos = strrpos($classpath, '.');
		if ($lastdotpos) $classpath{$lastdotpos+1} = strtoupper($classpath{$lastdotpos+1});
		else ucfirst($classpath);

		return Phing::import($classpath);
	}

	/**
	 * Factory method to load a new builder instance based on specified type.
	 * @param Table $table
	 * @param $type The "key" for class to load.
	 * @throws BuildException if specified class cannot be found / loaded.
	 */
	public static function builderFactory(Table $table, $type)
	{
		$classname = self::getBuilderClass($type);

		$cacheKey = strtolower($classname . $table->getName());
		
		if (!isset(self::$cache[$cacheKey])) {
		    self::$cache[$cacheKey] = new $classname($table);
		}
		
		return self::$cache[$cacheKey];
	}

	/**
     * Utility function to build a path for use in include()/require() statement.
     *
     * Supports two function signatures:
     * (1) getFilePath($dotPathClass);
     * (2) getFilePath($dotPathPrefix, $className);
     *
     * @param string $path dot-path to class or to package prefix.
     * @param string $classname class name
     * @return string
     */
    public static function getFilePath($path, $classname = null, $extension = '.php')
    {
        $path = strtr(ltrim($path, '.'), '.', '/');
        if ($classname !== null) {
            if ($path !== "") { $path .= '/'; }
            return $path . $classname . $extension;
        } else {
            return $path . $extension;
        }
    }

	// --------------------------------------------------------------
	// Non-static properties & methods inherited by subclasses
	// --------------------------------------------------------------

	/**
	 * The current table.
	 * @var Table
	 */
	private $table;

	/**
	 * An array of warning messages that can be retrieved for display (e.g. as part of phing build process).
	 * @var array string[]
	 */
	private $warnings = array();

	/**
	 * Creates new instance of DataModelBuilder subclass.
	 * @param Table $table The Table which we are using to build [OM, DDL, etc.].
	 */
	public function __construct(Table $table)
	{
		$this->table = $table;
	}

	/**
	 * Returns the Platform class for this table (database).
	 * @return Platform
	 */
	protected function getPlatform()
	{
		return $this->getTable()->getDatabase()->getPlatform();
	}

	/**
	 * Returns the database for current table.
	 * @return Database
	 */
	protected function getDatabase()
	{
		return $this->getTable()->getDatabase();
	}

	/**
	 * Returns the current Table object.
	 * @return Table
	 */
	protected function getTable()
	{
		return $this->table;
	}

	/**
	 * Pushes a message onto the stack of warnings.
	 * @param string $msg The warning message.
	 */
	protected function warn($msg)
	{
		$this->warnings[] = $msg;
	}

	/**
	 * Gets array of warning messages.
	 * @return array string[]
	 */
	public function getWarnings()
	{
		return $this->warnings;
	}

    /**
     *  Should Propel-generated classes be assumed to be autoloaded?
     *
     *  @return boolean TRUE if Propel-generated classes are autoloaded, false to have Propel generate include statements.
     */
    public function isAutoloadGeneratedClassess()
    {
        return $this->getBuildProperty('autoloadGeneratedClasses');
    }

    /**
     *  Should Propel-core classes be assumed to be autoloaded?
     *
     *  @return boolean TRUE if Propel-core classes are autoloaded (by Propel::autoload()), false to have Propel generate include statements for core classes.
     */
    public function isAutoloadCoreClassess()
    {
        return $this->getBuildProperty('autoloadCoreClasses');
    }

	/**
	 * Wraps call to Platform->quoteIdentifier() with a check to see whether quoting is enabled.
	 *
	 * All subclasses should call this quoteIdentifier() method rather than calling the Platform
	 * method directly.  This method is used by both DataSQLBuilder and DDLBuilder, and potentially
	 * in the OM builders also, which is why it is defined in this class.
	 *
	 * @param string $text The text to quote.
	 * @return string Quoted text.
	 */
	public function quoteIdentifier($text)
	{
		if (!self::getBuildProperty('disableIdentifierQuoting')) {
			return $this->getPlatform()->quoteIdentifier($text);
		}
		return $text;
	}
}
