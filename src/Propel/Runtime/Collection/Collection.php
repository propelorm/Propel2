<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Runtime\Collection\Exception\ModelNotFoundException;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Propel;
use Serializable;

/**
 * Class for iterating over a list of Propel elements
 * The collection keys must be integers - no associative array accepted
 *
 * @method \Propel\Runtime\Collection\Collection fromXML(string $data) Populate the collection from an XML string
 * @method \Propel\Runtime\Collection\Collection fromYAML(string $data) Populate the collection from a YAML string
 * @method \Propel\Runtime\Collection\Collection fromJSON(string $data) Populate the collection from a JSON string
 * @method \Propel\Runtime\Collection\Collection fromCSV(string $data) Populate the collection from a CSV string
 *
 * @method string toXML(bool $usePrefix = true, bool $includeLazyLoadColumns = true) Export the collection to an XML string
 * @method string toYAML(bool $usePrefix = true, bool $includeLazyLoadColumns = true) Export the collection to a YAML string
 * @method string toJSON(bool $usePrefix = true, bool $includeLazyLoadColumns = true) Export the collection to a JSON string
 * @method string toCSV(bool $usePrefix = true, bool $includeLazyLoadColumns = true) Export the collection to a CSV string
 *
 * @author Francois Zaninotto
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable, Serializable
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
     * @var \Propel\Runtime\Formatter\AbstractFormatter
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var \Propel\Common\Pluralizer\PluralizerInterface|null
     */
    private $pluralizer;

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function append($value)
    {
        $this->data[] = $value;
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     *
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
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param array $input
     *
     * @return void
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
     *
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return \Propel\Runtime\Collection\CollectionIterator
     */
    public function getIterator()
    {
        return new CollectionIterator($this);
    }

    /**
     * Count elements in the collection
     *
     * @return int
     */
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
        if (count($this->data) === 0) {
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
        if ($this->count() === 0) {
            return null;
        }

        end($this->data);

        return current($this->data);
    }

    /**
     * Check if the collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * Get an element from its key
     * Alias for ArrayObject::offsetGet()
     *
     * @param mixed $key
     *
     * @throws \Propel\Runtime\Exception\UnexpectedValueException
     *
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
        if ($this->count() === 0) {
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
     * Prepend one elements to the end of the collection
     *
     * @param mixed $value the element to prepend
     *
     * @return void
     */
    public function push($value)
    {
        $this[] = $value;
    }

    /**
     * Prepend one or more elements to the beginning of the collection
     *
     * @param mixed $value the element to prepend
     *
     * @return int The number of new elements in the array
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
     *
     * @return void
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Removes a specified collection element
     * Alias for ArrayObject::offsetUnset()
     *
     * @param mixed $key
     *
     * @throws \Propel\Runtime\Exception\UnexpectedValueException
     *
     * @return void
     */
    public function remove($key)
    {
        if (!$this->offsetExists($key)) {
            throw new UnexpectedValueException(sprintf('Unknown key %s.', $key));
        }

        $this->offsetUnset($key);
    }

    /**
     * Clears the collection
     *
     * @return void
     */
    public function clear()
    {
        $this->exchangeArray([]);
    }

    /**
     * Whether or not this collection contains a specified element
     *
     * @param mixed $element
     *
     * @return bool
     */
    public function contains($element)
    {
        return in_array($element, $this->getArrayCopy(), true);
    }

    /**
     * Search an element in the collection
     *
     * @param mixed $element
     *
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
     * @param \Propel\Runtime\Collection\Collection $collection A Propel collection.
     *
     * @return \Propel\Runtime\Collection\Collection An array of Propel objects from the collection that are not presents in the given collection.
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
        $repr = [
            'data' => $this->getArrayCopy(),
            'model' => $this->model,
            'fullyQualifiedModel' => $this->fullyQualifiedModel,
        ];

        return serialize($repr);
    }

    /**
     * @param string $data
     *
     * @return void
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
     *
     * @return void
     */
    public function setModel($model)
    {
        if (false !== $pos = strrpos($model, '\\')) {
            $this->model = substr($model, $pos + 1);
        } else {
            $this->model = $model;
        }
        $this->fullyQualifiedModel = ((strpos($model, '\\') === 0) ? '' : '\\') . $model;
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

    /**
     * @throws \Propel\Runtime\Collection\Exception\ModelNotFoundException
     *
     * @return string
     */
    public function getTableMapClass()
    {
        $model = $this->getModel();

        if (empty($model)) {
            throw new ModelNotFoundException('You must set the collection model before interacting with it');
        }

        return constant($this->getFullyQualifiedModel() . '::TABLE_MAP');
    }

    /**
     * @param \Propel\Runtime\Formatter\AbstractFormatter $formatter
     *
     * @return void
     */
    public function setFormatter(AbstractFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @return \Propel\Runtime\Formatter\AbstractFormatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Get a write connection object for the database containing the elements of the collection
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A ConnectionInterface connection object
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
     * @param mixed $parser A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
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
     * @param mixed $parser A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param bool $usePrefix (optional) If true, the returned element keys will be prefixed with the
     * model class name ('Article_0', 'Article_1', etc). Defaults to TRUE.
     * Not supported by ArrayCollection, as ArrayFormatter has
     * already created the array used here with integers as keys.
     * @param bool $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * Not supported by ArrayCollection, as ArrayFormatter has
     * already included lazy-load columns in the array used here.
     *
     * @return string The exported data
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
     * @param mixed $params
     *
     * @throws \Propel\Runtime\Exception\BadMethodCallException
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (strpos($name, 'from') === 0) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }
        if (strpos($name, 'to') === 0) {
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
        return (string)$this->exportTo(constant($this->getTableMapClass() . '::DEFAULT_STRING_FORMAT'), false);
    }

    /**
     * Creates clones of the containing data.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this as $key => $obj) {
            if (is_object($obj)) {
                $this[$key] = clone $obj;
            }
        }
    }

    /**
     * @return \Propel\Common\Pluralizer\PluralizerInterface|null
     */
    protected function getPluralizer()
    {
        if ($this->pluralizer === null) {
            $this->pluralizer = $this->createPluralizer();
        }

        return $this->pluralizer;
    }

    /**
     * Overwrite this method if you want to use a custom pluralizer
     *
     * @return \Propel\Common\Pluralizer\PluralizerInterface
     */
    protected function createPluralizer()
    {
        return new StandardEnglishPluralizer();
    }

    /**
     * @return string
     */
    protected function getPluralModelName()
    {
        return $this->getPluralizer()->getPluralForm($this->getModel());
    }

    /**
     * @return string
     */
    public function hashCode()
    {
        return spl_object_hash($this);
    }
}
