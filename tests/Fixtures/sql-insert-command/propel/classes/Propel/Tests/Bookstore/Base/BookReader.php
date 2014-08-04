<?php

namespace Propel\Tests\Bookstore\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Tests\Bookstore\BookOpinion as ChildBookOpinion;
use Propel\Tests\Bookstore\BookOpinionQuery as ChildBookOpinionQuery;
use Propel\Tests\Bookstore\BookReader as ChildBookReader;
use Propel\Tests\Bookstore\BookReaderQuery as ChildBookReaderQuery;
use Propel\Tests\Bookstore\ReaderFavorite as ChildReaderFavorite;
use Propel\Tests\Bookstore\ReaderFavoriteQuery as ChildReaderFavoriteQuery;
use Propel\Tests\Bookstore\Map\BookReaderTableMap;

/**
 * Base class that represents a row from the 'book_reader' table.
 *
 *
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class BookReader implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookReaderTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the name field.
     * @var        string
     */
    protected $name;

    /**
     * @var        ObjectCollection|ChildBookOpinion[] Collection to store aggregation of ChildBookOpinion objects.
     */
    protected $collBookOpinions;
    protected $collBookOpinionsPartial;

    /**
     * @var        ObjectCollection|ChildReaderFavorite[] Collection to store aggregation of ChildReaderFavorite objects.
     */
    protected $collReaderFavorites;
    protected $collReaderFavoritesPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookOpinion[]
     */
    protected $bookOpinionsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildReaderFavorite[]
     */
    protected $readerFavoritesScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\BookReader object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>BookReader</code> instance.  If
     * <code>obj</code> is an instance of <code>BookReader</code>, delegates to
     * <code>equals(BookReader)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        if (!$obj instanceof static) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey() || null === $obj->getPrimaryKey()) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return $this|BookReader The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [id] column value.
     * Book reader ID number
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [name] column value.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookReaderTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookReaderTableMap::translateFieldName('Name', TableMap::TYPE_PHPNAME, $indexType)];
            $this->name = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 2; // 2 = BookReaderTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\BookReader'), 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
    } // ensureConsistency

    /**
     * Set the value of [id] column.
     * Book reader ID number
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookReader The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[BookReaderTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [name] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookReader The current object (for fluent API support)
     */
    public function setName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->name !== $v) {
            $this->name = $v;
            $this->modifiedColumns[BookReaderTableMap::COL_NAME] = true;
        }

        return $this;
    } // setName()

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookReaderTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookReaderQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collBookOpinions = null;

            $this->collReaderFavorites = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see BookReader::setDeleted()
     * @see BookReader::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookReaderTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookReaderQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $this->setDeleted(true);
            }
        });
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookReaderTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $isInsert = $this->isNew();
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                BookReaderTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }

            return $affectedRows;
        });
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->bookOpinionsScheduledForDeletion !== null) {
                if (!$this->bookOpinionsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\BookOpinionQuery::create()
                        ->filterByPrimaryKeys($this->bookOpinionsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->bookOpinionsScheduledForDeletion = null;
                }
            }

            if ($this->collBookOpinions !== null) {
                foreach ($this->collBookOpinions as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->readerFavoritesScheduledForDeletion !== null) {
                if (!$this->readerFavoritesScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\ReaderFavoriteQuery::create()
                        ->filterByPrimaryKeys($this->readerFavoritesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->readerFavoritesScheduledForDeletion = null;
                }
            }

            if ($this->collReaderFavorites !== null) {
                foreach ($this->collReaderFavorites as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[BookReaderTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . BookReaderTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookReaderTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(BookReaderTableMap::COL_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'NAME';
        }

        $sql = sprintf(
            'INSERT INTO book_reader (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'NAME':
                        $stmt->bindValue($identifier, $this->name, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookReaderTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getName();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['BookReader'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['BookReader'][$this->getPrimaryKey()] = true;
        $keys = BookReaderTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getName(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collBookOpinions) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookOpinions';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'book_opinions';
                        break;
                    default:
                        $key = 'BookOpinions';
                }

                $result[$key] = $this->collBookOpinions->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collReaderFavorites) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'readerFavorites';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'reader_favorites';
                        break;
                    default:
                        $key = 'ReaderFavorites';
                }

                $result[$key] = $this->collReaderFavorites->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param  string $name
     * @param  mixed  $value field value
     * @param  string $type The type of fieldname the $name is of:
     *                one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                Defaults to TableMap::TYPE_PHPNAME.
     * @return $this|\Propel\Tests\Bookstore\BookReader
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookReaderTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\BookReader
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setName($value);
                break;
        } // switch()

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = BookReaderTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setName($arr[$keys[1]]);
        }
    }

     /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return $this|\Propel\Tests\Bookstore\BookReader The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(BookReaderTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookReaderTableMap::COL_ID)) {
            $criteria->add(BookReaderTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(BookReaderTableMap::COL_NAME)) {
            $criteria->add(BookReaderTableMap::COL_NAME, $this->name);
        }

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(BookReaderTableMap::DATABASE_NAME);
        $criteria->add(BookReaderTableMap::COL_ID, $this->id);

        return $criteria;
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        $validPk = null !== $this->getId();

        $validPrimaryKeyFKs = 0;
        $primaryKeyFKs = [];

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\BookReader (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setName($this->getName());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getBookOpinions() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addBookOpinion($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getReaderFavorites() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addReaderFavorite($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param  boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return \Propel\Tests\Bookstore\BookReader Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('BookOpinion' == $relationName) {
            return $this->initBookOpinions();
        }
        if ('ReaderFavorite' == $relationName) {
            return $this->initReaderFavorites();
        }
    }

    /**
     * Clears out the collBookOpinions collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBookOpinions()
     */
    public function clearBookOpinions()
    {
        $this->collBookOpinions = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collBookOpinions collection loaded partially.
     */
    public function resetPartialBookOpinions($v = true)
    {
        $this->collBookOpinionsPartial = $v;
    }

    /**
     * Initializes the collBookOpinions collection.
     *
     * By default this just sets the collBookOpinions collection to an empty array (like clearcollBookOpinions());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initBookOpinions($overrideExisting = true)
    {
        if (null !== $this->collBookOpinions && !$overrideExisting) {
            return;
        }
        $this->collBookOpinions = new ObjectCollection();
        $this->collBookOpinions->setModel('\Propel\Tests\Bookstore\BookOpinion');
    }

    /**
     * Gets an array of ChildBookOpinion objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookReader is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBookOpinion[] List of ChildBookOpinion objects
     * @throws PropelException
     */
    public function getBookOpinions(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBookOpinionsPartial && !$this->isNew();
        if (null === $this->collBookOpinions || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collBookOpinions) {
                // return empty collection
                $this->initBookOpinions();
            } else {
                $collBookOpinions = ChildBookOpinionQuery::create(null, $criteria)
                    ->filterByBookReader($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collBookOpinionsPartial && count($collBookOpinions)) {
                        $this->initBookOpinions(false);

                        foreach ($collBookOpinions as $obj) {
                            if (false == $this->collBookOpinions->contains($obj)) {
                                $this->collBookOpinions->append($obj);
                            }
                        }

                        $this->collBookOpinionsPartial = true;
                    }

                    return $collBookOpinions;
                }

                if ($partial && $this->collBookOpinions) {
                    foreach ($this->collBookOpinions as $obj) {
                        if ($obj->isNew()) {
                            $collBookOpinions[] = $obj;
                        }
                    }
                }

                $this->collBookOpinions = $collBookOpinions;
                $this->collBookOpinionsPartial = false;
            }
        }

        return $this->collBookOpinions;
    }

    /**
     * Sets a collection of ChildBookOpinion objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $bookOpinions A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBookReader The current object (for fluent API support)
     */
    public function setBookOpinions(Collection $bookOpinions, ConnectionInterface $con = null)
    {
        /** @var ChildBookOpinion[] $bookOpinionsToDelete */
        $bookOpinionsToDelete = $this->getBookOpinions(new Criteria(), $con)->diff($bookOpinions);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->bookOpinionsScheduledForDeletion = clone $bookOpinionsToDelete;

        foreach ($bookOpinionsToDelete as $bookOpinionRemoved) {
            $bookOpinionRemoved->setBookReader(null);
        }

        $this->collBookOpinions = null;
        foreach ($bookOpinions as $bookOpinion) {
            $this->addBookOpinion($bookOpinion);
        }

        $this->collBookOpinions = $bookOpinions;
        $this->collBookOpinionsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related BookOpinion objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related BookOpinion objects.
     * @throws PropelException
     */
    public function countBookOpinions(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBookOpinionsPartial && !$this->isNew();
        if (null === $this->collBookOpinions || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBookOpinions) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getBookOpinions());
            }

            $query = ChildBookOpinionQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByBookReader($this)
                ->count($con);
        }

        return count($this->collBookOpinions);
    }

    /**
     * Method called to associate a ChildBookOpinion object to this object
     * through the ChildBookOpinion foreign key attribute.
     *
     * @param  ChildBookOpinion $l ChildBookOpinion
     * @return $this|\Propel\Tests\Bookstore\BookReader The current object (for fluent API support)
     */
    public function addBookOpinion(ChildBookOpinion $l)
    {
        if ($this->collBookOpinions === null) {
            $this->initBookOpinions();
            $this->collBookOpinionsPartial = true;
        }

        if (!$this->collBookOpinions->contains($l)) {
            $this->doAddBookOpinion($l);
        }

        return $this;
    }

    /**
     * @param ChildBookOpinion $bookOpinion The ChildBookOpinion object to add.
     */
    protected function doAddBookOpinion(ChildBookOpinion $bookOpinion)
    {
        $this->collBookOpinions[]= $bookOpinion;
        $bookOpinion->setBookReader($this);
    }

    /**
     * @param  ChildBookOpinion $bookOpinion The ChildBookOpinion object to remove.
     * @return $this|ChildBookReader The current object (for fluent API support)
     */
    public function removeBookOpinion(ChildBookOpinion $bookOpinion)
    {
        if ($this->getBookOpinions()->contains($bookOpinion)) {
            $pos = $this->collBookOpinions->search($bookOpinion);
            $this->collBookOpinions->remove($pos);
            if (null === $this->bookOpinionsScheduledForDeletion) {
                $this->bookOpinionsScheduledForDeletion = clone $this->collBookOpinions;
                $this->bookOpinionsScheduledForDeletion->clear();
            }
            $this->bookOpinionsScheduledForDeletion[]= clone $bookOpinion;
            $bookOpinion->setBookReader(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this BookReader is new, it will return
     * an empty collection; or if this BookReader has previously
     * been saved, it will retrieve related BookOpinions from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in BookReader.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookOpinion[] List of ChildBookOpinion objects
     */
    public function getBookOpinionsJoinBook(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookOpinionQuery::create(null, $criteria);
        $query->joinWith('Book', $joinBehavior);

        return $this->getBookOpinions($query, $con);
    }

    /**
     * Clears out the collReaderFavorites collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addReaderFavorites()
     */
    public function clearReaderFavorites()
    {
        $this->collReaderFavorites = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collReaderFavorites collection loaded partially.
     */
    public function resetPartialReaderFavorites($v = true)
    {
        $this->collReaderFavoritesPartial = $v;
    }

    /**
     * Initializes the collReaderFavorites collection.
     *
     * By default this just sets the collReaderFavorites collection to an empty array (like clearcollReaderFavorites());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initReaderFavorites($overrideExisting = true)
    {
        if (null !== $this->collReaderFavorites && !$overrideExisting) {
            return;
        }
        $this->collReaderFavorites = new ObjectCollection();
        $this->collReaderFavorites->setModel('\Propel\Tests\Bookstore\ReaderFavorite');
    }

    /**
     * Gets an array of ChildReaderFavorite objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookReader is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildReaderFavorite[] List of ChildReaderFavorite objects
     * @throws PropelException
     */
    public function getReaderFavorites(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collReaderFavoritesPartial && !$this->isNew();
        if (null === $this->collReaderFavorites || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collReaderFavorites) {
                // return empty collection
                $this->initReaderFavorites();
            } else {
                $collReaderFavorites = ChildReaderFavoriteQuery::create(null, $criteria)
                    ->filterByBookReader($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collReaderFavoritesPartial && count($collReaderFavorites)) {
                        $this->initReaderFavorites(false);

                        foreach ($collReaderFavorites as $obj) {
                            if (false == $this->collReaderFavorites->contains($obj)) {
                                $this->collReaderFavorites->append($obj);
                            }
                        }

                        $this->collReaderFavoritesPartial = true;
                    }

                    return $collReaderFavorites;
                }

                if ($partial && $this->collReaderFavorites) {
                    foreach ($this->collReaderFavorites as $obj) {
                        if ($obj->isNew()) {
                            $collReaderFavorites[] = $obj;
                        }
                    }
                }

                $this->collReaderFavorites = $collReaderFavorites;
                $this->collReaderFavoritesPartial = false;
            }
        }

        return $this->collReaderFavorites;
    }

    /**
     * Sets a collection of ChildReaderFavorite objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $readerFavorites A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBookReader The current object (for fluent API support)
     */
    public function setReaderFavorites(Collection $readerFavorites, ConnectionInterface $con = null)
    {
        /** @var ChildReaderFavorite[] $readerFavoritesToDelete */
        $readerFavoritesToDelete = $this->getReaderFavorites(new Criteria(), $con)->diff($readerFavorites);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->readerFavoritesScheduledForDeletion = clone $readerFavoritesToDelete;

        foreach ($readerFavoritesToDelete as $readerFavoriteRemoved) {
            $readerFavoriteRemoved->setBookReader(null);
        }

        $this->collReaderFavorites = null;
        foreach ($readerFavorites as $readerFavorite) {
            $this->addReaderFavorite($readerFavorite);
        }

        $this->collReaderFavorites = $readerFavorites;
        $this->collReaderFavoritesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ReaderFavorite objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ReaderFavorite objects.
     * @throws PropelException
     */
    public function countReaderFavorites(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collReaderFavoritesPartial && !$this->isNew();
        if (null === $this->collReaderFavorites || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collReaderFavorites) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getReaderFavorites());
            }

            $query = ChildReaderFavoriteQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByBookReader($this)
                ->count($con);
        }

        return count($this->collReaderFavorites);
    }

    /**
     * Method called to associate a ChildReaderFavorite object to this object
     * through the ChildReaderFavorite foreign key attribute.
     *
     * @param  ChildReaderFavorite $l ChildReaderFavorite
     * @return $this|\Propel\Tests\Bookstore\BookReader The current object (for fluent API support)
     */
    public function addReaderFavorite(ChildReaderFavorite $l)
    {
        if ($this->collReaderFavorites === null) {
            $this->initReaderFavorites();
            $this->collReaderFavoritesPartial = true;
        }

        if (!$this->collReaderFavorites->contains($l)) {
            $this->doAddReaderFavorite($l);
        }

        return $this;
    }

    /**
     * @param ChildReaderFavorite $readerFavorite The ChildReaderFavorite object to add.
     */
    protected function doAddReaderFavorite(ChildReaderFavorite $readerFavorite)
    {
        $this->collReaderFavorites[]= $readerFavorite;
        $readerFavorite->setBookReader($this);
    }

    /**
     * @param  ChildReaderFavorite $readerFavorite The ChildReaderFavorite object to remove.
     * @return $this|ChildBookReader The current object (for fluent API support)
     */
    public function removeReaderFavorite(ChildReaderFavorite $readerFavorite)
    {
        if ($this->getReaderFavorites()->contains($readerFavorite)) {
            $pos = $this->collReaderFavorites->search($readerFavorite);
            $this->collReaderFavorites->remove($pos);
            if (null === $this->readerFavoritesScheduledForDeletion) {
                $this->readerFavoritesScheduledForDeletion = clone $this->collReaderFavorites;
                $this->readerFavoritesScheduledForDeletion->clear();
            }
            $this->readerFavoritesScheduledForDeletion[]= clone $readerFavorite;
            $readerFavorite->setBookReader(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this BookReader is new, it will return
     * an empty collection; or if this BookReader has previously
     * been saved, it will retrieve related ReaderFavorites from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in BookReader.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildReaderFavorite[] List of ChildReaderFavorite objects
     */
    public function getReaderFavoritesJoinBook(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildReaderFavoriteQuery::create(null, $criteria);
        $query->joinWith('Book', $joinBehavior);

        return $this->getReaderFavorites($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this BookReader is new, it will return
     * an empty collection; or if this BookReader has previously
     * been saved, it will retrieve related ReaderFavorites from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in BookReader.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildReaderFavorite[] List of ChildReaderFavorite objects
     */
    public function getReaderFavoritesJoinBookOpinion(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildReaderFavoriteQuery::create(null, $criteria);
        $query->joinWith('BookOpinion', $joinBehavior);

        return $this->getReaderFavorites($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        $this->id = null;
        $this->name = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references and back-references to other model objects or collections of model objects.
     *
     * This method is used to reset all php object references (not the actual reference in the database).
     * Necessary for object serialisation.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collBookOpinions) {
                foreach ($this->collBookOpinions as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collReaderFavorites) {
                foreach ($this->collReaderFavorites as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collBookOpinions = null;
        $this->collReaderFavorites = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(BookReaderTableMap::DEFAULT_STRING_FORMAT);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
