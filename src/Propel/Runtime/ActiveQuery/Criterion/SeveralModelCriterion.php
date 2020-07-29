<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException;

/**
 * Specialized ModelCriterion used for ternary model clause, e.G 'book.ID BETWEEN ? AND ?'
 */
class SeveralModelCriterion extends AbstractModelCriterion
{
    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @throws \Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        if (!is_array($this->value)) {
            throw new InvalidValueException('Only array values are supported by this Criterion');
        }
        $clause = $this->clause;
        foreach ($this->value as $value) {
            if ($value === null) {
                // FIXME we eventually need to translate a BETWEEN to
                // something like WHERE (col < :p1 OR :p1 IS NULL) AND (col < :p2 OR :p2 IS NULL)
                // in order to support null values
                throw new InvalidValueException('Null values are not supported inside BETWEEN clauses');
            }
            $params[] = [
                'table' => $this->realtable,
                'column' => $this->column,
                'value' => $value,
            ];
            $clause = self::strReplaceOnce('?', ':p' . count($params), $clause);
        }
        $sb .= $clause;
    }

    /**
     * Replace only once
     * taken from http://www.php.net/manual/en/function.str-replace.php
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    protected static function strReplaceOnce($search, $replace, $subject)
    {
        $firstChar = strpos($subject, $search);
        if ($firstChar !== false) {
            $beforeStr = substr($subject, 0, $firstChar);
            $afterStr = substr($subject, $firstChar + strlen($search));

            return $beforeStr . $replace . $afterStr;
        }

        return $subject;
    }
}
