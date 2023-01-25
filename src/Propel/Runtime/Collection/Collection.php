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
use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Runtime\Collection\Exception\ModelNotFoundException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Formatter\AbstractFormatter;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Propel;
use Serializable;
use Traversable;

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
 *
 * @implements \ArrayAccess<int|string, mixed>
 * @implements \IteratorAggregate<int|string, mixed>
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
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [$this->serialize()];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->unserialize($data[0]);
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function append($value): void
    {
        $this->data[] = $value;
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @psalm-suppress ReservedWord
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function &offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        return null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
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
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * @param array $input
     *
     * @return void
     */
    public function exchangeArray(array $input): void
    {
        $this->data = $input;
    }

    /**
     * Get the data in the collection
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getArrayCopy(): array
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
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return \Propel\Runtime\Collection\CollectionIterator|\Propel\Runtime\Collection\IteratorInterface
     */
    public function getIterator(): Traversable
    {
        return new CollectionIterator($this);
    }

    /**
     * Count elements in the collection
     *
     * @return int
     */
    public function count(): int
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
    public function isEmpty(): bool
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
    public function push($value): void
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
    public function prepend($value): int
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
    public function set($key, $value): void
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
    public function remove($key): void
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
    public function clear(): void
    {
        $this->exchangeArray([]);
    }

    /**
     * Whether this collection contains a specified element
     *
     * @param mixed $element
     *
     * @return bool
     */
    public function contains($element): bool
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
     * @return self An array of Propel objects from the collection that are not presents in the given collection.
     */
    public function diff(Collection $collection): self
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
     * @return string|null
     */
    #[\ReturnTypeWillChange]
    public function serialize(): ?string
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
    #[\ReturnTypeWillChange]
    public function unserialize($data): void
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
    public function setModel(string $model): void
    {
        $pos = strrpos($model, '\\');
        if ($pos !== false) {
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
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the model of the elements in the collection
     *
     * @return string Fully qualified Name of the Propel object class stored in the collection
     */
    public function getFullyQualifiedModel(): string
    {
        return $this->fullyQualifiedModel;
    }

    /**
     * @psalm-return class-string<\Propel\Runtime\Map\TableMap>
     *
     * @throws \Propel\Runtime\Collection\Exception\ModelNotFoundException
     *
     * @return string
     */
    public function getTableMapClass(): string
    {
        $model = $this->getModel();

        if (!$model) {
            throw new ModelNotFoundException('You must set the collection model before interacting with it');
        }

        return $this->getFullyQualifiedModel()::TABLE_MAP;
    }

    /**
     * @param \Propel\Runtime\Formatter\AbstractFormatter $formatter
     *
     * @return void
     */
    public function setFormatter(AbstractFormatter $formatter): void
    {
        $this->formatter = $formatter;
    }

    /**
     * @return \Propel\Runtime\Formatter\AbstractFormatter
     */
    public function getFormatter(): AbstractFormatter
    {
        return $this->formatter;
    }

    /**
     * Get a write connection object for the database containing the elements of the collection
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A ConnectionInterface connection object
     */
    public function getWriteConnection(): ConnectionInterface
    {
        $databaseName = $this->getTableMapClass()::DATABASE_NAME;

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
     * @return void
     */
    public function importFrom($parser, string $data): void
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->listToArray($data, $this->getPluralModelName()));
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
     * @param \Propel\Runtime\Parser\AbstractParser|string $parser A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param bool $usePrefix (optional) If true, the returned element keys will be prefixed with the
     * model class name ('Article_0', 'Article_1', etc). Defaults to TRUE.
     * Not supported by ArrayCollection, as ArrayFormatter has
     * already created the array used here with integers as keys.
     * @param bool $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * Not supported by ArrayCollection, as ArrayFormatter has
     * already included lazy-load columns in the array used here.
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM. Defaults to TableMap::TYPE_PHPNAME.
     *
     * @return string The exported data
     */
    public function exportTo($parser, bool $usePrefix = true, bool $includeLazyLoadColumns = true, string $keyType = TableMap::TYPE_PHPNAME): string
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $array = $this->toArray(null, $usePrefix, $keyType, $includeLazyLoadColumns);

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
     * @return array|string|null
     */
    public function __call(string $name, $params)
    {
        if (strpos($name, 'from') === 0) {
            $format = substr($name, 4);
            $this->importFrom($format, reset($params));

            return null;
        }

        if (strpos($name, 'to') === 0) {
            $format = substr($name, 2);
            $usePrefix = $params[0] ?? false;
            $includeLazyLoadColumns = $params[1] ?? true;
            $keyType = $params[2] ?? TableMap::TYPE_PHPNAME;

            return $this->exportTo($format, $usePrefix, $includeLazyLoadColumns, $keyType);
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
    public function __toString(): string
    {
        return $this->exportTo($this->getTableMapClass()::DEFAULT_STRING_FORMAT, false);
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
     * @return \Propel\Common\Pluralizer\PluralizerInterface
     */
    protected function getPluralizer(): PluralizerInterface
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
    protected function createPluralizer(): PluralizerInterface
    {
        return new StandardEnglishPluralizer();
    }

    /**
     * @return string
     */
    protected function getPluralModelName(): string
    {
        return $this->getPluralizer()->getPluralForm($this->getModel());
    }

    /**
     * @return string
     */
    public function hashCode(): string
    {
        return spl_object_hash($this);
    }
}
