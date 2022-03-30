<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

/**
 * Class for iterating over a list of Propel objects
 *
 * @author Francois Zaninotto
 */
class ObjectCombinationCollection extends ObjectCollection
{
    /**
     * Get an array of the primary keys of all the objects in the collection
     *
     * @param bool $usePrefix
     *
     * @return array The list of the primary keys of the collection
     */
    public function getPrimaryKeys(bool $usePrefix = true): array
    {
        $ret = [];

        foreach ($this as $combination) {
            $pkCombo = [];
            /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $obj */
            foreach ($combination as $key => $obj) {
                $pkCombo[$key] = $obj->getPrimaryKey();
            }
            $ret[] = $pkCombo;
        }

        return $ret;
    }

    /**
     * @inheritDoc
     */
    public function push($value): void
    {
        parent::push(func_get_args());
    }

    /**
     * Returns all values from one position/column.
     *
     * @param int $position beginning with 1
     *
     * @return array
     */
    public function getObjectsFromPosition(int $position = 1): array
    {
        $result = [];
        foreach ($this as $array) {
            $result[] = $array[$position - 1];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function search($element)
    {
        $hashes = [];
        $isActiveRecord = [];
        foreach (func_get_args() as $pos => $obj) {
            if ($obj instanceof ActiveRecordInterface) {
                $hashes[$pos] = $obj->hashCode();
                $isActiveRecord[$pos] = true;
            } else {
                $hashes[$pos] = $obj;
                $isActiveRecord[$pos] = false;
            }
        }
        foreach ($this as $pos => $combination) {
            $found = true;
            foreach ($combination as $idx => $obj) {
                if ($obj === null) {
                    if ($obj !== $hashes[$idx]) {
                        $found = false;

                        break;
                    }
                } elseif ($isActiveRecord[$idx] ? $obj->hashCode() !== $hashes[$idx] : $obj !== $hashes[$idx]) {
                    $found = false;

                    break;
                }
            }
            if ($found) {
                return $pos;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function removeObject($element): void
    {
        $pos = $this->search(...func_get_args());
        if ($pos !== false) {
            $this->remove($pos);
        }
    }

    /**
     * @inheritDoc
     */
    public function contains($element): bool
    {
        return $this->search(...func_get_args()) !== false;
    }
}
