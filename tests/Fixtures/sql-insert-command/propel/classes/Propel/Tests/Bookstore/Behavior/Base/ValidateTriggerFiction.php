<?php

namespace Propel\Tests\Bookstore\Behavior\Base;

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
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBook as ChildValidateTriggerBook;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery as ChildValidateTriggerBookQuery;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction as ChildValidateTriggerFiction;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionI18n as ChildValidateTriggerFictionI18n;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionI18nQuery as ChildValidateTriggerFictionI18nQuery;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionQuery as ChildValidateTriggerFictionQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateTriggerFictionTableMap;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;

/**
 * Base class that represents a row from the 'validate_trigger_fiction' table.
 *
 *
 *
* @package    propel.generator.Propel.Tests.Bookstore.Behavior.Base
*/
abstract class ValidateTriggerFiction extends ChildValidateTriggerBook implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Behavior\\Map\\ValidateTriggerFictionTableMap';


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
     * The value for the foo field.
     * @var        string
     */
    protected $foo;

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the isbn field.
     * @var        string
     */
    protected $isbn;

    /**
     * The value for the price field.
     * @var        double
     */
    protected $price;

    /**
     * The value for the publisher_id field.
     * @var        int
     */
    protected $publisher_id;

    /**
     * The value for the author_id field.
     * @var        int
     */
    protected $author_id;

    /**
     * @var        ChildValidateTriggerBook
     */
    protected $aValidateTriggerBook;

    /**
     * @var        ObjectCollection|ChildValidateTriggerFictionI18n[] Collection to store aggregation of ChildValidateTriggerFictionI18n objects.
     */
    protected $collValidateTriggerFictionI18ns;
    protected $collValidateTriggerFictionI18nsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // validate behavior

    /**
     * Flag to prevent endless validation loop, if this object is referenced
     * by another object which falls in this transaction.
     * @var        boolean
     */
    protected $alreadyInValidation = false;

    /**
     * ConstraintViolationList object
     *
     * @see     http://api.symfony.com/2.0/Symfony/Component/Validator/ConstraintViolationList.html
     * @var     ConstraintViolationList
     */
    protected $validationFailures;

    // i18n behavior

    /**
     * Current locale
     * @var        string
     */
    protected $currentLocale = 'en_US';

    /**
     * Current translation objects
     * @var        array[ChildValidateTriggerFictionI18n]
     */
    protected $currentTranslations;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildValidateTriggerFictionI18n[]
     */
    protected $validateTriggerFictionI18nsScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Behavior\Base\ValidateTriggerFiction object.
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
     * Compares this with another <code>ValidateTriggerFiction</code> instance.  If
     * <code>obj</code> is an instance of <code>ValidateTriggerFiction</code>, delegates to
     * <code>equals(ValidateTriggerFiction)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|ValidateTriggerFiction The current object, for fluid interface
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
     * Get the [foo] column value.
     *
     * @return string
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     * Get the [id] column value.
     * Book Id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [isbn] column value.
     * ISBN Number
     * @return string
     */
    public function getISBN()
    {
        return $this->isbn;
    }

    /**
     * Get the [price] column value.
     * Price of the book.
     * @return double
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Get the [publisher_id] column value.
     * Foreign Key Publisher
     * @return int
     */
    public function getPublisherId()
    {
        return $this->publisher_id;
    }

    /**
     * Get the [author_id] column value.
     * Foreign Key Author
     * @return int
     */
    public function getAuthorId()
    {
        return $this->author_id;
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : ValidateTriggerFictionTableMap::translateFieldName('Foo', TableMap::TYPE_PHPNAME, $indexType)];
            $this->foo = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : ValidateTriggerFictionTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : ValidateTriggerFictionTableMap::translateFieldName('ISBN', TableMap::TYPE_PHPNAME, $indexType)];
            $this->isbn = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : ValidateTriggerFictionTableMap::translateFieldName('Price', TableMap::TYPE_PHPNAME, $indexType)];
            $this->price = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : ValidateTriggerFictionTableMap::translateFieldName('PublisherId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->publisher_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : ValidateTriggerFictionTableMap::translateFieldName('AuthorId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->author_id = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = ValidateTriggerFictionTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerFiction'), 0, $e);
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
        if ($this->aValidateTriggerBook !== null && $this->id !== $this->aValidateTriggerBook->getId()) {
            $this->aValidateTriggerBook = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [foo] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function setFoo($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->foo !== $v) {
            $this->foo = $v;
            $this->modifiedColumns[ValidateTriggerFictionTableMap::COL_FOO] = true;
        }

        return $this;
    } // setFoo()

    /**
     * Set the value of [id] column.
     * Book Id
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[ValidateTriggerFictionTableMap::COL_ID] = true;
        }

        if ($this->aValidateTriggerBook !== null && $this->aValidateTriggerBook->getId() !== $v) {
            $this->aValidateTriggerBook = null;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [isbn] column.
     * ISBN Number
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function setISBN($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->isbn !== $v) {
            $this->isbn = $v;
            $this->modifiedColumns[ValidateTriggerFictionTableMap::COL_ISBN] = true;
        }

        return $this;
    } // setISBN()

    /**
     * Set the value of [price] column.
     * Price of the book.
     * @param  double $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function setPrice($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->price !== $v) {
            $this->price = $v;
            $this->modifiedColumns[ValidateTriggerFictionTableMap::COL_PRICE] = true;
        }

        return $this;
    } // setPrice()

    /**
     * Set the value of [publisher_id] column.
     * Foreign Key Publisher
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function setPublisherId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->publisher_id !== $v) {
            $this->publisher_id = $v;
            $this->modifiedColumns[ValidateTriggerFictionTableMap::COL_PUBLISHER_ID] = true;
        }

        return $this;
    } // setPublisherId()

    /**
     * Set the value of [author_id] column.
     * Foreign Key Author
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function setAuthorId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->author_id !== $v) {
            $this->author_id = $v;
            $this->modifiedColumns[ValidateTriggerFictionTableMap::COL_AUTHOR_ID] = true;
        }

        return $this;
    } // setAuthorId()

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
            $con = Propel::getServiceContainer()->getReadConnection(ValidateTriggerFictionTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildValidateTriggerFictionQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aValidateTriggerBook = null;
            $this->collValidateTriggerFictionI18ns = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see ValidateTriggerFiction::setDeleted()
     * @see ValidateTriggerFiction::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerFictionTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildValidateTriggerFictionQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                // concrete_inheritance behavior
                $this->getParentOrCreate($con)->delete($con);

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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerFictionTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $isInsert = $this->isNew();
            $ret = $this->preSave($con);
            // concrete_inheritance behavior
            $parent = $this->getSyncParent($con);
            $parent->save($con);
            $this->setPrimaryKey($parent->getPrimaryKey());

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
                ValidateTriggerFictionTableMap::addInstanceToPool($this);
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

            if ($this->aValidateTriggerBook !== null) {
                if ($this->aValidateTriggerBook->isModified() || $this->aValidateTriggerBook->isNew()) {
                    $affectedRows += $this->aValidateTriggerBook->save($con);
                }
                $this->setValidateTriggerBook($this->aValidateTriggerBook);
            }

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

            if ($this->validateTriggerFictionI18nsScheduledForDeletion !== null) {
                if (!$this->validateTriggerFictionI18nsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionI18nQuery::create()
                        ->filterByPrimaryKeys($this->validateTriggerFictionI18nsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->validateTriggerFictionI18nsScheduledForDeletion = null;
                }
            }

            if ($this->collValidateTriggerFictionI18ns !== null) {
                foreach ($this->collValidateTriggerFictionI18ns as $referrerFK) {
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


         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_FOO)) {
            $modifiedColumns[':p' . $index++]  = 'FOO';
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_ISBN)) {
            $modifiedColumns[':p' . $index++]  = 'ISBN';
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_PRICE)) {
            $modifiedColumns[':p' . $index++]  = 'PRICE';
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_PUBLISHER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'PUBLISHER_ID';
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_AUTHOR_ID)) {
            $modifiedColumns[':p' . $index++]  = 'AUTHOR_ID';
        }

        $sql = sprintf(
            'INSERT INTO validate_trigger_fiction (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'FOO':
                        $stmt->bindValue($identifier, $this->foo, PDO::PARAM_STR);
                        break;
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'ISBN':
                        $stmt->bindValue($identifier, $this->isbn, PDO::PARAM_STR);
                        break;
                    case 'PRICE':
                        $stmt->bindValue($identifier, $this->price, PDO::PARAM_STR);
                        break;
                    case 'PUBLISHER_ID':
                        $stmt->bindValue($identifier, $this->publisher_id, PDO::PARAM_INT);
                        break;
                    case 'AUTHOR_ID':
                        $stmt->bindValue($identifier, $this->author_id, PDO::PARAM_INT);
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
        $pos = ValidateTriggerFictionTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getFoo();
                break;
            case 1:
                return $this->getId();
                break;
            case 2:
                return $this->getISBN();
                break;
            case 3:
                return $this->getPrice();
                break;
            case 4:
                return $this->getPublisherId();
                break;
            case 5:
                return $this->getAuthorId();
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
        if (isset($alreadyDumpedObjects['ValidateTriggerFiction'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['ValidateTriggerFiction'][$this->getPrimaryKey()] = true;
        $keys = ValidateTriggerFictionTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getFoo(),
            $keys[1] => $this->getId(),
            $keys[2] => $this->getISBN(),
            $keys[3] => $this->getPrice(),
            $keys[4] => $this->getPublisherId(),
            $keys[5] => $this->getAuthorId(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aValidateTriggerBook) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'validateTriggerBook';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'validate_trigger_book';
                        break;
                    default:
                        $key = 'ValidateTriggerBook';
                }

                $result[$key] = $this->aValidateTriggerBook->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collValidateTriggerFictionI18ns) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'validateTriggerFictionI18ns';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'validate_trigger_fiction_i18ns';
                        break;
                    default:
                        $key = 'ValidateTriggerFictionI18ns';
                }

                $result[$key] = $this->collValidateTriggerFictionI18ns->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = ValidateTriggerFictionTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setFoo($value);
                break;
            case 1:
                $this->setId($value);
                break;
            case 2:
                $this->setISBN($value);
                break;
            case 3:
                $this->setPrice($value);
                break;
            case 4:
                $this->setPublisherId($value);
                break;
            case 5:
                $this->setAuthorId($value);
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
        $keys = ValidateTriggerFictionTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setFoo($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setISBN($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setPrice($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setPublisherId($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setAuthorId($arr[$keys[5]]);
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
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object, for fluid interface
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
        $criteria = new Criteria(ValidateTriggerFictionTableMap::DATABASE_NAME);

        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_FOO)) {
            $criteria->add(ValidateTriggerFictionTableMap::COL_FOO, $this->foo);
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_ID)) {
            $criteria->add(ValidateTriggerFictionTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_ISBN)) {
            $criteria->add(ValidateTriggerFictionTableMap::COL_ISBN, $this->isbn);
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_PRICE)) {
            $criteria->add(ValidateTriggerFictionTableMap::COL_PRICE, $this->price);
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_PUBLISHER_ID)) {
            $criteria->add(ValidateTriggerFictionTableMap::COL_PUBLISHER_ID, $this->publisher_id);
        }
        if ($this->isColumnModified(ValidateTriggerFictionTableMap::COL_AUTHOR_ID)) {
            $criteria->add(ValidateTriggerFictionTableMap::COL_AUTHOR_ID, $this->author_id);
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
        $criteria = new Criteria(ValidateTriggerFictionTableMap::DATABASE_NAME);
        $criteria->add(ValidateTriggerFictionTableMap::COL_ID, $this->id);

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

        $validPrimaryKeyFKs = 1;
        $primaryKeyFKs = [];

        //relation validate_trigger_fiction_fk_c65579 to table validate_trigger_book
        if ($this->aValidateTriggerBook && $hash = spl_object_hash($this->aValidateTriggerBook)) {
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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setFoo($this->getFoo());
        $copyObj->setId($this->getId());
        $copyObj->setISBN($this->getISBN());
        $copyObj->setPrice($this->getPrice());
        $copyObj->setPublisherId($this->getPublisherId());
        $copyObj->setAuthorId($this->getAuthorId());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getValidateTriggerFictionI18ns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addValidateTriggerFictionI18n($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

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
     * @return \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction Clone of current object.
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
     * Declares an association between this object and a ChildValidateTriggerBook object.
     *
     * @param  ChildValidateTriggerBook $v
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     * @throws PropelException
     */
    public function setValidateTriggerBook(ChildValidateTriggerBook $v = null)
    {
        if ($v === null) {
            $this->setId(NULL);
        } else {
            $this->setId($v->getId());
        }

        $this->aValidateTriggerBook = $v;

        // Add binding for other direction of this 1:1 relationship.
        if ($v !== null) {
            $v->setValidateTriggerFiction($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildValidateTriggerBook object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildValidateTriggerBook The associated ChildValidateTriggerBook object.
     * @throws PropelException
     */
    public function getValidateTriggerBook(ConnectionInterface $con = null)
    {
        if ($this->aValidateTriggerBook === null && ($this->id !== null)) {
            $this->aValidateTriggerBook = ChildValidateTriggerBookQuery::create()->findPk($this->id, $con);
            // Because this foreign key represents a one-to-one relationship, we will create a bi-directional association.
            $this->aValidateTriggerBook->setValidateTriggerFiction($this);
        }

        return $this->aValidateTriggerBook;
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
        if ('ValidateTriggerFictionI18n' == $relationName) {
            return $this->initValidateTriggerFictionI18ns();
        }
    }

    /**
     * Clears out the collValidateTriggerFictionI18ns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addValidateTriggerFictionI18ns()
     */
    public function clearValidateTriggerFictionI18ns()
    {
        $this->collValidateTriggerFictionI18ns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collValidateTriggerFictionI18ns collection loaded partially.
     */
    public function resetPartialValidateTriggerFictionI18ns($v = true)
    {
        $this->collValidateTriggerFictionI18nsPartial = $v;
    }

    /**
     * Initializes the collValidateTriggerFictionI18ns collection.
     *
     * By default this just sets the collValidateTriggerFictionI18ns collection to an empty array (like clearcollValidateTriggerFictionI18ns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initValidateTriggerFictionI18ns($overrideExisting = true)
    {
        if (null !== $this->collValidateTriggerFictionI18ns && !$overrideExisting) {
            return;
        }
        $this->collValidateTriggerFictionI18ns = new ObjectCollection();
        $this->collValidateTriggerFictionI18ns->setModel('\Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionI18n');
    }

    /**
     * Gets an array of ChildValidateTriggerFictionI18n objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildValidateTriggerFiction is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildValidateTriggerFictionI18n[] List of ChildValidateTriggerFictionI18n objects
     * @throws PropelException
     */
    public function getValidateTriggerFictionI18ns(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collValidateTriggerFictionI18nsPartial && !$this->isNew();
        if (null === $this->collValidateTriggerFictionI18ns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collValidateTriggerFictionI18ns) {
                // return empty collection
                $this->initValidateTriggerFictionI18ns();
            } else {
                $collValidateTriggerFictionI18ns = ChildValidateTriggerFictionI18nQuery::create(null, $criteria)
                    ->filterByValidateTriggerFiction($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collValidateTriggerFictionI18nsPartial && count($collValidateTriggerFictionI18ns)) {
                        $this->initValidateTriggerFictionI18ns(false);

                        foreach ($collValidateTriggerFictionI18ns as $obj) {
                            if (false == $this->collValidateTriggerFictionI18ns->contains($obj)) {
                                $this->collValidateTriggerFictionI18ns->append($obj);
                            }
                        }

                        $this->collValidateTriggerFictionI18nsPartial = true;
                    }

                    return $collValidateTriggerFictionI18ns;
                }

                if ($partial && $this->collValidateTriggerFictionI18ns) {
                    foreach ($this->collValidateTriggerFictionI18ns as $obj) {
                        if ($obj->isNew()) {
                            $collValidateTriggerFictionI18ns[] = $obj;
                        }
                    }
                }

                $this->collValidateTriggerFictionI18ns = $collValidateTriggerFictionI18ns;
                $this->collValidateTriggerFictionI18nsPartial = false;
            }
        }

        return $this->collValidateTriggerFictionI18ns;
    }

    /**
     * Sets a collection of ChildValidateTriggerFictionI18n objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $validateTriggerFictionI18ns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildValidateTriggerFiction The current object (for fluent API support)
     */
    public function setValidateTriggerFictionI18ns(Collection $validateTriggerFictionI18ns, ConnectionInterface $con = null)
    {
        /** @var ChildValidateTriggerFictionI18n[] $validateTriggerFictionI18nsToDelete */
        $validateTriggerFictionI18nsToDelete = $this->getValidateTriggerFictionI18ns(new Criteria(), $con)->diff($validateTriggerFictionI18ns);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->validateTriggerFictionI18nsScheduledForDeletion = clone $validateTriggerFictionI18nsToDelete;

        foreach ($validateTriggerFictionI18nsToDelete as $validateTriggerFictionI18nRemoved) {
            $validateTriggerFictionI18nRemoved->setValidateTriggerFiction(null);
        }

        $this->collValidateTriggerFictionI18ns = null;
        foreach ($validateTriggerFictionI18ns as $validateTriggerFictionI18n) {
            $this->addValidateTriggerFictionI18n($validateTriggerFictionI18n);
        }

        $this->collValidateTriggerFictionI18ns = $validateTriggerFictionI18ns;
        $this->collValidateTriggerFictionI18nsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ValidateTriggerFictionI18n objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ValidateTriggerFictionI18n objects.
     * @throws PropelException
     */
    public function countValidateTriggerFictionI18ns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collValidateTriggerFictionI18nsPartial && !$this->isNew();
        if (null === $this->collValidateTriggerFictionI18ns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collValidateTriggerFictionI18ns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getValidateTriggerFictionI18ns());
            }

            $query = ChildValidateTriggerFictionI18nQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByValidateTriggerFiction($this)
                ->count($con);
        }

        return count($this->collValidateTriggerFictionI18ns);
    }

    /**
     * Method called to associate a ChildValidateTriggerFictionI18n object to this object
     * through the ChildValidateTriggerFictionI18n foreign key attribute.
     *
     * @param  ChildValidateTriggerFictionI18n $l ChildValidateTriggerFictionI18n
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction The current object (for fluent API support)
     */
    public function addValidateTriggerFictionI18n(ChildValidateTriggerFictionI18n $l)
    {
        if ($l && $locale = $l->getLocale()) {
            $this->setLocale($locale);
            $this->currentTranslations[$locale] = $l;
        }
        if ($this->collValidateTriggerFictionI18ns === null) {
            $this->initValidateTriggerFictionI18ns();
            $this->collValidateTriggerFictionI18nsPartial = true;
        }

        if (!$this->collValidateTriggerFictionI18ns->contains($l)) {
            $this->doAddValidateTriggerFictionI18n($l);
        }

        return $this;
    }

    /**
     * @param ChildValidateTriggerFictionI18n $validateTriggerFictionI18n The ChildValidateTriggerFictionI18n object to add.
     */
    protected function doAddValidateTriggerFictionI18n(ChildValidateTriggerFictionI18n $validateTriggerFictionI18n)
    {
        $this->collValidateTriggerFictionI18ns[]= $validateTriggerFictionI18n;
        $validateTriggerFictionI18n->setValidateTriggerFiction($this);
    }

    /**
     * @param  ChildValidateTriggerFictionI18n $validateTriggerFictionI18n The ChildValidateTriggerFictionI18n object to remove.
     * @return $this|ChildValidateTriggerFiction The current object (for fluent API support)
     */
    public function removeValidateTriggerFictionI18n(ChildValidateTriggerFictionI18n $validateTriggerFictionI18n)
    {
        if ($this->getValidateTriggerFictionI18ns()->contains($validateTriggerFictionI18n)) {
            $pos = $this->collValidateTriggerFictionI18ns->search($validateTriggerFictionI18n);
            $this->collValidateTriggerFictionI18ns->remove($pos);
            if (null === $this->validateTriggerFictionI18nsScheduledForDeletion) {
                $this->validateTriggerFictionI18nsScheduledForDeletion = clone $this->collValidateTriggerFictionI18ns;
                $this->validateTriggerFictionI18nsScheduledForDeletion->clear();
            }
            $this->validateTriggerFictionI18nsScheduledForDeletion[]= clone $validateTriggerFictionI18n;
            $validateTriggerFictionI18n->setValidateTriggerFiction(null);
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
        if (null !== $this->aValidateTriggerBook) {
            $this->aValidateTriggerBook->removeValidateTriggerFiction($this);
        }
        $this->foo = null;
        $this->id = null;
        $this->isbn = null;
        $this->price = null;
        $this->publisher_id = null;
        $this->author_id = null;
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
            if ($this->collValidateTriggerFictionI18ns) {
                foreach ($this->collValidateTriggerFictionI18ns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        // i18n behavior
        $this->currentLocale = 'en_US';
        $this->currentTranslations = null;

        $this->collValidateTriggerFictionI18ns = null;
        $this->aValidateTriggerBook = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(ValidateTriggerFictionTableMap::DEFAULT_STRING_FORMAT);
    }

    // concrete_inheritance behavior

    /**
     * Get or Create the parent ChildValidateTriggerBook object of the current object
     *
     * @return    ChildValidateTriggerBook The parent object
     */
    public function getParentOrCreate($con = null)
    {
        if ($this->isNew()) {
            if ($this->isPrimaryKeyNull()) {
                $parent = new ChildValidateTriggerBook();
                $parent->setDescendantClass('Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction');

                return $parent;
            } else {
                $parent = \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery::create()->findPk($this->getPrimaryKey(), $con);
                if (null === $parent || null !== $parent->getDescendantClass()) {
                    $parent = new ChildValidateTriggerBook();
                    $parent->setPrimaryKey($this->getPrimaryKey());
                    $parent->setDescendantClass('Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction');
                }

                return $parent;
            }
        } else {
            return ChildValidateTriggerBookQuery::create()->findPk($this->getPrimaryKey(), $con);
        }
    }

    /**
     * Create or Update the parent ValidateTriggerBook object
     * And return its primary key
     *
     * @return    int The primary key of the parent object
     */
    public function getSyncParent($con = null)
    {
        $parent = $this->getParentOrCreate($con);
        $parent->setISBN($this->getISBN());
        $parent->setPrice($this->getPrice());
        $parent->setPublisherId($this->getPublisherId());
        $parent->setAuthorId($this->getAuthorId());

        return $parent;
    }

    // validate behavior

    /**
     * Configure validators constraints. The Validator object uses this method
     * to perform object validation.
     *
     * @param ClassMetadata $metadata
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('isbn', new Regex(array ('pattern' => '/[^\\d-]+/','match' => false,'message' => 'Please enter a valid ISBN',)));
    }

    /**
     * Validates the object and all objects related to this table.
     *
     * @see        getValidationFailures()
     * @param      object $validator A Validator class instance
     * @return     boolean Whether all objects pass validation.
     */
    public function validate(Validator $validator = null)
    {
        if (null === $validator) {
            $validator = new Validator(new ClassMetadataFactory(new StaticMethodLoader()), new ConstraintValidatorFactory(), new DefaultTranslator());
        }

        $failureMap = new ConstraintViolationList();

        if (!$this->alreadyInValidation) {
            $this->alreadyInValidation = true;
            $retval = null;

            // We call the validate method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            // If validate() method exists, the validate-behavior is configured for related object
            if (method_exists($this->aValidateTriggerBook, 'validate')) {
                if (!$this->aValidateTriggerBook->validate($validator)) {
                    $failureMap->addAll($this->aValidateTriggerBook->getValidationFailures());
                }
            }

            $retval = $validator->validate($this);
            if (count($retval) > 0) {
                $failureMap->addAll($retval);
            }

            if (null !== $this->collValidateTriggerFictionI18ns) {
                foreach ($this->collValidateTriggerFictionI18ns as $referrerFK) {
                    if (method_exists($referrerFK, 'validate')) {
                        if (!$referrerFK->validate($validator)) {
                            $failureMap->addAll($referrerFK->getValidationFailures());
                        }
                    }
                }
            }

            $this->alreadyInValidation = false;
        }

        $this->validationFailures = $failureMap;

        return (Boolean) (!(count($this->validationFailures) > 0));

    }

    /**
     * Gets any ConstraintViolation objects that resulted from last call to validate().
     *
     *
     * @return     object ConstraintViolationList
     * @see        validate()
     */
    public function getValidationFailures()
    {
        return $this->validationFailures;
    }

    // i18n behavior

    /**
     * Sets the locale for translations
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     *
     * @return    $this|ChildValidateTriggerFiction The current object (for fluent API support)
     */
    public function setLocale($locale = 'en_US')
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * Gets the locale for translations
     *
     * @return    string $locale Locale to use for the translation, e.g. 'fr_FR'
     */
    public function getLocale()
    {
        return $this->currentLocale;
    }

    /**
     * Returns the current translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildValidateTriggerFictionI18n */
    public function getTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!isset($this->currentTranslations[$locale])) {
            if (null !== $this->collValidateTriggerFictionI18ns) {
                foreach ($this->collValidateTriggerFictionI18ns as $translation) {
                    if ($translation->getLocale() == $locale) {
                        $this->currentTranslations[$locale] = $translation;

                        return $translation;
                    }
                }
            }
            if ($this->isNew()) {
                $translation = new ChildValidateTriggerFictionI18n();
                $translation->setLocale($locale);
            } else {
                $translation = ChildValidateTriggerFictionI18nQuery::create()
                    ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                    ->findOneOrCreate($con);
                $this->currentTranslations[$locale] = $translation;
            }
            $this->addValidateTriggerFictionI18n($translation);
        }

        return $this->currentTranslations[$locale];
    }

    /**
     * Remove the translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return    $this|ChildValidateTriggerFiction The current object (for fluent API support)
     */
    public function removeTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!$this->isNew()) {
            ChildValidateTriggerFictionI18nQuery::create()
                ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                ->delete($con);
        }
        if (isset($this->currentTranslations[$locale])) {
            unset($this->currentTranslations[$locale]);
        }
        foreach ($this->collValidateTriggerFictionI18ns as $key => $translation) {
            if ($translation->getLocale() == $locale) {
                unset($this->collValidateTriggerFictionI18ns[$key]);
                break;
            }
        }

        return $this;
    }

    /**
     * Returns the current translation
     *
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildValidateTriggerFictionI18n */
    public function getCurrentTranslation(ConnectionInterface $con = null)
    {
        return $this->getTranslation($this->getLocale(), $con);
    }


        /**
         * Get the [title] column value.
         * Book Title
         * @return string
         */
        public function getTitle()
        {
        return $this->getCurrentTranslation()->getTitle();
    }


        /**
         * Set the value of [title] column.
         * Book Title
         * @param  string $v new value
         * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionI18n The current object (for fluent API support)
         */
        public function setTitle($v)
        {    $this->getCurrentTranslation()->setTitle($v);

        return $this;
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
