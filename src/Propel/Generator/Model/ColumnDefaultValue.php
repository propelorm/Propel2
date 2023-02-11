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
    /**
     * @var string
     */
    public const TYPE_VALUE = 'value';

    /**
     * @var string
     */
    public const TYPE_EXPR = 'expr';

    /**
     * @var string|int|null The default value, as specified in the schema.
     */
    private $value;

    /**
     * @var string The type of value represented by this object (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR).
     */
    private $type = self::TYPE_VALUE;

    /**
     * Creates a new DefaultValue object.
     *
     * @param string|null $value The default value, as specified in the schema.
     * @param string|null $type The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     */
    public function __construct(?string $value, ?string $type = null)
    {
        $this->setValue($value);

        if ($type !== null) {
            $this->setType($type);
        }
    }

    /**
     * @return string The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Convenience method to indicate whether the value in this object is an expression (as opposed to simple value).
     *
     * @return bool Whether value this object holds is an expression.
     */
    public function isExpression(): bool
    {
        return $this->type === self::TYPE_EXPR;
    }

    /**
     * @return string|int|null The value, as specified in the schema.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|int|null $value The value, as specified in the schema.
     *
     * @return void
     */
    public function setValue($value): void
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
    public function equals(ColumnDefaultValue $other): bool
    {
        if ($this->getType() !== $other->getType()) {
            return false;
        }

        if ($this == $other) {
            return true;
        }

        // special case for current timestamp
        $equivalents = ['CURRENT_TIMESTAMP', 'NOW()'];
        $value = strtoupper((string)$this->getValue());
        $otherValue = strtoupper((string)$other->getValue());

        return in_array($value, $equivalents, true) && in_array($otherValue, $equivalents, true);
    }
}
