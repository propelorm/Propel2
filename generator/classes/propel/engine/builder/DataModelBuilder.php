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
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder
 */
abstract class DataModelBuilder {
	
	/**
	 * The current table.
	 * @var Table
	 */
	private $table;	
	
	/**
	 * Build properties (after they've been transformed from "propel.some.name" => "someName").
	 * @var array string[]
	 */
	private static $buildProperties = array();
	
	/**
	 * Creates new instance of DataModelBuilder subclass.
	 * @param Table $table The Table which we are using to build [OM, DDL, etc.].
	 */
	public function __construct(Table $table)
	{
		$this->table = $table;
	}
	
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
	 * 
	 */
	protected function getPlatform()
	{
		return $this->getTable()->getDatabase()->getPlatform();
	}
	
	/**
	 * 
	 */
	protected function getDatabase()
	{
		return $this->getTable()->getDatabase();
	}
	
	/**
	 * 
	 */
	protected function getTable()
	{
		return $this->table;
	}	
    
    /**
     * Gets the path to be used in include()/require() statement.
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

	/**
	 * Factory method to load a new builder instance based on specified type.
	 * @param Table $table
	 * @param $type
	 * @throws BuildException if specified class cannot be found / loaded.
	 */
	public static function builderFactory(Table $table, $type)
	{
		$propname = 'builder' . ucfirst(strtolower($type)) . 'Class';
		$classpath = self::getBuildProperty($propname);
		if (empty($classpath)) {
			throw new BuildException("Unable to find class path for '$propname' property.");
		}
		$classname = Phing::import($classpath);
		return new $classname($table);
	}
	
}