<?php

namespace Propel\Tests\Bookstore\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Tests\Bookstore\Bookstore as ChildBookstore;
use Propel\Tests\Bookstore\BookstoreQuery as ChildBookstoreQuery;
use Propel\Tests\Bookstore\BookstoreSaleQuery as ChildBookstoreSaleQuery;
use Propel\Tests\Bookstore\Publisher as ChildPublisher;
use Propel\Tests\Bookstore\PublisherQuery as ChildPublisherQuery;
use Propel\Tests\Bookstore\Map\BookstoreSaleTableMap;

/**
 * Base class that represents a row from the 'bookstore_sale' table.
 *
 *
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class BookstoreSale implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookstoreSaleTableMap';


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
     * The value for the bookstore_id field.
     * Note: this column has a database default value of: 1
     * @var        int
     */
    protected $bookstore_id;

    /**
     * The value for the publisher_id field.
     * @var        int
     */
    protected $publisher_id;

    /**
     * The value for the sale_name field.
     * @var        string
     */
    protected $sale_name;

    /**
     * The value for the discount field.
     * Note: this column has a database default value of: (expression) 10
     * @var        int
     */
    protected $discount;

    /**
     * @var        ChildBookstore
     */
    protected $aBookstore;

    /**
     * @var        ChildPublisher
     */
    protected $aPublisher;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->bookstore_id = 1;
    }

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\BookstoreSale object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
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
     * Compares this with another <code>BookstoreSale</code> instance.  If
     * <code>obj</code> is an instance of <code>BookstoreSale</code>, delegates to
     * <code>equals(BookstoreSale)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|BookstoreSale The current object, for fluid interface
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
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [bookstore_id] column value.
     *
     * @return int
     */
    public function getBookstoreId()
    {
        return $this->bookstore_id;
    }

    /**
     * Get the [publisher_id] column value.
     *
     * @return int
     */
    public function getPublisherId()
    {
        return $this->publisher_id;
    }

    /**
     * Get the [sale_name] column value.
     *
     * @return string
     */
    public function getSaleName()
    {
        return $this->sale_name;
    }

    /**
     * Get the [discount] column value.
     * Discount percentage
     * @return int
     */
    public function getDiscount()
    {
        return $this->discount;
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
            if ($this->bookstore_id !== 1) {
                return false;
            }

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookstoreSaleTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookstoreSaleTableMap::translateFieldName('BookstoreId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->bookstore_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : BookstoreSaleTableMap::translateFieldName('PublisherId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->publisher_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : BookstoreSaleTableMap::translateFieldName('SaleName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->sale_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : BookstoreSaleTableMap::translateFieldName('Discount', TableMap::TYPE_PHPNAME, $indexType)];
            $this->discount = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 5; // 5 = BookstoreSaleTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\BookstoreSale'), 0, $e);
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
        if ($this->aBookstore !== null && $this->bookstore_id !== $this->aBookstore->getId()) {
            $this->aBookstore = null;
        }
        if ($this->aPublisher !== null && $this->publisher_id !== $this->aPublisher->getId()) {
            $this->aPublisher = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[BookstoreSaleTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [bookstore_id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     */
    public function setBookstoreId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->bookstore_id !== $v) {
            $this->bookstore_id = $v;
            $this->modifiedColumns[BookstoreSaleTableMap::COL_BOOKSTORE_ID] = true;
        }

        if ($this->aBookstore !== null && $this->aBookstore->getId() !== $v) {
            $this->aBookstore = null;
        }

        return $this;
    } // setBookstoreId()

    /**
     * Set the value of [publisher_id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     */
    public function setPublisherId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->publisher_id !== $v) {
            $this->publisher_id = $v;
            $this->modifiedColumns[BookstoreSaleTableMap::COL_PUBLISHER_ID] = true;
        }

        if ($this->aPublisher !== null && $this->aPublisher->getId() !== $v) {
            $this->aPublisher = null;
        }

        return $this;
    } // setPublisherId()

    /**
     * Set the value of [sale_name] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     */
    public function setSaleName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->sale_name !== $v) {
            $this->sale_name = $v;
            $this->modifiedColumns[BookstoreSaleTableMap::COL_SALE_NAME] = true;
        }

        return $this;
    } // setSaleName()

    /**
     * Set the value of [discount] column.
     * Discount percentage
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     */
    public function setDiscount($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->discount !== $v) {
            $this->discount = $v;
            $this->modifiedColumns[BookstoreSaleTableMap::COL_DISCOUNT] = true;
        }

        return $this;
    } // setDiscount()

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
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreSaleTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookstoreSaleQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aBookstore = null;
            $this->aPublisher = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see BookstoreSale::setDeleted()
     * @see BookstoreSale::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreSaleTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookstoreSaleQuery::create()
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
     * Since this table was configured to reload rows on update, the object will
     * be reloaded from the database if an UPDATE operation is performed (unless
     * the $skipReload parameter is TRUE).
     *
     * @param      ConnectionInterface $con
     * @param      boolean $skipReload Whether to skip the reload for this object from database.
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null, $skipReload = false)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreSaleTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $isInsert = $this->isNew();
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con, $skipReload);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                BookstoreSaleTableMap::addInstanceToPool($this);
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
     * @param      boolean $skipReload Whether to skip the reload for this object from database.
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con, $skipReload = false)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            $reloadObject = false;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aBookstore !== null) {
                if ($this->aBookstore->isModified() || $this->aBookstore->isNew()) {
                    $affectedRows += $this->aBookstore->save($con);
                }
                $this->setBookstore($this->aBookstore);
            }

            if ($this->aPublisher !== null) {
                if ($this->aPublisher->isModified() || $this->aPublisher->isNew()) {
                    $affectedRows += $this->aPublisher->save($con);
                }
                $this->setPublisher($this->aPublisher);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                    if (!$skipReload) {
                        $reloadObject = true;
                    }
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            $this->alreadyInSave = false;

            if ($reloadObject) {
                $this->reload($con);
            }

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

        $this->modifiedColumns[BookstoreSaleTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . BookstoreSaleTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_BOOKSTORE_ID)) {
            $modifiedColumns[':p' . $index++]  = 'BOOKSTORE_ID';
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_PUBLISHER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'PUBLISHER_ID';
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_SALE_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'SALE_NAME';
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_DISCOUNT)) {
            $modifiedColumns[':p' . $index++]  = 'DISCOUNT';
        }

        $sql = sprintf(
            'INSERT INTO bookstore_sale (%s) VALUES (%s)',
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
                    case 'BOOKSTORE_ID':
                        $stmt->bindValue($identifier, $this->bookstore_id, PDO::PARAM_INT);
                        break;
                    case 'PUBLISHER_ID':
                        $stmt->bindValue($identifier, $this->publisher_id, PDO::PARAM_INT);
                        break;
                    case 'SALE_NAME':
                        $stmt->bindValue($identifier, $this->sale_name, PDO::PARAM_STR);
                        break;
                    case 'DISCOUNT':
                        $stmt->bindValue($identifier, $this->discount, PDO::PARAM_INT);
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
        $pos = BookstoreSaleTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getBookstoreId();
                break;
            case 2:
                return $this->getPublisherId();
                break;
            case 3:
                return $this->getSaleName();
                break;
            case 4:
                return $this->getDiscount();
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
        if (isset($alreadyDumpedObjects['BookstoreSale'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['BookstoreSale'][$this->getPrimaryKey()] = true;
        $keys = BookstoreSaleTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getBookstoreId(),
            $keys[2] => $this->getPublisherId(),
            $keys[3] => $this->getSaleName(),
            $keys[4] => $this->getDiscount(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aBookstore) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookstore';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'bookstore';
                        break;
                    default:
                        $key = 'Bookstore';
                }

                $result[$key] = $this->aBookstore->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aPublisher) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'publisher';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'publisher';
                        break;
                    default:
                        $key = 'Publisher';
                }

                $result[$key] = $this->aPublisher->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookstoreSaleTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setBookstoreId($value);
                break;
            case 2:
                $this->setPublisherId($value);
                break;
            case 3:
                $this->setSaleName($value);
                break;
            case 4:
                $this->setDiscount($value);
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
        $keys = BookstoreSaleTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setBookstoreId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setPublisherId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setSaleName($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setDiscount($arr[$keys[4]]);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object, for fluid interface
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
        $criteria = new Criteria(BookstoreSaleTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookstoreSaleTableMap::COL_ID)) {
            $criteria->add(BookstoreSaleTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_BOOKSTORE_ID)) {
            $criteria->add(BookstoreSaleTableMap::COL_BOOKSTORE_ID, $this->bookstore_id);
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_PUBLISHER_ID)) {
            $criteria->add(BookstoreSaleTableMap::COL_PUBLISHER_ID, $this->publisher_id);
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_SALE_NAME)) {
            $criteria->add(BookstoreSaleTableMap::COL_SALE_NAME, $this->sale_name);
        }
        if ($this->isColumnModified(BookstoreSaleTableMap::COL_DISCOUNT)) {
            $criteria->add(BookstoreSaleTableMap::COL_DISCOUNT, $this->discount);
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
        $criteria = new Criteria(BookstoreSaleTableMap::DATABASE_NAME);
        $criteria->add(BookstoreSaleTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\BookstoreSale (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setBookstoreId($this->getBookstoreId());
        $copyObj->setPublisherId($this->getPublisherId());
        $copyObj->setSaleName($this->getSaleName());
        $copyObj->setDiscount($this->getDiscount());
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
     * @return \Propel\Tests\Bookstore\BookstoreSale Clone of current object.
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
     * Declares an association between this object and a ChildBookstore object.
     *
     * @param  ChildBookstore $v
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     * @throws PropelException
     */
    public function setBookstore(ChildBookstore $v = null)
    {
        if ($v === null) {
            $this->setBookstoreId(1);
        } else {
            $this->setBookstoreId($v->getId());
        }

        $this->aBookstore = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildBookstore object, it will not be re-added.
        if ($v !== null) {
            $v->addBookstoreSale($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildBookstore object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildBookstore The associated ChildBookstore object.
     * @throws PropelException
     */
    public function getBookstore(ConnectionInterface $con = null)
    {
        if ($this->aBookstore === null && ($this->bookstore_id !== null)) {
            $this->aBookstore = ChildBookstoreQuery::create()->findPk($this->bookstore_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aBookstore->addBookstoreSales($this);
             */
        }

        return $this->aBookstore;
    }

    /**
     * Declares an association between this object and a ChildPublisher object.
     *
     * @param  ChildPublisher $v
     * @return $this|\Propel\Tests\Bookstore\BookstoreSale The current object (for fluent API support)
     * @throws PropelException
     */
    public function setPublisher(ChildPublisher $v = null)
    {
        if ($v === null) {
            $this->setPublisherId(NULL);
        } else {
            $this->setPublisherId($v->getId());
        }

        $this->aPublisher = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildPublisher object, it will not be re-added.
        if ($v !== null) {
            $v->addBookstoreSale($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildPublisher object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildPublisher The associated ChildPublisher object.
     * @throws PropelException
     */
    public function getPublisher(ConnectionInterface $con = null)
    {
        if ($this->aPublisher === null && ($this->publisher_id !== null)) {
            $this->aPublisher = ChildPublisherQuery::create()->findPk($this->publisher_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aPublisher->addBookstoreSales($this);
             */
        }

        return $this->aPublisher;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aBookstore) {
            $this->aBookstore->removeBookstoreSale($this);
        }
        if (null !== $this->aPublisher) {
            $this->aPublisher->removeBookstoreSale($this);
        }
        $this->id = null;
        $this->bookstore_id = null;
        $this->publisher_id = null;
        $this->sale_name = null;
        $this->discount = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
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
        } // if ($deep)

        $this->aBookstore = null;
        $this->aPublisher = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(BookstoreSaleTableMap::DEFAULT_STRING_FORMAT);
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
