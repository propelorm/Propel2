<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Map\TableMap;

/**
 * Class for iterating over a statement and returning one Propel object at a time
 *
 * @author Francois Zaninotto
 */
class OnDemandCollection extends Collection
{
    /**
     * @var OnDemandIterator
     */
    protected $iterator;

    protected $currentRow;

    protected $currentKey;

    protected $isValid;

    /**
     * @param AbstractFormatter    $formatter
     * @param DataFetcherInterface $dataFetcher
     */
    public function initIterator(AbstractFormatter $formatter, DataFetcherInterface $dataFetcher)
    {
        $this->currentKey = -1;
        $this->iterator = new OnDemandIterator($formatter, $dataFetcher);
    }

    /**
     * Get an array representation of the collection
     * Each object is turned into an array and the result is returned
     *
     * @param string $keyColumn If null, the returned array uses an incremental index.
     *                               Otherwise, the array is indexed using the specified column
     * @param boolean $usePrefix If true, the returned array prefixes keys
     *                               with the model class name ('Article_0', 'Article_1', etc).
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME,
     *                               TableMap::TYPE_STUDLYPHPNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME,
     *                               TableMap::TYPE_NUM. Defaults to TableMap::TYPE_PHPNAME.
     * @param boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param array   $alreadyDumpedObjects   List of objects to skip to avoid recursion
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
    public function toArray($keyColumn = null, $usePrefix = false, $keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array())
    {
        $ret = array();
        $keyGetterMethod = 'get' . $keyColumn;

        /** @var $obj BaseObject */
        foreach ($this as $key => $obj) {
            $key = null === $keyColumn ? $key : $obj->$keyGetterMethod();
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
     */
    public function fromArray($arr)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    // IteratorAggregate Interface

    /**
     * @return OnDemandIterator
     */
    public function getIterator()
    {
        return $this->iterator;
    }

    // ArrayAccess Interface

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @param  integer                                   $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        if ($offset === $this->currentKey) {
            return true;
        }

        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @param  integer                                   $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($offset === $this->currentKey) {
            return $this->currentRow;
        }

        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    /**
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @param integer $offset
     * @param mixed   $value
     */
    public function offsetSet($offset, $value)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    /**
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     * @param  integer                                                     $offset
     */
    public function offsetUnset($offset)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    // Serializable Interface

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function serialize()
    {
        throw new PropelException('The On Demand Collection cannot be serialized');
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     * @param  string                                    $data
     */
    public function unserialize($data)
    {
        throw new PropelException('The On Demand Collection cannot be serialized');
    }

    // Countable Interface

    /**
     * Returns the number of rows in the resultset
     * Warning: this number is inaccurate for most databases. Do not rely on it for a portable application.
     *
     * @return integer Number of results
     */
    public function count()
    {
        return $this->iterator->count();
    }

    // ArrayObject methods

    public function append($value)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function prepend($value)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function asort()
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function exchangeArray($input)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function getArrayCopy()
    {
        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    public function getFlags()
    {
        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    public function ksort()
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function natcasesort()
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function natsort()
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function setFlags($flags)
    {
        throw new PropelException('The On Demand Collection does not allow access by offset');
    }

    public function uasort($cmp_function)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    public function uksort($cmp_function)
    {
        throw new ReadOnlyModelException('The On Demand Collection is read only');
    }

    /**
     * {@inheritdoc}
     */
    public function exportTo($parser, $usePrefix = true, $includeLazyLoadColumns = true)
    {
        throw new PropelException('A OnDemandCollection cannot be exported.');
    }
}
