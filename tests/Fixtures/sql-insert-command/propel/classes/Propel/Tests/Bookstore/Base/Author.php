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
use Propel\Tests\Bookstore\Author as ChildAuthor;
use Propel\Tests\Bookstore\AuthorQuery as ChildAuthorQuery;
use Propel\Tests\Bookstore\Book as ChildBook;
use Propel\Tests\Bookstore\BookQuery as ChildBookQuery;
use Propel\Tests\Bookstore\Essay as ChildEssay;
use Propel\Tests\Bookstore\EssayQuery as ChildEssayQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;

/**
 * Base class that represents a row from the 'author' table.
 *
 * Author Table
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class Author implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\AuthorTableMap';


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
     * The value for the first_name field.
     * @var        string
     */
    protected $first_name;

    /**
     * The value for the last_name field.
     * @var        string
     */
    protected $last_name;

    /**
     * The value for the email field.
     * @var        string
     */
    protected $email;

    /**
     * The value for the age field.
     * @var        int
     */
    protected $age;

    /**
     * @var        ObjectCollection|ChildBook[] Collection to store aggregation of ChildBook objects.
     */
    protected $collBooks;
    protected $collBooksPartial;

    /**
     * @var        ObjectCollection|ChildEssay[] Collection to store aggregation of ChildEssay objects.
     */
    protected $collEssaysRelatedByFirstAuthor;
    protected $collEssaysRelatedByFirstAuthorPartial;

    /**
     * @var        ObjectCollection|ChildEssay[] Collection to store aggregation of ChildEssay objects.
     */
    protected $collEssaysRelatedBySecondAuthor;
    protected $collEssaysRelatedBySecondAuthorPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBook[]
     */
    protected $booksScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildEssay[]
     */
    protected $essaysRelatedByFirstAuthorScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildEssay[]
     */
    protected $essaysRelatedBySecondAuthorScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\Author object.
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
     * Compares this with another <code>Author</code> instance.  If
     * <code>obj</code> is an instance of <code>Author</code>, delegates to
     * <code>equals(Author)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|Author The current object, for fluid interface
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
     * Author Id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [first_name] column value.
     * First Name
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Get the [last_name] column value.
     * Last Name
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Get the [email] column value.
     * E-Mail Address
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get the [age] column value.
     * The authors age
     * @return int
     */
    public function getAge()
    {
        return $this->age;
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : AuthorTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : AuthorTableMap::translateFieldName('FirstName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->first_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : AuthorTableMap::translateFieldName('LastName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->last_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : AuthorTableMap::translateFieldName('Email', TableMap::TYPE_PHPNAME, $indexType)];
            $this->email = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : AuthorTableMap::translateFieldName('Age', TableMap::TYPE_PHPNAME, $indexType)];
            $this->age = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 5; // 5 = AuthorTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\Author'), 0, $e);
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
     * Author Id
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[AuthorTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [first_name] column.
     * First Name
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function setFirstName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->first_name !== $v) {
            $this->first_name = $v;
            $this->modifiedColumns[AuthorTableMap::COL_FIRST_NAME] = true;
        }

        return $this;
    } // setFirstName()

    /**
     * Set the value of [last_name] column.
     * Last Name
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function setLastName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->last_name !== $v) {
            $this->last_name = $v;
            $this->modifiedColumns[AuthorTableMap::COL_LAST_NAME] = true;
        }

        return $this;
    } // setLastName()

    /**
     * Set the value of [email] column.
     * E-Mail Address
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function setEmail($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->email !== $v) {
            $this->email = $v;
            $this->modifiedColumns[AuthorTableMap::COL_EMAIL] = true;
        }

        return $this;
    } // setEmail()

    /**
     * Set the value of [age] column.
     * The authors age
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function setAge($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->age !== $v) {
            $this->age = $v;
            $this->modifiedColumns[AuthorTableMap::COL_AGE] = true;
        }

        return $this;
    } // setAge()

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
            $con = Propel::getServiceContainer()->getReadConnection(AuthorTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildAuthorQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collBooks = null;

            $this->collEssaysRelatedByFirstAuthor = null;

            $this->collEssaysRelatedBySecondAuthor = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Author::setDeleted()
     * @see Author::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(AuthorTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildAuthorQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(AuthorTableMap::DATABASE_NAME);
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
                AuthorTableMap::addInstanceToPool($this);
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

            if ($this->booksScheduledForDeletion !== null) {
                if (!$this->booksScheduledForDeletion->isEmpty()) {
                    foreach ($this->booksScheduledForDeletion as $book) {
                        // need to save related object because we set the relation to null
                        $book->save($con);
                    }
                    $this->booksScheduledForDeletion = null;
                }
            }

            if ($this->collBooks !== null) {
                foreach ($this->collBooks as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->essaysRelatedByFirstAuthorScheduledForDeletion !== null) {
                if (!$this->essaysRelatedByFirstAuthorScheduledForDeletion->isEmpty()) {
                    foreach ($this->essaysRelatedByFirstAuthorScheduledForDeletion as $essayRelatedByFirstAuthor) {
                        // need to save related object because we set the relation to null
                        $essayRelatedByFirstAuthor->save($con);
                    }
                    $this->essaysRelatedByFirstAuthorScheduledForDeletion = null;
                }
            }

            if ($this->collEssaysRelatedByFirstAuthor !== null) {
                foreach ($this->collEssaysRelatedByFirstAuthor as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->essaysRelatedBySecondAuthorScheduledForDeletion !== null) {
                if (!$this->essaysRelatedBySecondAuthorScheduledForDeletion->isEmpty()) {
                    foreach ($this->essaysRelatedBySecondAuthorScheduledForDeletion as $essayRelatedBySecondAuthor) {
                        // need to save related object because we set the relation to null
                        $essayRelatedBySecondAuthor->save($con);
                    }
                    $this->essaysRelatedBySecondAuthorScheduledForDeletion = null;
                }
            }

            if ($this->collEssaysRelatedBySecondAuthor !== null) {
                foreach ($this->collEssaysRelatedBySecondAuthor as $referrerFK) {
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

        $this->modifiedColumns[AuthorTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . AuthorTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(AuthorTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(AuthorTableMap::COL_FIRST_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'FIRST_NAME';
        }
        if ($this->isColumnModified(AuthorTableMap::COL_LAST_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'LAST_NAME';
        }
        if ($this->isColumnModified(AuthorTableMap::COL_EMAIL)) {
            $modifiedColumns[':p' . $index++]  = 'EMAIL';
        }
        if ($this->isColumnModified(AuthorTableMap::COL_AGE)) {
            $modifiedColumns[':p' . $index++]  = 'AGE';
        }

        $sql = sprintf(
            'INSERT INTO author (%s) VALUES (%s)',
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
                    case 'FIRST_NAME':
                        $stmt->bindValue($identifier, $this->first_name, PDO::PARAM_STR);
                        break;
                    case 'LAST_NAME':
                        $stmt->bindValue($identifier, $this->last_name, PDO::PARAM_STR);
                        break;
                    case 'EMAIL':
                        $stmt->bindValue($identifier, $this->email, PDO::PARAM_STR);
                        break;
                    case 'AGE':
                        $stmt->bindValue($identifier, $this->age, PDO::PARAM_INT);
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
        $pos = AuthorTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getFirstName();
                break;
            case 2:
                return $this->getLastName();
                break;
            case 3:
                return $this->getEmail();
                break;
            case 4:
                return $this->getAge();
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
        if (isset($alreadyDumpedObjects['Author'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Author'][$this->getPrimaryKey()] = true;
        $keys = AuthorTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getFirstName(),
            $keys[2] => $this->getLastName(),
            $keys[3] => $this->getEmail(),
            $keys[4] => $this->getAge(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collBooks) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'books';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'books';
                        break;
                    default:
                        $key = 'Books';
                }

                $result[$key] = $this->collBooks->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collEssaysRelatedByFirstAuthor) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'essays';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'essays';
                        break;
                    default:
                        $key = 'Essays';
                }

                $result[$key] = $this->collEssaysRelatedByFirstAuthor->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collEssaysRelatedBySecondAuthor) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'essays';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'essays';
                        break;
                    default:
                        $key = 'Essays';
                }

                $result[$key] = $this->collEssaysRelatedBySecondAuthor->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\Author
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = AuthorTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\Author
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setFirstName($value);
                break;
            case 2:
                $this->setLastName($value);
                break;
            case 3:
                $this->setEmail($value);
                break;
            case 4:
                $this->setAge($value);
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
        $keys = AuthorTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setFirstName($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setLastName($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setEmail($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setAge($arr[$keys[4]]);
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
     * @return $this|\Propel\Tests\Bookstore\Author The current object, for fluid interface
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
        $criteria = new Criteria(AuthorTableMap::DATABASE_NAME);

        if ($this->isColumnModified(AuthorTableMap::COL_ID)) {
            $criteria->add(AuthorTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(AuthorTableMap::COL_FIRST_NAME)) {
            $criteria->add(AuthorTableMap::COL_FIRST_NAME, $this->first_name);
        }
        if ($this->isColumnModified(AuthorTableMap::COL_LAST_NAME)) {
            $criteria->add(AuthorTableMap::COL_LAST_NAME, $this->last_name);
        }
        if ($this->isColumnModified(AuthorTableMap::COL_EMAIL)) {
            $criteria->add(AuthorTableMap::COL_EMAIL, $this->email);
        }
        if ($this->isColumnModified(AuthorTableMap::COL_AGE)) {
            $criteria->add(AuthorTableMap::COL_AGE, $this->age);
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
        $criteria = new Criteria(AuthorTableMap::DATABASE_NAME);
        $criteria->add(AuthorTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\Author (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setFirstName($this->getFirstName());
        $copyObj->setLastName($this->getLastName());
        $copyObj->setEmail($this->getEmail());
        $copyObj->setAge($this->getAge());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getBooks() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addBook($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getEssaysRelatedByFirstAuthor() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addEssayRelatedByFirstAuthor($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getEssaysRelatedBySecondAuthor() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addEssayRelatedBySecondAuthor($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\Author Clone of current object.
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
        if ('Book' == $relationName) {
            return $this->initBooks();
        }
        if ('EssayRelatedByFirstAuthor' == $relationName) {
            return $this->initEssaysRelatedByFirstAuthor();
        }
        if ('EssayRelatedBySecondAuthor' == $relationName) {
            return $this->initEssaysRelatedBySecondAuthor();
        }
    }

    /**
     * Clears out the collBooks collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBooks()
     */
    public function clearBooks()
    {
        $this->collBooks = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collBooks collection loaded partially.
     */
    public function resetPartialBooks($v = true)
    {
        $this->collBooksPartial = $v;
    }

    /**
     * Initializes the collBooks collection.
     *
     * By default this just sets the collBooks collection to an empty array (like clearcollBooks());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initBooks($overrideExisting = true)
    {
        if (null !== $this->collBooks && !$overrideExisting) {
            return;
        }
        $this->collBooks = new ObjectCollection();
        $this->collBooks->setModel('\Propel\Tests\Bookstore\Book');
    }

    /**
     * Gets an array of ChildBook objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildAuthor is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBook[] List of ChildBook objects
     * @throws PropelException
     */
    public function getBooks(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBooksPartial && !$this->isNew();
        if (null === $this->collBooks || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collBooks) {
                // return empty collection
                $this->initBooks();
            } else {
                $collBooks = ChildBookQuery::create(null, $criteria)
                    ->filterByAuthor($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collBooksPartial && count($collBooks)) {
                        $this->initBooks(false);

                        foreach ($collBooks as $obj) {
                            if (false == $this->collBooks->contains($obj)) {
                                $this->collBooks->append($obj);
                            }
                        }

                        $this->collBooksPartial = true;
                    }

                    return $collBooks;
                }

                if ($partial && $this->collBooks) {
                    foreach ($this->collBooks as $obj) {
                        if ($obj->isNew()) {
                            $collBooks[] = $obj;
                        }
                    }
                }

                $this->collBooks = $collBooks;
                $this->collBooksPartial = false;
            }
        }

        return $this->collBooks;
    }

    /**
     * Sets a collection of ChildBook objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $books A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildAuthor The current object (for fluent API support)
     */
    public function setBooks(Collection $books, ConnectionInterface $con = null)
    {
        /** @var ChildBook[] $booksToDelete */
        $booksToDelete = $this->getBooks(new Criteria(), $con)->diff($books);


        $this->booksScheduledForDeletion = $booksToDelete;

        foreach ($booksToDelete as $bookRemoved) {
            $bookRemoved->setAuthor(null);
        }

        $this->collBooks = null;
        foreach ($books as $book) {
            $this->addBook($book);
        }

        $this->collBooks = $books;
        $this->collBooksPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Book objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Book objects.
     * @throws PropelException
     */
    public function countBooks(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBooksPartial && !$this->isNew();
        if (null === $this->collBooks || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBooks) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getBooks());
            }

            $query = ChildBookQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByAuthor($this)
                ->count($con);
        }

        return count($this->collBooks);
    }

    /**
     * Method called to associate a ChildBook object to this object
     * through the ChildBook foreign key attribute.
     *
     * @param  ChildBook $l ChildBook
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function addBook(ChildBook $l)
    {
        if ($this->collBooks === null) {
            $this->initBooks();
            $this->collBooksPartial = true;
        }

        if (!$this->collBooks->contains($l)) {
            $this->doAddBook($l);
        }

        return $this;
    }

    /**
     * @param ChildBook $book The ChildBook object to add.
     */
    protected function doAddBook(ChildBook $book)
    {
        $this->collBooks[]= $book;
        $book->setAuthor($this);
    }

    /**
     * @param  ChildBook $book The ChildBook object to remove.
     * @return $this|ChildAuthor The current object (for fluent API support)
     */
    public function removeBook(ChildBook $book)
    {
        if ($this->getBooks()->contains($book)) {
            $pos = $this->collBooks->search($book);
            $this->collBooks->remove($pos);
            if (null === $this->booksScheduledForDeletion) {
                $this->booksScheduledForDeletion = clone $this->collBooks;
                $this->booksScheduledForDeletion->clear();
            }
            $this->booksScheduledForDeletion[]= $book;
            $book->setAuthor(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Author is new, it will return
     * an empty collection; or if this Author has previously
     * been saved, it will retrieve related Books from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Author.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBook[] List of ChildBook objects
     */
    public function getBooksJoinPublisher(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookQuery::create(null, $criteria);
        $query->joinWith('Publisher', $joinBehavior);

        return $this->getBooks($query, $con);
    }

    /**
     * Clears out the collEssaysRelatedByFirstAuthor collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addEssaysRelatedByFirstAuthor()
     */
    public function clearEssaysRelatedByFirstAuthor()
    {
        $this->collEssaysRelatedByFirstAuthor = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collEssaysRelatedByFirstAuthor collection loaded partially.
     */
    public function resetPartialEssaysRelatedByFirstAuthor($v = true)
    {
        $this->collEssaysRelatedByFirstAuthorPartial = $v;
    }

    /**
     * Initializes the collEssaysRelatedByFirstAuthor collection.
     *
     * By default this just sets the collEssaysRelatedByFirstAuthor collection to an empty array (like clearcollEssaysRelatedByFirstAuthor());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initEssaysRelatedByFirstAuthor($overrideExisting = true)
    {
        if (null !== $this->collEssaysRelatedByFirstAuthor && !$overrideExisting) {
            return;
        }
        $this->collEssaysRelatedByFirstAuthor = new ObjectCollection();
        $this->collEssaysRelatedByFirstAuthor->setModel('\Propel\Tests\Bookstore\Essay');
    }

    /**
     * Gets an array of ChildEssay objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildAuthor is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildEssay[] List of ChildEssay objects
     * @throws PropelException
     */
    public function getEssaysRelatedByFirstAuthor(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collEssaysRelatedByFirstAuthorPartial && !$this->isNew();
        if (null === $this->collEssaysRelatedByFirstAuthor || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collEssaysRelatedByFirstAuthor) {
                // return empty collection
                $this->initEssaysRelatedByFirstAuthor();
            } else {
                $collEssaysRelatedByFirstAuthor = ChildEssayQuery::create(null, $criteria)
                    ->filterByAuthorRelatedByFirstAuthor($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collEssaysRelatedByFirstAuthorPartial && count($collEssaysRelatedByFirstAuthor)) {
                        $this->initEssaysRelatedByFirstAuthor(false);

                        foreach ($collEssaysRelatedByFirstAuthor as $obj) {
                            if (false == $this->collEssaysRelatedByFirstAuthor->contains($obj)) {
                                $this->collEssaysRelatedByFirstAuthor->append($obj);
                            }
                        }

                        $this->collEssaysRelatedByFirstAuthorPartial = true;
                    }

                    return $collEssaysRelatedByFirstAuthor;
                }

                if ($partial && $this->collEssaysRelatedByFirstAuthor) {
                    foreach ($this->collEssaysRelatedByFirstAuthor as $obj) {
                        if ($obj->isNew()) {
                            $collEssaysRelatedByFirstAuthor[] = $obj;
                        }
                    }
                }

                $this->collEssaysRelatedByFirstAuthor = $collEssaysRelatedByFirstAuthor;
                $this->collEssaysRelatedByFirstAuthorPartial = false;
            }
        }

        return $this->collEssaysRelatedByFirstAuthor;
    }

    /**
     * Sets a collection of ChildEssay objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $essaysRelatedByFirstAuthor A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildAuthor The current object (for fluent API support)
     */
    public function setEssaysRelatedByFirstAuthor(Collection $essaysRelatedByFirstAuthor, ConnectionInterface $con = null)
    {
        /** @var ChildEssay[] $essaysRelatedByFirstAuthorToDelete */
        $essaysRelatedByFirstAuthorToDelete = $this->getEssaysRelatedByFirstAuthor(new Criteria(), $con)->diff($essaysRelatedByFirstAuthor);


        $this->essaysRelatedByFirstAuthorScheduledForDeletion = $essaysRelatedByFirstAuthorToDelete;

        foreach ($essaysRelatedByFirstAuthorToDelete as $essayRelatedByFirstAuthorRemoved) {
            $essayRelatedByFirstAuthorRemoved->setAuthorRelatedByFirstAuthor(null);
        }

        $this->collEssaysRelatedByFirstAuthor = null;
        foreach ($essaysRelatedByFirstAuthor as $essayRelatedByFirstAuthor) {
            $this->addEssayRelatedByFirstAuthor($essayRelatedByFirstAuthor);
        }

        $this->collEssaysRelatedByFirstAuthor = $essaysRelatedByFirstAuthor;
        $this->collEssaysRelatedByFirstAuthorPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Essay objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Essay objects.
     * @throws PropelException
     */
    public function countEssaysRelatedByFirstAuthor(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collEssaysRelatedByFirstAuthorPartial && !$this->isNew();
        if (null === $this->collEssaysRelatedByFirstAuthor || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collEssaysRelatedByFirstAuthor) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getEssaysRelatedByFirstAuthor());
            }

            $query = ChildEssayQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByAuthorRelatedByFirstAuthor($this)
                ->count($con);
        }

        return count($this->collEssaysRelatedByFirstAuthor);
    }

    /**
     * Method called to associate a ChildEssay object to this object
     * through the ChildEssay foreign key attribute.
     *
     * @param  ChildEssay $l ChildEssay
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function addEssayRelatedByFirstAuthor(ChildEssay $l)
    {
        if ($this->collEssaysRelatedByFirstAuthor === null) {
            $this->initEssaysRelatedByFirstAuthor();
            $this->collEssaysRelatedByFirstAuthorPartial = true;
        }

        if (!$this->collEssaysRelatedByFirstAuthor->contains($l)) {
            $this->doAddEssayRelatedByFirstAuthor($l);
        }

        return $this;
    }

    /**
     * @param ChildEssay $essayRelatedByFirstAuthor The ChildEssay object to add.
     */
    protected function doAddEssayRelatedByFirstAuthor(ChildEssay $essayRelatedByFirstAuthor)
    {
        $this->collEssaysRelatedByFirstAuthor[]= $essayRelatedByFirstAuthor;
        $essayRelatedByFirstAuthor->setAuthorRelatedByFirstAuthor($this);
    }

    /**
     * @param  ChildEssay $essayRelatedByFirstAuthor The ChildEssay object to remove.
     * @return $this|ChildAuthor The current object (for fluent API support)
     */
    public function removeEssayRelatedByFirstAuthor(ChildEssay $essayRelatedByFirstAuthor)
    {
        if ($this->getEssaysRelatedByFirstAuthor()->contains($essayRelatedByFirstAuthor)) {
            $pos = $this->collEssaysRelatedByFirstAuthor->search($essayRelatedByFirstAuthor);
            $this->collEssaysRelatedByFirstAuthor->remove($pos);
            if (null === $this->essaysRelatedByFirstAuthorScheduledForDeletion) {
                $this->essaysRelatedByFirstAuthorScheduledForDeletion = clone $this->collEssaysRelatedByFirstAuthor;
                $this->essaysRelatedByFirstAuthorScheduledForDeletion->clear();
            }
            $this->essaysRelatedByFirstAuthorScheduledForDeletion[]= $essayRelatedByFirstAuthor;
            $essayRelatedByFirstAuthor->setAuthorRelatedByFirstAuthor(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Author is new, it will return
     * an empty collection; or if this Author has previously
     * been saved, it will retrieve related EssaysRelatedByFirstAuthor from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Author.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildEssay[] List of ChildEssay objects
     */
    public function getEssaysRelatedByFirstAuthorJoinEssayRelatedByNextEssayId(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildEssayQuery::create(null, $criteria);
        $query->joinWith('EssayRelatedByNextEssayId', $joinBehavior);

        return $this->getEssaysRelatedByFirstAuthor($query, $con);
    }

    /**
     * Clears out the collEssaysRelatedBySecondAuthor collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addEssaysRelatedBySecondAuthor()
     */
    public function clearEssaysRelatedBySecondAuthor()
    {
        $this->collEssaysRelatedBySecondAuthor = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collEssaysRelatedBySecondAuthor collection loaded partially.
     */
    public function resetPartialEssaysRelatedBySecondAuthor($v = true)
    {
        $this->collEssaysRelatedBySecondAuthorPartial = $v;
    }

    /**
     * Initializes the collEssaysRelatedBySecondAuthor collection.
     *
     * By default this just sets the collEssaysRelatedBySecondAuthor collection to an empty array (like clearcollEssaysRelatedBySecondAuthor());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initEssaysRelatedBySecondAuthor($overrideExisting = true)
    {
        if (null !== $this->collEssaysRelatedBySecondAuthor && !$overrideExisting) {
            return;
        }
        $this->collEssaysRelatedBySecondAuthor = new ObjectCollection();
        $this->collEssaysRelatedBySecondAuthor->setModel('\Propel\Tests\Bookstore\Essay');
    }

    /**
     * Gets an array of ChildEssay objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildAuthor is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildEssay[] List of ChildEssay objects
     * @throws PropelException
     */
    public function getEssaysRelatedBySecondAuthor(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collEssaysRelatedBySecondAuthorPartial && !$this->isNew();
        if (null === $this->collEssaysRelatedBySecondAuthor || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collEssaysRelatedBySecondAuthor) {
                // return empty collection
                $this->initEssaysRelatedBySecondAuthor();
            } else {
                $collEssaysRelatedBySecondAuthor = ChildEssayQuery::create(null, $criteria)
                    ->filterByAuthorRelatedBySecondAuthor($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collEssaysRelatedBySecondAuthorPartial && count($collEssaysRelatedBySecondAuthor)) {
                        $this->initEssaysRelatedBySecondAuthor(false);

                        foreach ($collEssaysRelatedBySecondAuthor as $obj) {
                            if (false == $this->collEssaysRelatedBySecondAuthor->contains($obj)) {
                                $this->collEssaysRelatedBySecondAuthor->append($obj);
                            }
                        }

                        $this->collEssaysRelatedBySecondAuthorPartial = true;
                    }

                    return $collEssaysRelatedBySecondAuthor;
                }

                if ($partial && $this->collEssaysRelatedBySecondAuthor) {
                    foreach ($this->collEssaysRelatedBySecondAuthor as $obj) {
                        if ($obj->isNew()) {
                            $collEssaysRelatedBySecondAuthor[] = $obj;
                        }
                    }
                }

                $this->collEssaysRelatedBySecondAuthor = $collEssaysRelatedBySecondAuthor;
                $this->collEssaysRelatedBySecondAuthorPartial = false;
            }
        }

        return $this->collEssaysRelatedBySecondAuthor;
    }

    /**
     * Sets a collection of ChildEssay objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $essaysRelatedBySecondAuthor A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildAuthor The current object (for fluent API support)
     */
    public function setEssaysRelatedBySecondAuthor(Collection $essaysRelatedBySecondAuthor, ConnectionInterface $con = null)
    {
        /** @var ChildEssay[] $essaysRelatedBySecondAuthorToDelete */
        $essaysRelatedBySecondAuthorToDelete = $this->getEssaysRelatedBySecondAuthor(new Criteria(), $con)->diff($essaysRelatedBySecondAuthor);


        $this->essaysRelatedBySecondAuthorScheduledForDeletion = $essaysRelatedBySecondAuthorToDelete;

        foreach ($essaysRelatedBySecondAuthorToDelete as $essayRelatedBySecondAuthorRemoved) {
            $essayRelatedBySecondAuthorRemoved->setAuthorRelatedBySecondAuthor(null);
        }

        $this->collEssaysRelatedBySecondAuthor = null;
        foreach ($essaysRelatedBySecondAuthor as $essayRelatedBySecondAuthor) {
            $this->addEssayRelatedBySecondAuthor($essayRelatedBySecondAuthor);
        }

        $this->collEssaysRelatedBySecondAuthor = $essaysRelatedBySecondAuthor;
        $this->collEssaysRelatedBySecondAuthorPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Essay objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Essay objects.
     * @throws PropelException
     */
    public function countEssaysRelatedBySecondAuthor(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collEssaysRelatedBySecondAuthorPartial && !$this->isNew();
        if (null === $this->collEssaysRelatedBySecondAuthor || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collEssaysRelatedBySecondAuthor) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getEssaysRelatedBySecondAuthor());
            }

            $query = ChildEssayQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByAuthorRelatedBySecondAuthor($this)
                ->count($con);
        }

        return count($this->collEssaysRelatedBySecondAuthor);
    }

    /**
     * Method called to associate a ChildEssay object to this object
     * through the ChildEssay foreign key attribute.
     *
     * @param  ChildEssay $l ChildEssay
     * @return $this|\Propel\Tests\Bookstore\Author The current object (for fluent API support)
     */
    public function addEssayRelatedBySecondAuthor(ChildEssay $l)
    {
        if ($this->collEssaysRelatedBySecondAuthor === null) {
            $this->initEssaysRelatedBySecondAuthor();
            $this->collEssaysRelatedBySecondAuthorPartial = true;
        }

        if (!$this->collEssaysRelatedBySecondAuthor->contains($l)) {
            $this->doAddEssayRelatedBySecondAuthor($l);
        }

        return $this;
    }

    /**
     * @param ChildEssay $essayRelatedBySecondAuthor The ChildEssay object to add.
     */
    protected function doAddEssayRelatedBySecondAuthor(ChildEssay $essayRelatedBySecondAuthor)
    {
        $this->collEssaysRelatedBySecondAuthor[]= $essayRelatedBySecondAuthor;
        $essayRelatedBySecondAuthor->setAuthorRelatedBySecondAuthor($this);
    }

    /**
     * @param  ChildEssay $essayRelatedBySecondAuthor The ChildEssay object to remove.
     * @return $this|ChildAuthor The current object (for fluent API support)
     */
    public function removeEssayRelatedBySecondAuthor(ChildEssay $essayRelatedBySecondAuthor)
    {
        if ($this->getEssaysRelatedBySecondAuthor()->contains($essayRelatedBySecondAuthor)) {
            $pos = $this->collEssaysRelatedBySecondAuthor->search($essayRelatedBySecondAuthor);
            $this->collEssaysRelatedBySecondAuthor->remove($pos);
            if (null === $this->essaysRelatedBySecondAuthorScheduledForDeletion) {
                $this->essaysRelatedBySecondAuthorScheduledForDeletion = clone $this->collEssaysRelatedBySecondAuthor;
                $this->essaysRelatedBySecondAuthorScheduledForDeletion->clear();
            }
            $this->essaysRelatedBySecondAuthorScheduledForDeletion[]= $essayRelatedBySecondAuthor;
            $essayRelatedBySecondAuthor->setAuthorRelatedBySecondAuthor(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Author is new, it will return
     * an empty collection; or if this Author has previously
     * been saved, it will retrieve related EssaysRelatedBySecondAuthor from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Author.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildEssay[] List of ChildEssay objects
     */
    public function getEssaysRelatedBySecondAuthorJoinEssayRelatedByNextEssayId(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildEssayQuery::create(null, $criteria);
        $query->joinWith('EssayRelatedByNextEssayId', $joinBehavior);

        return $this->getEssaysRelatedBySecondAuthor($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        $this->id = null;
        $this->first_name = null;
        $this->last_name = null;
        $this->email = null;
        $this->age = null;
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
            if ($this->collBooks) {
                foreach ($this->collBooks as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collEssaysRelatedByFirstAuthor) {
                foreach ($this->collEssaysRelatedByFirstAuthor as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collEssaysRelatedBySecondAuthor) {
                foreach ($this->collEssaysRelatedBySecondAuthor as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collBooks = null;
        $this->collEssaysRelatedByFirstAuthor = null;
        $this->collEssaysRelatedBySecondAuthor = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(AuthorTableMap::DEFAULT_STRING_FORMAT);
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
