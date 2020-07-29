<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * A class for holding a column default value.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class ColumnDefaultValue
{
    public const TYPE_VALUE = 'value';
    public const TYPE_EXPR = 'expr';

    /**
     * @var string|null The default value, as specified in the schema.
     */
    private $value;

    /**
     * @var string The type of value represented by this object (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR).
     */
    private $type = ColumnDefaultValue::TYPE_VALUE;

    /**
     * Creates a new DefaultValue object.
     *
     * @param string|null $value The default value, as specified in the schema.
     * @param string|null $type The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     */
    public function __construct($value, $type = null)
    {
        $this->setValue($value);

        if ($type !== null) {
            $this->setType($type);
        }
    }

    /**
     * @return string The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     *
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Convenience method to indicate whether the value in this object is an expression (as opposed to simple value).
     *
     * @return bool Whether value this object holds is an expression.
     */
    public function isExpression()
    {
        return $this->type === self::TYPE_EXPR;
    }

    /**
     * @return string|null The value, as specified in the schema.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|null $value The value, as specified in the schema.
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * A method to compare if two Default values match
     *
     * @author Niklas Närhinen <niklas@narhinen.net>
     *
     * @param \Propel\Generator\Model\ColumnDefaultValue $other The value to compare to
     *
     * @return bool Whether this object represents same default value as $other
     */
    public function equals(ColumnDefaultValue $other)
    {
        if ($this->getType() !== $other->getType()) {
            return false;
        }

        if ($this == $other) {
            return true;
        }

        // special case for current timestamp
        $equivalents = [ 'CURRENT_TIMESTAMP', 'NOW()' ];
        if (in_array(strtoupper($this->getValue()), $equivalents) && in_array(strtoupper($other->getValue()), $equivalents)) {
            return true;
        }

        return false; // Can't help, they are different
    }
}
