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
 * DatabaseMap is used to model a database.
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
 * classes.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Daniel Rall <dlr@collab.net> (Torque)
 * @version    $Revision$
 * @package    propel.map
 */
class DatabaseMap {

	/** Name of the database. */
	private $name;

	/** Name of the tables in the database. */
	protected $tables = array();

	/**
	 * The table MapBuilder objects that will initialize tables (on demand).
	 * @var        array Map of table builders (name => MapBuilder)
	 */
	private $tableBuilders = array();

	/**
	 * Constructor.
	 *
	 * @param      string $name Name of the database.
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Does this database contain this specific table?
	 *
	 * @param      string $name The String representation of the table.
	 * @return     boolean True if the database contains the table.
	 */
	public function containsTable($name)
	{
		if ( strpos($name, '.') > 0) {
			$name = substr($name, 0, strpos($name, '.'));
		}
		// table builders are *always* loaded, whereas the tables aren't necessarily
		return isset($this->tableBuilders[$name]);
	}

	/**
	 * Get the name of this database.
	 *
	 * @return     string The name of the database.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get a TableMap for the table by name.
	 *
	 * @param      string $name Name of the table.
	 * @return     TableMap A TableMap
	 * @throws     PropelException if the table is undefined
	 */
	public function getTable($name)
	{
		if (!isset($this->tables[$name])) {
			if (!isset($this->tableBuilders[$name])) {
				throw new PropelException("Cannot fetch TableMap for undefined table: " . $name . ".  Make sure you have the static MapBuilder registration code after your peer stub class definition.");
			}
			$this->tableBuilders[$name]->doBuild();
		}
		return $this->tables[$name];
	}

	/**
	 * Get a TableMap[] of all of the tables in the database.
	 *
	 * @return     array A TableMap[].
	 */
	public function getTables()
	{
		// if there's a mismatch in the tables and tableBuilders
		if (count($this->tableBuilders) != count($this->tables)) {
			$missingTables = array_diff(array_keys($this->tableBuilders), array_keys($this->tables));
			foreach ($missingTables as $table) {
				$this->tableBuilders[$table]->doBuild();
			}
		}
		return $this->tables;
	}

	/**
	 * Add a new table to the database by name.
	 *
	 * This method creates an empty TableMap that must then be populated. This
	 * is called indirectly on-demand by the getTable() method, when there is
	 * a table builder (MapBuilder) registered, but no TableMap loaded.
	 *
	 * @param      string $tableName The name of the table.
	 * @return     TableMap The newly created TableMap.
	 */
	public function addTable($tableName)
	{
		$this->tables[$tableName] = new TableMap($tableName, $this);
		return $this->tables[$tableName];
	}

	/**
	 * Add a new table builder (MapBuilder) to the database by name.
	 *
	 * @param      string $tableName The name of the table.
	 */
	public function addTableBuilder($tableName, MapBuilder $builder)
	{
		$this->tableBuilders[$tableName] = $builder;
	}
}
