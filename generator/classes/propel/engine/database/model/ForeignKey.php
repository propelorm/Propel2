<?php
/*
 *  $Id: ForeignKey.php,v 1.4 2005/03/16 03:57:54 hlellelid Exp $
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

/**
 * A Class for information about foreign keys of a table.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Fedor <fedor.karpelevitch@home.com>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @version $Revision: 1.4 $
 * @package propel.engine.database.model
 */
class ForeignKey extends XMLElement {

    private $foreignTableName;
    private $name;
    private $onUpdate;
    private $onDelete;
    private $parentTable;
    private $localColumns = array();
    private $foreignColumns = array();
    private $vendorSpecificInfo = array();

    // the uppercase equivalent of the onDelete/onUpdate values in the dtd
    const NONE     = "";            // No "ON [ DELETE | UPDATE]" behaviour specified.
    const CASCADE  = "NO ACTION";
    const CASCADE  = "CASCADE";
    const RESTRICT = "RESTRICT";
    const SETNULL  = "SET DEFAULT";
    const SETNULL  = "SET NULL";

    /**
     * Sets up the ForeignKey object based on the attributes that were passed to loadFromXML().
	 * @see parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->foreignTableName = $this->getAttribute("foreignTable");
        $this->name = $this->getAttribute("name");
        $this->onUpdate = $this->normalizeFKey($this->getAttribute("onUpdate"));
        $this->onDelete = $this->normalizeFKey($this->getAttribute("onDelete"));
    }

    /**
     * normalizes the input of onDelete, onUpdate attributes
     */
    private function normalizeFKey($attrib)
    {
        if ($attrib === null) {
            $attrib = self::NONE;
        }
        $attrib = strtoupper($attrib);
        if ($attrib == "SETNULL") {
            $attrib =  self::SETNULL;
        }
        return $attrib;
    }

    /**
     * returns whether or not the onUpdate attribute is set
     */
    public function hasOnUpdate()
    {
       return ($this->onUpdate !== self::NONE);
    }

    /**
     * returns whether or not the onDelete attribute is set
     */
    public function hasOnDelete()
    {
       return ($this->onDelete !== self::NONE);
    }

    /**
     * returns the onUpdate attribute
     */
    public function getOnUpdate()
    {
       return $this->onUpdate;
    }

    /**
     * returns the onDelete attribute
     */
    public function getOnDelete()
    {
       return $this->onDelete;
    }

    /**
     * sets the onDelete attribute
     */
    public function setOnDelete($value)
    {
       $this->onDelete = $this->normalizeFKey($value);
    }

    /**
     * sets the onUpdate attribute
     */
    public function setOnUpdate($value)
    {
       $this->onUpdate = $this->normalizeFKey($value);
    }

    /**
     * Returns the name attribute.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name attribute.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the foreignTableName of the FK
     */
    public function getForeignTableName()
    {
        return $this->foreignTableName;
    }

    /**
     * Set the foreignTableName of the FK
     */
    public function setForeignTableName($tableName)
    {
        $this->foreignTableName = $tableName;
    }

    /**
     * Set the parent Table of the foreign key
     */
    public function setTable(Table $parent)
    {
        $this->parentTable = $parent;
    }

    /**
     * Get the parent Table of the foreign key
     */
    public function getTable()
    {
        return $this->parentTable;
    }

    /**
     * Returns the Name of the table the foreign key is in
     */
    public function getTableName()
    {
        return $this->parentTable->getName();
    }

    /**
     * adds a new reference entry to the foreign key
     */
    public function addReference($p1, $p2 = null)
    {
        if (is_array($p1)) {
            $this->addReference(@$p1["local"], @$p1["foreign"]);
        } else {
            $this->localColumns[] = $p1;
            $this->foreignColumns[] = $p2;
        }
    }

    /**
     * Return a comma delimited string of local column names
     */
    public function getLocalColumnNames()
    {
        return Column::makeList($this->getLocalColumns());
    }

    /**
     * Return a comma delimited string of foreign column names
     */
    public function getForeignColumnNames()
    {
        return Column::makeList($this->getForeignColumns());
    }

    /**
     * Return the list of local columns. You should not edit this List.
     */
    public function getLocalColumns()
    {
        return $this->localColumns;
    }

    /**
     * Utility method to get local column to foreign column
     * mapping for this foreign key.
     */
    public function getLocalForeignMapping()
    {
        $h = array();
        for ($i=0, $size=count($this->localColumns); $i < $size; $i++) {
            $h[$this->localColumns[$i]] = $this->foreignColumns[$i];
        }
        return $h;
    }

    /**
     * Return the list of local columns. You should not edit this List.
     */
    public function getForeignColumns()
    {
        return $this->foreignColumns;
    }

    /**
     * Utility method to get local column to foreign column
     * mapping for this foreign key.
     */
    public function getForeignLocalMapping()
    {
        $h = array();
        for ($i=0, $size=count($this->localColumns); $i < $size; $i++) {
            $h[ $this->foreignColumns[$i] ] = $this->localColumns[$i];
        }
        return $h;
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
     * String representation of the foreign key. This is an xml representation.
     */
    public function toString()
    {
        $result = "    <foreign-key foreignTable=\""
            . $this->getForeignTableName()
            . "\" name=\""
            . $this->getName()
            . "\">\n";

        for ($i=0, $size=count($this->localColumns); $i < $size; $i++) {
            $result .= "        <reference local=\""
                . $this->localColumns[$i]
                . "\" foreign=\""
                . $this->foreignColumns[$i]
                . "\"/>\n";
        }
        $result .= "    </foreign-key>\n";
        return $result;
    }
}

