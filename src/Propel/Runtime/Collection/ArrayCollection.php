<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Class for iterating over a list of Propel objects stored as arrays
 *
 * @author Francois Zaninotto
 */
class ArrayCollection extends Collection
{
    /**
     * @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    protected $workerObject;

    /**
     * Save all the elements in the collection
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function save(?ConnectionInterface $con = null): void
    {
        if (!method_exists($this->getFullyQualifiedModel(), 'save')) {
            throw new ReadOnlyModelException('Cannot save objects on a read-only model');
        }
        if ($con === null) {
            $con = $this->getWriteConnection();
        }
        $con->transaction(function () use ($con): void {
            $obj = $this->getWorkerObject();
            foreach ($this as $element) {
                $obj->clear();
                $obj->fromArray($element);
                $obj->setNew($obj->isPrimaryKeyNull());
                $obj->save($con);
            }
        });
    }

    /**
     * Delete all the elements in the collection
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function delete(?ConnectionInterface $con = null): void
    {
        if (!method_exists($this->getFullyQualifiedModel(), 'delete')) {
            throw new ReadOnlyModelException('Cannot delete objects on a read-only model');
        }
        if ($con === null) {
            $con = $this->getWriteConnection();
        }
        $con->transaction(function () use ($con): void {
            foreach ($this as $element) {
                $obj = $this->getWorkerObject();
                $obj->setDeleted(false);
                $obj->fromArray($element);
                $obj->delete($con);
            }
        });
    }

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
        $tableMapClass = $this->getTableMapClass();

        foreach ($this as $key => $element) {
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $tableMapClass::getPrimaryKeyFromRow(array_values($element));
        }

        return $ret;
    }

    /**
     * Populates the collection from an array
     * Uses the object model to force the column types
     * Does not empty the collection before adding the data from the array
     *
     * @param array $arr
     *
     * @return void
     */
    public function fromArray(array $arr): void
    {
        $obj = $this->getWorkerObject();
        foreach ($arr as $element) {
            $obj->clear();
            $obj->fromArray($element);
            $this->append($obj->toArray());
        }
    }

    /**
     * Get an array representation of the collection
     * This is not an alias for getData(), since it returns a copy of the data
     *
     * @param string|null $keyColumn If null, the returned array uses an incremental index.
     *                           Otherwise, the array is indexed using the specified column
     * @param bool $usePrefix If true, the returned array prefixes keys
     * with the model class name ('Article_0', 'Article_1', etc).
     * <code>
     * $bookCollection->toArray();
     * array(
     * 0 => array('Id' => 123, 'Title' => 'War And Peace'),
     * 1 => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * $bookCollection->toArray('Id');
     * array(
     * 123 => array('Id' => 123, 'Title' => 'War And Peace'),
     * 456 => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * $bookCollection->toArray(null, true);
     * array(
     * 'Book_0' => array('Id' => 123, 'Title' => 'War And Peace'),
     * 'Book_1' => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * </code>
     *
     * @return array
     */
    public function toArray(?string $keyColumn = null, bool $usePrefix = false): array
    {
        $ret = [];
        foreach ($this as $key => $element) {
            $key = $keyColumn === null ? $key : $element[$keyColumn];
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $element;
        }

        return $ret;
    }

    /**
     * Synonym for toArray(), to provide a similar interface to PropelObjectCollection
     *
     * @param string|null $keyColumn
     * @param bool $usePrefix
     *
     * @return array
     */
    public function getArrayCopy(?string $keyColumn = null, bool $usePrefix = false): array
    {
        if ($keyColumn === null && $usePrefix === false) {
            return parent::getArrayCopy();
        }

        return $this->toArray($keyColumn, $usePrefix);
    }

    /**
     * Get an associative array representation of the collection
     * The first parameter specifies the column to be used for the key,
     * And the second for the value.
     * <code>
     * $res = $coll->toKeyValue('Id', 'Name');
     * </code>
     *
     * @param string $keyColumn
     * @param string $valueColumn
     *
     * @return array
     */
    public function toKeyValue(string $keyColumn, string $valueColumn): array
    {
        $ret = [];
        foreach ($this as $obj) {
            $ret[$obj[$keyColumn]] = $obj[$valueColumn];
        }

        return $ret;
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    protected function getWorkerObject(): ActiveRecordInterface
    {
        if ($this->workerObject === null) {
            $model = $this->getModel();
            if (!$model) {
                throw new PropelException('You must set the collection model before interacting with it');
            }
            /** @phpstan-var class-string<\Propel\Runtime\ActiveRecord\ActiveRecordInterface> $class */
            $class = $this->getFullyQualifiedModel();
            $this->workerObject = new $class();
        }

        return $this->workerObject;
    }
}
