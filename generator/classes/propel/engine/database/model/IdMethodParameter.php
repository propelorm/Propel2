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
 * Information related to an ID method.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John McNally <jmcnally@collab.net> (Torque)
 * @author     Daniel Rall <dlr@collab.net> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class IdMethodParameter extends XMLElement {

	private $name;
	private $value;
	private $parentTable;

   /**
	 * Sets up the IdMethodParameter object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$this->name = $this->getAttribute("name");
		$this->value = $this->getAttribute("value");
	}

	/**
	 * Get the parameter name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the parameter name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get the parameter value
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Set the parameter value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Set the parent Table of the id method
	 */
	public function setTable(Table $parent)
	{
		$this->parentTable = $parent;
	}

	/**
	 * Get the parent Table of the id method
	 */
	public function getTable()
	{
		return $this->parentTable;
	}

	/**
	 * Returns the Name of the table the id method is in
	 */
	public function getTableName()
	{
		return $this->parentTable->getName();
	}

	/**
	 * @see        XMLElement::appendXml(DOMNode)
	 */
	public function appendXml(DOMNode $node)
	{
		$doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

		$paramNode = $node->appendChild($doc->createElement('id-method-parameter'));
		if ($this->getName()) {
			$paramNode->setAttribute('name', $this->getName());
		}
		$paramNode->setAttribute('value', $this->getValue());
	}
}
