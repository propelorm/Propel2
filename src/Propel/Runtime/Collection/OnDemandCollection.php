<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Map\TableMap;

/**
 * Class for iterating over a statement and returning one Propel object at a time
 *
 * @author Francois Zaninotto
 */
class OnDemandCollection extends Collection
{
    /**
     * @var \Iterator
     */
    private $lastIterator;

    /**
     * @param \Propel\Runtime\Formatter\ObjectFormatter $formatter
     * @param \Propel\Runtime\DataFetcher\DataFetcherInterface $dataFetcher
     *
     * @return void
     */
    public function initIterator(AbstractFormatter $formatter, DataFetcherInterface $dataFetcher)
    {
        $this->lastIterator = new OnDemandIterator($formatter, $dataFetcher);
    }

    /**
     * Get an array representation of the collection
     * Each object is turned into an array and the result is returned
     *
     * @param string|null $keyColumn If null, the returned array uses an incremental index.
     *                                        Otherwise, the array is indexed using the specified column
     * @param bool $usePrefix If true, the returned array prefixes keys
     * with the model class name ('Article_0', 'Article_1', etc).
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME,
     *                                        TableMap::TYPE_CAMELNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME,
     *                                        TableMap::TYPE_NUM. Defaults to TableMap::TYPE_PHPNAME.
     * @param bool $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param array $alreadyDumpedObjects List of objects to skip to avoid recursion
     *
     * <code>
     * $bookCollection->toArray();
     * array(
     *  0 => array('Id' => 123, 'Title' => 'War And Peace'),
     *  1 => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * $bookCollection->toArray('Id');
     * array(
     *  123 => array('Id' => 123, 'Title' => 'War And Peace'),
     *  456 => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * $bookCollection->toArray(null, true);
     * array(
     *  'Book_0' => array('Id' => 123, 'Title' => 'War And Peace'),
     *  'Book_1' => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * </code>
     *
     * @return array
     */
    public function toArray(
        $keyColumn = null,
        $usePrefix = false,
        $keyType = TableMap::TYPE_PHPNAME,
        $includeLazyLoadColumns = true,
        $alreadyDumpedObjects = []
    ) {
        $ret = [];
        $keyGetterMethod = 'get' . $keyColumn;

        /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $obj */
        foreach ($this as $key => $obj) {
            $key = $keyColumn === null ? $key : $obj->$keyGetterMethod();
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj->toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, true);
        }

        return $ret;
    }

    /**
     * Populates the collection from an array
     * Each object is populated from an array and the result is stored
     * Does not empty the collection before adding the data from the array
     *
     * @param array $arr
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function fromArray($arr)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    // IteratorAggregate Interface

    /**
     * @return \Propel\Runtime\Collection\OnDemandIterator
     */
    public function getIterator()
    {
        return $this->lastIterator;
    }

    // ArrayAccess Interface

    /**
     * @param int $offset
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    /**
     * @param int $offset
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    /**
     * @param int $offset
     * @param mixed $value
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    /**
     * @param int $offset
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    // Serializable Interface

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return string
     */
    public function serialize()
    {
        throw new PropelException('The On Demand Collection cannot be serialized');
    }

    /**
     * @param string $data
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return void
     */
    public function unserialize($data)
    {
        throw new PropelException('The On Demand Collection cannot be serialized');
    }

    // Countable Interface

    /**
     * Returns the number of rows in the resultset
     *
     * @return int Number of results
     */
    public function count()
    {
        return $this->getIterator()->count();
    }

    // ArrayObject methods

    /**
     * @param mixed $value
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function append($value)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    /**
     * @param mixed $value
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return int
     */
    public function prepend($value)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    /**
     * @param array $input
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function exchangeArray($input)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return array
     */
    public function getArrayCopy()
    {
        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    /**
     * @inheritDoc
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function exportTo($parser, $usePrefix = true, $includeLazyLoadColumns = true)
    {
        throw new PropelException('A OnDemandCollection cannot be exported.');
    }
}
