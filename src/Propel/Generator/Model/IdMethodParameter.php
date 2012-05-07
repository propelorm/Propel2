<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Model;

/**
 * Information related to an ID method.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John McNally <jmcnally@collab.net> (Torque)
 * @author     Daniel Rall <dlr@collab.net> (Torque)
 */
class IdMethodParameter extends XmlElement
{
    private $name;

    private $value;

    private $parentTable;

    /**
     * Sets up the IdMethodParameter object based on the attributes that were passed to loadFromXML().
     * @see        parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->name = $this->getAttribute('name');
        $this->value = $this->getAttribute('value');
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
     * @see        XmlElement::appendXml(DOMNode)
     */
    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $paramNode = $node->appendChild($doc->createElement('id-method-parameter'));
        if ($this->getName()) {
            $paramNode->setAttribute('name', $this->getName());
        }
        $paramNode->setAttribute('value', $this->getValue());
    }
}