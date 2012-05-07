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
 * A Class for information regarding possible objects representing a table
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John McNally <jmcnally@collab.net> (Torque)
 */
class Inheritance extends XmlElement
{
    private $key;
    private $className;
    private $pkg;
    private $ancestor;
    private $parent;

    /**
     * Sets up the Inheritance object based on the attributes that were passed to loadFromXML().
     * @see        parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->key = $this->getAttribute('key');
        $this->className = $this->getAttribute('class');
        $this->pkg = $this->getAttribute('package');
        $this->ancestor = $this->getAttribute('extends');
    }

    /**
     * Get the value of key.
     * @return     value of key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value of key.
     * @param      v  Value to assign to key.
     */
    public function setKey($v)
    {
        $this->key = $v;
    }

    /**
     * Get the value of parent.
     * @return     value of parent.
     */
    public function getColumn()
    {
        return $this->parent;
    }

    /**
     * Set the value of parent.
     * @param      v  Value to assign to parent.
     */
    public function setColumn(Column  $v)
    {
        $this->parent = $v;
    }

    /**
     * Get the value of className.
     * @return     value of className.
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set the value of className.
     * @param      v  Value to assign to className.
     */
    public function setClassName($v)
    {
        $this->className = $v;
    }

    /**
     * Get the value of package.
     * @return     value of package.
     */
    public function getPackage()
    {
        return $this->pkg;
    }

    /**
     * Set the value of package.
     * @param      v  Value to assign to package.
     */
    public function setPackage($v)
    {
        $this->pkg = $v;
    }

    /**
     * Get the value of ancestor.
     * @return     value of ancestor.
     */
    public function getAncestor()
    {
        return $this->ancestor;
    }

    /**
     * Set the value of ancestor.
     * @param      v  Value to assign to ancestor.
     */
    public function setAncestor($v)
    {
        $this->ancestor = $v;
    }

    /**
     * @see        XmlElement::appendXml(DOMNode)
     */
    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $inherNode = $node->appendChild($doc->createElement('inheritance'));
        $inherNode->setAttribute('key', $this->key);
        $inherNode->setAttribute('class', $this->className);

        if (null !== $this->ancestor) {
            $inherNode->setAttribute('extends', $this->ancestor);
        }
    }
}