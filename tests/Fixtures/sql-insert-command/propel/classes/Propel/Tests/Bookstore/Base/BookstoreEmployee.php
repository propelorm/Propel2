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
use Propel\Tests\Bookstore\BookstoreEmployee as ChildBookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount as ChildBookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery as ChildBookstoreEmployeeAccountQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery as ChildBookstoreEmployeeQuery;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;

/**
 * Base class that represents a row from the 'bookstore_employee' table.
 *
 * Hierarchical table to represent employees of a bookstore.
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class BookstoreEmployee implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookstoreEmployeeTableMap';


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
     * The value for the class_key field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $class_key;

    /**
     * The value for the name field.
     * @var        string
     */
    protected $name;

    /**
     * The value for the job_title field.
     * @var        string
     */
    protected $job_title;

    /**
     * The value for the supervisor_id field.
     * @var        int
     */
    protected $supervisor_id;

    /**
     * The value for the photo field.
     * @var        resource
     */
    protected $photo;

    /**
     * Whether the lazy-loaded $photo value has been loaded from database.
     * This is necessary to avoid repeated lookups if $photo column is NULL in the db.
     * @var boolean
     */
    protected $photo_isLoaded = false;

    /**
     * @var        ChildBookstoreEmployee
     */
    protected $aSupervisor;

    /**
     * @var        ObjectCollection|ChildBookstoreEmployee[] Collection to store aggregation of ChildBookstoreEmployee objects.
     */
    protected $collSubordinates;
    protected $collSubordinatesPartial;

    /**
     * @var        ChildBookstoreEmployeeAccount one-to-one related ChildBookstoreEmployeeAccount object
     */
    protected $singleBookstoreEmployeeAccount;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookstoreEmployee[]
     */
    protected $subordinatesScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->class_key = 0;
    }

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\BookstoreEmployee object.
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
     * Compares this with another <code>BookstoreEmployee</code> instance.  If
     * <code>obj</code> is an instance of <code>BookstoreEmployee</code>, delegates to
     * <code>equals(BookstoreEmployee)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|BookstoreEmployee The current object, for fluid interface
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
     * Employee ID number
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [class_key] column value.
     *
     * @return int
     */
    public function getClassKey()
    {
        return $this->class_key;
    }

    /**
     * Get the [name] column value.
     * Employee name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the [job_title] column value.
     * Employee job title
     * @return string
     */
    public function getJobTitle()
    {
        return $this->job_title;
    }

    /**
     * Get the [supervisor_id] column value.
     * Fkey to supervisor.
     * @return int
     */
    public function getSupervisorId()
    {
        return $this->supervisor_id;
    }

    /**
     * Get the [photo] column value.
     *
     * @param      ConnectionInterface $con An optional ConnectionInterface connection to use for fetching this lazy-loaded column.
     * @return resource
     */
    public function getPhoto(ConnectionInterface $con = null)
    {
        if (!$this->photo_isLoaded && $this->photo === null && !$this->isNew()) {
            $this->loadPhoto($con);
        }

        return $this->photo;
    }

    /**
     * Load the value for the lazy-loaded [photo] column.
     *
     * This method performs an additional query to return the value for
     * the [photo] column, since it is not populated by
     * the hydrate() method.
     *
     * @param      $con ConnectionInterface (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - any underlying error will be wrapped and re-thrown.
     */
    protected function loadPhoto(ConnectionInterface $con = null)
    {
        $c = $this->buildPkeyCriteria();
        $c->addSelectColumn(BookstoreEmployeeTableMap::COL_PHOTO);
        try {
            $dataFetcher = ChildBookstoreEmployeeQuery::create(null, $c)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
            $row = $dataFetcher->fetch();
            $dataFetcher->close();

        $firstColumn = $row ? current($row) : null;

            if ($firstColumn !== null) {
                $this->photo = fopen('php://memory', 'r+');
                fwrite($this->photo, $firstColumn);
                rewind($this->photo);
            } else {
                $this->photo = null;
            }
            $this->photo_isLoaded = true;
        } catch (Exception $e) {
            throw new PropelException("Error loading value for [photo] column on demand.", 0, $e);
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
            if ($this->class_key !== 0) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookstoreEmployeeTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookstoreEmployeeTableMap::translateFieldName('ClassKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->class_key = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : BookstoreEmployeeTableMap::translateFieldName('Name', TableMap::TYPE_PHPNAME, $indexType)];
            $this->name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : BookstoreEmployeeTableMap::translateFieldName('JobTitle', TableMap::TYPE_PHPNAME, $indexType)];
            $this->job_title = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : BookstoreEmployeeTableMap::translateFieldName('SupervisorId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->supervisor_id = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 5; // 5 = BookstoreEmployeeTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\BookstoreEmployee'), 0, $e);
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
        if ($this->aSupervisor !== null && $this->supervisor_id !== $this->aSupervisor->getId()) {
            $this->aSupervisor = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [id] column.
     * Employee ID number
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[BookstoreEmployeeTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [class_key] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function setClassKey($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->class_key !== $v) {
            $this->class_key = $v;
            $this->modifiedColumns[BookstoreEmployeeTableMap::COL_CLASS_KEY] = true;
        }

        return $this;
    } // setClassKey()

    /**
     * Set the value of [name] column.
     * Employee name
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function setName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->name !== $v) {
            $this->name = $v;
            $this->modifiedColumns[BookstoreEmployeeTableMap::COL_NAME] = true;
        }

        return $this;
    } // setName()

    /**
     * Set the value of [job_title] column.
     * Employee job title
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function setJobTitle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->job_title !== $v) {
            $this->job_title = $v;
            $this->modifiedColumns[BookstoreEmployeeTableMap::COL_JOB_TITLE] = true;
        }

        return $this;
    } // setJobTitle()

    /**
     * Set the value of [supervisor_id] column.
     * Fkey to supervisor.
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function setSupervisorId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->supervisor_id !== $v) {
            $this->supervisor_id = $v;
            $this->modifiedColumns[BookstoreEmployeeTableMap::COL_SUPERVISOR_ID] = true;
        }

        if ($this->aSupervisor !== null && $this->aSupervisor->getId() !== $v) {
            $this->aSupervisor = null;
        }

        return $this;
    } // setSupervisorId()

    /**
     * Set the value of [photo] column.
     *
     * @param  resource $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function setPhoto($v)
    {
        // explicitly set the is-loaded flag to true for this lazy load col;
        // it doesn't matter if the value is actually set or not (logic below) as
        // any attempt to set the value means that no db lookup should be performed
        // when the getPhoto() method is called.
        $this->photo_isLoaded = true;

        // Because BLOB columns are streams in PDO we have to assume that they are
        // always modified when a new value is passed in.  For example, the contents
        // of the stream itself may have changed externally.
        if (!is_resource($v) && $v !== null) {
            $this->photo = fopen('php://memory', 'r+');
            fwrite($this->photo, $v);
            rewind($this->photo);
        } else { // it's already a stream
            $this->photo = $v;
        }
        $this->modifiedColumns[BookstoreEmployeeTableMap::COL_PHOTO] = true;

        return $this;
    } // setPhoto()

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
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookstoreEmployeeQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        // Reset the photo lazy-load column
        $this->photo = null;
        $this->photo_isLoaded = false;

        if ($deep) {  // also de-associate any related objects?

            $this->aSupervisor = null;
            $this->collSubordinates = null;

            $this->singleBookstoreEmployeeAccount = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see BookstoreEmployee::setDeleted()
     * @see BookstoreEmployee::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookstoreEmployeeQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
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
                BookstoreEmployeeTableMap::addInstanceToPool($this);
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

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aSupervisor !== null) {
                if ($this->aSupervisor->isModified() || $this->aSupervisor->isNew()) {
                    $affectedRows += $this->aSupervisor->save($con);
                }
                $this->setSupervisor($this->aSupervisor);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                // Rewind the photo LOB column, since PDO does not rewind after inserting value.
                if ($this->photo !== null && is_resource($this->photo)) {
                    rewind($this->photo);
                }

                $this->resetModified();
            }

            if ($this->subordinatesScheduledForDeletion !== null) {
                if (!$this->subordinatesScheduledForDeletion->isEmpty()) {
                    foreach ($this->subordinatesScheduledForDeletion as $subordinate) {
                        // need to save related object because we set the relation to null
                        $subordinate->save($con);
                    }
                    $this->subordinatesScheduledForDeletion = null;
                }
            }

            if ($this->collSubordinates !== null) {
                foreach ($this->collSubordinates as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->singleBookstoreEmployeeAccount !== null) {
                if (!$this->singleBookstoreEmployeeAccount->isDeleted() && ($this->singleBookstoreEmployeeAccount->isNew() || $this->singleBookstoreEmployeeAccount->isModified())) {
                    $affectedRows += $this->singleBookstoreEmployeeAccount->save($con);
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

        $this->modifiedColumns[BookstoreEmployeeTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . BookstoreEmployeeTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_CLASS_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'CLASS_KEY';
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'NAME';
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_JOB_TITLE)) {
            $modifiedColumns[':p' . $index++]  = 'JOB_TITLE';
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID)) {
            $modifiedColumns[':p' . $index++]  = 'SUPERVISOR_ID';
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_PHOTO)) {
            $modifiedColumns[':p' . $index++]  = 'PHOTO';
        }

        $sql = sprintf(
            'INSERT INTO bookstore_employee (%s) VALUES (%s)',
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
                    case 'CLASS_KEY':
                        $stmt->bindValue($identifier, $this->class_key, PDO::PARAM_INT);
                        break;
                    case 'NAME':
                        $stmt->bindValue($identifier, $this->name, PDO::PARAM_STR);
                        break;
                    case 'JOB_TITLE':
                        $stmt->bindValue($identifier, $this->job_title, PDO::PARAM_STR);
                        break;
                    case 'SUPERVISOR_ID':
                        $stmt->bindValue($identifier, $this->supervisor_id, PDO::PARAM_INT);
                        break;
                    case 'PHOTO':
                        if (is_resource($this->photo)) {
                            rewind($this->photo);
                        }
                        $stmt->bindValue($identifier, $this->photo, PDO::PARAM_LOB);
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
        $pos = BookstoreEmployeeTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getClassKey();
                break;
            case 2:
                return $this->getName();
                break;
            case 3:
                return $this->getJobTitle();
                break;
            case 4:
                return $this->getSupervisorId();
                break;
            case 5:
                return $this->getPhoto();
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
        if (isset($alreadyDumpedObjects['BookstoreEmployee'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['BookstoreEmployee'][$this->getPrimaryKey()] = true;
        $keys = BookstoreEmployeeTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getClassKey(),
            $keys[2] => $this->getName(),
            $keys[3] => $this->getJobTitle(),
            $keys[4] => $this->getSupervisorId(),
            $keys[5] => ($includeLazyLoadColumns) ? $this->getPhoto() : null,
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aSupervisor) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookstoreEmployee';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'bookstore_employee';
                        break;
                    default:
                        $key = 'BookstoreEmployee';
                }

                $result[$key] = $this->aSupervisor->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collSubordinates) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookstoreEmployees';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'bookstore_employees';
                        break;
                    default:
                        $key = 'BookstoreEmployees';
                }

                $result[$key] = $this->collSubordinates->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->singleBookstoreEmployeeAccount) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookstoreEmployeeAccount';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'bookstore_employee_account';
                        break;
                    default:
                        $key = 'BookstoreEmployeeAccount';
                }

                $result[$key] = $this->singleBookstoreEmployeeAccount->toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, true);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookstoreEmployeeTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setClassKey($value);
                break;
            case 2:
                $this->setName($value);
                break;
            case 3:
                $this->setJobTitle($value);
                break;
            case 4:
                $this->setSupervisorId($value);
                break;
            case 5:
                $this->setPhoto($value);
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
        $keys = BookstoreEmployeeTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setClassKey($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setName($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setJobTitle($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setSupervisorId($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setPhoto($arr[$keys[5]]);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object, for fluid interface
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
        $criteria = new Criteria(BookstoreEmployeeTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_ID)) {
            $criteria->add(BookstoreEmployeeTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_CLASS_KEY)) {
            $criteria->add(BookstoreEmployeeTableMap::COL_CLASS_KEY, $this->class_key);
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_NAME)) {
            $criteria->add(BookstoreEmployeeTableMap::COL_NAME, $this->name);
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_JOB_TITLE)) {
            $criteria->add(BookstoreEmployeeTableMap::COL_JOB_TITLE, $this->job_title);
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID)) {
            $criteria->add(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, $this->supervisor_id);
        }
        if ($this->isColumnModified(BookstoreEmployeeTableMap::COL_PHOTO)) {
            $criteria->add(BookstoreEmployeeTableMap::COL_PHOTO, $this->photo);
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
        $criteria = new Criteria(BookstoreEmployeeTableMap::DATABASE_NAME);
        $criteria->add(BookstoreEmployeeTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\BookstoreEmployee (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setClassKey($this->getClassKey());
        $copyObj->setName($this->getName());
        $copyObj->setJobTitle($this->getJobTitle());
        $copyObj->setSupervisorId($this->getSupervisorId());
        $copyObj->setPhoto($this->getPhoto());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getSubordinates() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addSubordinate($relObj->copy($deepCopy));
                }
            }

            $relObj = $this->getBookstoreEmployeeAccount();
            if ($relObj) {
                $copyObj->setBookstoreEmployeeAccount($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\BookstoreEmployee Clone of current object.
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
     * Declares an association between this object and a ChildBookstoreEmployee object.
     *
     * @param  ChildBookstoreEmployee $v
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     * @throws PropelException
     */
    public function setSupervisor(ChildBookstoreEmployee $v = null)
    {
        if ($v === null) {
            $this->setSupervisorId(NULL);
        } else {
            $this->setSupervisorId($v->getId());
        }

        $this->aSupervisor = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildBookstoreEmployee object, it will not be re-added.
        if ($v !== null) {
            $v->addSubordinate($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildBookstoreEmployee object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildBookstoreEmployee The associated ChildBookstoreEmployee object.
     * @throws PropelException
     */
    public function getSupervisor(ConnectionInterface $con = null)
    {
        if ($this->aSupervisor === null && ($this->supervisor_id !== null)) {
            $this->aSupervisor = ChildBookstoreEmployeeQuery::create()->findPk($this->supervisor_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aSupervisor->addSubordinates($this);
             */
        }

        return $this->aSupervisor;
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
        if ('Subordinate' == $relationName) {
            return $this->initSubordinates();
        }
    }

    /**
     * Clears out the collSubordinates collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addSubordinates()
     */
    public function clearSubordinates()
    {
        $this->collSubordinates = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collSubordinates collection loaded partially.
     */
    public function resetPartialSubordinates($v = true)
    {
        $this->collSubordinatesPartial = $v;
    }

    /**
     * Initializes the collSubordinates collection.
     *
     * By default this just sets the collSubordinates collection to an empty array (like clearcollSubordinates());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initSubordinates($overrideExisting = true)
    {
        if (null !== $this->collSubordinates && !$overrideExisting) {
            return;
        }
        $this->collSubordinates = new ObjectCollection();
        $this->collSubordinates->setModel('\Propel\Tests\Bookstore\BookstoreEmployee');
    }

    /**
     * Gets an array of ChildBookstoreEmployee objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookstoreEmployee is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBookstoreEmployee[] List of ChildBookstoreEmployee objects
     * @throws PropelException
     */
    public function getSubordinates(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collSubordinatesPartial && !$this->isNew();
        if (null === $this->collSubordinates || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collSubordinates) {
                // return empty collection
                $this->initSubordinates();
            } else {
                $collSubordinates = ChildBookstoreEmployeeQuery::create(null, $criteria)
                    ->filterBySupervisor($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collSubordinatesPartial && count($collSubordinates)) {
                        $this->initSubordinates(false);

                        foreach ($collSubordinates as $obj) {
                            if (false == $this->collSubordinates->contains($obj)) {
                                $this->collSubordinates->append($obj);
                            }
                        }

                        $this->collSubordinatesPartial = true;
                    }

                    return $collSubordinates;
                }

                if ($partial && $this->collSubordinates) {
                    foreach ($this->collSubordinates as $obj) {
                        if ($obj->isNew()) {
                            $collSubordinates[] = $obj;
                        }
                    }
                }

                $this->collSubordinates = $collSubordinates;
                $this->collSubordinatesPartial = false;
            }
        }

        return $this->collSubordinates;
    }

    /**
     * Sets a collection of ChildBookstoreEmployee objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $subordinates A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBookstoreEmployee The current object (for fluent API support)
     */
    public function setSubordinates(Collection $subordinates, ConnectionInterface $con = null)
    {
        /** @var ChildBookstoreEmployee[] $subordinatesToDelete */
        $subordinatesToDelete = $this->getSubordinates(new Criteria(), $con)->diff($subordinates);


        $this->subordinatesScheduledForDeletion = $subordinatesToDelete;

        foreach ($subordinatesToDelete as $subordinateRemoved) {
            $subordinateRemoved->setSupervisor(null);
        }

        $this->collSubordinates = null;
        foreach ($subordinates as $subordinate) {
            $this->addSubordinate($subordinate);
        }

        $this->collSubordinates = $subordinates;
        $this->collSubordinatesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related BookstoreEmployee objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related BookstoreEmployee objects.
     * @throws PropelException
     */
    public function countSubordinates(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collSubordinatesPartial && !$this->isNew();
        if (null === $this->collSubordinates || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collSubordinates) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getSubordinates());
            }

            $query = ChildBookstoreEmployeeQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterBySupervisor($this)
                ->count($con);
        }

        return count($this->collSubordinates);
    }

    /**
     * Method called to associate a \Propel\Tests\Bookstore\Base\BookstoreEmployee object to this object
     * through the \Propel\Tests\Bookstore\Base\BookstoreEmployee foreign key attribute.
     *
     * @param  \Propel\Tests\Bookstore\Base\BookstoreEmployee $l \Propel\Tests\Bookstore\Base\BookstoreEmployee
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     */
    public function addSubordinate(\Propel\Tests\Bookstore\Base\BookstoreEmployee $l)
    {
        if ($this->collSubordinates === null) {
            $this->initSubordinates();
            $this->collSubordinatesPartial = true;
        }

        if (!$this->collSubordinates->contains($l)) {
            $this->doAddSubordinate($l);
        }

        return $this;
    }

    /**
     * @param \Propel\Tests\Bookstore\Base\BookstoreEmployee $subordinate The \Propel\Tests\Bookstore\Base\BookstoreEmployee object to add.
     */
    protected function doAddSubordinate(\Propel\Tests\Bookstore\Base\BookstoreEmployee $subordinate)
    {
        $this->collSubordinates[]= $subordinate;
        $subordinate->setSupervisor($this);
    }

    /**
     * @param  \Propel\Tests\Bookstore\Base\BookstoreEmployee $subordinate The \Propel\Tests\Bookstore\Base\BookstoreEmployee object to remove.
     * @return $this|ChildBookstoreEmployee The current object (for fluent API support)
     */
    public function removeSubordinate(\Propel\Tests\Bookstore\Base\BookstoreEmployee $subordinate)
    {
        if ($this->getSubordinates()->contains($subordinate)) {
            $pos = $this->collSubordinates->search($subordinate);
            $this->collSubordinates->remove($pos);
            if (null === $this->subordinatesScheduledForDeletion) {
                $this->subordinatesScheduledForDeletion = clone $this->collSubordinates;
                $this->subordinatesScheduledForDeletion->clear();
            }
            $this->subordinatesScheduledForDeletion[]= $subordinate;
            $subordinate->setSupervisor(null);
        }

        return $this;
    }

    /**
     * Gets a single ChildBookstoreEmployeeAccount object, which is related to this object by a one-to-one relationship.
     *
     * @param  ConnectionInterface $con optional connection object
     * @return ChildBookstoreEmployeeAccount
     * @throws PropelException
     */
    public function getBookstoreEmployeeAccount(ConnectionInterface $con = null)
    {

        if ($this->singleBookstoreEmployeeAccount === null && !$this->isNew()) {
            $this->singleBookstoreEmployeeAccount = ChildBookstoreEmployeeAccountQuery::create()->findPk($this->getPrimaryKey(), $con);
        }

        return $this->singleBookstoreEmployeeAccount;
    }

    /**
     * Sets a single ChildBookstoreEmployeeAccount object as related to this object by a one-to-one relationship.
     *
     * @param  ChildBookstoreEmployeeAccount $v ChildBookstoreEmployeeAccount
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployee The current object (for fluent API support)
     * @throws PropelException
     */
    public function setBookstoreEmployeeAccount(ChildBookstoreEmployeeAccount $v = null)
    {
        $this->singleBookstoreEmployeeAccount = $v;

        // Make sure that that the passed-in ChildBookstoreEmployeeAccount isn't already associated with this object
        if ($v !== null && $v->getBookstoreEmployee(null, false) === null) {
            $v->setBookstoreEmployee($this);
        }

        return $this;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aSupervisor) {
            $this->aSupervisor->removeSubordinate($this);
        }
        $this->id = null;
        $this->class_key = null;
        $this->name = null;
        $this->job_title = null;
        $this->supervisor_id = null;
        $this->photo = null;
        $this->photo_isLoaded = false;
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
            if ($this->collSubordinates) {
                foreach ($this->collSubordinates as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->singleBookstoreEmployeeAccount) {
                $this->singleBookstoreEmployeeAccount->clearAllReferences($deep);
            }
        } // if ($deep)

        $this->collSubordinates = null;
        $this->singleBookstoreEmployeeAccount = null;
        $this->aSupervisor = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(BookstoreEmployeeTableMap::DEFAULT_STRING_FORMAT);
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
