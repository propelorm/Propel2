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

require_once 'propel/engine/database/model/XMLElement.php';

/**
 * A Class for information about foreign keys of a table.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @author     Fedor <fedor.karpelevitch@home.com>
 * @author     Daniel Rall <dlr@finemaltcoding.com>
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class ForeignKey extends XMLElement {

	private $foreignTableName;
	private $name;
	private $phpName;
	private $refPhpName;
	private $onUpdate;
	private $onDelete;
	private $parentTable;
	private $localColumns = array();
	private $foreignColumns = array();

	// the uppercase equivalent of the onDelete/onUpdate values in the dtd
	const NONE     = "";            // No "ON [ DELETE | UPDATE]" behaviour specified.
	const NOACTION  = "NO ACTION";
	const CASCADE  = "CASCADE";
	const RESTRICT = "RESTRICT";
	const SETDEFAULT  = "SET DEFAULT";
	const SETNULL  = "SET NULL";

	/**
	 * Constructs a new ForeignKey object.
	 *
	 * @param      string $name
	 */
	public function __construct($name=null)
	{
		$this->name = $name;
	}

	/**
	 * Sets up the ForeignKey object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$this->foreignTableName = $this->getAttribute("foreignTable");
		$this->name = $this->getAttribute("name");
		$this->phpName = $this->getAttribute("phpName");
		$this->refPhpName = $this->getAttribute("refPhpName");
		$this->onUpdate = $this->normalizeFKey($this->getAttribute("onUpdate"));
		$this->onDelete = $this->normalizeFKey($this->getAttribute("onDelete"));
	}

	/**
	 * normalizes the input of onDelete, onUpdate attributes
	 */
	private function normalizeFKey($attrib)
	{
		if ($attrib === null  || strtoupper($attrib) == "NONE") {
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
	 * @return     string
	 */
	public function getOnUpdate()
	{
		return $this->onUpdate;
	}

	/**
	 * Returns the onDelete attribute
	 * @return     string
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
	 * Gets the phpName for this foreign key (if any).
	 * @return     string
	 */
	public function getPhpName()
	{
		return $this->phpName;
	}

	/**
	 * Sets a phpName to use for this foreign key.
	 * @param      string $name
	 */
	public function setPhpName($name)
	{
		$this->phpName = $name;
	}

	/**
	 * Gets the refPhpName for this foreign key (if any).
	 * @return     string
	 */
	public function getRefPhpName()
	{
		return $this->refPhpName;
	}

	/**
	 * Sets a refPhpName to use for this foreign key.
	 * @param      string $name
	 */
	public function setRefPhpName($name)
	{
		$this->refPhpName = $name;
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
	 * Gets the resolved foreign Table model object.
	 * @return     Table
	 */
	public function getForeignTable()
	{
		return $this->getTable()->getDatabase()->getTable($this->getForeignTableName());
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
	 * Adds a new reference entry to the foreign key.
	 */
	public function addReference($p1, $p2 = null)
	{
		if (is_array($p1)) {
			$this->addReference(@$p1["local"], @$p1["foreign"]);
		} else {
			if ($p1 instanceof Column) {
				$p1 = $p1->getName();
			}
			if ($p2 instanceof Column) {
				$p2 = $p2->getName();
			}
			$this->localColumns[] = $p1;
			$this->foreignColumns[] = $p2;
		}
	}

	/**
	 * Return a comma delimited string of local column names
	 * @deprecated because Column::makeList() is deprecated; use the array-returning getLocalColumns() and DDLBuilder->getColumnList() instead instead.
	 */
	public function getLocalColumnNames()
	{
		return Column::makeList($this->getLocalColumns(), $this->getTable()->getDatabase()->getPlatform());
	}

	/**
	 * Return a comma delimited string of foreign column names
	 * @deprecated because Column::makeList() is deprecated; use the array-returning getForeignColumns() and DDLBuilder->getColumnList() instead instead.
	 */
	public function getForeignColumnNames()
	{
		return Column::makeList($this->getForeignColumns(), $this->getTable()->getDatabase()->getPlatform());
	}

	/**
	 * Return an array of local column names.
	 * @return     array string[]
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
	 * Get the foreign column mapped to specified local column.
	 * @return     string Column name.
	 */
	public function getMappedForeignColumn($local)
	{
		$m = $this->getLocalForeignMapping();
		if (isset($m[$local])) {
			return $m[$local];
		}
		return null;
	}

	/**
	 * Get the local column mapped to specified foreign column.
	 * @return     string Column name.
	 */
	public function getMappedLocalColumn($foreign)
	{
		$m = $this->getForeignLocalMapping();
		if (isset($m[$foreign])) {
			return $m[$foreign];
		}
		return null;
	}

	/**
	 * Return an array of foreign column objects.
	 * @return     array Column[]
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
	 * Whether this foreign key is also the primary key of the local table.
	 *
	 * @return     boolean
	 */
	public function isLocalPrimaryKey()
	{
		$localCols = $this->getLocalColumns();

		$localPKColumnObjs = $this->getTable()->getPrimaryKey();

		$localPKCols = array();
		foreach ($localPKColumnObjs as $lPKCol) {
			$localPKCols[] = $lPKCol->getName();
		}
		//
		//		print "Local key columns: \n";
		//		print_r($localCols);
		//
		//		print "Local table primary key columns: \n";
		//		print_r($localPKCols);

		return (!array_diff($localPKCols, $localCols));
	}

	/**
	 * Whether this foreign key is matched by an invertes foreign key (on foreign table).
	 *
	 * This is to prevent duplicate columns being generated for a 1:1 relationship that is represented
	 * by foreign keys on both tables.  I don't know if that's good practice ... but hell, why not
	 * support it.
	 *
	 * @param      ForeignKey $fk
	 * @return     boolean
	 * @link       http://propel.phpdb.org/trac/ticket/549
	 */
	public function isMatchedByInverseFK()
	{
		$foreignTable = $this->getForeignTable();
		$map = $this->getForeignLocalMapping();

		foreach ($foreignTable->getForeignKeys() as $refFK) {
			$fkMap = $refFK->getLocalForeignMapping();
			if ( ($refFK->getTableName() == $this->getTableName()) && ($map == $fkMap) ) { // compares keys and values, but doesn't care about order, included check to make sure it's the same table (fixes #679)
				return true;
			}
		}

		return false;
	}

	/**
	 * @see        XMLElement::appendXml(DOMNode)
	 */
	public function appendXml(DOMNode $node)
	{
		$doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

		$fkNode = $node->appendChild($doc->createElement('foreign-key'));

		$fkNode->setAttribute('foreignTable', $this->getForeignTableName());
		$fkNode->setAttribute('name', $this->getName());

		if ($this->getPhpName()) {
			$fkNode->setAttribute('phpName', $this->getPhpName());
		}

		if ($this->getRefPhpName()) {
			$fkNode->setAttribute('refPhpName', $this->getRefPhpName());
		}

		if ($this->getOnDelete()) {
			$fkNode->setAttribute('onDelete', $this->getOnDelete());
		}

		if ($this->getOnUpdate()) {
			$fkNode->setAttribute('onUpdate', $this->getOnUpdate());
		}

		for ($i=0, $size=count($this->localColumns); $i < $size; $i++) {
			$refNode = $fkNode->appendChild($doc->createElement('reference'));
			$refNode->setAttribute('local', $this->localColumns[$i]);
			$refNode->setAttribute('foreign', $this->foreignColumns[$i]);
		}

		foreach ($this->vendorInfos as $vi) {
			$vi->appendXml($fkNode);
		}
	}
}
