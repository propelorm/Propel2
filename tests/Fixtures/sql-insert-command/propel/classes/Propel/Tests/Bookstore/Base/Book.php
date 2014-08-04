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
use Propel\Tests\Bookstore\BookClubList as ChildBookClubList;
use Propel\Tests\Bookstore\BookClubListQuery as ChildBookClubListQuery;
use Propel\Tests\Bookstore\BookListFavorite as ChildBookListFavorite;
use Propel\Tests\Bookstore\BookListFavoriteQuery as ChildBookListFavoriteQuery;
use Propel\Tests\Bookstore\BookListRel as ChildBookListRel;
use Propel\Tests\Bookstore\BookListRelQuery as ChildBookListRelQuery;
use Propel\Tests\Bookstore\BookOpinion as ChildBookOpinion;
use Propel\Tests\Bookstore\BookOpinionQuery as ChildBookOpinionQuery;
use Propel\Tests\Bookstore\BookQuery as ChildBookQuery;
use Propel\Tests\Bookstore\BookSummary as ChildBookSummary;
use Propel\Tests\Bookstore\BookSummaryQuery as ChildBookSummaryQuery;
use Propel\Tests\Bookstore\BookstoreContest as ChildBookstoreContest;
use Propel\Tests\Bookstore\BookstoreContestQuery as ChildBookstoreContestQuery;
use Propel\Tests\Bookstore\Media as ChildMedia;
use Propel\Tests\Bookstore\MediaQuery as ChildMediaQuery;
use Propel\Tests\Bookstore\Publisher as ChildPublisher;
use Propel\Tests\Bookstore\PublisherQuery as ChildPublisherQuery;
use Propel\Tests\Bookstore\ReaderFavorite as ChildReaderFavorite;
use Propel\Tests\Bookstore\ReaderFavoriteQuery as ChildReaderFavoriteQuery;
use Propel\Tests\Bookstore\Review as ChildReview;
use Propel\Tests\Bookstore\ReviewQuery as ChildReviewQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Base class that represents a row from the 'book' table.
 *
 * Book Table
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class Book implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookTableMap';


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
     * @var        ChildPublisher
     */
    protected $aPublisher;

    /**
     * @var        ChildAuthor
     */
    protected $aAuthor;

    /**
     * @var        ObjectCollection|ChildBookSummary[] Collection to store aggregation of ChildBookSummary objects.
     */
    protected $collBookSummaries;
    protected $collBookSummariesPartial;

    /**
     * @var        ObjectCollection|ChildReview[] Collection to store aggregation of ChildReview objects.
     */
    protected $collReviews;
    protected $collReviewsPartial;

    /**
     * @var        ObjectCollection|ChildMedia[] Collection to store aggregation of ChildMedia objects.
     */
    protected $collMedias;
    protected $collMediasPartial;

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
     * @var        ObjectCollection|ChildBookstoreContest[] Collection to store aggregation of ChildBookstoreContest objects.
     */
    protected $collBookstoreContests;
    protected $collBookstoreContestsPartial;

    /**
     * @var        ObjectCollection|ChildBookClubList[] Cross Collection to store aggregation of ChildBookClubList objects.
     */
    protected $collBookClubLists;

    /**
     * @var bool
     */
    protected $collBookClubListsPartial;

    /**
     * @var        ObjectCollection|ChildBookClubList[] Cross Collection to store aggregation of ChildBookClubList objects.
     */
    protected $collFavoriteBookClubLists;

    /**
     * @var bool
     */
    protected $collFavoriteBookClubListsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookClubList[]
     */
    protected $bookClubListsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookClubList[]
     */
    protected $favoriteBookClubListsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookSummary[]
     */
    protected $bookSummariesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildReview[]
     */
    protected $reviewsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildMedia[]
     */
    protected $mediasScheduledForDeletion = null;

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
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildBookstoreContest[]
     */
    protected $bookstoreContestsScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\Book object.
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
     * Compares this with another <code>Book</code> instance.  If
     * <code>obj</code> is an instance of <code>Book</code>, delegates to
     * <code>equals(Book)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|Book The current object, for fluid interface
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookTableMap::translateFieldName('Title', TableMap::TYPE_PHPNAME, $indexType)];
            $this->title = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : BookTableMap::translateFieldName('ISBN', TableMap::TYPE_PHPNAME, $indexType)];
            $this->isbn = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : BookTableMap::translateFieldName('Price', TableMap::TYPE_PHPNAME, $indexType)];
            $this->price = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : BookTableMap::translateFieldName('PublisherId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->publisher_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : BookTableMap::translateFieldName('AuthorId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->author_id = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = BookTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\Book'), 0, $e);
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
        if ($this->aPublisher !== null && $this->publisher_id !== $this->aPublisher->getId()) {
            $this->aPublisher = null;
        }
        if ($this->aAuthor !== null && $this->author_id !== $this->aAuthor->getId()) {
            $this->aAuthor = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [id] column.
     * Book Id
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[BookTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [title] column.
     * Book Title
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function setTitle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->title !== $v) {
            $this->title = $v;
            $this->modifiedColumns[BookTableMap::COL_TITLE] = true;
        }

        return $this;
    } // setTitle()

    /**
     * Set the value of [isbn] column.
     * ISBN Number
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function setISBN($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->isbn !== $v) {
            $this->isbn = $v;
            $this->modifiedColumns[BookTableMap::COL_ISBN] = true;
        }

        return $this;
    } // setISBN()

    /**
     * Set the value of [price] column.
     * Price of the book.
     * @param  double $v new value
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function setPrice($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->price !== $v) {
            $this->price = $v;
            $this->modifiedColumns[BookTableMap::COL_PRICE] = true;
        }

        return $this;
    } // setPrice()

    /**
     * Set the value of [publisher_id] column.
     * Foreign Key Publisher
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function setPublisherId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->publisher_id !== $v) {
            $this->publisher_id = $v;
            $this->modifiedColumns[BookTableMap::COL_PUBLISHER_ID] = true;
        }

        if ($this->aPublisher !== null && $this->aPublisher->getId() !== $v) {
            $this->aPublisher = null;
        }

        return $this;
    } // setPublisherId()

    /**
     * Set the value of [author_id] column.
     * Foreign Key Author
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function setAuthorId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->author_id !== $v) {
            $this->author_id = $v;
            $this->modifiedColumns[BookTableMap::COL_AUTHOR_ID] = true;
        }

        if ($this->aAuthor !== null && $this->aAuthor->getId() !== $v) {
            $this->aAuthor = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(BookTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aPublisher = null;
            $this->aAuthor = null;
            $this->collBookSummaries = null;

            $this->collReviews = null;

            $this->collMedias = null;

            $this->collBookListRels = null;

            $this->collBookListFavorites = null;

            $this->collBookOpinions = null;

            $this->collReaderFavorites = null;

            $this->collBookstoreContests = null;

            $this->collBookClubLists = null;
            $this->collFavoriteBookClubLists = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Book::setDeleted()
     * @see Book::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
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
                BookTableMap::addInstanceToPool($this);
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

            if ($this->aPublisher !== null) {
                if ($this->aPublisher->isModified() || $this->aPublisher->isNew()) {
                    $affectedRows += $this->aPublisher->save($con);
                }
                $this->setPublisher($this->aPublisher);
            }

            if ($this->aAuthor !== null) {
                if ($this->aAuthor->isModified() || $this->aAuthor->isNew()) {
                    $affectedRows += $this->aAuthor->save($con);
                }
                $this->setAuthor($this->aAuthor);
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

            if ($this->bookClubListsScheduledForDeletion !== null) {
                if (!$this->bookClubListsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->bookClubListsScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[0] = $this->getId();
                        $entryPk[1] = $entry->getId();
                        $pks[] = $entryPk;
                    }

                    \Propel\Tests\Bookstore\BookListRelQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->bookClubListsScheduledForDeletion = null;
                }

            }

            if ($this->collBookClubLists) {
                foreach ($this->collBookClubLists as $bookClubList) {
                    if (!$bookClubList->isDeleted() && ($bookClubList->isNew() || $bookClubList->isModified())) {
                        $bookClubList->save($con);
                    }
                }
            }


            if ($this->favoriteBookClubListsScheduledForDeletion !== null) {
                if (!$this->favoriteBookClubListsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->favoriteBookClubListsScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[0] = $this->getId();
                        $entryPk[1] = $entry->getId();
                        $pks[] = $entryPk;
                    }

                    \Propel\Tests\Bookstore\BookListFavoriteQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->favoriteBookClubListsScheduledForDeletion = null;
                }

            }

            if ($this->collFavoriteBookClubLists) {
                foreach ($this->collFavoriteBookClubLists as $favoriteBookClubList) {
                    if (!$favoriteBookClubList->isDeleted() && ($favoriteBookClubList->isNew() || $favoriteBookClubList->isModified())) {
                        $favoriteBookClubList->save($con);
                    }
                }
            }


            if ($this->bookSummariesScheduledForDeletion !== null) {
                if (!$this->bookSummariesScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\BookSummaryQuery::create()
                        ->filterByPrimaryKeys($this->bookSummariesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->bookSummariesScheduledForDeletion = null;
                }
            }

            if ($this->collBookSummaries !== null) {
                foreach ($this->collBookSummaries as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->reviewsScheduledForDeletion !== null) {
                if (!$this->reviewsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\ReviewQuery::create()
                        ->filterByPrimaryKeys($this->reviewsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->reviewsScheduledForDeletion = null;
                }
            }

            if ($this->collReviews !== null) {
                foreach ($this->collReviews as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->mediasScheduledForDeletion !== null) {
                if (!$this->mediasScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\MediaQuery::create()
                        ->filterByPrimaryKeys($this->mediasScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->mediasScheduledForDeletion = null;
                }
            }

            if ($this->collMedias !== null) {
                foreach ($this->collMedias as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
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

            if ($this->bookstoreContestsScheduledForDeletion !== null) {
                if (!$this->bookstoreContestsScheduledForDeletion->isEmpty()) {
                    foreach ($this->bookstoreContestsScheduledForDeletion as $bookstoreContest) {
                        // need to save related object because we set the relation to null
                        $bookstoreContest->save($con);
                    }
                    $this->bookstoreContestsScheduledForDeletion = null;
                }
            }

            if ($this->collBookstoreContests !== null) {
                foreach ($this->collBookstoreContests as $referrerFK) {
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

        $this->modifiedColumns[BookTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . BookTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(BookTableMap::COL_TITLE)) {
            $modifiedColumns[':p' . $index++]  = 'TITLE';
        }
        if ($this->isColumnModified(BookTableMap::COL_ISBN)) {
            $modifiedColumns[':p' . $index++]  = 'ISBN';
        }
        if ($this->isColumnModified(BookTableMap::COL_PRICE)) {
            $modifiedColumns[':p' . $index++]  = 'PRICE';
        }
        if ($this->isColumnModified(BookTableMap::COL_PUBLISHER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'PUBLISHER_ID';
        }
        if ($this->isColumnModified(BookTableMap::COL_AUTHOR_ID)) {
            $modifiedColumns[':p' . $index++]  = 'AUTHOR_ID';
        }

        $sql = sprintf(
            'INSERT INTO book (%s) VALUES (%s)',
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
        $pos = BookTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
        if (isset($alreadyDumpedObjects['Book'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Book'][$this->getPrimaryKey()] = true;
        $keys = BookTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getTitle(),
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
            if (null !== $this->aAuthor) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'author';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'author';
                        break;
                    default:
                        $key = 'Author';
                }

                $result[$key] = $this->aAuthor->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collBookSummaries) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookSummaries';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'book_summaries';
                        break;
                    default:
                        $key = 'BookSummaries';
                }

                $result[$key] = $this->collBookSummaries->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collReviews) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'reviews';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'reviews';
                        break;
                    default:
                        $key = 'Reviews';
                }

                $result[$key] = $this->collReviews->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collMedias) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'medias';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'medias';
                        break;
                    default:
                        $key = 'Medias';
                }

                $result[$key] = $this->collMedias->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
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
            if (null !== $this->collBookstoreContests) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'bookstoreContests';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'bookstore_contests';
                        break;
                    default:
                        $key = 'BookstoreContests';
                }

                $result[$key] = $this->collBookstoreContests->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\Book
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\Book
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
        $keys = BookTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setTitle($arr[$keys[1]]);
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
     * @return $this|\Propel\Tests\Bookstore\Book The current object, for fluid interface
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
        $criteria = new Criteria(BookTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookTableMap::COL_ID)) {
            $criteria->add(BookTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(BookTableMap::COL_TITLE)) {
            $criteria->add(BookTableMap::COL_TITLE, $this->title);
        }
        if ($this->isColumnModified(BookTableMap::COL_ISBN)) {
            $criteria->add(BookTableMap::COL_ISBN, $this->isbn);
        }
        if ($this->isColumnModified(BookTableMap::COL_PRICE)) {
            $criteria->add(BookTableMap::COL_PRICE, $this->price);
        }
        if ($this->isColumnModified(BookTableMap::COL_PUBLISHER_ID)) {
            $criteria->add(BookTableMap::COL_PUBLISHER_ID, $this->publisher_id);
        }
        if ($this->isColumnModified(BookTableMap::COL_AUTHOR_ID)) {
            $criteria->add(BookTableMap::COL_AUTHOR_ID, $this->author_id);
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
        $criteria = new Criteria(BookTableMap::DATABASE_NAME);
        $criteria->add(BookTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\Book (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setTitle($this->getTitle());
        $copyObj->setISBN($this->getISBN());
        $copyObj->setPrice($this->getPrice());
        $copyObj->setPublisherId($this->getPublisherId());
        $copyObj->setAuthorId($this->getAuthorId());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getBookSummaries() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addBookSummary($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getReviews() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addReview($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getMedias() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addMedia($relObj->copy($deepCopy));
                }
            }

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

            foreach ($this->getBookstoreContests() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addBookstoreContest($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\Book Clone of current object.
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
     * Declares an association between this object and a ChildPublisher object.
     *
     * @param  ChildPublisher $v
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
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
            $v->addBook($this);
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
                $this->aPublisher->addBooks($this);
             */
        }

        return $this->aPublisher;
    }

    /**
     * Declares an association between this object and a ChildAuthor object.
     *
     * @param  ChildAuthor $v
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     * @throws PropelException
     */
    public function setAuthor(ChildAuthor $v = null)
    {
        if ($v === null) {
            $this->setAuthorId(NULL);
        } else {
            $this->setAuthorId($v->getId());
        }

        $this->aAuthor = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildAuthor object, it will not be re-added.
        if ($v !== null) {
            $v->addBook($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildAuthor object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildAuthor The associated ChildAuthor object.
     * @throws PropelException
     */
    public function getAuthor(ConnectionInterface $con = null)
    {
        if ($this->aAuthor === null && ($this->author_id !== null)) {
            $this->aAuthor = ChildAuthorQuery::create()->findPk($this->author_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aAuthor->addBooks($this);
             */
        }

        return $this->aAuthor;
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
        if ('BookSummary' == $relationName) {
            return $this->initBookSummaries();
        }
        if ('Review' == $relationName) {
            return $this->initReviews();
        }
        if ('Media' == $relationName) {
            return $this->initMedias();
        }
        if ('BookListRel' == $relationName) {
            return $this->initBookListRels();
        }
        if ('BookListFavorite' == $relationName) {
            return $this->initBookListFavorites();
        }
        if ('BookOpinion' == $relationName) {
            return $this->initBookOpinions();
        }
        if ('ReaderFavorite' == $relationName) {
            return $this->initReaderFavorites();
        }
        if ('BookstoreContest' == $relationName) {
            return $this->initBookstoreContests();
        }
    }

    /**
     * Clears out the collBookSummaries collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBookSummaries()
     */
    public function clearBookSummaries()
    {
        $this->collBookSummaries = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collBookSummaries collection loaded partially.
     */
    public function resetPartialBookSummaries($v = true)
    {
        $this->collBookSummariesPartial = $v;
    }

    /**
     * Initializes the collBookSummaries collection.
     *
     * By default this just sets the collBookSummaries collection to an empty array (like clearcollBookSummaries());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initBookSummaries($overrideExisting = true)
    {
        if (null !== $this->collBookSummaries && !$overrideExisting) {
            return;
        }
        $this->collBookSummaries = new ObjectCollection();
        $this->collBookSummaries->setModel('\Propel\Tests\Bookstore\BookSummary');
    }

    /**
     * Gets an array of ChildBookSummary objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBookSummary[] List of ChildBookSummary objects
     * @throws PropelException
     */
    public function getBookSummaries(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBookSummariesPartial && !$this->isNew();
        if (null === $this->collBookSummaries || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collBookSummaries) {
                // return empty collection
                $this->initBookSummaries();
            } else {
                $collBookSummaries = ChildBookSummaryQuery::create(null, $criteria)
                    ->filterBySummarizedBook($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collBookSummariesPartial && count($collBookSummaries)) {
                        $this->initBookSummaries(false);

                        foreach ($collBookSummaries as $obj) {
                            if (false == $this->collBookSummaries->contains($obj)) {
                                $this->collBookSummaries->append($obj);
                            }
                        }

                        $this->collBookSummariesPartial = true;
                    }

                    return $collBookSummaries;
                }

                if ($partial && $this->collBookSummaries) {
                    foreach ($this->collBookSummaries as $obj) {
                        if ($obj->isNew()) {
                            $collBookSummaries[] = $obj;
                        }
                    }
                }

                $this->collBookSummaries = $collBookSummaries;
                $this->collBookSummariesPartial = false;
            }
        }

        return $this->collBookSummaries;
    }

    /**
     * Sets a collection of ChildBookSummary objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $bookSummaries A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function setBookSummaries(Collection $bookSummaries, ConnectionInterface $con = null)
    {
        /** @var ChildBookSummary[] $bookSummariesToDelete */
        $bookSummariesToDelete = $this->getBookSummaries(new Criteria(), $con)->diff($bookSummaries);


        $this->bookSummariesScheduledForDeletion = $bookSummariesToDelete;

        foreach ($bookSummariesToDelete as $bookSummaryRemoved) {
            $bookSummaryRemoved->setSummarizedBook(null);
        }

        $this->collBookSummaries = null;
        foreach ($bookSummaries as $bookSummary) {
            $this->addBookSummary($bookSummary);
        }

        $this->collBookSummaries = $bookSummaries;
        $this->collBookSummariesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related BookSummary objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related BookSummary objects.
     * @throws PropelException
     */
    public function countBookSummaries(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBookSummariesPartial && !$this->isNew();
        if (null === $this->collBookSummaries || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBookSummaries) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getBookSummaries());
            }

            $query = ChildBookSummaryQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterBySummarizedBook($this)
                ->count($con);
        }

        return count($this->collBookSummaries);
    }

    /**
     * Method called to associate a ChildBookSummary object to this object
     * through the ChildBookSummary foreign key attribute.
     *
     * @param  ChildBookSummary $l ChildBookSummary
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function addBookSummary(ChildBookSummary $l)
    {
        if ($this->collBookSummaries === null) {
            $this->initBookSummaries();
            $this->collBookSummariesPartial = true;
        }

        if (!$this->collBookSummaries->contains($l)) {
            $this->doAddBookSummary($l);
        }

        return $this;
    }

    /**
     * @param ChildBookSummary $bookSummary The ChildBookSummary object to add.
     */
    protected function doAddBookSummary(ChildBookSummary $bookSummary)
    {
        $this->collBookSummaries[]= $bookSummary;
        $bookSummary->setSummarizedBook($this);
    }

    /**
     * @param  ChildBookSummary $bookSummary The ChildBookSummary object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function removeBookSummary(ChildBookSummary $bookSummary)
    {
        if ($this->getBookSummaries()->contains($bookSummary)) {
            $pos = $this->collBookSummaries->search($bookSummary);
            $this->collBookSummaries->remove($pos);
            if (null === $this->bookSummariesScheduledForDeletion) {
                $this->bookSummariesScheduledForDeletion = clone $this->collBookSummaries;
                $this->bookSummariesScheduledForDeletion->clear();
            }
            $this->bookSummariesScheduledForDeletion[]= clone $bookSummary;
            $bookSummary->setSummarizedBook(null);
        }

        return $this;
    }

    /**
     * Clears out the collReviews collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addReviews()
     */
    public function clearReviews()
    {
        $this->collReviews = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collReviews collection loaded partially.
     */
    public function resetPartialReviews($v = true)
    {
        $this->collReviewsPartial = $v;
    }

    /**
     * Initializes the collReviews collection.
     *
     * By default this just sets the collReviews collection to an empty array (like clearcollReviews());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initReviews($overrideExisting = true)
    {
        if (null !== $this->collReviews && !$overrideExisting) {
            return;
        }
        $this->collReviews = new ObjectCollection();
        $this->collReviews->setModel('\Propel\Tests\Bookstore\Review');
    }

    /**
     * Gets an array of ChildReview objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildReview[] List of ChildReview objects
     * @throws PropelException
     */
    public function getReviews(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collReviewsPartial && !$this->isNew();
        if (null === $this->collReviews || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collReviews) {
                // return empty collection
                $this->initReviews();
            } else {
                $collReviews = ChildReviewQuery::create(null, $criteria)
                    ->filterByBook($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collReviewsPartial && count($collReviews)) {
                        $this->initReviews(false);

                        foreach ($collReviews as $obj) {
                            if (false == $this->collReviews->contains($obj)) {
                                $this->collReviews->append($obj);
                            }
                        }

                        $this->collReviewsPartial = true;
                    }

                    return $collReviews;
                }

                if ($partial && $this->collReviews) {
                    foreach ($this->collReviews as $obj) {
                        if ($obj->isNew()) {
                            $collReviews[] = $obj;
                        }
                    }
                }

                $this->collReviews = $collReviews;
                $this->collReviewsPartial = false;
            }
        }

        return $this->collReviews;
    }

    /**
     * Sets a collection of ChildReview objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $reviews A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function setReviews(Collection $reviews, ConnectionInterface $con = null)
    {
        /** @var ChildReview[] $reviewsToDelete */
        $reviewsToDelete = $this->getReviews(new Criteria(), $con)->diff($reviews);


        $this->reviewsScheduledForDeletion = $reviewsToDelete;

        foreach ($reviewsToDelete as $reviewRemoved) {
            $reviewRemoved->setBook(null);
        }

        $this->collReviews = null;
        foreach ($reviews as $review) {
            $this->addReview($review);
        }

        $this->collReviews = $reviews;
        $this->collReviewsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Review objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Review objects.
     * @throws PropelException
     */
    public function countReviews(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collReviewsPartial && !$this->isNew();
        if (null === $this->collReviews || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collReviews) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getReviews());
            }

            $query = ChildReviewQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByBook($this)
                ->count($con);
        }

        return count($this->collReviews);
    }

    /**
     * Method called to associate a ChildReview object to this object
     * through the ChildReview foreign key attribute.
     *
     * @param  ChildReview $l ChildReview
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function addReview(ChildReview $l)
    {
        if ($this->collReviews === null) {
            $this->initReviews();
            $this->collReviewsPartial = true;
        }

        if (!$this->collReviews->contains($l)) {
            $this->doAddReview($l);
        }

        return $this;
    }

    /**
     * @param ChildReview $review The ChildReview object to add.
     */
    protected function doAddReview(ChildReview $review)
    {
        $this->collReviews[]= $review;
        $review->setBook($this);
    }

    /**
     * @param  ChildReview $review The ChildReview object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function removeReview(ChildReview $review)
    {
        if ($this->getReviews()->contains($review)) {
            $pos = $this->collReviews->search($review);
            $this->collReviews->remove($pos);
            if (null === $this->reviewsScheduledForDeletion) {
                $this->reviewsScheduledForDeletion = clone $this->collReviews;
                $this->reviewsScheduledForDeletion->clear();
            }
            $this->reviewsScheduledForDeletion[]= $review;
            $review->setBook(null);
        }

        return $this;
    }

    /**
     * Clears out the collMedias collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addMedias()
     */
    public function clearMedias()
    {
        $this->collMedias = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collMedias collection loaded partially.
     */
    public function resetPartialMedias($v = true)
    {
        $this->collMediasPartial = $v;
    }

    /**
     * Initializes the collMedias collection.
     *
     * By default this just sets the collMedias collection to an empty array (like clearcollMedias());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initMedias($overrideExisting = true)
    {
        if (null !== $this->collMedias && !$overrideExisting) {
            return;
        }
        $this->collMedias = new ObjectCollection();
        $this->collMedias->setModel('\Propel\Tests\Bookstore\Media');
    }

    /**
     * Gets an array of ChildMedia objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildMedia[] List of ChildMedia objects
     * @throws PropelException
     */
    public function getMedias(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collMediasPartial && !$this->isNew();
        if (null === $this->collMedias || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collMedias) {
                // return empty collection
                $this->initMedias();
            } else {
                $collMedias = ChildMediaQuery::create(null, $criteria)
                    ->filterByBook($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collMediasPartial && count($collMedias)) {
                        $this->initMedias(false);

                        foreach ($collMedias as $obj) {
                            if (false == $this->collMedias->contains($obj)) {
                                $this->collMedias->append($obj);
                            }
                        }

                        $this->collMediasPartial = true;
                    }

                    return $collMedias;
                }

                if ($partial && $this->collMedias) {
                    foreach ($this->collMedias as $obj) {
                        if ($obj->isNew()) {
                            $collMedias[] = $obj;
                        }
                    }
                }

                $this->collMedias = $collMedias;
                $this->collMediasPartial = false;
            }
        }

        return $this->collMedias;
    }

    /**
     * Sets a collection of ChildMedia objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $medias A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function setMedias(Collection $medias, ConnectionInterface $con = null)
    {
        /** @var ChildMedia[] $mediasToDelete */
        $mediasToDelete = $this->getMedias(new Criteria(), $con)->diff($medias);


        $this->mediasScheduledForDeletion = $mediasToDelete;

        foreach ($mediasToDelete as $mediaRemoved) {
            $mediaRemoved->setBook(null);
        }

        $this->collMedias = null;
        foreach ($medias as $media) {
            $this->addMedia($media);
        }

        $this->collMedias = $medias;
        $this->collMediasPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Media objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Media objects.
     * @throws PropelException
     */
    public function countMedias(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collMediasPartial && !$this->isNew();
        if (null === $this->collMedias || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collMedias) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getMedias());
            }

            $query = ChildMediaQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByBook($this)
                ->count($con);
        }

        return count($this->collMedias);
    }

    /**
     * Method called to associate a ChildMedia object to this object
     * through the ChildMedia foreign key attribute.
     *
     * @param  ChildMedia $l ChildMedia
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function addMedia(ChildMedia $l)
    {
        if ($this->collMedias === null) {
            $this->initMedias();
            $this->collMediasPartial = true;
        }

        if (!$this->collMedias->contains($l)) {
            $this->doAddMedia($l);
        }

        return $this;
    }

    /**
     * @param ChildMedia $media The ChildMedia object to add.
     */
    protected function doAddMedia(ChildMedia $media)
    {
        $this->collMedias[]= $media;
        $media->setBook($this);
    }

    /**
     * @param  ChildMedia $media The ChildMedia object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function removeMedia(ChildMedia $media)
    {
        if ($this->getMedias()->contains($media)) {
            $pos = $this->collMedias->search($media);
            $this->collMedias->remove($pos);
            if (null === $this->mediasScheduledForDeletion) {
                $this->mediasScheduledForDeletion = clone $this->collMedias;
                $this->mediasScheduledForDeletion->clear();
            }
            $this->mediasScheduledForDeletion[]= clone $media;
            $media->setBook(null);
        }

        return $this;
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
     * If this ChildBook is new, it will return
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
                    ->filterByBook($this)
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
     * @return $this|ChildBook The current object (for fluent API support)
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
            $bookListRelRemoved->setBook(null);
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
                ->filterByBook($this)
                ->count($con);
        }

        return count($this->collBookListRels);
    }

    /**
     * Method called to associate a ChildBookListRel object to this object
     * through the ChildBookListRel foreign key attribute.
     *
     * @param  ChildBookListRel $l ChildBookListRel
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
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
        $bookListRel->setBook($this);
    }

    /**
     * @param  ChildBookListRel $bookListRel The ChildBookListRel object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
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
            $bookListRel->setBook(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related BookListRels from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookListRel[] List of ChildBookListRel objects
     */
    public function getBookListRelsJoinBookClubList(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookListRelQuery::create(null, $criteria);
        $query->joinWith('BookClubList', $joinBehavior);

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
     * If this ChildBook is new, it will return
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
                    ->filterByFavoriteBook($this)
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
     * @return $this|ChildBook The current object (for fluent API support)
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
            $bookListFavoriteRemoved->setFavoriteBook(null);
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
                ->filterByFavoriteBook($this)
                ->count($con);
        }

        return count($this->collBookListFavorites);
    }

    /**
     * Method called to associate a ChildBookListFavorite object to this object
     * through the ChildBookListFavorite foreign key attribute.
     *
     * @param  ChildBookListFavorite $l ChildBookListFavorite
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
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
        $bookListFavorite->setFavoriteBook($this);
    }

    /**
     * @param  ChildBookListFavorite $bookListFavorite The ChildBookListFavorite object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
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
            $bookListFavorite->setFavoriteBook(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related BookListFavorites from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookListFavorite[] List of ChildBookListFavorite objects
     */
    public function getBookListFavoritesJoinFavoriteBookClubList(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookListFavoriteQuery::create(null, $criteria);
        $query->joinWith('FavoriteBookClubList', $joinBehavior);

        return $this->getBookListFavorites($query, $con);
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
     * If this ChildBook is new, it will return
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
                    ->filterByBook($this)
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
     * @return $this|ChildBook The current object (for fluent API support)
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
            $bookOpinionRemoved->setBook(null);
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
                ->filterByBook($this)
                ->count($con);
        }

        return count($this->collBookOpinions);
    }

    /**
     * Method called to associate a ChildBookOpinion object to this object
     * through the ChildBookOpinion foreign key attribute.
     *
     * @param  ChildBookOpinion $l ChildBookOpinion
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
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
        $bookOpinion->setBook($this);
    }

    /**
     * @param  ChildBookOpinion $bookOpinion The ChildBookOpinion object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
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
            $bookOpinion->setBook(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related BookOpinions from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookOpinion[] List of ChildBookOpinion objects
     */
    public function getBookOpinionsJoinBookReader(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookOpinionQuery::create(null, $criteria);
        $query->joinWith('BookReader', $joinBehavior);

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
     * If this ChildBook is new, it will return
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
                    ->filterByBook($this)
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
     * @return $this|ChildBook The current object (for fluent API support)
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
            $readerFavoriteRemoved->setBook(null);
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
                ->filterByBook($this)
                ->count($con);
        }

        return count($this->collReaderFavorites);
    }

    /**
     * Method called to associate a ChildReaderFavorite object to this object
     * through the ChildReaderFavorite foreign key attribute.
     *
     * @param  ChildReaderFavorite $l ChildReaderFavorite
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
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
        $readerFavorite->setBook($this);
    }

    /**
     * @param  ChildReaderFavorite $readerFavorite The ChildReaderFavorite object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
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
            $readerFavorite->setBook(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related ReaderFavorites from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildReaderFavorite[] List of ChildReaderFavorite objects
     */
    public function getReaderFavoritesJoinBookReader(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildReaderFavoriteQuery::create(null, $criteria);
        $query->joinWith('BookReader', $joinBehavior);

        return $this->getReaderFavorites($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related ReaderFavorites from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
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
     * Clears out the collBookstoreContests collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBookstoreContests()
     */
    public function clearBookstoreContests()
    {
        $this->collBookstoreContests = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collBookstoreContests collection loaded partially.
     */
    public function resetPartialBookstoreContests($v = true)
    {
        $this->collBookstoreContestsPartial = $v;
    }

    /**
     * Initializes the collBookstoreContests collection.
     *
     * By default this just sets the collBookstoreContests collection to an empty array (like clearcollBookstoreContests());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initBookstoreContests($overrideExisting = true)
    {
        if (null !== $this->collBookstoreContests && !$overrideExisting) {
            return;
        }
        $this->collBookstoreContests = new ObjectCollection();
        $this->collBookstoreContests->setModel('\Propel\Tests\Bookstore\BookstoreContest');
    }

    /**
     * Gets an array of ChildBookstoreContest objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildBookstoreContest[] List of ChildBookstoreContest objects
     * @throws PropelException
     */
    public function getBookstoreContests(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBookstoreContestsPartial && !$this->isNew();
        if (null === $this->collBookstoreContests || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collBookstoreContests) {
                // return empty collection
                $this->initBookstoreContests();
            } else {
                $collBookstoreContests = ChildBookstoreContestQuery::create(null, $criteria)
                    ->filterByWork($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collBookstoreContestsPartial && count($collBookstoreContests)) {
                        $this->initBookstoreContests(false);

                        foreach ($collBookstoreContests as $obj) {
                            if (false == $this->collBookstoreContests->contains($obj)) {
                                $this->collBookstoreContests->append($obj);
                            }
                        }

                        $this->collBookstoreContestsPartial = true;
                    }

                    return $collBookstoreContests;
                }

                if ($partial && $this->collBookstoreContests) {
                    foreach ($this->collBookstoreContests as $obj) {
                        if ($obj->isNew()) {
                            $collBookstoreContests[] = $obj;
                        }
                    }
                }

                $this->collBookstoreContests = $collBookstoreContests;
                $this->collBookstoreContestsPartial = false;
            }
        }

        return $this->collBookstoreContests;
    }

    /**
     * Sets a collection of ChildBookstoreContest objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $bookstoreContests A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function setBookstoreContests(Collection $bookstoreContests, ConnectionInterface $con = null)
    {
        /** @var ChildBookstoreContest[] $bookstoreContestsToDelete */
        $bookstoreContestsToDelete = $this->getBookstoreContests(new Criteria(), $con)->diff($bookstoreContests);


        $this->bookstoreContestsScheduledForDeletion = $bookstoreContestsToDelete;

        foreach ($bookstoreContestsToDelete as $bookstoreContestRemoved) {
            $bookstoreContestRemoved->setWork(null);
        }

        $this->collBookstoreContests = null;
        foreach ($bookstoreContests as $bookstoreContest) {
            $this->addBookstoreContest($bookstoreContest);
        }

        $this->collBookstoreContests = $bookstoreContests;
        $this->collBookstoreContestsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related BookstoreContest objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related BookstoreContest objects.
     * @throws PropelException
     */
    public function countBookstoreContests(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBookstoreContestsPartial && !$this->isNew();
        if (null === $this->collBookstoreContests || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBookstoreContests) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getBookstoreContests());
            }

            $query = ChildBookstoreContestQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByWork($this)
                ->count($con);
        }

        return count($this->collBookstoreContests);
    }

    /**
     * Method called to associate a ChildBookstoreContest object to this object
     * through the ChildBookstoreContest foreign key attribute.
     *
     * @param  ChildBookstoreContest $l ChildBookstoreContest
     * @return $this|\Propel\Tests\Bookstore\Book The current object (for fluent API support)
     */
    public function addBookstoreContest(ChildBookstoreContest $l)
    {
        if ($this->collBookstoreContests === null) {
            $this->initBookstoreContests();
            $this->collBookstoreContestsPartial = true;
        }

        if (!$this->collBookstoreContests->contains($l)) {
            $this->doAddBookstoreContest($l);
        }

        return $this;
    }

    /**
     * @param ChildBookstoreContest $bookstoreContest The ChildBookstoreContest object to add.
     */
    protected function doAddBookstoreContest(ChildBookstoreContest $bookstoreContest)
    {
        $this->collBookstoreContests[]= $bookstoreContest;
        $bookstoreContest->setWork($this);
    }

    /**
     * @param  ChildBookstoreContest $bookstoreContest The ChildBookstoreContest object to remove.
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function removeBookstoreContest(ChildBookstoreContest $bookstoreContest)
    {
        if ($this->getBookstoreContests()->contains($bookstoreContest)) {
            $pos = $this->collBookstoreContests->search($bookstoreContest);
            $this->collBookstoreContests->remove($pos);
            if (null === $this->bookstoreContestsScheduledForDeletion) {
                $this->bookstoreContestsScheduledForDeletion = clone $this->collBookstoreContests;
                $this->bookstoreContestsScheduledForDeletion->clear();
            }
            $this->bookstoreContestsScheduledForDeletion[]= $bookstoreContest;
            $bookstoreContest->setWork(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related BookstoreContests from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookstoreContest[] List of ChildBookstoreContest objects
     */
    public function getBookstoreContestsJoinBookstore(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookstoreContestQuery::create(null, $criteria);
        $query->joinWith('Bookstore', $joinBehavior);

        return $this->getBookstoreContests($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Book is new, it will return
     * an empty collection; or if this Book has previously
     * been saved, it will retrieve related BookstoreContests from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Book.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildBookstoreContest[] List of ChildBookstoreContest objects
     */
    public function getBookstoreContestsJoinContest(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildBookstoreContestQuery::create(null, $criteria);
        $query->joinWith('Contest', $joinBehavior);

        return $this->getBookstoreContests($query, $con);
    }

    /**
     * Clears out the collBookClubLists collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addBookClubLists()
     */
    public function clearBookClubLists()
    {
        $this->collBookClubLists = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the collBookClubLists collection.
     *
     * By default this just sets the collBookClubLists collection to an empty collection (like clearBookClubLists());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initBookClubLists()
    {
        $this->collBookClubLists = new ObjectCollection();
        $this->collBookClubListsPartial = true;

        $this->collBookClubLists->setModel('\Propel\Tests\Bookstore\BookClubList');
    }

    /**
     * Checks if the collBookClubLists collection is loaded.
     *
     * @return bool
     */
    public function isBookClubListsLoaded()
    {
        return null !== $this->collBookClubLists;
    }

    /**
     * Gets a collection of ChildBookClubList objects related by a many-to-many relationship
     * to the current object by way of the book_x_list cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildBookClubList[] List of ChildBookClubList objects
     */
    public function getBookClubLists(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collBookClubListsPartial && !$this->isNew();
        if (null === $this->collBookClubLists || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collBookClubLists) {
                    $this->initBookClubLists();
                }
            } else {

                $query = ChildBookClubListQuery::create(null, $criteria)
                    ->filterByBook($this);
                $collBookClubLists = $query->find($con);
                if (null !== $criteria) {
                    return $collBookClubLists;
                }

                if ($partial && $this->collBookClubLists) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collBookClubLists as $obj) {
                        if (!$collBookClubLists->contains($obj)) {
                            $collBookClubLists[] = $obj;
                        }
                    }
                }

                $this->collBookClubLists = $collBookClubLists;
                $this->collBookClubListsPartial = false;
            }
        }

        return $this->collBookClubLists;
    }

    /**
     * Sets a collection of BookClubList objects related by a many-to-many relationship
     * to the current object by way of the book_x_list cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $bookClubLists A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function setBookClubLists(Collection $bookClubLists, ConnectionInterface $con = null)
    {
        $this->clearBookClubLists();
        $currentBookClubLists = $this->getBookClubLists();

        $bookClubListsScheduledForDeletion = $currentBookClubLists->diff($bookClubLists);

        foreach ($bookClubListsScheduledForDeletion as $toDelete) {
            $this->removeBookClubList($toDelete);
        }

        foreach ($bookClubLists as $bookClubList) {
            if (!$currentBookClubLists->contains($bookClubList)) {
                $this->doAddBookClubList($bookClubList);
            }
        }

        $this->collBookClubListsPartial = false;
        $this->collBookClubLists = $bookClubLists;

        return $this;
    }

    /**
     * Gets the number of BookClubList objects related by a many-to-many relationship
     * to the current object by way of the book_x_list cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related BookClubList objects
     */
    public function countBookClubLists(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collBookClubListsPartial && !$this->isNew();
        if (null === $this->collBookClubLists || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collBookClubLists) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getBookClubLists());
                }

                $query = ChildBookClubListQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByBook($this)
                    ->count($con);
            }
        } else {
            return count($this->collBookClubLists);
        }
    }

    /**
     * Associate a ChildBookClubList to this object
     * through the book_x_list cross reference table.
     *
     * @param ChildBookClubList $bookClubList
     * @return ChildBook The current object (for fluent API support)
     */
    public function addBookClubList(ChildBookClubList $bookClubList)
    {
        if ($this->collBookClubLists === null) {
            $this->initBookClubLists();
        }

        if (!$this->getBookClubLists()->contains($bookClubList)) {
            // only add it if the **same** object is not already associated
            $this->collBookClubLists->push($bookClubList);
            $this->doAddBookClubList($bookClubList);
        }

        return $this;
    }

    /**
     *
     * @param ChildBookClubList $bookClubList
     */
    protected function doAddBookClubList(ChildBookClubList $bookClubList)
    {
        $bookListRel = new ChildBookListRel();

        $bookListRel->setBookClubList($bookClubList);

        $bookListRel->setBook($this);

        $this->addBookListRel($bookListRel);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$bookClubList->isBooksLoaded()) {
            $bookClubList->initBooks();
            $bookClubList->getBooks()->push($this);
        } elseif (!$bookClubList->getBooks()->contains($this)) {
            $bookClubList->getBooks()->push($this);
        }

    }

    /**
     * Remove bookClubList of this object
     * through the book_x_list cross reference table.
     *
     * @param ChildBookClubList $bookClubList
     * @return ChildBook The current object (for fluent API support)
     */
    public function removeBookClubList(ChildBookClubList $bookClubList)
    {
        if ($this->getBookClubLists()->contains($bookClubList)) { $bookListRel = new ChildBookListRel();

            $bookListRel->setBookClubList($bookClubList);
            if ($bookClubList->isBooksLoaded()) {
                //remove the back reference if available
                $bookClubList->getBooks()->removeObject($this);
            }

            $bookListRel->setBook($this);
            $this->removeBookListRel(clone $bookListRel);
            $bookListRel->clear();

            $this->collBookClubLists->remove($this->collBookClubLists->search($bookClubList));

            if (null === $this->bookClubListsScheduledForDeletion) {
                $this->bookClubListsScheduledForDeletion = clone $this->collBookClubLists;
                $this->bookClubListsScheduledForDeletion->clear();
            }

            $this->bookClubListsScheduledForDeletion->push($bookClubList);
        }


        return $this;
    }

    /**
     * Clears out the collFavoriteBookClubLists collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addFavoriteBookClubLists()
     */
    public function clearFavoriteBookClubLists()
    {
        $this->collFavoriteBookClubLists = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the collFavoriteBookClubLists collection.
     *
     * By default this just sets the collFavoriteBookClubLists collection to an empty collection (like clearFavoriteBookClubLists());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initFavoriteBookClubLists()
    {
        $this->collFavoriteBookClubLists = new ObjectCollection();
        $this->collFavoriteBookClubListsPartial = true;

        $this->collFavoriteBookClubLists->setModel('\Propel\Tests\Bookstore\BookClubList');
    }

    /**
     * Checks if the collFavoriteBookClubLists collection is loaded.
     *
     * @return bool
     */
    public function isFavoriteBookClubListsLoaded()
    {
        return null !== $this->collFavoriteBookClubLists;
    }

    /**
     * Gets a collection of ChildBookClubList objects related by a many-to-many relationship
     * to the current object by way of the book_club_list_favorite_books cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBook is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildBookClubList[] List of ChildBookClubList objects
     */
    public function getFavoriteBookClubLists(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collFavoriteBookClubListsPartial && !$this->isNew();
        if (null === $this->collFavoriteBookClubLists || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collFavoriteBookClubLists) {
                    $this->initFavoriteBookClubLists();
                }
            } else {

                $query = ChildBookClubListQuery::create(null, $criteria)
                    ->filterByFavoriteBook($this);
                $collFavoriteBookClubLists = $query->find($con);
                if (null !== $criteria) {
                    return $collFavoriteBookClubLists;
                }

                if ($partial && $this->collFavoriteBookClubLists) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collFavoriteBookClubLists as $obj) {
                        if (!$collFavoriteBookClubLists->contains($obj)) {
                            $collFavoriteBookClubLists[] = $obj;
                        }
                    }
                }

                $this->collFavoriteBookClubLists = $collFavoriteBookClubLists;
                $this->collFavoriteBookClubListsPartial = false;
            }
        }

        return $this->collFavoriteBookClubLists;
    }

    /**
     * Sets a collection of BookClubList objects related by a many-to-many relationship
     * to the current object by way of the book_club_list_favorite_books cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $favoriteBookClubLists A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildBook The current object (for fluent API support)
     */
    public function setFavoriteBookClubLists(Collection $favoriteBookClubLists, ConnectionInterface $con = null)
    {
        $this->clearFavoriteBookClubLists();
        $currentFavoriteBookClubLists = $this->getFavoriteBookClubLists();

        $favoriteBookClubListsScheduledForDeletion = $currentFavoriteBookClubLists->diff($favoriteBookClubLists);

        foreach ($favoriteBookClubListsScheduledForDeletion as $toDelete) {
            $this->removeFavoriteBookClubList($toDelete);
        }

        foreach ($favoriteBookClubLists as $favoriteBookClubList) {
            if (!$currentFavoriteBookClubLists->contains($favoriteBookClubList)) {
                $this->doAddFavoriteBookClubList($favoriteBookClubList);
            }
        }

        $this->collFavoriteBookClubListsPartial = false;
        $this->collFavoriteBookClubLists = $favoriteBookClubLists;

        return $this;
    }

    /**
     * Gets the number of BookClubList objects related by a many-to-many relationship
     * to the current object by way of the book_club_list_favorite_books cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related BookClubList objects
     */
    public function countFavoriteBookClubLists(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collFavoriteBookClubListsPartial && !$this->isNew();
        if (null === $this->collFavoriteBookClubLists || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collFavoriteBookClubLists) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getFavoriteBookClubLists());
                }

                $query = ChildBookClubListQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByFavoriteBook($this)
                    ->count($con);
            }
        } else {
            return count($this->collFavoriteBookClubLists);
        }
    }

    /**
     * Associate a ChildBookClubList to this object
     * through the book_club_list_favorite_books cross reference table.
     *
     * @param ChildBookClubList $favoriteBookClubList
     * @return ChildBook The current object (for fluent API support)
     */
    public function addFavoriteBookClubList(ChildBookClubList $favoriteBookClubList)
    {
        if ($this->collFavoriteBookClubLists === null) {
            $this->initFavoriteBookClubLists();
        }

        if (!$this->getFavoriteBookClubLists()->contains($favoriteBookClubList)) {
            // only add it if the **same** object is not already associated
            $this->collFavoriteBookClubLists->push($favoriteBookClubList);
            $this->doAddFavoriteBookClubList($favoriteBookClubList);
        }

        return $this;
    }

    /**
     *
     * @param ChildBookClubList $favoriteBookClubList
     */
    protected function doAddFavoriteBookClubList(ChildBookClubList $favoriteBookClubList)
    {
        $bookListFavorite = new ChildBookListFavorite();

        $bookListFavorite->setFavoriteBookClubList($favoriteBookClubList);

        $bookListFavorite->setFavoriteBook($this);

        $this->addBookListFavorite($bookListFavorite);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$favoriteBookClubList->isFavoriteBooksLoaded()) {
            $favoriteBookClubList->initFavoriteBooks();
            $favoriteBookClubList->getFavoriteBooks()->push($this);
        } elseif (!$favoriteBookClubList->getFavoriteBooks()->contains($this)) {
            $favoriteBookClubList->getFavoriteBooks()->push($this);
        }

    }

    /**
     * Remove favoriteBookClubList of this object
     * through the book_club_list_favorite_books cross reference table.
     *
     * @param ChildBookClubList $favoriteBookClubList
     * @return ChildBook The current object (for fluent API support)
     */
    public function removeFavoriteBookClubList(ChildBookClubList $favoriteBookClubList)
    {
        if ($this->getFavoriteBookClubLists()->contains($favoriteBookClubList)) { $bookListFavorite = new ChildBookListFavorite();

            $bookListFavorite->setFavoriteBookClubList($favoriteBookClubList);
            if ($favoriteBookClubList->isFavoriteBooksLoaded()) {
                //remove the back reference if available
                $favoriteBookClubList->getFavoriteBooks()->removeObject($this);
            }

            $bookListFavorite->setFavoriteBook($this);
            $this->removeBookListFavorite(clone $bookListFavorite);
            $bookListFavorite->clear();

            $this->collFavoriteBookClubLists->remove($this->collFavoriteBookClubLists->search($favoriteBookClubList));

            if (null === $this->favoriteBookClubListsScheduledForDeletion) {
                $this->favoriteBookClubListsScheduledForDeletion = clone $this->collFavoriteBookClubLists;
                $this->favoriteBookClubListsScheduledForDeletion->clear();
            }

            $this->favoriteBookClubListsScheduledForDeletion->push($favoriteBookClubList);
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
        if (null !== $this->aPublisher) {
            $this->aPublisher->removeBook($this);
        }
        if (null !== $this->aAuthor) {
            $this->aAuthor->removeBook($this);
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
            if ($this->collBookSummaries) {
                foreach ($this->collBookSummaries as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collReviews) {
                foreach ($this->collReviews as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collMedias) {
                foreach ($this->collMedias as $o) {
                    $o->clearAllReferences($deep);
                }
            }
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
            if ($this->collBookstoreContests) {
                foreach ($this->collBookstoreContests as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collBookClubLists) {
                foreach ($this->collBookClubLists as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collFavoriteBookClubLists) {
                foreach ($this->collFavoriteBookClubLists as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collBookSummaries = null;
        $this->collReviews = null;
        $this->collMedias = null;
        $this->collBookListRels = null;
        $this->collBookListFavorites = null;
        $this->collBookOpinions = null;
        $this->collReaderFavorites = null;
        $this->collBookstoreContests = null;
        $this->collBookClubLists = null;
        $this->collFavoriteBookClubLists = null;
        $this->aPublisher = null;
        $this->aAuthor = null;
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
