<?php

namespace Propel\Tests\Bookstore\Base;

use \DateTime;
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
use Propel\Runtime\Util\PropelDateTime;
use Propel\Tests\Bookstore\Bookstore as ChildBookstore;
use Propel\Tests\Bookstore\BookstoreContest as ChildBookstoreContest;
use Propel\Tests\Bookstore\BookstoreContestEntryQuery as ChildBookstoreContestEntryQuery;
use Propel\Tests\Bookstore\BookstoreContestQuery as ChildBookstoreContestQuery;
use Propel\Tests\Bookstore\BookstoreQuery as ChildBookstoreQuery;
use Propel\Tests\Bookstore\Customer as ChildCustomer;
use Propel\Tests\Bookstore\CustomerQuery as ChildCustomerQuery;
use Propel\Tests\Bookstore\Map\BookstoreContestEntryTableMap;

/**
 * Base class that represents a row from the 'bookstore_contest_entry' table.
 *
 *
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class BookstoreContestEntry implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookstoreContestEntryTableMap';


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
     * The value for the bookstore_id field.
     * @var        int
     */
    protected $bookstore_id;

    /**
     * The value for the contest_id field.
     * @var        int
     */
    protected $contest_id;

    /**
     * The value for the customer_id field.
     * @var        int
     */
    protected $customer_id;

    /**
     * The value for the entry_date field.
     * Note: this column has a database default value of: (expression) CURRENT_TIMESTAMP
     * @var        \DateTime
     */
    protected $entry_date;

    /**
     * @var        ChildBookstore
     */
    protected $aBookstore;

    /**
     * @var        ChildCustomer
     */
    protected $aCustomer;

    /**
     * @var        ChildBookstoreContest
     */
    protected $aBookstoreContest;

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
    }

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\BookstoreContestEntry object.
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
     * Compares this with another <code>BookstoreContestEntry</code> instance.  If
     * <code>obj</code> is an instance of <code>BookstoreContestEntry</code>, delegates to
     * <code>equals(BookstoreContestEntry)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|BookstoreContestEntry The current object, for fluid interface
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
     * Get the [bookstore_id] column value.
     *
     * @return int
     */
    public function getBookstoreId()
    {
        return $this->bookstore_id;
    }

    /**
     * Get the [contest_id] column value.
     *
     * @return int
     */
    public function getContestId()
    {
        return $this->contest_id;
    }

    /**
     * Get the [customer_id] column value.
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Get the [optionally formatted] temporal [entry_date] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getEntryDate($format = NULL)
    {
        if ($format === null) {
            return $this->entry_date;
        } else {
            return $this->entry_date instanceof \DateTime ? $this->entry_date->format($format) : null;
        }
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookstoreContestEntryTableMap::translateFieldName('BookstoreId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->bookstore_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookstoreContestEntryTableMap::translateFieldName('ContestId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->contest_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : BookstoreContestEntryTableMap::translateFieldName('CustomerId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->customer_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : BookstoreContestEntryTableMap::translateFieldName('EntryDate', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->entry_date = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = BookstoreContestEntryTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\BookstoreContestEntry'), 0, $e);
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
        if ($this->aBookstoreContest !== null && $this->bookstore_id !== $this->aBookstoreContest->getBookstoreId()) {
            $this->aBookstoreContest = null;
        }
        if ($this->aBookstoreContest !== null && $this->contest_id !== $this->aBookstoreContest->getContestId()) {
            $this->aBookstoreContest = null;
        }
        if ($this->aCustomer !== null && $this->customer_id !== $this->aCustomer->getId()) {
            $this->aCustomer = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [bookstore_id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     */
    public function setBookstoreId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->bookstore_id !== $v) {
            $this->bookstore_id = $v;
            $this->modifiedColumns[BookstoreContestEntryTableMap::COL_BOOKSTORE_ID] = true;
        }

        if ($this->aBookstore !== null && $this->aBookstore->getId() !== $v) {
            $this->aBookstore = null;
        }

        if ($this->aBookstoreContest !== null && $this->aBookstoreContest->getBookstoreId() !== $v) {
            $this->aBookstoreContest = null;
        }

        return $this;
    } // setBookstoreId()

    /**
     * Set the value of [contest_id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     */
    public function setContestId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->contest_id !== $v) {
            $this->contest_id = $v;
            $this->modifiedColumns[BookstoreContestEntryTableMap::COL_CONTEST_ID] = true;
        }

        if ($this->aBookstoreContest !== null && $this->aBookstoreContest->getContestId() !== $v) {
            $this->aBookstoreContest = null;
        }

        return $this;
    } // setContestId()

    /**
     * Set the value of [customer_id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     */
    public function setCustomerId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->customer_id !== $v) {
            $this->customer_id = $v;
            $this->modifiedColumns[BookstoreContestEntryTableMap::COL_CUSTOMER_ID] = true;
        }

        if ($this->aCustomer !== null && $this->aCustomer->getId() !== $v) {
            $this->aCustomer = null;
        }

        return $this;
    } // setCustomerId()

    /**
     * Sets the value of [entry_date] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     */
    public function setEntryDate($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->entry_date !== null || $dt !== null) {
            if ($dt !== $this->entry_date) {
                $this->entry_date = $dt;
                $this->modifiedColumns[BookstoreContestEntryTableMap::COL_ENTRY_DATE] = true;
            }
        } // if either are not null

        return $this;
    } // setEntryDate()

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
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreContestEntryTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookstoreContestEntryQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aBookstore = null;
            $this->aCustomer = null;
            $this->aBookstoreContest = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see BookstoreContestEntry::setDeleted()
     * @see BookstoreContestEntry::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreContestEntryTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookstoreContestEntryQuery::create()
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
     * Since this table was configured to reload rows on insert, the object will
     * be reloaded from the database if an INSERT operation is performed (unless
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreContestEntryTableMap::DATABASE_NAME);
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
                BookstoreContestEntryTableMap::addInstanceToPool($this);
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

            if ($this->aCustomer !== null) {
                if ($this->aCustomer->isModified() || $this->aCustomer->isNew()) {
                    $affectedRows += $this->aCustomer->save($con);
                }
                $this->setCustomer($this->aCustomer);
            }

            if ($this->aBookstoreContest !== null) {
                if ($this->aBookstoreContest->isModified() || $this->aBookstoreContest->isNew()) {
                    $affectedRows += $this->aBookstoreContest->save($con);
                }
                $this->setBookstoreContest($this->aBookstoreContest);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    if (!$skipReload) {
                        $reloadObject = true;
                    }
                } else {
                    $this->doUpdate($con);
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


         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID)) {
            $modifiedColumns[':p' . $index++]  = 'BOOKSTORE_ID';
        }
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_CONTEST_ID)) {
            $modifiedColumns[':p' . $index++]  = 'CONTEST_ID';
        }
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_CUSTOMER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'CUSTOMER_ID';
        }
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_ENTRY_DATE)) {
            $modifiedColumns[':p' . $index++]  = 'ENTRY_DATE';
        }

        $sql = sprintf(
            'INSERT INTO bookstore_contest_entry (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'BOOKSTORE_ID':
                        $stmt->bindValue($identifier, $this->bookstore_id, PDO::PARAM_INT);
                        break;
                    case 'CONTEST_ID':
                        $stmt->bindValue($identifier, $this->contest_id, PDO::PARAM_INT);
                        break;
                    case 'CUSTOMER_ID':
                        $stmt->bindValue($identifier, $this->customer_id, PDO::PARAM_INT);
                        break;
                    case 'ENTRY_DATE':
                        $stmt->bindValue($identifier, $this->entry_date ? $this->entry_date->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

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
        $pos = BookstoreContestEntryTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getBookstoreId();
                break;
            case 1:
                return $this->getContestId();
                break;
            case 2:
                return $this->getCustomerId();
                break;
            case 3:
                return $this->getEntryDate();
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
        if (isset($alreadyDumpedObjects['BookstoreContestEntry'][serialize($this->getPrimaryKey())])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['BookstoreContestEntry'][serialize($this->getPrimaryKey())] = true;
        $keys = BookstoreContestEntryTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getBookstoreId(),
            $keys[1] => $this->getContestId(),
            $keys[2] => $this->getCustomerId(),
            $keys[3] => $this->getEntryDate(),
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
            if (null !== $this->aCustomer) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'customer';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'customer';
                        break;
                    default:
                        $key = 'Customer';
                }

                $result[$key] = $this->aCustomer->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aBookstoreContest) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookstoreContest';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'bookstore_contest';
                        break;
                    default:
                        $key = 'BookstoreContest';
                }

                $result[$key] = $this->aBookstoreContest->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookstoreContestEntryTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setBookstoreId($value);
                break;
            case 1:
                $this->setContestId($value);
                break;
            case 2:
                $this->setCustomerId($value);
                break;
            case 3:
                $this->setEntryDate($value);
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
        $keys = BookstoreContestEntryTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setBookstoreId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setContestId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setCustomerId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setEntryDate($arr[$keys[3]]);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object, for fluid interface
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
        $criteria = new Criteria(BookstoreContestEntryTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID)) {
            $criteria->add(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $this->bookstore_id);
        }
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_CONTEST_ID)) {
            $criteria->add(BookstoreContestEntryTableMap::COL_CONTEST_ID, $this->contest_id);
        }
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_CUSTOMER_ID)) {
            $criteria->add(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $this->customer_id);
        }
        if ($this->isColumnModified(BookstoreContestEntryTableMap::COL_ENTRY_DATE)) {
            $criteria->add(BookstoreContestEntryTableMap::COL_ENTRY_DATE, $this->entry_date);
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
        $criteria = new Criteria(BookstoreContestEntryTableMap::DATABASE_NAME);
        $criteria->add(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $this->bookstore_id);
        $criteria->add(BookstoreContestEntryTableMap::COL_CONTEST_ID, $this->contest_id);
        $criteria->add(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $this->customer_id);

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
        $validPk = null !== $this->getBookstoreId() &&
            null !== $this->getContestId() &&
            null !== $this->getCustomerId();

        $validPrimaryKeyFKs = 4;
        $primaryKeyFKs = [];

        //relation bookstore_contest_entry_fk_d73164 to table bookstore
        if ($this->aBookstore && $hash = spl_object_hash($this->aBookstore)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        //relation bookstore_contest_entry_fk_7e8f3e to table customer
        if ($this->aCustomer && $hash = spl_object_hash($this->aCustomer)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        //relation bookstore_contest_entry_fk_f5af06 to table bookstore_contest
        if ($this->aBookstoreContest && $hash = spl_object_hash($this->aBookstoreContest)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the composite primary key for this object.
     * The array elements will be in same order as specified in XML.
     * @return array
     */
    public function getPrimaryKey()
    {
        $pks = array();
        $pks[0] = $this->getBookstoreId();
        $pks[1] = $this->getContestId();
        $pks[2] = $this->getCustomerId();

        return $pks;
    }

    /**
     * Set the [composite] primary key.
     *
     * @param      array $keys The elements of the composite key (order must match the order in XML file).
     * @return void
     */
    public function setPrimaryKey($keys)
    {
        $this->setBookstoreId($keys[0]);
        $this->setContestId($keys[1]);
        $this->setCustomerId($keys[2]);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return (null === $this->getBookstoreId()) && (null === $this->getContestId()) && (null === $this->getCustomerId());
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\BookstoreContestEntry (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setBookstoreId($this->getBookstoreId());
        $copyObj->setContestId($this->getContestId());
        $copyObj->setCustomerId($this->getCustomerId());
        $copyObj->setEntryDate($this->getEntryDate());
        if ($makeNew) {
            $copyObj->setNew(true);
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
     * @return \Propel\Tests\Bookstore\BookstoreContestEntry Clone of current object.
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     * @throws PropelException
     */
    public function setBookstore(ChildBookstore $v = null)
    {
        if ($v === null) {
            $this->setBookstoreId(NULL);
        } else {
            $this->setBookstoreId($v->getId());
        }

        $this->aBookstore = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildBookstore object, it will not be re-added.
        if ($v !== null) {
            $v->addBookstoreContestEntry($this);
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
                $this->aBookstore->addBookstoreContestEntries($this);
             */
        }

        return $this->aBookstore;
    }

    /**
     * Declares an association between this object and a ChildCustomer object.
     *
     * @param  ChildCustomer $v
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     * @throws PropelException
     */
    public function setCustomer(ChildCustomer $v = null)
    {
        if ($v === null) {
            $this->setCustomerId(NULL);
        } else {
            $this->setCustomerId($v->getId());
        }

        $this->aCustomer = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildCustomer object, it will not be re-added.
        if ($v !== null) {
            $v->addBookstoreContestEntry($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildCustomer object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildCustomer The associated ChildCustomer object.
     * @throws PropelException
     */
    public function getCustomer(ConnectionInterface $con = null)
    {
        if ($this->aCustomer === null && ($this->customer_id !== null)) {
            $this->aCustomer = ChildCustomerQuery::create()->findPk($this->customer_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aCustomer->addBookstoreContestEntries($this);
             */
        }

        return $this->aCustomer;
    }

    /**
     * Declares an association between this object and a ChildBookstoreContest object.
     *
     * @param  ChildBookstoreContest $v
     * @return $this|\Propel\Tests\Bookstore\BookstoreContestEntry The current object (for fluent API support)
     * @throws PropelException
     */
    public function setBookstoreContest(ChildBookstoreContest $v = null)
    {
        if ($v === null) {
            $this->setBookstoreId(NULL);
        } else {
            $this->setBookstoreId($v->getBookstoreId());
        }

        if ($v === null) {
            $this->setContestId(NULL);
        } else {
            $this->setContestId($v->getContestId());
        }

        $this->aBookstoreContest = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildBookstoreContest object, it will not be re-added.
        if ($v !== null) {
            $v->addBookstoreContestEntry($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildBookstoreContest object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildBookstoreContest The associated ChildBookstoreContest object.
     * @throws PropelException
     */
    public function getBookstoreContest(ConnectionInterface $con = null)
    {
        if ($this->aBookstoreContest === null && ($this->bookstore_id !== null && $this->contest_id !== null)) {
            $this->aBookstoreContest = ChildBookstoreContestQuery::create()->findPk(array($this->bookstore_id, $this->contest_id), $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aBookstoreContest->addBookstoreContestEntries($this);
             */
        }

        return $this->aBookstoreContest;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aBookstore) {
            $this->aBookstore->removeBookstoreContestEntry($this);
        }
        if (null !== $this->aCustomer) {
            $this->aCustomer->removeBookstoreContestEntry($this);
        }
        if (null !== $this->aBookstoreContest) {
            $this->aBookstoreContest->removeBookstoreContestEntry($this);
        }
        $this->bookstore_id = null;
        $this->contest_id = null;
        $this->customer_id = null;
        $this->entry_date = null;
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
        $this->aCustomer = null;
        $this->aBookstoreContest = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(BookstoreContestEntryTableMap::DEFAULT_STRING_FORMAT);
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
