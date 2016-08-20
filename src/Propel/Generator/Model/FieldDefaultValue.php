<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * A class for holding a column default value.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class FieldDefaultValue
{
    const TYPE_VALUE = 'value';
    const TYPE_EXPR  = 'expr';

    /**
     * @var string The default value, as specified in the schema.
     */
    private $value;

    /**
     * @var string The type of value represented by this object (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR).
     */
    private $type = FieldDefaultValue::TYPE_VALUE;

    /**
     * Creates a new DefaultValue object.
     *
     * @param string $value The default value, as specified in the schema.
     * @param string $type  The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     */
    public function __construct($value, $type = null)
    {
        $this->setValue($value);

        if (null !== $type) {
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
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Convenience method to indicate whether the value in this object is an expression (as opposed to simple value).
     *
     * @return boolean Whether value this object holds is an expression.
     */
    public function isExpression()
    {
        return self::TYPE_EXPR === $this->type;
    }

    /**
     * @return string The value, as specified in the schema.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value The value, as specified in the schema.
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * A method to compare if two Default values match
     *
     * @param  FieldDefaultValue $other The value to compare to
     * @return boolean            Whether this object represents same default value as $other
     * @author     Niklas Närhinen <niklas@narhinen.net>
     */
    public function equals(FieldDefaultValue $other)
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

    /**
     * 
     */
    public function getTypedValue()
    {

    }
}
