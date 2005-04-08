<?php

/*
 *  $Id: DatabaseMap.php,v 1.14 2004/11/16 13:51:24 hlellelid Exp $
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

include_once 'propel/map/TableMap.php';

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
 * classes. See propel/templates/om/php5/MapBuilder.tpl and the classes generated
 * by that template for your datamodel to further understand how these are put 
 * together.
 * 
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @version $Revision: 1.14 $
 * @package propel.map
 */
class DatabaseMap {

    /** Name of the database. */
    private $name;

    /** Name of the tables in the database. */
    private $tables;

    /**
     * Constructor.
     *
     * @param string $name Name of the database.
     */
    function __construct($name)
    {
        $this->name = $name;
        $this->tables = array();
    }

    /**
     * Does this database contain this specific table?
     *
     * @param string $name The String representation of the table.
     * @return boolean True if the database contains the table.
     */
    public function containsTable($name)
    {
        if ( strpos($name, '.') > 0) {
            $name = substr($name, 0, strpos($name, '.'));
        }
        return isset($this->tables[$name]);
    }

    /**
     * Get the name of this database.
     *
     * @return string The name of the database.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get a TableMap for the table by name.
     *
     * @param string $name Name of the table.
     * @return TableMap A TableMap
     * @throws PropelException if the table is undefined
     */
    public function getTable($name)
    {
        if (!isset($this->tables[$name])) {
            throw new PropelException("Cannot fetch TableMap for undefined table: " . $name);
        }
        return $this->tables[$name];
    }

    /**
     * Get a TableMap[] of all of the tables in the database.
     *
     * @return array A TableMap[].
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Add a new table to the database by name.  It creates an empty
     * TableMap that you need to populate.
     *
     * @param string $tableName The name of the table.
   * @return TableMap The newly created TableMap.
     */
    public function addTable($tableName)
    {
        $this->tables[$tableName] = new TableMap($tableName, $this);
    return $this->tables[$tableName];
    }
}
