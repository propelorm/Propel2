<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Propel;
use Propel\Runtime\Collection\Exception\ModelNotFoundException;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Map\TableMap;

/**
 * Class for iterating over a list of Propel elements
 * The collection keys must be integers - no associative array accepted
 *
 * @method Collection fromXML(string $data) Populate the collection from an XML string
 * @method Collection fromYAML(string $data) Populate the collection from a YAML string
 * @method Collection fromJSON(string $data) Populate the collection from a JSON string
 * @method Collection fromCSV(string $data) Populate the collection from a CSV string
 *
 * @method string toXML(boolean $usePrefix = true, boolean $includeLazyLoadColumns = true) Export the collection to an XML string
 * @method string toYAML(boolean $usePrefix = true, boolean $includeLazyLoadColumns = true) Export the collection to a YAML string
 * @method string toJSON(boolean $usePrefix = true, boolean $includeLazyLoadColumns = true) Export the collection to a JSON string
 * @method string toCSV(boolean $usePrefix = true, boolean $includeLazyLoadColumns = true) Export the collection to a CSV string
 *
 * @author Francois Zaninotto
 */
class Collection implements \ArrayAccess, \IteratorAggregate, \Countable, \Serializable
{
    /**
     * @var string
     */
    protected $model = '';

    /**
     * The fully qualified classname of the model
     *
     * @var string
     */
    protected $fullyQualifiedModel = '';

    /**
     * @var AbstractFormatter
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var PluralizerInterface|null
     */
    private $pluralizer;

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    /**
     * @param mixed $value
     */
    public function append($value)
    {
        $this->data[] = $value;
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param  mixed $offset
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param array $input
     */
    public function exchangeArray($input)
    {
        $this->data = $input;
    }

    /**
     * Get the data in the collection
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return $this->data;
    }

    /**
     * Set the data in the collection
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return CollectionIterator
     */
    public function getIterator()
    {
        return new CollectionIterator($this);
    }

    public function count()
    {
        return count($this->data);
    }

    /**
     * Get the first element in the collection
     *
     * @return mixed
     */
    public function getFirst()
    {
        if (0 === count($this->data)) {
            return null;
        }
        reset($this->data);

        return current($this->data);
    }

    /**
     * Get the last element in the collection
     *
     * @return mixed
     */
    public function getLast()
    {
        if (0 === $this->count()) {
            return null;
        }

        end($this->data);

        return current($this->data);
    }

    /**
     * Check if the collection is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }

    /**
     * Get an element from its key
     * Alias for ArrayObject::offsetGet()
     *
     * @param  mixed $key
     * @return mixed The element
     */
    public function get($key)
    {
        if (!$this->offsetExists($key)) {
            throw new UnexpectedValueException(sprintf('Unknown key %s.', $key));
        }

        return $this->offsetGet($key);
    }

    /**
     * Pops an element off the end of the collection
     *
     * @return mixed The popped element
     */
    public function pop()
    {
        if (0 === $this->count()) {
            return null;
        }

        $array = $this->getArrayCopy();
        $ret = array_pop($array);
        $this->exchangeArray($array);

        return $ret;
    }

    /**
     * Pops an element off the beginning of the collection
     *
     * @return mixed The popped element
     */
    public function shift()
    {
        // the reindexing is complicated to deal with through the iterator
        // so let's use the simple solution
        $arr = $this->getArrayCopy();
        $ret = array_shift($arr);
        $this->exchangeArray($arr);

        return $ret;
    }

    /**
     * Prepend one  elements to the end of the collection
     *
     * @param mixed $value the element to prepend
     */
    public function push($value)
    {
        $this[] = $value;
    }

    /**
     * Prepend one or more elements to the beginning of the collection
     *
     * @param  mixed   $value the element to prepend
     * @return integer The number of new elements in the array
     */
    public function prepend($value)
    {
        // the reindexing is complicated to deal with through the iterator
        // so let's use the simple solution
        $arr = $this->getArrayCopy();
        $ret = array_unshift($arr, $value);
        $this->exchangeArray($arr);

        return $ret;
    }

    /**
     * Add an element to the collection with the given key
     * Alias for ArrayObject::offsetSet()
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Removes a specified collection element
     * Alias for ArrayObject::offsetUnset()
     *
     * @param  mixed $key
     * @return mixed The removed element
     */
    public function remove($key)
    {
        if (!$this->offsetExists($key)) {
            throw new UnexpectedValueException(sprintf('Unknown key %s.', $key));
        }

        return $this->offsetUnset($key);
    }

    /**
     * Clears the collection
     *
     * @return array The previous collection
     */
    public function clear()
    {
        return $this->exchangeArray(array());
    }

    /**
     * Whether or not this collection contains a specified element
     *
     * @param  mixed   $element
     * @return boolean
     */
    public function contains($element)
    {
        return in_array($element, $this->getArrayCopy(), true);
    }

    /**
     * Search an element in the collection
     *
     * @param  mixed $element
     * @return mixed Returns the key for the element if it is found in the collection, FALSE otherwise
     */
    public function search($element)
    {
        return array_search($element, $this->getArrayCopy(), true);
    }

    /**
     * Returns an array of objects present in the collection that
     * are not presents in the given collection.
     *
     * @param  Collection $collection A Propel collection.
     * @return Collection An array of Propel objects from the collection that are not presents in the given collection.
     */
    public function diff(Collection $collection)
    {
        $diff = clone $this;
        $diff->clear();

        foreach ($this as $object) {
            if (!$collection->contains($object)) {
                $diff[] = $object;
            }
        }

        return $diff;
    }

    // Serializable interface

    /**
     * @return string
     */
    public function serialize()
    {
        $repr = array(
            'data' => $this->getArrayCopy(),
            'model' => $this->model,
            'fullyQualifiedModel' => $this->fullyQualifiedModel,
        );

        return serialize($repr);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $repr = unserialize($data);
        $this->exchangeArray($repr['data']);
        $this->model = $repr['model'];
        $this->fullyQualifiedModel = $repr['fullyQualifiedModel'];
    }

    // Propel collection methods

    /**
     * Set the model of the elements in the collection
     *
     * @param string $model Name of the Propel object classes stored in the collection
     */
    public function setModel($model)
    {
        if (false !== $pos = strrpos($model, '\\')) {
            $this->model = substr($model, $pos + 1);
        } else {
            $this->model = $model;
        }
        $this->fullyQualifiedModel = ((0 === strpos($model, '\\')) ? '' : '\\') . $model;
    }

    /**
     * Get the model of the elements in the collection
     *
     * @return string Name of the Propel object class stored in the collection
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the model of the elements in the collection
     *
     * @return string Fully qualified Name of the Propel object class stored in the collection
     */
    public function getFullyQualifiedModel()
    {
        return $this->fullyQualifiedModel;
    }

    public function getTableMapClass()
    {
        $model = $this->getModel();

        if (empty($model)) {
            throw new ModelNotFoundException('You must set the collection model before interacting with it');
        }

        return constant($this->getFullyQualifiedModel() . '::TABLE_MAP');
    }

    /**
     * @param AbstractFormatter $formatter
     */
    public function setFormatter(AbstractFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @return AbstractFormatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Get a write connection object for the database containing the elements of the collection
     *
     * @return ConnectionInterface A ConnectionInterface connection object
     */
    public function getWriteConnection()
    {
        $databaseName = constant($this->getTableMapClass() . '::DATABASE_NAME');

        return Propel::getServiceContainer()->getWriteConnection($databaseName);
    }

    /**
     * Populate the current collection from a string, using a given parser format
     * <code>
     * $coll = new ObjectCollection();
     * $coll->setModel('Book');
     * $coll->importFrom('JSON', '{{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}}');
     * </code>
     *
     * @param mixed  $parser A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data   The source data to import from
     *
     * @return mixed The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $this->fromArray($parser->listToArray($data, $this->getPluralModelName()), TableMap::TYPE_PHPNAME);
    }

    /**
     * Export the current collection to a string, using a given parser format
     * <code>
     * $books = BookQuery::create()->find();
     * echo $book->exportTo('JSON');
     *  => {{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}}');
     * </code>
     *
     * A OnDemandCollection cannot be exported. Any attempt will result in a PropelException being thrown.
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $usePrefix              (optional) If true, the returned element keys will be prefixed with the
     *                                         model class name ('Article_0', 'Article_1', etc). Defaults to TRUE.
     *                                         Not supported by ArrayCollection, as ArrayFormatter has
     *                                         already created the array used here with integers as keys.
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     *                                         Not supported by ArrayCollection, as ArrayFormatter has
     *                                         already included lazy-load columns in the array used here.
     * @return string  The exported data
     */
    public function exportTo($parser, $usePrefix = true, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $array = $this->toArray(null, $usePrefix, TableMap::TYPE_PHPNAME, $includeLazyLoadColumns);

        return $parser->listFromArray($array, $this->getPluralModelName());
    }

    /**
     * Catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you use a custom BaseObject
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }
        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $usePrefix = isset($params[0]) ? $params[0] : false;
            $includeLazyLoadColumns = isset($params[1]) ? $params[1] : true;

            return $this->exportTo($format, $usePrefix, $includeLazyLoadColumns);
        }
        throw new BadMethodCallException('Call to undefined method: ' . $name);
    }

    /**
     * Returns a string representation of the current collection.
     * Based on the string representation of the underlying objects, defined in
     * the TableMap::DEFAULT_STRING_FORMAT constant
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(constant($this->getTableMapClass() . '::DEFAULT_STRING_FORMAT'), false);
    }

    /**
     * Creates clones of the containing data.
     */
    public function __clone()
    {
        foreach ($this as $key => $obj) {
            if (is_object($obj)) {
                $this[$key] = clone $obj;
            }
        }
    }

    protected function getPluralizer()
    {
        if ($this->pluralizer === null) {
            $this->pluralizer = $this->createPluralizer();
        }

        return $this->pluralizer;
    }

    /**
     * Overwrite this method if you want to use a custom pluralizer
     */
    protected function createPluralizer()
    {
        return new StandardEnglishPluralizer();
    }

    protected function getPluralModelName()
    {
        return $this->getPluralizer()->getPluralForm($this->getModel());
    }
}
