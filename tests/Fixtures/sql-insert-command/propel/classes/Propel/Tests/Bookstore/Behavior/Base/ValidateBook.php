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
use Propel\Runtime\Validator\Constraints\Unique;
use Propel\Tests\Bookstore\Behavior\ValidateAuthor as ChildValidateAuthor;
use Propel\Tests\Bookstore\Behavior\ValidateAuthorQuery as ChildValidateAuthorQuery;
use Propel\Tests\Bookstore\Behavior\ValidateBook as ChildValidateBook;
use Propel\Tests\Bookstore\Behavior\ValidateBookQuery as ChildValidateBookQuery;
use Propel\Tests\Bookstore\Behavior\ValidatePublisher as ChildValidatePublisher;
use Propel\Tests\Bookstore\Behavior\ValidatePublisherQuery as ChildValidatePublisherQuery;
use Propel\Tests\Bookstore\Behavior\ValidateReader as ChildValidateReader;
use Propel\Tests\Bookstore\Behavior\ValidateReaderBook as ChildValidateReaderBook;
use Propel\Tests\Bookstore\Behavior\ValidateReaderBookQuery as ChildValidateReaderBookQuery;
use Propel\Tests\Bookstore\Behavior\ValidateReaderQuery as ChildValidateReaderQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateBookTableMap;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;

/**
 * Base class that represents a row from the 'validate_book' table.
 *
 * Book Table
 *
* @package    propel.generator.Propel.Tests.Bookstore.Behavior.Base
*/
abstract class ValidateBook implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Behavior\\Map\\ValidateBookTableMap';


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
     * The value for the title field.
     * @var        string
     */
    protected $title;

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
     * @var        ChildValidatePublisher
     */
    protected $aValidatePublisher;

    /**
     * @var        ChildValidateAuthor
     */
    protected $aValidateAuthor;

    /**
     * @var        ObjectCollection|ChildValidateReaderBook[] Collection to store aggregation of ChildValidateReaderBook objects.
     */
    protected $collValidateReaderBooks;
    protected $collValidateReaderBooksPartial;

    /**
     * @var        ObjectCollection|ChildValidateReader[] Cross Collection to store aggregation of ChildValidateReader objects.
     */
    protected $collValidateReaders;

    /**
     * @var bool
     */
    protected $collValidateReadersPartial;

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

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildValidateReader[]
     */
    protected $validateReadersScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildValidateReaderBook[]
     */
    protected $validateReaderBooksScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Behavior\Base\ValidateBook object.
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
     * Compares this with another <code>ValidateBook</code> instance.  If
     * <code>obj</code> is an instance of <code>ValidateBook</code>, delegates to
     * <code>equals(ValidateBook)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|ValidateBook The current object, for fluid interface
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
     * Book Id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [title] column value.
     * Book Title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the [isbn] column value.
     * ISBN Number
     * @return string
     */
    public function getIsbn()
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : ValidateBookTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : ValidateBookTableMap::translateFieldName('Title', TableMap::TYPE_PHPNAME, $indexType)];
            $this->title = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : ValidateBookTableMap::translateFieldName('Isbn', TableMap::TYPE_PHPNAME, $indexType)];
            $this->isbn = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : ValidateBookTableMap::translateFieldName('Price', TableMap::TYPE_PHPNAME, $indexType)];
            $this->price = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : ValidateBookTableMap::translateFieldName('PublisherId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->publisher_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : ValidateBookTableMap::translateFieldName('AuthorId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->author_id = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = ValidateBookTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateBook'), 0, $e);
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
        if ($this->aValidatePublisher !== null && $this->publisher_id !== $this->aValidatePublisher->getId()) {
            $this->aValidatePublisher = null;
        }
        if ($this->aValidateAuthor !== null && $this->author_id !== $this->aValidateAuthor->getId()) {
            $this->aValidateAuthor = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [id] column.
     * Book Id
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[ValidateBookTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [title] column.
     * Book Title
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function setTitle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->title !== $v) {
            $this->title = $v;
            $this->modifiedColumns[ValidateBookTableMap::COL_TITLE] = true;
        }

        return $this;
    } // setTitle()

    /**
     * Set the value of [isbn] column.
     * ISBN Number
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function setIsbn($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->isbn !== $v) {
            $this->isbn = $v;
            $this->modifiedColumns[ValidateBookTableMap::COL_ISBN] = true;
        }

        return $this;
    } // setIsbn()

    /**
     * Set the value of [price] column.
     * Price of the book.
     * @param  double $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function setPrice($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->price !== $v) {
            $this->price = $v;
            $this->modifiedColumns[ValidateBookTableMap::COL_PRICE] = true;
        }

        return $this;
    } // setPrice()

    /**
     * Set the value of [publisher_id] column.
     * Foreign Key Publisher
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function setPublisherId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->publisher_id !== $v) {
            $this->publisher_id = $v;
            $this->modifiedColumns[ValidateBookTableMap::COL_PUBLISHER_ID] = true;
        }

        if ($this->aValidatePublisher !== null && $this->aValidatePublisher->getId() !== $v) {
            $this->aValidatePublisher = null;
        }

        return $this;
    } // setPublisherId()

    /**
     * Set the value of [author_id] column.
     * Foreign Key Author
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function setAuthorId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->author_id !== $v) {
            $this->author_id = $v;
            $this->modifiedColumns[ValidateBookTableMap::COL_AUTHOR_ID] = true;
        }

        if ($this->aValidateAuthor !== null && $this->aValidateAuthor->getId() !== $v) {
            $this->aValidateAuthor = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(ValidateBookTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildValidateBookQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aValidatePublisher = null;
            $this->aValidateAuthor = null;
            $this->collValidateReaderBooks = null;

            $this->collValidateReaders = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see ValidateBook::setDeleted()
     * @see ValidateBook::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateBookTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildValidateBookQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateBookTableMap::DATABASE_NAME);
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
                ValidateBookTableMap::addInstanceToPool($this);
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

            if ($this->aValidatePublisher !== null) {
                if ($this->aValidatePublisher->isModified() || $this->aValidatePublisher->isNew()) {
                    $affectedRows += $this->aValidatePublisher->save($con);
                }
                $this->setValidatePublisher($this->aValidatePublisher);
            }

            if ($this->aValidateAuthor !== null) {
                if ($this->aValidateAuthor->isModified() || $this->aValidateAuthor->isNew()) {
                    $affectedRows += $this->aValidateAuthor->save($con);
                }
                $this->setValidateAuthor($this->aValidateAuthor);
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

            if ($this->validateReadersScheduledForDeletion !== null) {
                if (!$this->validateReadersScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->validateReadersScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[1] = $this->getId();
                        $entryPk[0] = $entry->getId();
                        $pks[] = $entryPk;
                    }

                    \Propel\Tests\Bookstore\Behavior\ValidateReaderBookQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->validateReadersScheduledForDeletion = null;
                }

            }

            if ($this->collValidateReaders) {
                foreach ($this->collValidateReaders as $validateReader) {
                    if (!$validateReader->isDeleted() && ($validateReader->isNew() || $validateReader->isModified())) {
                        $validateReader->save($con);
                    }
                }
            }


            if ($this->validateReaderBooksScheduledForDeletion !== null) {
                if (!$this->validateReaderBooksScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\Behavior\ValidateReaderBookQuery::create()
                        ->filterByPrimaryKeys($this->validateReaderBooksScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->validateReaderBooksScheduledForDeletion = null;
                }
            }

            if ($this->collValidateReaderBooks !== null) {
                foreach ($this->collValidateReaderBooks as $referrerFK) {
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

        $this->modifiedColumns[ValidateBookTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . ValidateBookTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(ValidateBookTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_TITLE)) {
            $modifiedColumns[':p' . $index++]  = 'TITLE';
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_ISBN)) {
            $modifiedColumns[':p' . $index++]  = 'ISBN';
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_PRICE)) {
            $modifiedColumns[':p' . $index++]  = 'PRICE';
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_PUBLISHER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'PUBLISHER_ID';
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_AUTHOR_ID)) {
            $modifiedColumns[':p' . $index++]  = 'AUTHOR_ID';
        }

        $sql = sprintf(
            'INSERT INTO validate_book (%s) VALUES (%s)',
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
                    case 'TITLE':
                        $stmt->bindValue($identifier, $this->title, PDO::PARAM_STR);
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
        $pos = ValidateBookTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getTitle();
                break;
            case 2:
                return $this->getIsbn();
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
        if (isset($alreadyDumpedObjects['ValidateBook'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['ValidateBook'][$this->getPrimaryKey()] = true;
        $keys = ValidateBookTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getTitle(),
            $keys[2] => $this->getIsbn(),
            $keys[3] => $this->getPrice(),
            $keys[4] => $this->getPublisherId(),
            $keys[5] => $this->getAuthorId(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aValidatePublisher) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'validatePublisher';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'validate_publisher';
                        break;
                    default:
                        $key = 'ValidatePublisher';
                }

                $result[$key] = $this->aValidatePublisher->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aValidateAuthor) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'validateAuthor';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'validate_author';
                        break;
                    default:
                        $key = 'ValidateAuthor';
                }

                $result[$key] = $this->aValidateAuthor->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collValidateReaderBooks) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'validateReaderBooks';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'validate_reader_books';
                        break;
                    default:
                        $key = 'ValidateReaderBooks';
                }

                $result[$key] = $this->collValidateReaderBooks->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = ValidateBookTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setTitle($value);
                break;
            case 2:
                $this->setIsbn($value);
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
        $keys = ValidateBookTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setTitle($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setIsbn($arr[$keys[2]]);
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
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object, for fluid interface
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
        $criteria = new Criteria(ValidateBookTableMap::DATABASE_NAME);

        if ($this->isColumnModified(ValidateBookTableMap::COL_ID)) {
            $criteria->add(ValidateBookTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_TITLE)) {
            $criteria->add(ValidateBookTableMap::COL_TITLE, $this->title);
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_ISBN)) {
            $criteria->add(ValidateBookTableMap::COL_ISBN, $this->isbn);
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_PRICE)) {
            $criteria->add(ValidateBookTableMap::COL_PRICE, $this->price);
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_PUBLISHER_ID)) {
            $criteria->add(ValidateBookTableMap::COL_PUBLISHER_ID, $this->publisher_id);
        }
        if ($this->isColumnModified(ValidateBookTableMap::COL_AUTHOR_ID)) {
            $criteria->add(ValidateBookTableMap::COL_AUTHOR_ID, $this->author_id);
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
        $criteria = new Criteria(ValidateBookTableMap::DATABASE_NAME);
        $criteria->add(ValidateBookTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\Behavior\ValidateBook (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setTitle($this->getTitle());
        $copyObj->setIsbn($this->getIsbn());
        $copyObj->setPrice($this->getPrice());
        $copyObj->setPublisherId($this->getPublisherId());
        $copyObj->setAuthorId($this->getAuthorId());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getValidateReaderBooks() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addValidateReaderBook($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\Behavior\ValidateBook Clone of current object.
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
     * Declares an association between this object and a ChildValidatePublisher object.
     *
     * @param  ChildValidatePublisher $v
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     * @throws PropelException
     */
    public function setValidatePublisher(ChildValidatePublisher $v = null)
    {
        if ($v === null) {
            $this->setPublisherId(NULL);
        } else {
            $this->setPublisherId($v->getId());
        }

        $this->aValidatePublisher = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildValidatePublisher object, it will not be re-added.
        if ($v !== null) {
            $v->addValidateBook($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildValidatePublisher object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildValidatePublisher The associated ChildValidatePublisher object.
     * @throws PropelException
     */
    public function getValidatePublisher(ConnectionInterface $con = null)
    {
        if ($this->aValidatePublisher === null && ($this->publisher_id !== null)) {
            $this->aValidatePublisher = ChildValidatePublisherQuery::create()->findPk($this->publisher_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aValidatePublisher->addValidateBooks($this);
             */
        }

        return $this->aValidatePublisher;
    }

    /**
     * Declares an association between this object and a ChildValidateAuthor object.
     *
     * @param  ChildValidateAuthor $v
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     * @throws PropelException
     */
    public function setValidateAuthor(ChildValidateAuthor $v = null)
    {
        if ($v === null) {
            $this->setAuthorId(NULL);
        } else {
            $this->setAuthorId($v->getId());
        }

        $this->aValidateAuthor = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildValidateAuthor object, it will not be re-added.
        if ($v !== null) {
            $v->addValidateBook($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildValidateAuthor object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildValidateAuthor The associated ChildValidateAuthor object.
     * @throws PropelException
     */
    public function getValidateAuthor(ConnectionInterface $con = null)
    {
        if ($this->aValidateAuthor === null && ($this->author_id !== null)) {
            $this->aValidateAuthor = ChildValidateAuthorQuery::create()->findPk($this->author_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aValidateAuthor->addValidateBooks($this);
             */
        }

        return $this->aValidateAuthor;
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
        if ('ValidateReaderBook' == $relationName) {
            return $this->initValidateReaderBooks();
        }
    }

    /**
     * Clears out the collValidateReaderBooks collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addValidateReaderBooks()
     */
    public function clearValidateReaderBooks()
    {
        $this->collValidateReaderBooks = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collValidateReaderBooks collection loaded partially.
     */
    public function resetPartialValidateReaderBooks($v = true)
    {
        $this->collValidateReaderBooksPartial = $v;
    }

    /**
     * Initializes the collValidateReaderBooks collection.
     *
     * By default this just sets the collValidateReaderBooks collection to an empty array (like clearcollValidateReaderBooks());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initValidateReaderBooks($overrideExisting = true)
    {
        if (null !== $this->collValidateReaderBooks && !$overrideExisting) {
            return;
        }
        $this->collValidateReaderBooks = new ObjectCollection();
        $this->collValidateReaderBooks->setModel('\Propel\Tests\Bookstore\Behavior\ValidateReaderBook');
    }

    /**
     * Gets an array of ChildValidateReaderBook objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildValidateBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildValidateReaderBook[] List of ChildValidateReaderBook objects
     * @throws PropelException
     */
    public function getValidateReaderBooks(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collValidateReaderBooksPartial && !$this->isNew();
        if (null === $this->collValidateReaderBooks || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collValidateReaderBooks) {
                // return empty collection
                $this->initValidateReaderBooks();
            } else {
                $collValidateReaderBooks = ChildValidateReaderBookQuery::create(null, $criteria)
                    ->filterByValidateBook($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collValidateReaderBooksPartial && count($collValidateReaderBooks)) {
                        $this->initValidateReaderBooks(false);

                        foreach ($collValidateReaderBooks as $obj) {
                            if (false == $this->collValidateReaderBooks->contains($obj)) {
                                $this->collValidateReaderBooks->append($obj);
                            }
                        }

                        $this->collValidateReaderBooksPartial = true;
                    }

                    return $collValidateReaderBooks;
                }

                if ($partial && $this->collValidateReaderBooks) {
                    foreach ($this->collValidateReaderBooks as $obj) {
                        if ($obj->isNew()) {
                            $collValidateReaderBooks[] = $obj;
                        }
                    }
                }

                $this->collValidateReaderBooks = $collValidateReaderBooks;
                $this->collValidateReaderBooksPartial = false;
            }
        }

        return $this->collValidateReaderBooks;
    }

    /**
     * Sets a collection of ChildValidateReaderBook objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $validateReaderBooks A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildValidateBook The current object (for fluent API support)
     */
    public function setValidateReaderBooks(Collection $validateReaderBooks, ConnectionInterface $con = null)
    {
        /** @var ChildValidateReaderBook[] $validateReaderBooksToDelete */
        $validateReaderBooksToDelete = $this->getValidateReaderBooks(new Criteria(), $con)->diff($validateReaderBooks);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->validateReaderBooksScheduledForDeletion = clone $validateReaderBooksToDelete;

        foreach ($validateReaderBooksToDelete as $validateReaderBookRemoved) {
            $validateReaderBookRemoved->setValidateBook(null);
        }

        $this->collValidateReaderBooks = null;
        foreach ($validateReaderBooks as $validateReaderBook) {
            $this->addValidateReaderBook($validateReaderBook);
        }

        $this->collValidateReaderBooks = $validateReaderBooks;
        $this->collValidateReaderBooksPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ValidateReaderBook objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ValidateReaderBook objects.
     * @throws PropelException
     */
    public function countValidateReaderBooks(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collValidateReaderBooksPartial && !$this->isNew();
        if (null === $this->collValidateReaderBooks || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collValidateReaderBooks) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getValidateReaderBooks());
            }

            $query = ChildValidateReaderBookQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByValidateBook($this)
                ->count($con);
        }

        return count($this->collValidateReaderBooks);
    }

    /**
     * Method called to associate a ChildValidateReaderBook object to this object
     * through the ChildValidateReaderBook foreign key attribute.
     *
     * @param  ChildValidateReaderBook $l ChildValidateReaderBook
     * @return $this|\Propel\Tests\Bookstore\Behavior\ValidateBook The current object (for fluent API support)
     */
    public function addValidateReaderBook(ChildValidateReaderBook $l)
    {
        if ($this->collValidateReaderBooks === null) {
            $this->initValidateReaderBooks();
            $this->collValidateReaderBooksPartial = true;
        }

        if (!$this->collValidateReaderBooks->contains($l)) {
            $this->doAddValidateReaderBook($l);
        }

        return $this;
    }

    /**
     * @param ChildValidateReaderBook $validateReaderBook The ChildValidateReaderBook object to add.
     */
    protected function doAddValidateReaderBook(ChildValidateReaderBook $validateReaderBook)
    {
        $this->collValidateReaderBooks[]= $validateReaderBook;
        $validateReaderBook->setValidateBook($this);
    }

    /**
     * @param  ChildValidateReaderBook $validateReaderBook The ChildValidateReaderBook object to remove.
     * @return $this|ChildValidateBook The current object (for fluent API support)
     */
    public function removeValidateReaderBook(ChildValidateReaderBook $validateReaderBook)
    {
        if ($this->getValidateReaderBooks()->contains($validateReaderBook)) {
            $pos = $this->collValidateReaderBooks->search($validateReaderBook);
            $this->collValidateReaderBooks->remove($pos);
            if (null === $this->validateReaderBooksScheduledForDeletion) {
                $this->validateReaderBooksScheduledForDeletion = clone $this->collValidateReaderBooks;
                $this->validateReaderBooksScheduledForDeletion->clear();
            }
            $this->validateReaderBooksScheduledForDeletion[]= clone $validateReaderBook;
            $validateReaderBook->setValidateBook(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ValidateBook is new, it will return
     * an empty collection; or if this ValidateBook has previously
     * been saved, it will retrieve related ValidateReaderBooks from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ValidateBook.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildValidateReaderBook[] List of ChildValidateReaderBook objects
     */
    public function getValidateReaderBooksJoinValidateReader(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildValidateReaderBookQuery::create(null, $criteria);
        $query->joinWith('ValidateReader', $joinBehavior);

        return $this->getValidateReaderBooks($query, $con);
    }

    /**
     * Clears out the collValidateReaders collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addValidateReaders()
     */
    public function clearValidateReaders()
    {
        $this->collValidateReaders = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the collValidateReaders collection.
     *
     * By default this just sets the collValidateReaders collection to an empty collection (like clearValidateReaders());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initValidateReaders()
    {
        $this->collValidateReaders = new ObjectCollection();
        $this->collValidateReadersPartial = true;

        $this->collValidateReaders->setModel('\Propel\Tests\Bookstore\Behavior\ValidateReader');
    }

    /**
     * Checks if the collValidateReaders collection is loaded.
     *
     * @return bool
     */
    public function isValidateReadersLoaded()
    {
        return null !== $this->collValidateReaders;
    }

    /**
     * Gets a collection of ChildValidateReader objects related by a many-to-many relationship
     * to the current object by way of the validate_reader_book cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildValidateBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildValidateReader[] List of ChildValidateReader objects
     */
    public function getValidateReaders(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collValidateReadersPartial && !$this->isNew();
        if (null === $this->collValidateReaders || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collValidateReaders) {
                    $this->initValidateReaders();
                }
            } else {

                $query = ChildValidateReaderQuery::create(null, $criteria)
                    ->filterByValidateBook($this);
                $collValidateReaders = $query->find($con);
                if (null !== $criteria) {
                    return $collValidateReaders;
                }

                if ($partial && $this->collValidateReaders) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collValidateReaders as $obj) {
                        if (!$collValidateReaders->contains($obj)) {
                            $collValidateReaders[] = $obj;
                        }
                    }
                }

                $this->collValidateReaders = $collValidateReaders;
                $this->collValidateReadersPartial = false;
            }
        }

        return $this->collValidateReaders;
    }

    /**
     * Sets a collection of ValidateReader objects related by a many-to-many relationship
     * to the current object by way of the validate_reader_book cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $validateReaders A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildValidateBook The current object (for fluent API support)
     */
    public function setValidateReaders(Collection $validateReaders, ConnectionInterface $con = null)
    {
        $this->clearValidateReaders();
        $currentValidateReaders = $this->getValidateReaders();

        $validateReadersScheduledForDeletion = $currentValidateReaders->diff($validateReaders);

        foreach ($validateReadersScheduledForDeletion as $toDelete) {
            $this->removeValidateReader($toDelete);
        }

        foreach ($validateReaders as $validateReader) {
            if (!$currentValidateReaders->contains($validateReader)) {
                $this->doAddValidateReader($validateReader);
            }
        }

        $this->collValidateReadersPartial = false;
        $this->collValidateReaders = $validateReaders;

        return $this;
    }

    /**
     * Gets the number of ValidateReader objects related by a many-to-many relationship
     * to the current object by way of the validate_reader_book cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ValidateReader objects
     */
    public function countValidateReaders(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collValidateReadersPartial && !$this->isNew();
        if (null === $this->collValidateReaders || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collValidateReaders) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getValidateReaders());
                }

                $query = ChildValidateReaderQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByValidateBook($this)
                    ->count($con);
            }
        } else {
            return count($this->collValidateReaders);
        }
    }

    /**
     * Associate a ChildValidateReader to this object
     * through the validate_reader_book cross reference table.
     *
     * @param ChildValidateReader $validateReader
     * @return ChildValidateBook The current object (for fluent API support)
     */
    public function addValidateReader(ChildValidateReader $validateReader)
    {
        if ($this->collValidateReaders === null) {
            $this->initValidateReaders();
        }

        if (!$this->getValidateReaders()->contains($validateReader)) {
            // only add it if the **same** object is not already associated
            $this->collValidateReaders->push($validateReader);
            $this->doAddValidateReader($validateReader);
        }

        return $this;
    }

    /**
     *
     * @param ChildValidateReader $validateReader
     */
    protected function doAddValidateReader(ChildValidateReader $validateReader)
    {
        $validateReaderBook = new ChildValidateReaderBook();

        $validateReaderBook->setValidateReader($validateReader);

        $validateReaderBook->setValidateBook($this);

        $this->addValidateReaderBook($validateReaderBook);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$validateReader->isValidateBooksLoaded()) {
            $validateReader->initValidateBooks();
            $validateReader->getValidateBooks()->push($this);
        } elseif (!$validateReader->getValidateBooks()->contains($this)) {
            $validateReader->getValidateBooks()->push($this);
        }

    }

    /**
     * Remove validateReader of this object
     * through the validate_reader_book cross reference table.
     *
     * @param ChildValidateReader $validateReader
     * @return ChildValidateBook The current object (for fluent API support)
     */
    public function removeValidateReader(ChildValidateReader $validateReader)
    {
        if ($this->getValidateReaders()->contains($validateReader)) { $validateReaderBook = new ChildValidateReaderBook();

            $validateReaderBook->setValidateReader($validateReader);
            if ($validateReader->isValidateBooksLoaded()) {
                //remove the back reference if available
                $validateReader->getValidateBooks()->removeObject($this);
            }

            $validateReaderBook->setValidateBook($this);
            $this->removeValidateReaderBook(clone $validateReaderBook);
            $validateReaderBook->clear();

            $this->collValidateReaders->remove($this->collValidateReaders->search($validateReader));

            if (null === $this->validateReadersScheduledForDeletion) {
                $this->validateReadersScheduledForDeletion = clone $this->collValidateReaders;
                $this->validateReadersScheduledForDeletion->clear();
            }

            $this->validateReadersScheduledForDeletion->push($validateReader);
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
        if (null !== $this->aValidatePublisher) {
            $this->aValidatePublisher->removeValidateBook($this);
        }
        if (null !== $this->aValidateAuthor) {
            $this->aValidateAuthor->removeValidateBook($this);
        }
        $this->id = null;
        $this->title = null;
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
            if ($this->collValidateReaderBooks) {
                foreach ($this->collValidateReaderBooks as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collValidateReaders) {
                foreach ($this->collValidateReaders as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collValidateReaderBooks = null;
        $this->collValidateReaders = null;
        $this->aValidatePublisher = null;
        $this->aValidateAuthor = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'title' column
     */
    public function __toString()
    {
        return (string) $this->getTitle();
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
        $metadata->addPropertyConstraint('title', new NotNull());
        $metadata->addPropertyConstraint('isbn', new Regex(array ('pattern' => '/[^\\d-]+/','match' => false,'message' => 'Please enter a valid ISBN',)));
        $metadata->addPropertyConstraint('isbn', new Unique(array ('message' => 'Column isbn must be unique',)));
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
            if (method_exists($this->aValidatePublisher, 'validate')) {
                if (!$this->aValidatePublisher->validate($validator)) {
                    $failureMap->addAll($this->aValidatePublisher->getValidationFailures());
                }
            }
            // If validate() method exists, the validate-behavior is configured for related object
            if (method_exists($this->aValidateAuthor, 'validate')) {
                if (!$this->aValidateAuthor->validate($validator)) {
                    $failureMap->addAll($this->aValidateAuthor->getValidationFailures());
                }
            }

            $retval = $validator->validate($this);
            if (count($retval) > 0) {
                $failureMap->addAll($retval);
            }

            if (null !== $this->collValidateReaderBooks) {
                foreach ($this->collValidateReaderBooks as $referrerFK) {
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
