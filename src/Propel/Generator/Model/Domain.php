<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;

/**
 * A class for holding data about a domain used in the schema.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Domain extends MappingModel
{
    private $name;
    private $description;
    private $size;
    private $scale;
    private $mappingType;
    private $sqlType;
    private $defaultValue;
    private $database;

    /**
     * Creates a new Domain object.
     *
     * If this domain needs a name, it must be specified manually.
     *
     * @param string  $type    Propel type.
     * @param string  $sqlType SQL type.
     * @param integer $size
     * @param integer $scale
     */
    public function __construct($type = null, $sqlType = null, $size = null, $scale = null)
    {
        parent::__construct();

        if (null !== $type) {
            $this->setType($type);
        }

        if (null !== $size) {
            $this->setSize($size);
        }

        if (null !== $scale) {
            $this->setScale($scale);
        }

        $this->setSqlType(null !== $sqlType ? $sqlType : $type);
    }

    /**
     * Copies the values from current object into passed-in Domain.
     *
     * @param Domain $domain Domain to copy values into.
     */
    public function copy(Domain $domain)
    {
        $this->defaultValue = $domain->getDefaultValue();
        $this->description = $domain->getDescription();
        $this->name = $domain->getName();
        $this->scale = $domain->getScale();
        $this->size = $domain->getSize();
        $this->sqlType = $domain->getSqlType();
        $this->mappingType = $domain->getType();
    }

    protected function setupObject()
    {
        $schemaType = strtoupper($this->getAttribute('type'));
        $this->copy($this->database->getPlatform()->getDomainForType($schemaType));

        // Name
        $this->name = $this->getAttribute('name');

        // Default value
        $defval = $this->getAttribute('defaultValue', $this->getAttribute('default'));
        if (null !== $defval) {
            $this->setDefaultValue(new ColumnDefaultValue($defval, ColumnDefaultValue::TYPE_VALUE));
        } elseif (null !== $this->getAttribute('defaultExpr')) {
            $this->setDefaultValue(new ColumnDefaultValue($this->getAttribute('defaultExpr'), ColumnDefaultValue::TYPE_EXPR));
        }

        $this->size = $this->getAttribute('size');
        $this->scale = $this->getAttribute('scale');
        $this->description = $this->getAttribute('description');
    }

    /**
     * Sets the owning database object (if this domain is being setup via XML).
     *
     * @param Database $database
     */
    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Returns the owning database object (if this domain was setup via XML).
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Returns the domain description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the domain description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the domain description.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the domain name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the scale value.
     *
     * @return integer
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Sets the scale value.
     *
     * @param integer $scale
     */
    public function setScale($scale)
    {
        $this->scale = null === $scale ? null : (int) $scale;
    }

    /**
     * Replaces the size if the new value is not null.
     *
     * @param integer $scale
     */
    public function replaceScale($scale)
    {
        if (null !== $scale) {
            $this->scale = (int) $scale;
        }
    }

    /**
     * Returns the size.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the size.
     *
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->size = null === $size ? null : (int) $size;
    }

    /**
     * Replaces the size if the new value is not null.
     *
     * @param integer $size
     */
    public function replaceSize($size)
    {
        if (null !== $size) {
            $this->size = $size;
        }
    }

    /**
     * Returns the mapping type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->mappingType;
    }

    /**
     * Sets the mapping type.
     *
     * @param string $mappingType
     */
    public function setType($mappingType)
    {
        $this->mappingType = $mappingType;
    }

    /**
     * Replaces the mapping type if the new value is not null.
     *
     * @param string $mappingType
     */
    public function replaceType($mappingType)
    {
        if (null !== $mappingType) {
            $this->mappingType = $mappingType;
        }
    }

    /**
     * Returns the default value object.
     *
     * @return ColumnDefaultValue
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Returns the default value, type-casted for use in PHP OM.
     *
     * @return mixed
     */
    public function getPhpDefaultValue()
    {
        if (null === $this->defaultValue) {
            return null;
        }

        if ($this->defaultValue->isExpression()) {
            throw new EngineException('Cannot get PHP version of default value for default value EXPRESSION.');
        }

        if (in_array($this->mappingType, [ PropelTypes::BOOLEAN, PropelTypes::BOOLEAN_EMU ])) {
            return $this->booleanValue($this->defaultValue->getValue());
        }

        if (PropelTypes::PHP_ARRAY === $this->mappingType) {
            return $this->getDefaultValueForArray($this->defaultValue->getValue());
        }

        return $this->defaultValue->getValue();
    }

    /**
     * Sets the default value.
     *
     * @param ColumnDefaultValue $value
     */
    public function setDefaultValue(ColumnDefaultValue $value)
    {
        $this->defaultValue = $value;
    }

    /**
     * Replaces the default value if the new value is not null.
     *
     * @param ColumnDefaultValue $value
     */
    public function replaceDefaultValue(ColumnDefaultValue $value = null)
    {
        if (null !== $value) {
            $this->defaultValue = $value;
        }
    }

    /**
     * Returns the SQL type.
     *
     * @return string
     */
    public function getSqlType()
    {
        return $this->sqlType;
    }

    /**
     * Sets the SQL type.
     *
     * @param string $sqlType
     */
    public function setSqlType($sqlType)
    {
        $this->sqlType = $sqlType;
    }

    /**
     * Replaces the SQL type if the new value is not null.
     *
     * @param string $sqlType
     */
    public function replaceSqlType($sqlType)
    {
        if (null !== $sqlType) {
            $this->sqlType = $sqlType;
        }
    }

    /**
     * Returns the size and scale in brackets for use in an sql schema.
     *
     * @return string
     */
    public function getSizeDefinition()
    {
        if (null === $this->size) {
            return '';
        }

        if (null !== $this->scale) {
            return sprintf('(%u,%u)', $this->size, $this->scale);
        }

        return sprintf('(%u)', $this->size);
    }

    public function __clone()
    {
        if ($this->defaultValue) {
            $this->defaultValue = clone $this->defaultValue;
        }
    }

    /**
     * @todo Remove? This method is never called.
     * @param \DOMNode $node
     */
    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $domainNode = $node->appendChild($doc->createElement('domain'));
        $domainNode->setAttribute('type', $this->getType());
        $domainNode->setAttribute('name', $this->getName());

        if ($this->getType() !== $this->sqlType) {
            $domainNode->setAttribute('sqlType', $this->sqlType);
        }

        if ($def = $this->getDefaultValue()) {
            if ($def->isExpression()) {
                $domainNode->setAttribute('defaultExpr', $def->getValue());
            } else {
                $domainNode->setAttribute('defaultValue', $def->getValue());
            }
        }

        if ($this->size) {
            $domainNode->setAttribute('size', $this->size);
        }

        if ($this->scale) {
            $domainNode->setAttribute('scale', $this->scale);
        }

        if ($this->description) {
            $domainNode->setAttribute('description', $this->description);
        }
    }
}
