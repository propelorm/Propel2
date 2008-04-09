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
include_once 'propel/engine/EngineException.php';

/**
 * Information about indices of a table.
 *
 * @author     Jason van Zyl <vanzyl@apache.org>
 * @author     Daniel Rall <dlr@finemaltcoding.com>
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class Index extends XMLElement {

	/** enables debug output */
	const DEBUG = false;

	private $indexName;
	private $parentTable;

	/** @var        array string[] */
	private $indexColumns;

	/** @var        array  */
	private $indexColumnSizes = array();

	/**
	 * Creates a new Index instance.
	 *
	 * @param      string $name
	 */
	public function __construct($name=null)
	{
		$this->indexName = $name;
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
		if ($this->isUnique()) {
			$inputs[] = count($table->getUnices()) + 1;
		} else {
			$inputs[] = count($table->getIndices()) + 1;
		}

		$this->indexName = NameFactory::generateName(
		NameFactory::CONSTRAINT_GENERATOR, $inputs);
	}

	/**
	 * Sets up the Index object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$this->indexName = $this->getAttribute("name");
	}

	/**
	 * @see        #isUnique()
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
	 * @see        #getName()
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
		return substr($this->indexName, 0, $this->getTable()->getDatabase()->getPlatform()->getMaxColumnNameLength());
	}

	/**
	 * @see        #setName(String name)
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
	 * @param      mixed $data Column or attributes from XML.
	 */
	public function addColumn($data)
	{
		if ($data instanceof Column) {
			$column = $data;
			$this->indexColumns[] = $column->getName();
			if ($column->getSize()) {
				$this->indexColumnSizes[$column->getName()] = $column->getSize();
			}
		} else {
			$attrib = $data;
			$name = $attrib["name"];
			$this->indexColumns[] = $name;
			if (isset($attrib["size"])) {
				$this->indexColumnSizes[$name] = $attrib["size"];
			}
		}
	}

	/**
	 * Sets array of columns to use for index.
	 *
	 * @param      array $indexColumns Column[]
	 */
	public function setColumns(array $indexColumns)
	{
		$this->indexColumns = array();
		$this->indexColumnSizes = array();
		foreach ($indexColumns as $col) {
			$this->addColumn($col);
		}
	}

	/**
	 * Whether there is a size for the specified column.
	 * @param      string $name
	 * @return     boolean
	 */
	public function hasColumnSize($name)
	{
		return isset($this->indexColumnSizes[$name]);
	}

	/**
	 * Returns the size for the specified column, if given.
	 * @param      string $name
	 * @return     numeric The size or NULL
	 */
	public function getColumnSize($name)
	{
		if (isset($this->indexColumnSizes[$name])) {
			return $this->indexColumnSizes[$name];
		}
		return null; // just to be explicit
	}

	/**
	 * @see        #getColumnList()
	 * @deprecated Use getColumnList() instead (which is not deprecated too!)
	 */
	public function getIndexColumnList()
	{
		return $this->getColumnList();
	}

	/**
	 * Return a comma delimited string of the columns which compose this index.
	 * @deprecated because Column::makeList() is deprecated; use the array-returning getColumns() and DDLBuilder->getColumnList() instead instead.
	 */
	public function getColumnList()
	{
		return Column::makeList($this->getColumns(), $this->getTable()->getDatabase()->getPlatform());
	}

	/**
	 * @see        #getColumns()
	 * @deprecated Use getColumns() instead.
	 */
	public function getIndexColumns()
	{
		return $this->getColumns();
	}

	/**
	 * Return the list of local columns. You should not edit this list.
	 * @return     array string[]
	 */
	public function getColumns()
	{
		return $this->indexColumns;
	}

	/**
	 * @see        XMLElement::appendXml(DOMNode)
	 */
	public function appendXml(DOMNode $node)
	{
		$doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

		$idxNode = $node->appendChild($doc->createElement('index'));
		$idxNode->setAttribute('name', $this->getName());

		foreach ($this->indexColumns as $colname) {
			$idxColNode = $idxNode->appendChild($doc->createElement('index-column'));
			$idxColNode->setAttribute('name', $colname);
		}

		foreach ($this->vendorInfos as $vi) {
			$vi->appendXml($idxNode);
		}
	}
}
