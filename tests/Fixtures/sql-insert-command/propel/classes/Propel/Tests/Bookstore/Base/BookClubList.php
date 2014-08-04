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
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;
use Propel\Tests\Bookstore\Book as ChildBook;
use Propel\Tests\Bookstore\BookClubList as ChildBookClubList;
use Propel\Tests\Bookstore\BookClubListQuery as ChildBookClubListQuery;
use Propel\Tests\Bookstore\BookListFavorite as ChildBookListFavorite;
use Propel\Tests\Bookstore\BookListFavoriteQuery as ChildBookListFavoriteQuery;
use Propel\Tests\Bookstore\BookListRel as ChildBookListRel;
use Propel\Tests\Bookstore\BookListRelQuery as ChildBookListRelQuery;
use Propel\Tests\Bookstore\BookQuery as ChildBookQuery;
use Propel\Tests\Bookstore\Map\BookClubListTableMap;

/**
 * Base class that represents a row from the 'book_club_list' table.
 *
 * Reading list for a book club.
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class BookClubList implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookClubListTableMap';


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
     * The value for the group_leader field.
     * @var        string
     */
    protected $group_leader;

    /**
     * The value for the theme field.
     * @var        string
     */
    protected $theme;

    /**
     * The value for the created_at field.
     * @var        \DateTime
     */
    protected $created_at;

    /**
     * @var        ObjectCollection|ChildBookListRel[] Collection to store aggregation of ChildBookListRel objects.
     */
    protected $collBookListRels;
    protected $collBookListRelsPartial;

    /**
     * @var        ObjectCollection|ChildBookListFavorite[] Collection to store aggregation of ChildBookListFavorite objects.
     */
    protected $collBookListFavorites;
    protected $collBookListFavoritesPartial;

    /**
     * @var        ObjectCollection|ChildBook[] Cross Collection to store aggregation of ChildBook objects.
     */
    protected $collBooks;

    /**
     * @var bool
     */
    protected $collBooksPartial;

    /**
     * @var        ObjectCollection|ChildBook[] Cross Collection to store aggregation of ChildBook objects.
     */
    protected $collFavoriteBooks;

    /**
     * @var bool
     */
    protected $collFavoriteBooksPartial;

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
     * @var ObjectCollection|ChildBook[]
     */
    protected $favoriteBooksScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookListRel[]
     */
    protected $bookListRelsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookListFavorite[]
     */
    protected $bookListFavoritesScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\BookClubList object.
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
     * Compares this with another <code>BookClubList</code> instance.  If
     * <code>obj</code> is an instance of <code>BookClubList</code>, delegates to
     * <code>equals(BookClubList)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|BookClubList The current object, for fluid interface
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
     * Unique ID for a school reading list.
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [group_leader] column value.
     * The name of the teacher in charge of summer reading.
     * @return string
     */
    public function getGroupLeader()
    {
        return $this->group_leader;
    }

    /**
     * Get the [theme] column value.
     * The theme, if applicable, for the reading list.
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->created_at;
        } else {
            return $this->created_at instanceof \DateTime ? $this->created_at->format($format) : null;
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookClubListTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookClubListTableMap::translateFieldName('GroupLeader', TableMap::TYPE_PHPNAME, $indexType)];
            $this->group_leader = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : BookClubListTableMap::translateFieldName('Theme', TableMap::TYPE_PHPNAME, $indexType)];
            $this->theme = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : BookClubListTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = BookClubListTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\BookClubList'), 0, $e);
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
     * Unique ID for a school reading list.
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[BookClubListTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [group_leader] column.
     * The name of the teacher in charge of summer reading.
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object (for fluent API support)
     */
    public function setGroupLeader($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->group_leader !== $v) {
            $this->group_leader = $v;
            $this->modifiedColumns[BookClubListTableMap::COL_GROUP_LEADER] = true;
        }

        return $this;
    } // setGroupLeader()

    /**
     * Set the value of [theme] column.
     * The theme, if applicable, for the reading list.
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object (for fluent API support)
     */
    public function setTheme($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->theme !== $v) {
            $this->theme = $v;
            $this->modifiedColumns[BookClubListTableMap::COL_THEME] = true;
        }

        return $this;
    } // setTheme()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[BookClubListTableMap::COL_CREATED_AT] = true;
            }
        } // if either are not null

        return $this;
    } // setCreatedAt()

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
            $con = Propel::getServiceContainer()->getReadConnection(BookClubListTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookClubListQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collBookListRels = null;

            $this->collBookListFavorites = null;

            $this->collBooks = null;
            $this->collFavoriteBooks = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see BookClubList::setDeleted()
     * @see BookClubList::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookClubListTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookClubListQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookClubListTableMap::DATABASE_NAME);
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
                BookClubListTableMap::addInstanceToPool($this);
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
                    $pks = array();
                    foreach ($this->booksScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[1] = $this->getId();
                        $entryPk[0] = $entry->getId();
                        $pks[] = $entryPk;
                    }

                    \Propel\Tests\Bookstore\BookListRelQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->booksScheduledForDeletion = null;
                }

            }

            if ($this->collBooks) {
                foreach ($this->collBooks as $book) {
                    if (!$book->isDeleted() && ($book->isNew() || $book->isModified())) {
                        $book->save($con);
                    }
                }
            }


            if ($this->favoriteBooksScheduledForDeletion !== null) {
                if (!$this->favoriteBooksScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->favoriteBooksScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[1] = $this->getId();
                        $entryPk[0] = $entry->getId();
                        $pks[] = $entryPk;
                    }

                    \Propel\Tests\Bookstore\BookListFavoriteQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->favoriteBooksScheduledForDeletion = null;
                }

            }

            if ($this->collFavoriteBooks) {
                foreach ($this->collFavoriteBooks as $favoriteBook) {
                    if (!$favoriteBook->isDeleted() && ($favoriteBook->isNew() || $favoriteBook->isModified())) {
                        $favoriteBook->save($con);
                    }
                }
            }


            if ($this->bookListRelsScheduledForDeletion !== null) {
                if (!$this->bookListRelsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\BookListRelQuery::create()
                        ->filterByPrimaryKeys($this->bookListRelsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->bookListRelsScheduledForDeletion = null;
                }
            }

            if ($this->collBookListRels !== null) {
                foreach ($this->collBookListRels as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->bookListFavoritesScheduledForDeletion !== null) {
                if (!$this->bookListFavoritesScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\BookListFavoriteQuery::create()
                        ->filterByPrimaryKeys($this->bookListFavoritesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->bookListFavoritesScheduledForDeletion = null;
                }
            }

            if ($this->collBookListFavorites !== null) {
                foreach ($this->collBookListFavorites as $referrerFK) {
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

        $this->modifiedColumns[BookClubListTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . BookClubListTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookClubListTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(BookClubListTableMap::COL_GROUP_LEADER)) {
            $modifiedColumns[':p' . $index++]  = 'GROUP_LEADER';
        }
        if ($this->isColumnModified(BookClubListTableMap::COL_THEME)) {
            $modifiedColumns[':p' . $index++]  = 'THEME';
        }
        if ($this->isColumnModified(BookClubListTableMap::COL_CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }

        $sql = sprintf(
            'INSERT INTO book_club_list (%s) VALUES (%s)',
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
                    case 'GROUP_LEADER':
                        $stmt->bindValue($identifier, $this->group_leader, PDO::PARAM_STR);
                        break;
                    case 'THEME':
                        $stmt->bindValue($identifier, $this->theme, PDO::PARAM_STR);
                        break;
                    case 'CREATED_AT':
                        $stmt->bindValue($identifier, $this->created_at ? $this->created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
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
        $pos = BookClubListTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getGroupLeader();
                break;
            case 2:
                return $this->getTheme();
                break;
            case 3:
                return $this->getCreatedAt();
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
        if (isset($alreadyDumpedObjects['BookClubList'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['BookClubList'][$this->getPrimaryKey()] = true;
        $keys = BookClubListTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getGroupLeader(),
            $keys[2] => $this->getTheme(),
            $keys[3] => $this->getCreatedAt(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collBookListRels) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookListRels';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'book_x_lists';
                        break;
                    default:
                        $key = 'BookListRels';
                }

                $result[$key] = $this->collBookListRels->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collBookListFavorites) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookListFavorites';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'book_club_list_favorite_bookss';
                        break;
                    default:
                        $key = 'BookListFavorites';
                }

                $result[$key] = $this->collBookListFavorites->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\BookClubList
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookClubListTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\BookClubList
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setGroupLeader($value);
                break;
            case 2:
                $this->setTheme($value);
                break;
            case 3:
                $this->setCreatedAt($value);
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
        $keys = BookClubListTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setGroupLeader($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setTheme($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setCreatedAt($arr[$keys[3]]);
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
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object, for fluid interface
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
        $criteria = new Criteria(BookClubListTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookClubListTableMap::COL_ID)) {
            $criteria->add(BookClubListTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(BookClubListTableMap::COL_GROUP_LEADER)) {
            $criteria->add(BookClubListTableMap::COL_GROUP_LEADER, $this->group_leader);
        }
        if ($this->isColumnModified(BookClubListTableMap::COL_THEME)) {
            $criteria->add(BookClubListTableMap::COL_THEME, $this->theme);
        }
        if ($this->isColumnModified(BookClubListTableMap::COL_CREATED_AT)) {
            $criteria->add(BookClubListTableMap::COL_CREATED_AT, $this->created_at);
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
        $criteria = new Criteria(BookClubListTableMap::DATABASE_NAME);
        $criteria->add(BookClubListTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\BookClubList (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setGroupLeader($this->getGroupLeader());
        $copyObj->setTheme($this->getTheme());
        $copyObj->setCreatedAt($this->getCreatedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getBookListRels() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addBookListRel($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getBookListFavorites() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addBookListFavorite($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\BookClubList Clone of current object.
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
        if ('BookListRel' == $relationName) {
            return $this->initBookListRels();
        }
        if ('BookListFavorite' == $relationName) {
            return $this->initBookListFavorites();
        }
    }

    /**
     * Clears out the collBookListRels collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBookListRels()
     */
    public function clearBookListRels()
    {
        $this->collBookListRels = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collBookListRels collection loaded partially.
     */
    public function resetPartialBookListRels($v = true)
    {
        $this->collBookListRelsPartial = $v;
    }

    /**
     * Initializes the collBookListRels collection.
     *
     * By default this just sets the collBookListRels collection to an empty array (like clearcollBookListRels());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initBookListRels($overrideExisting = true)
    {
        if (null !== $this->collBookListRels && !$overrideExisting) {
            return;
        }
        $this->collBookListRels = new ObjectCollection();
        $this->collBookListRels->setModel('\Propel\Tests\Bookstore\BookListRel');
    }

    /**
     * Gets an array of ChildBookListRel objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookClubList is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBookListRel[] List of ChildBookListRel objects
     * @throws PropelException
     */
    public function getBookListRels(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBookListRelsPartial && !$this->isNew();
        if (null === $this->collBookListRels || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collBookListRels) {
                // return empty collection
                $this->initBookListRels();
            } else {
                $collBookListRels = ChildBookListRelQuery::create(null, $criteria)
                    ->filterByBookClubList($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collBookListRelsPartial && count($collBookListRels)) {
                        $this->initBookListRels(false);

                        foreach ($collBookListRels as $obj) {
                            if (false == $this->collBookListRels->contains($obj)) {
                                $this->collBookListRels->append($obj);
                            }
                        }

                        $this->collBookListRelsPartial = true;
                    }

                    return $collBookListRels;
                }

                if ($partial && $this->collBookListRels) {
                    foreach ($this->collBookListRels as $obj) {
                        if ($obj->isNew()) {
                            $collBookListRels[] = $obj;
                        }
                    }
                }

                $this->collBookListRels = $collBookListRels;
                $this->collBookListRelsPartial = false;
            }
        }

        return $this->collBookListRels;
    }

    /**
     * Sets a collection of ChildBookListRel objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $bookListRels A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBookClubList The current object (for fluent API support)
     */
    public function setBookListRels(Collection $bookListRels, ConnectionInterface $con = null)
    {
        /** @var ChildBookListRel[] $bookListRelsToDelete */
        $bookListRelsToDelete = $this->getBookListRels(new Criteria(), $con)->diff($bookListRels);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->bookListRelsScheduledForDeletion = clone $bookListRelsToDelete;

        foreach ($bookListRelsToDelete as $bookListRelRemoved) {
            $bookListRelRemoved->setBookClubList(null);
        }

        $this->collBookListRels = null;
        foreach ($bookListRels as $bookListRel) {
            $this->addBookListRel($bookListRel);
        }

        $this->collBookListRels = $bookListRels;
        $this->collBookListRelsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related BookListRel objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related BookListRel objects.
     * @throws PropelException
     */
    public function countBookListRels(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBookListRelsPartial && !$this->isNew();
        if (null === $this->collBookListRels || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBookListRels) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getBookListRels());
            }

            $query = ChildBookListRelQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByBookClubList($this)
                ->count($con);
        }

        return count($this->collBookListRels);
    }

    /**
     * Method called to associate a ChildBookListRel object to this object
     * through the ChildBookListRel foreign key attribute.
     *
     * @param  ChildBookListRel $l ChildBookListRel
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object (for fluent API support)
     */
    public function addBookListRel(ChildBookListRel $l)
    {
        if ($this->collBookListRels === null) {
            $this->initBookListRels();
            $this->collBookListRelsPartial = true;
        }

        if (!$this->collBookListRels->contains($l)) {
            $this->doAddBookListRel($l);
        }

        return $this;
    }

    /**
     * @param ChildBookListRel $bookListRel The ChildBookListRel object to add.
     */
    protected function doAddBookListRel(ChildBookListRel $bookListRel)
    {
        $this->collBookListRels[]= $bookListRel;
        $bookListRel->setBookClubList($this);
    }

    /**
     * @param  ChildBookListRel $bookListRel The ChildBookListRel object to remove.
     * @return $this|ChildBookClubList The current object (for fluent API support)
     */
    public function removeBookListRel(ChildBookListRel $bookListRel)
    {
        if ($this->getBookListRels()->contains($bookListRel)) {
            $pos = $this->collBookListRels->search($bookListRel);
            $this->collBookListRels->remove($pos);
            if (null === $this->bookListRelsScheduledForDeletion) {
                $this->bookListRelsScheduledForDeletion = clone $this->collBookListRels;
                $this->bookListRelsScheduledForDeletion->clear();
            }
            $this->bookListRelsScheduledForDeletion[]= clone $bookListRel;
            $bookListRel->setBookClubList(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this BookClubList is new, it will return
     * an empty collection; or if this BookClubList has previously
     * been saved, it will retrieve related BookListRels from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in BookClubList.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookListRel[] List of ChildBookListRel objects
     */
    public function getBookListRelsJoinBook(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookListRelQuery::create(null, $criteria);
        $query->joinWith('Book', $joinBehavior);

        return $this->getBookListRels($query, $con);
    }

    /**
     * Clears out the collBookListFavorites collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBookListFavorites()
     */
    public function clearBookListFavorites()
    {
        $this->collBookListFavorites = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collBookListFavorites collection loaded partially.
     */
    public function resetPartialBookListFavorites($v = true)
    {
        $this->collBookListFavoritesPartial = $v;
    }

    /**
     * Initializes the collBookListFavorites collection.
     *
     * By default this just sets the collBookListFavorites collection to an empty array (like clearcollBookListFavorites());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initBookListFavorites($overrideExisting = true)
    {
        if (null !== $this->collBookListFavorites && !$overrideExisting) {
            return;
        }
        $this->collBookListFavorites = new ObjectCollection();
        $this->collBookListFavorites->setModel('\Propel\Tests\Bookstore\BookListFavorite');
    }

    /**
     * Gets an array of ChildBookListFavorite objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookClubList is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBookListFavorite[] List of ChildBookListFavorite objects
     * @throws PropelException
     */
    public function getBookListFavorites(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBookListFavoritesPartial && !$this->isNew();
        if (null === $this->collBookListFavorites || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collBookListFavorites) {
                // return empty collection
                $this->initBookListFavorites();
            } else {
                $collBookListFavorites = ChildBookListFavoriteQuery::create(null, $criteria)
                    ->filterByFavoriteBookClubList($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collBookListFavoritesPartial && count($collBookListFavorites)) {
                        $this->initBookListFavorites(false);

                        foreach ($collBookListFavorites as $obj) {
                            if (false == $this->collBookListFavorites->contains($obj)) {
                                $this->collBookListFavorites->append($obj);
                            }
                        }

                        $this->collBookListFavoritesPartial = true;
                    }

                    return $collBookListFavorites;
                }

                if ($partial && $this->collBookListFavorites) {
                    foreach ($this->collBookListFavorites as $obj) {
                        if ($obj->isNew()) {
                            $collBookListFavorites[] = $obj;
                        }
                    }
                }

                $this->collBookListFavorites = $collBookListFavorites;
                $this->collBookListFavoritesPartial = false;
            }
        }

        return $this->collBookListFavorites;
    }

    /**
     * Sets a collection of ChildBookListFavorite objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $bookListFavorites A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBookClubList The current object (for fluent API support)
     */
    public function setBookListFavorites(Collection $bookListFavorites, ConnectionInterface $con = null)
    {
        /** @var ChildBookListFavorite[] $bookListFavoritesToDelete */
        $bookListFavoritesToDelete = $this->getBookListFavorites(new Criteria(), $con)->diff($bookListFavorites);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->bookListFavoritesScheduledForDeletion = clone $bookListFavoritesToDelete;

        foreach ($bookListFavoritesToDelete as $bookListFavoriteRemoved) {
            $bookListFavoriteRemoved->setFavoriteBookClubList(null);
        }

        $this->collBookListFavorites = null;
        foreach ($bookListFavorites as $bookListFavorite) {
            $this->addBookListFavorite($bookListFavorite);
        }

        $this->collBookListFavorites = $bookListFavorites;
        $this->collBookListFavoritesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related BookListFavorite objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related BookListFavorite objects.
     * @throws PropelException
     */
    public function countBookListFavorites(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBookListFavoritesPartial && !$this->isNew();
        if (null === $this->collBookListFavorites || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBookListFavorites) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getBookListFavorites());
            }

            $query = ChildBookListFavoriteQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByFavoriteBookClubList($this)
                ->count($con);
        }

        return count($this->collBookListFavorites);
    }

    /**
     * Method called to associate a ChildBookListFavorite object to this object
     * through the ChildBookListFavorite foreign key attribute.
     *
     * @param  ChildBookListFavorite $l ChildBookListFavorite
     * @return $this|\Propel\Tests\Bookstore\BookClubList The current object (for fluent API support)
     */
    public function addBookListFavorite(ChildBookListFavorite $l)
    {
        if ($this->collBookListFavorites === null) {
            $this->initBookListFavorites();
            $this->collBookListFavoritesPartial = true;
        }

        if (!$this->collBookListFavorites->contains($l)) {
            $this->doAddBookListFavorite($l);
        }

        return $this;
    }

    /**
     * @param ChildBookListFavorite $bookListFavorite The ChildBookListFavorite object to add.
     */
    protected function doAddBookListFavorite(ChildBookListFavorite $bookListFavorite)
    {
        $this->collBookListFavorites[]= $bookListFavorite;
        $bookListFavorite->setFavoriteBookClubList($this);
    }

    /**
     * @param  ChildBookListFavorite $bookListFavorite The ChildBookListFavorite object to remove.
     * @return $this|ChildBookClubList The current object (for fluent API support)
     */
    public function removeBookListFavorite(ChildBookListFavorite $bookListFavorite)
    {
        if ($this->getBookListFavorites()->contains($bookListFavorite)) {
            $pos = $this->collBookListFavorites->search($bookListFavorite);
            $this->collBookListFavorites->remove($pos);
            if (null === $this->bookListFavoritesScheduledForDeletion) {
                $this->bookListFavoritesScheduledForDeletion = clone $this->collBookListFavorites;
                $this->bookListFavoritesScheduledForDeletion->clear();
            }
            $this->bookListFavoritesScheduledForDeletion[]= clone $bookListFavorite;
            $bookListFavorite->setFavoriteBookClubList(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this BookClubList is new, it will return
     * an empty collection; or if this BookClubList has previously
     * been saved, it will retrieve related BookListFavorites from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in BookClubList.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookListFavorite[] List of ChildBookListFavorite objects
     */
    public function getBookListFavoritesJoinFavoriteBook(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookListFavoriteQuery::create(null, $criteria);
        $query->joinWith('FavoriteBook', $joinBehavior);

        return $this->getBookListFavorites($query, $con);
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
     * Initializes the collBooks collection.
     *
     * By default this just sets the collBooks collection to an empty collection (like clearBooks());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initBooks()
    {
        $this->collBooks = new ObjectCollection();
        $this->collBooksPartial = true;

        $this->collBooks->setModel('\Propel\Tests\Bookstore\Book');
    }

    /**
     * Checks if the collBooks collection is loaded.
     *
     * @return bool
     */
    public function isBooksLoaded()
    {
        return null !== $this->collBooks;
    }

    /**
     * Gets a collection of ChildBook objects related by a many-to-many relationship
     * to the current object by way of the book_x_list cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookClubList is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildBook[] List of ChildBook objects
     */
    public function getBooks(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBooksPartial && !$this->isNew();
        if (null === $this->collBooks || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collBooks) {
                    $this->initBooks();
                }
            } else {

                $query = ChildBookQuery::create(null, $criteria)
                    ->filterByBookClubList($this);
                $collBooks = $query->find($con);
                if (null !== $criteria) {
                    return $collBooks;
                }

                if ($partial && $this->collBooks) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collBooks as $obj) {
                        if (!$collBooks->contains($obj)) {
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
     * Sets a collection of Book objects related by a many-to-many relationship
     * to the current object by way of the book_x_list cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $books A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildBookClubList The current object (for fluent API support)
     */
    public function setBooks(Collection $books, ConnectionInterface $con = null)
    {
        $this->clearBooks();
        $currentBooks = $this->getBooks();

        $booksScheduledForDeletion = $currentBooks->diff($books);

        foreach ($booksScheduledForDeletion as $toDelete) {
            $this->removeBook($toDelete);
        }

        foreach ($books as $book) {
            if (!$currentBooks->contains($book)) {
                $this->doAddBook($book);
            }
        }

        $this->collBooksPartial = false;
        $this->collBooks = $books;

        return $this;
    }

    /**
     * Gets the number of Book objects related by a many-to-many relationship
     * to the current object by way of the book_x_list cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related Book objects
     */
    public function countBooks(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBooksPartial && !$this->isNew();
        if (null === $this->collBooks || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBooks) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getBooks());
                }

                $query = ChildBookQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByBookClubList($this)
                    ->count($con);
            }
        } else {
            return count($this->collBooks);
        }
    }

    /**
     * Associate a ChildBook to this object
     * through the book_x_list cross reference table.
     *
     * @param ChildBook $book
     * @return ChildBookClubList The current object (for fluent API support)
     */
    public function addBook(ChildBook $book)
    {
        if ($this->collBooks === null) {
            $this->initBooks();
        }

        if (!$this->getBooks()->contains($book)) {
            // only add it if the **same** object is not already associated
            $this->collBooks->push($book);
            $this->doAddBook($book);
        }

        return $this;
    }

    /**
     *
     * @param ChildBook $book
     */
    protected function doAddBook(ChildBook $book)
    {
        $bookListRel = new ChildBookListRel();

        $bookListRel->setBook($book);

        $bookListRel->setBookClubList($this);

        $this->addBookListRel($bookListRel);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$book->isBookClubListsLoaded()) {
            $book->initBookClubLists();
            $book->getBookClubLists()->push($this);
        } elseif (!$book->getBookClubLists()->contains($this)) {
            $book->getBookClubLists()->push($this);
        }

    }

    /**
     * Remove book of this object
     * through the book_x_list cross reference table.
     *
     * @param ChildBook $book
     * @return ChildBookClubList The current object (for fluent API support)
     */
    public function removeBook(ChildBook $book)
    {
        if ($this->getBooks()->contains($book)) { $bookListRel = new ChildBookListRel();

            $bookListRel->setBook($book);
            if ($book->isBookClubListsLoaded()) {
                //remove the back reference if available
                $book->getBookClubLists()->removeObject($this);
            }

            $bookListRel->setBookClubList($this);
            $this->removeBookListRel(clone $bookListRel);
            $bookListRel->clear();

            $this->collBooks->remove($this->collBooks->search($book));

            if (null === $this->booksScheduledForDeletion) {
                $this->booksScheduledForDeletion = clone $this->collBooks;
                $this->booksScheduledForDeletion->clear();
            }

            $this->booksScheduledForDeletion->push($book);
        }


        return $this;
    }

    /**
     * Clears out the collFavoriteBooks collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addFavoriteBooks()
     */
    public function clearFavoriteBooks()
    {
        $this->collFavoriteBooks = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the collFavoriteBooks collection.
     *
     * By default this just sets the collFavoriteBooks collection to an empty collection (like clearFavoriteBooks());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initFavoriteBooks()
    {
        $this->collFavoriteBooks = new ObjectCollection();
        $this->collFavoriteBooksPartial = true;

        $this->collFavoriteBooks->setModel('\Propel\Tests\Bookstore\Book');
    }

    /**
     * Checks if the collFavoriteBooks collection is loaded.
     *
     * @return bool
     */
    public function isFavoriteBooksLoaded()
    {
        return null !== $this->collFavoriteBooks;
    }

    /**
     * Gets a collection of ChildBook objects related by a many-to-many relationship
     * to the current object by way of the book_club_list_favorite_books cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookClubList is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildBook[] List of ChildBook objects
     */
    public function getFavoriteBooks(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collFavoriteBooksPartial && !$this->isNew();
        if (null === $this->collFavoriteBooks || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collFavoriteBooks) {
                    $this->initFavoriteBooks();
                }
            } else {

                $query = ChildBookQuery::create(null, $criteria)
                    ->filterByFavoriteBookClubList($this);
                $collFavoriteBooks = $query->find($con);
                if (null !== $criteria) {
                    return $collFavoriteBooks;
                }

                if ($partial && $this->collFavoriteBooks) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collFavoriteBooks as $obj) {
                        if (!$collFavoriteBooks->contains($obj)) {
                            $collFavoriteBooks[] = $obj;
                        }
                    }
                }

                $this->collFavoriteBooks = $collFavoriteBooks;
                $this->collFavoriteBooksPartial = false;
            }
        }

        return $this->collFavoriteBooks;
    }

    /**
     * Sets a collection of Book objects related by a many-to-many relationship
     * to the current object by way of the book_club_list_favorite_books cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $favoriteBooks A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildBookClubList The current object (for fluent API support)
     */
    public function setFavoriteBooks(Collection $favoriteBooks, ConnectionInterface $con = null)
    {
        $this->clearFavoriteBooks();
        $currentFavoriteBooks = $this->getFavoriteBooks();

        $favoriteBooksScheduledForDeletion = $currentFavoriteBooks->diff($favoriteBooks);

        foreach ($favoriteBooksScheduledForDeletion as $toDelete) {
            $this->removeFavoriteBook($toDelete);
        }

        foreach ($favoriteBooks as $favoriteBook) {
            if (!$currentFavoriteBooks->contains($favoriteBook)) {
                $this->doAddFavoriteBook($favoriteBook);
            }
        }

        $this->collFavoriteBooksPartial = false;
        $this->collFavoriteBooks = $favoriteBooks;

        return $this;
    }

    /**
     * Gets the number of Book objects related by a many-to-many relationship
     * to the current object by way of the book_club_list_favorite_books cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related Book objects
     */
    public function countFavoriteBooks(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collFavoriteBooksPartial && !$this->isNew();
        if (null === $this->collFavoriteBooks || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collFavoriteBooks) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getFavoriteBooks());
                }

                $query = ChildBookQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByFavoriteBookClubList($this)
                    ->count($con);
            }
        } else {
            return count($this->collFavoriteBooks);
        }
    }

    /**
     * Associate a ChildBook to this object
     * through the book_club_list_favorite_books cross reference table.
     *
     * @param ChildBook $favoriteBook
     * @return ChildBookClubList The current object (for fluent API support)
     */
    public function addFavoriteBook(ChildBook $favoriteBook)
    {
        if ($this->collFavoriteBooks === null) {
            $this->initFavoriteBooks();
        }

        if (!$this->getFavoriteBooks()->contains($favoriteBook)) {
            // only add it if the **same** object is not already associated
            $this->collFavoriteBooks->push($favoriteBook);
            $this->doAddFavoriteBook($favoriteBook);
        }

        return $this;
    }

    /**
     *
     * @param ChildBook $favoriteBook
     */
    protected function doAddFavoriteBook(ChildBook $favoriteBook)
    {
        $bookListFavorite = new ChildBookListFavorite();

        $bookListFavorite->setFavoriteBook($favoriteBook);

        $bookListFavorite->setFavoriteBookClubList($this);

        $this->addBookListFavorite($bookListFavorite);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$favoriteBook->isFavoriteBookClubListsLoaded()) {
            $favoriteBook->initFavoriteBookClubLists();
            $favoriteBook->getFavoriteBookClubLists()->push($this);
        } elseif (!$favoriteBook->getFavoriteBookClubLists()->contains($this)) {
            $favoriteBook->getFavoriteBookClubLists()->push($this);
        }

    }

    /**
     * Remove favoriteBook of this object
     * through the book_club_list_favorite_books cross reference table.
     *
     * @param ChildBook $favoriteBook
     * @return ChildBookClubList The current object (for fluent API support)
     */
    public function removeFavoriteBook(ChildBook $favoriteBook)
    {
        if ($this->getFavoriteBooks()->contains($favoriteBook)) { $bookListFavorite = new ChildBookListFavorite();

            $bookListFavorite->setFavoriteBook($favoriteBook);
            if ($favoriteBook->isFavoriteBookClubListsLoaded()) {
                //remove the back reference if available
                $favoriteBook->getFavoriteBookClubLists()->removeObject($this);
            }

            $bookListFavorite->setFavoriteBookClubList($this);
            $this->removeBookListFavorite(clone $bookListFavorite);
            $bookListFavorite->clear();

            $this->collFavoriteBooks->remove($this->collFavoriteBooks->search($favoriteBook));

            if (null === $this->favoriteBooksScheduledForDeletion) {
                $this->favoriteBooksScheduledForDeletion = clone $this->collFavoriteBooks;
                $this->favoriteBooksScheduledForDeletion->clear();
            }

            $this->favoriteBooksScheduledForDeletion->push($favoriteBook);
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
        $this->id = null;
        $this->group_leader = null;
        $this->theme = null;
        $this->created_at = null;
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
            if ($this->collBookListRels) {
                foreach ($this->collBookListRels as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collBookListFavorites) {
                foreach ($this->collBookListFavorites as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collBooks) {
                foreach ($this->collBooks as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collFavoriteBooks) {
                foreach ($this->collFavoriteBooks as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collBookListRels = null;
        $this->collBookListFavorites = null;
        $this->collBooks = null;
        $this->collFavoriteBooks = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(BookClubListTableMap::DEFAULT_STRING_FORMAT);
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
