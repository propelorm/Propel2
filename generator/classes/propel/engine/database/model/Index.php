<?php

/*
 *  $Id: Index.php,v 1.5 2005/03/19 13:56:54 micha Exp $
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

/**
 * Information about indices of a table.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @version $Revision: 1.5 $
 * @package propel.engine.database.model
 */
class Index extends XMLElement {

    /** enables debug output */
    const DEBUG = false;

    private $indexName;
    private $parentTable;

    /** @var array string[] */
    private $indexColumns;

    /** @var array */
    protected $vendorSpecificInfo = array();

    /**
     * Creates a new instance with default characteristics (no name or
     * parent table, small column list size allocation, non-unique).
     *
     * @param Table $table
     * @param array $indexColumns
     */
    public function __construct(Table $table, $indexColumns = array())
    {
        $this->indexColumns = $indexColumns;
        $this->setTable($table);
        if (!empty($indexColumns)) {

            $this->createName();

            if (self::DEBUG) {
                print("Created Index named " . $this->getName()
                        . " with " . count($indexColumns) . " columns\n");
            }
        }
    }

    private function createName()
    {
        $table = $this->getTable();
        $inputs = array();
        $inputs[] = $table->getDatabase();
        $inputs[] = $table->getName();
        if ($this->isUnique()) {
            $inputs[] = "U";
        } else {
            $inputs[] = "I";
        }
        // ASSUMPTION: This Index not yet added to the list.
        $inputs[] = count($table->getIndices()) + 1;
        $this->indexName = NameFactory::generateName(
                NameFactory::CONSTRAINT_GENERATOR, $inputs);
    }

    /**
     * Sets up the Index object based on the attributes that were passed to loadFromXML().
	 * @see parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->indexName = $this->getAttribute("name");
    }

    /**
     * @see #isUnique()
     * @deprecated Use isUnique() instead.
     */
    public function getIsUnique()
    {
        return $this->isUnique();
    }

    /**
     * Returns the uniqueness of this index.
     */
    public function isUnique()
    {
        return false;
    }

    /**
     * @see #getName()
     * @deprecated Use getName() instead.
     */
    public function getIndexName()
    {
        return $this->getName();
    }

    /**
     * Gets the name of this index.
     */
    public function getName()
    {
        if ($this->indexName === null) {
            try {
                // generate an index name if we don't have a supplied one
                $this->createName();
            } catch (EngineException $e) {
                // still no name
            }
        }
        return $this->indexName;
    }

    /**
     * @see #setName(String name)
     * @deprecated Use setName(String name) instead.
     */
    public function setIndexName($name)
    {
        $this->setName($name);
    }

    /**
     * Set the name of this index.
     */
    public function setName($name)
    {
        $this->indexName = $name;
    }

    /**
     * Set the parent Table of the index
     */
    public function setTable(Table $parent)
    {
        $this->parentTable = $parent;
    }

    /**
     * Get the parent Table of the index
     */
    public function getTable()
    {
        return $this->parentTable;
    }

    /**
     * Returns the Name of the table the index is in
     */
    public function getTableName()
    {
        return $this->parentTable->getName();
    }

    /**
     * Adds a new column to an index.
     */
    public function addColumn($attrib)
    {
        $this->indexColumns[] = @$attrib["name"];
    }

    /**
     * Sets vendor specific parameter
     */
    public function setVendorParameter($name, $value)
    {
        $this->vendorSpecificInfo[$name] = $value;
    }

    /**
     * Sets vendor specific information to an index.
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
     * @see #getColumnList()
     * @deprecated Use getColumnList() instead.
     */
    public function getIndexColumnList()
    {
        return $this->getColumnList();
    }

    /**
     * Return a comma delimited string of the columns which compose this index.
     */
    public function getColumnList()
    {
        return Column::makeList($this->getColumns());
    }

    /**
     * @see #getColumns()
     * @deprecated Use getColumns() instead.
     */
    public function getIndexColumns()
    {
        return $this->getColumns();
    }

    /**
     * Return the list of local columns. You should not edit this list.
     */
    public function getColumns()
    {
        return $this->indexColumns;
    }

    /**
     * Returns the list of names of the columns referenced by this
     * index.  Slightly over-allocates the list's buffer (just in case
     * more elements are going to be added, such as when a name is
     * being generated).  Feel free to modify this list.
     */
    protected function getColumnNames()
    {
        $names = array();
        $cols = $this->getColumns();
        for($i=0,$size=count($cols); $i < $size; $i++) {
            $c = $cols[$i];
            $names[] = $c->getName();
        }
        return $names;
    }

    /**
     * String representation of the index. This is an xml representation.
     */
    public function toString()
    {

        $result = " <index name=\""
              . $this->getName()
              .'"';

        $result .= ">\n";

        for ($i=0, $size=count($this->indexColumns); $i < $size; $i++) {
            $result .= "  <index-column name=\""
                . $this->indexColumns[$i]
                . "\"/>\n";
        }
        $result .= " </index>\n";
        return $result;
    }
}
