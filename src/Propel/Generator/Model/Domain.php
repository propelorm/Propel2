<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use DOMDocument;
use DOMNode;
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
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var int|null
     */
    private $size;

    /**
     * @var int|null
     */
    private $scale;

    /**
     * @var string|null
     */
    private $mappingType;

    /**
     * @var string|null
     */
    private $sqlType;

    /**
     * @var \Propel\Generator\Model\ColumnDefaultValue|null
     */
    private $defaultValue;

    /**
     * @var \Propel\Generator\Model\Database|null
     */
    private $database;

    /**
     * Creates a new Domain object.
     *
     * If this domain needs a name, it must be specified manually.
     *
     * @param string|null $type Propel type.
     * @param string|null $sqlType SQL type.
     * @param int|null $size
     * @param int|null $scale
     */
    public function __construct($type = null, $sqlType = null, $size = null, $scale = null)
    {
        if ($type !== null) {
            $this->setType($type);
        }

        if ($size !== null) {
            $this->setSize($size);
        }

        if ($scale !== null) {
            $this->setScale($scale);
        }

        $this->setSqlType($sqlType !== null ? $sqlType : $type);
    }

    /**
     * Copies the values from current object into passed-in Domain.
     *
     * @param \Propel\Generator\Model\Domain $domain Domain to copy values into.
     *
     * @return void
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

    /**
     * @return void
     */
    protected function setupObject()
    {
        $schemaType = strtoupper($this->getAttribute('type'));
        $this->copy($this->database->getPlatform()->getDomainForType($schemaType));

        // Name
        $this->name = $this->getAttribute('name');

        // Default value
        $defval = $this->getAttribute('defaultValue', $this->getAttribute('default'));
        if ($defval !== null) {
            $this->setDefaultValue(new ColumnDefaultValue($defval, ColumnDefaultValue::TYPE_VALUE));
        } elseif ($this->getAttribute('defaultExpr') !== null) {
            $this->setDefaultValue(new ColumnDefaultValue($this->getAttribute('defaultExpr'), ColumnDefaultValue::TYPE_EXPR));
        }

        $this->size = $this->getAttribute('size') ? (int)$this->getAttribute('size') : null;
        $this->scale = $this->getAttribute('scale') ? (int)$this->getAttribute('scale') : null;
        $this->description = $this->getAttribute('description');
    }

    /**
     * Sets the owning database object (if this domain is being setup via XML).
     *
     * @param \Propel\Generator\Model\Database $database
     *
     * @return void
     */
    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Returns the owning database object (if this domain was setup via XML).
     *
     * @return \Propel\Generator\Model\Database
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
     *
     * @return void
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
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the scale value.
     *
     * @return int|null
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Sets the scale value.
     *
     * @param int $scale
     *
     * @return void
     */
    public function setScale($scale)
    {
        $this->scale = $scale === null ? null : (int)$scale;
    }

    /**
     * Replaces the size if the new value is not null.
     *
     * @param int|null $scale
     *
     * @return void
     */
    public function replaceScale($scale)
    {
        if ($scale !== null) {
            $this->scale = (int)$scale;
        }
    }

    /**
     * Returns the size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the size.
     *
     * @param int|null $size
     *
     * @return void
     */
    public function setSize($size)
    {
        $this->size = $size === null ? null : (int)$size;
    }

    /**
     * Replaces the size if the new value is not null.
     *
     * @param int|null $size
     *
     * @return void
     */
    public function replaceSize($size)
    {
        if ($size !== null) {
            $this->size = (int)$size;
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
     *
     * @return void
     */
    public function setType($mappingType)
    {
        $this->mappingType = $mappingType;
    }

    /**
     * Replaces the mapping type if the new value is not null.
     *
     * @param string|null $mappingType
     *
     * @return void
     */
    public function replaceType($mappingType)
    {
        if ($mappingType !== null) {
            $this->mappingType = $mappingType;
        }
    }

    /**
     * Returns the default value object.
     *
     * @return \Propel\Generator\Model\ColumnDefaultValue|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Returns the default value, type-casted for use in PHP OM.
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return string|array|bool|null
     */
    public function getPhpDefaultValue()
    {
        if ($this->defaultValue === null) {
            return null;
        }

        if ($this->defaultValue->isExpression()) {
            throw new EngineException('Cannot get PHP version of default value for default value EXPRESSION.');
        }

        if (in_array($this->mappingType, [ PropelTypes::BOOLEAN, PropelTypes::BOOLEAN_EMU ])) {
            return $this->booleanValue($this->defaultValue->getValue());
        }

        if ($this->mappingType === PropelTypes::PHP_ARRAY) {
            return $this->getDefaultValueForArray($this->defaultValue->getValue());
        }
        if ($this->mappingType === PropelTypes::SET) {
            return $this->getDefaultValueForSet($this->defaultValue->getValue());
        }

        return $this->defaultValue->getValue();
    }

    /**
     * Sets the default value.
     *
     * @param \Propel\Generator\Model\ColumnDefaultValue $value
     *
     * @return void
     */
    public function setDefaultValue(ColumnDefaultValue $value)
    {
        $this->defaultValue = $value;
    }

    /**
     * Replaces the default value if the new value is not null.
     *
     * @param \Propel\Generator\Model\ColumnDefaultValue|null $value
     *
     * @return void
     */
    public function replaceDefaultValue(?ColumnDefaultValue $value = null)
    {
        if ($value !== null) {
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
     *
     * @return void
     */
    public function setSqlType($sqlType)
    {
        $this->sqlType = $sqlType;
    }

    /**
     * Replaces the SQL type if the new value is not null.
     *
     * @param string|null $sqlType
     *
     * @return void
     */
    public function replaceSqlType($sqlType)
    {
        if ($sqlType !== null) {
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
        if ($this->size === null) {
            return '';
        }

        if ($this->scale !== null) {
            return sprintf('(%u,%u)', $this->size, $this->scale);
        }

        return sprintf('(%u)', $this->size);
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if ($this->defaultValue) {
            $this->defaultValue = clone $this->defaultValue;
        }
    }

    /**
     * @todo Remove? This method is never called.
     *
     * @param \DOMNode $node
     *
     * @return void
     */
    public function appendXml(DOMNode $node)
    {
        $doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

        /** @var \DOMElement $domainNode */
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
            $domainNode->setAttribute('size', (string)$this->size);
        }

        if ($this->scale) {
            $domainNode->setAttribute('scale', (string)$this->scale);
        }

        if ($this->description) {
            $domainNode->setAttribute('description', $this->description);
        }
    }
}
