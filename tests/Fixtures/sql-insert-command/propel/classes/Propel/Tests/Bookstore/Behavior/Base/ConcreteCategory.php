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
use Propel\Tests\Bookstore\Behavior\ConcreteArticle as ChildConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery as ChildConcreteArticleQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteCategory as ChildConcreteCategory;
use Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery as ChildConcreteCategoryQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContent as ChildConcreteContent;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery as ChildConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteNews as ChildConcreteNews;
use Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery as ChildConcreteNewsQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizz as ChildConcreteQuizz;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery as ChildConcreteQuizzQuery;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteCategoryTableMap;

/**
 * Base class that represents a row from the 'concrete_category' table.
 *
 *
 *
* @package    propel.generator.Propel.Tests.Bookstore.Behavior.Base
*/
abstract class ConcreteCategory implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Behavior\\Map\\ConcreteCategoryTableMap';


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
     * @var        ObjectCollection|ChildConcreteContent[] Collection to store aggregation of ChildConcreteContent objects.
     */
    protected $collConcreteContents;
    protected $collConcreteContentsPartial;

    /**
     * @var        ObjectCollection|ChildConcreteArticle[] Collection to store aggregation of ChildConcreteArticle objects.
     */
    protected $collConcreteArticles;
    protected $collConcreteArticlesPartial;

    /**
     * @var        ObjectCollection|ChildConcreteNews[] Collection to store aggregation of ChildConcreteNews objects.
     */
    protected $collConcreteNewss;
    protected $collConcreteNewssPartial;

    /**
     * @var        ObjectCollection|ChildConcreteQuizz[] Collection to store aggregation of ChildConcreteQuizz objects.
     */
    protected $collConcreteQuizzs;
    protected $collConcreteQuizzsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildConcreteContent[]
     */
    protected $concreteContentsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildConcreteArticle[]
     */
    protected $concreteArticlesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildConcreteNews[]
     */
    protected $concreteNewssScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildConcreteQuizz[]
     */
    protected $concreteQuizzsScheduledForDeletion = null;

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Behavior\Base\ConcreteCategory object.
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
     * Compares this with another <code>ConcreteCategory</code> instance.  If
     * <code>obj</code> is an instance of <code>ConcreteCategory</code>, delegates to
     * <code>equals(ConcreteCategory)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|ConcreteCategory The current object, for fluid interface
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : ConcreteCategoryTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : ConcreteCategoryTableMap::translateFieldName('Name', TableMap::TYPE_PHPNAME, $indexType)];
            $this->name = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 2; // 2 = ConcreteCategoryTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteCategory'), 0, $e);
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
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[ConcreteCategoryTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [name] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object (for fluent API support)
     */
    public function setName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->name !== $v) {
            $this->name = $v;
            $this->modifiedColumns[ConcreteCategoryTableMap::COL_NAME] = true;
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
            $con = Propel::getServiceContainer()->getReadConnection(ConcreteCategoryTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildConcreteCategoryQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collConcreteContents = null;

            $this->collConcreteArticles = null;

            $this->collConcreteNewss = null;

            $this->collConcreteQuizzs = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see ConcreteCategory::setDeleted()
     * @see ConcreteCategory::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteCategoryTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildConcreteCategoryQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteCategoryTableMap::DATABASE_NAME);
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
                ConcreteCategoryTableMap::addInstanceToPool($this);
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

            if ($this->concreteContentsScheduledForDeletion !== null) {
                if (!$this->concreteContentsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\Behavior\ConcreteContentQuery::create()
                        ->filterByPrimaryKeys($this->concreteContentsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->concreteContentsScheduledForDeletion = null;
                }
            }

            if ($this->collConcreteContents !== null) {
                foreach ($this->collConcreteContents as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->concreteArticlesScheduledForDeletion !== null) {
                if (!$this->concreteArticlesScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery::create()
                        ->filterByPrimaryKeys($this->concreteArticlesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->concreteArticlesScheduledForDeletion = null;
                }
            }

            if ($this->collConcreteArticles !== null) {
                foreach ($this->collConcreteArticles as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->concreteNewssScheduledForDeletion !== null) {
                if (!$this->concreteNewssScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery::create()
                        ->filterByPrimaryKeys($this->concreteNewssScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->concreteNewssScheduledForDeletion = null;
                }
            }

            if ($this->collConcreteNewss !== null) {
                foreach ($this->collConcreteNewss as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->concreteQuizzsScheduledForDeletion !== null) {
                if (!$this->concreteQuizzsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery::create()
                        ->filterByPrimaryKeys($this->concreteQuizzsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->concreteQuizzsScheduledForDeletion = null;
                }
            }

            if ($this->collConcreteQuizzs !== null) {
                foreach ($this->collConcreteQuizzs as $referrerFK) {
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

        $this->modifiedColumns[ConcreteCategoryTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . ConcreteCategoryTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(ConcreteCategoryTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(ConcreteCategoryTableMap::COL_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'NAME';
        }

        $sql = sprintf(
            'INSERT INTO concrete_category (%s) VALUES (%s)',
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
        $pos = ConcreteCategoryTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
        if (isset($alreadyDumpedObjects['ConcreteCategory'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['ConcreteCategory'][$this->getPrimaryKey()] = true;
        $keys = ConcreteCategoryTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getName(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collConcreteContents) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'concreteContents';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'concrete_contents';
                        break;
                    default:
                        $key = 'ConcreteContents';
                }

                $result[$key] = $this->collConcreteContents->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collConcreteArticles) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'concreteArticles';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'concrete_articles';
                        break;
                    default:
                        $key = 'ConcreteArticles';
                }

                $result[$key] = $this->collConcreteArticles->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collConcreteNewss) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'concreteNewss';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'concrete_newss';
                        break;
                    default:
                        $key = 'ConcreteNewss';
                }

                $result[$key] = $this->collConcreteNewss->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collConcreteQuizzs) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'concreteQuizzs';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'concrete_quizzs';
                        break;
                    default:
                        $key = 'ConcreteQuizzs';
                }

                $result[$key] = $this->collConcreteQuizzs->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = ConcreteCategoryTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory
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
        $keys = ConcreteCategoryTableMap::getFieldNames($keyType);

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
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object, for fluid interface
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
        $criteria = new Criteria(ConcreteCategoryTableMap::DATABASE_NAME);

        if ($this->isColumnModified(ConcreteCategoryTableMap::COL_ID)) {
            $criteria->add(ConcreteCategoryTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(ConcreteCategoryTableMap::COL_NAME)) {
            $criteria->add(ConcreteCategoryTableMap::COL_NAME, $this->name);
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
        $criteria = new Criteria(ConcreteCategoryTableMap::DATABASE_NAME);
        $criteria->add(ConcreteCategoryTableMap::COL_ID, $this->id);

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
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\Behavior\ConcreteCategory (or compatible) type.
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

            foreach ($this->getConcreteContents() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addConcreteContent($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getConcreteArticles() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addConcreteArticle($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getConcreteNewss() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addConcreteNews($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getConcreteQuizzs() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addConcreteQuizz($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteCategory Clone of current object.
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
        if ('ConcreteContent' == $relationName) {
            return $this->initConcreteContents();
        }
        if ('ConcreteArticle' == $relationName) {
            return $this->initConcreteArticles();
        }
        if ('ConcreteNews' == $relationName) {
            return $this->initConcreteNewss();
        }
        if ('ConcreteQuizz' == $relationName) {
            return $this->initConcreteQuizzs();
        }
    }

    /**
     * Clears out the collConcreteContents collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addConcreteContents()
     */
    public function clearConcreteContents()
    {
        $this->collConcreteContents = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collConcreteContents collection loaded partially.
     */
    public function resetPartialConcreteContents($v = true)
    {
        $this->collConcreteContentsPartial = $v;
    }

    /**
     * Initializes the collConcreteContents collection.
     *
     * By default this just sets the collConcreteContents collection to an empty array (like clearcollConcreteContents());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initConcreteContents($overrideExisting = true)
    {
        if (null !== $this->collConcreteContents && !$overrideExisting) {
            return;
        }
        $this->collConcreteContents = new ObjectCollection();
        $this->collConcreteContents->setModel('\Propel\Tests\Bookstore\Behavior\ConcreteContent');
    }

    /**
     * Gets an array of ChildConcreteContent objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildConcreteCategory is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildConcreteContent[] List of ChildConcreteContent objects
     * @throws PropelException
     */
    public function getConcreteContents(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteContentsPartial && !$this->isNew();
        if (null === $this->collConcreteContents || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collConcreteContents) {
                // return empty collection
                $this->initConcreteContents();
            } else {
                $collConcreteContents = ChildConcreteContentQuery::create(null, $criteria)
                    ->filterByConcreteCategory($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collConcreteContentsPartial && count($collConcreteContents)) {
                        $this->initConcreteContents(false);

                        foreach ($collConcreteContents as $obj) {
                            if (false == $this->collConcreteContents->contains($obj)) {
                                $this->collConcreteContents->append($obj);
                            }
                        }

                        $this->collConcreteContentsPartial = true;
                    }

                    return $collConcreteContents;
                }

                if ($partial && $this->collConcreteContents) {
                    foreach ($this->collConcreteContents as $obj) {
                        if ($obj->isNew()) {
                            $collConcreteContents[] = $obj;
                        }
                    }
                }

                $this->collConcreteContents = $collConcreteContents;
                $this->collConcreteContentsPartial = false;
            }
        }

        return $this->collConcreteContents;
    }

    /**
     * Sets a collection of ChildConcreteContent objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $concreteContents A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function setConcreteContents(Collection $concreteContents, ConnectionInterface $con = null)
    {
        /** @var ChildConcreteContent[] $concreteContentsToDelete */
        $concreteContentsToDelete = $this->getConcreteContents(new Criteria(), $con)->diff($concreteContents);


        $this->concreteContentsScheduledForDeletion = $concreteContentsToDelete;

        foreach ($concreteContentsToDelete as $concreteContentRemoved) {
            $concreteContentRemoved->setConcreteCategory(null);
        }

        $this->collConcreteContents = null;
        foreach ($concreteContents as $concreteContent) {
            $this->addConcreteContent($concreteContent);
        }

        $this->collConcreteContents = $concreteContents;
        $this->collConcreteContentsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ConcreteContent objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ConcreteContent objects.
     * @throws PropelException
     */
    public function countConcreteContents(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteContentsPartial && !$this->isNew();
        if (null === $this->collConcreteContents || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collConcreteContents) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getConcreteContents());
            }

            $query = ChildConcreteContentQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByConcreteCategory($this)
                ->count($con);
        }

        return count($this->collConcreteContents);
    }

    /**
     * Method called to associate a ChildConcreteContent object to this object
     * through the ChildConcreteContent foreign key attribute.
     *
     * @param  ChildConcreteContent $l ChildConcreteContent
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object (for fluent API support)
     */
    public function addConcreteContent(ChildConcreteContent $l)
    {
        if ($this->collConcreteContents === null) {
            $this->initConcreteContents();
            $this->collConcreteContentsPartial = true;
        }

        if (!$this->collConcreteContents->contains($l)) {
            $this->doAddConcreteContent($l);
        }

        return $this;
    }

    /**
     * @param ChildConcreteContent $concreteContent The ChildConcreteContent object to add.
     */
    protected function doAddConcreteContent(ChildConcreteContent $concreteContent)
    {
        $this->collConcreteContents[]= $concreteContent;
        $concreteContent->setConcreteCategory($this);
    }

    /**
     * @param  ChildConcreteContent $concreteContent The ChildConcreteContent object to remove.
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function removeConcreteContent(ChildConcreteContent $concreteContent)
    {
        if ($this->getConcreteContents()->contains($concreteContent)) {
            $pos = $this->collConcreteContents->search($concreteContent);
            $this->collConcreteContents->remove($pos);
            if (null === $this->concreteContentsScheduledForDeletion) {
                $this->concreteContentsScheduledForDeletion = clone $this->collConcreteContents;
                $this->concreteContentsScheduledForDeletion->clear();
            }
            $this->concreteContentsScheduledForDeletion[]= $concreteContent;
            $concreteContent->setConcreteCategory(null);
        }

        return $this;
    }

    /**
     * Clears out the collConcreteArticles collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addConcreteArticles()
     */
    public function clearConcreteArticles()
    {
        $this->collConcreteArticles = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collConcreteArticles collection loaded partially.
     */
    public function resetPartialConcreteArticles($v = true)
    {
        $this->collConcreteArticlesPartial = $v;
    }

    /**
     * Initializes the collConcreteArticles collection.
     *
     * By default this just sets the collConcreteArticles collection to an empty array (like clearcollConcreteArticles());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initConcreteArticles($overrideExisting = true)
    {
        if (null !== $this->collConcreteArticles && !$overrideExisting) {
            return;
        }
        $this->collConcreteArticles = new ObjectCollection();
        $this->collConcreteArticles->setModel('\Propel\Tests\Bookstore\Behavior\ConcreteArticle');
    }

    /**
     * Gets an array of ChildConcreteArticle objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildConcreteCategory is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildConcreteArticle[] List of ChildConcreteArticle objects
     * @throws PropelException
     */
    public function getConcreteArticles(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteArticlesPartial && !$this->isNew();
        if (null === $this->collConcreteArticles || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collConcreteArticles) {
                // return empty collection
                $this->initConcreteArticles();
            } else {
                $collConcreteArticles = ChildConcreteArticleQuery::create(null, $criteria)
                    ->filterByConcreteCategory($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collConcreteArticlesPartial && count($collConcreteArticles)) {
                        $this->initConcreteArticles(false);

                        foreach ($collConcreteArticles as $obj) {
                            if (false == $this->collConcreteArticles->contains($obj)) {
                                $this->collConcreteArticles->append($obj);
                            }
                        }

                        $this->collConcreteArticlesPartial = true;
                    }

                    return $collConcreteArticles;
                }

                if ($partial && $this->collConcreteArticles) {
                    foreach ($this->collConcreteArticles as $obj) {
                        if ($obj->isNew()) {
                            $collConcreteArticles[] = $obj;
                        }
                    }
                }

                $this->collConcreteArticles = $collConcreteArticles;
                $this->collConcreteArticlesPartial = false;
            }
        }

        return $this->collConcreteArticles;
    }

    /**
     * Sets a collection of ChildConcreteArticle objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $concreteArticles A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function setConcreteArticles(Collection $concreteArticles, ConnectionInterface $con = null)
    {
        /** @var ChildConcreteArticle[] $concreteArticlesToDelete */
        $concreteArticlesToDelete = $this->getConcreteArticles(new Criteria(), $con)->diff($concreteArticles);


        $this->concreteArticlesScheduledForDeletion = $concreteArticlesToDelete;

        foreach ($concreteArticlesToDelete as $concreteArticleRemoved) {
            $concreteArticleRemoved->setConcreteCategory(null);
        }

        $this->collConcreteArticles = null;
        foreach ($concreteArticles as $concreteArticle) {
            $this->addConcreteArticle($concreteArticle);
        }

        $this->collConcreteArticles = $concreteArticles;
        $this->collConcreteArticlesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ConcreteArticle objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ConcreteArticle objects.
     * @throws PropelException
     */
    public function countConcreteArticles(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteArticlesPartial && !$this->isNew();
        if (null === $this->collConcreteArticles || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collConcreteArticles) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getConcreteArticles());
            }

            $query = ChildConcreteArticleQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByConcreteCategory($this)
                ->count($con);
        }

        return count($this->collConcreteArticles);
    }

    /**
     * Method called to associate a ChildConcreteArticle object to this object
     * through the ChildConcreteArticle foreign key attribute.
     *
     * @param  ChildConcreteArticle $l ChildConcreteArticle
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object (for fluent API support)
     */
    public function addConcreteArticle(ChildConcreteArticle $l)
    {
        if ($this->collConcreteArticles === null) {
            $this->initConcreteArticles();
            $this->collConcreteArticlesPartial = true;
        }

        if (!$this->collConcreteArticles->contains($l)) {
            $this->doAddConcreteArticle($l);
        }

        return $this;
    }

    /**
     * @param ChildConcreteArticle $concreteArticle The ChildConcreteArticle object to add.
     */
    protected function doAddConcreteArticle(ChildConcreteArticle $concreteArticle)
    {
        $this->collConcreteArticles[]= $concreteArticle;
        $concreteArticle->setConcreteCategory($this);
    }

    /**
     * @param  ChildConcreteArticle $concreteArticle The ChildConcreteArticle object to remove.
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function removeConcreteArticle(ChildConcreteArticle $concreteArticle)
    {
        if ($this->getConcreteArticles()->contains($concreteArticle)) {
            $pos = $this->collConcreteArticles->search($concreteArticle);
            $this->collConcreteArticles->remove($pos);
            if (null === $this->concreteArticlesScheduledForDeletion) {
                $this->concreteArticlesScheduledForDeletion = clone $this->collConcreteArticles;
                $this->concreteArticlesScheduledForDeletion->clear();
            }
            $this->concreteArticlesScheduledForDeletion[]= $concreteArticle;
            $concreteArticle->setConcreteCategory(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ConcreteCategory is new, it will return
     * an empty collection; or if this ConcreteCategory has previously
     * been saved, it will retrieve related ConcreteArticles from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ConcreteCategory.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildConcreteArticle[] List of ChildConcreteArticle objects
     */
    public function getConcreteArticlesJoinConcreteAuthor(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildConcreteArticleQuery::create(null, $criteria);
        $query->joinWith('ConcreteAuthor', $joinBehavior);

        return $this->getConcreteArticles($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ConcreteCategory is new, it will return
     * an empty collection; or if this ConcreteCategory has previously
     * been saved, it will retrieve related ConcreteArticles from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ConcreteCategory.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildConcreteArticle[] List of ChildConcreteArticle objects
     */
    public function getConcreteArticlesJoinConcreteContent(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildConcreteArticleQuery::create(null, $criteria);
        $query->joinWith('ConcreteContent', $joinBehavior);

        return $this->getConcreteArticles($query, $con);
    }

    /**
     * Clears out the collConcreteNewss collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addConcreteNewss()
     */
    public function clearConcreteNewss()
    {
        $this->collConcreteNewss = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collConcreteNewss collection loaded partially.
     */
    public function resetPartialConcreteNewss($v = true)
    {
        $this->collConcreteNewssPartial = $v;
    }

    /**
     * Initializes the collConcreteNewss collection.
     *
     * By default this just sets the collConcreteNewss collection to an empty array (like clearcollConcreteNewss());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initConcreteNewss($overrideExisting = true)
    {
        if (null !== $this->collConcreteNewss && !$overrideExisting) {
            return;
        }
        $this->collConcreteNewss = new ObjectCollection();
        $this->collConcreteNewss->setModel('\Propel\Tests\Bookstore\Behavior\ConcreteNews');
    }

    /**
     * Gets an array of ChildConcreteNews objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildConcreteCategory is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildConcreteNews[] List of ChildConcreteNews objects
     * @throws PropelException
     */
    public function getConcreteNewss(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteNewssPartial && !$this->isNew();
        if (null === $this->collConcreteNewss || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collConcreteNewss) {
                // return empty collection
                $this->initConcreteNewss();
            } else {
                $collConcreteNewss = ChildConcreteNewsQuery::create(null, $criteria)
                    ->filterByConcreteCategory($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collConcreteNewssPartial && count($collConcreteNewss)) {
                        $this->initConcreteNewss(false);

                        foreach ($collConcreteNewss as $obj) {
                            if (false == $this->collConcreteNewss->contains($obj)) {
                                $this->collConcreteNewss->append($obj);
                            }
                        }

                        $this->collConcreteNewssPartial = true;
                    }

                    return $collConcreteNewss;
                }

                if ($partial && $this->collConcreteNewss) {
                    foreach ($this->collConcreteNewss as $obj) {
                        if ($obj->isNew()) {
                            $collConcreteNewss[] = $obj;
                        }
                    }
                }

                $this->collConcreteNewss = $collConcreteNewss;
                $this->collConcreteNewssPartial = false;
            }
        }

        return $this->collConcreteNewss;
    }

    /**
     * Sets a collection of ChildConcreteNews objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $concreteNewss A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function setConcreteNewss(Collection $concreteNewss, ConnectionInterface $con = null)
    {
        /** @var ChildConcreteNews[] $concreteNewssToDelete */
        $concreteNewssToDelete = $this->getConcreteNewss(new Criteria(), $con)->diff($concreteNewss);


        $this->concreteNewssScheduledForDeletion = $concreteNewssToDelete;

        foreach ($concreteNewssToDelete as $concreteNewsRemoved) {
            $concreteNewsRemoved->setConcreteCategory(null);
        }

        $this->collConcreteNewss = null;
        foreach ($concreteNewss as $concreteNews) {
            $this->addConcreteNews($concreteNews);
        }

        $this->collConcreteNewss = $concreteNewss;
        $this->collConcreteNewssPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ConcreteNews objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ConcreteNews objects.
     * @throws PropelException
     */
    public function countConcreteNewss(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteNewssPartial && !$this->isNew();
        if (null === $this->collConcreteNewss || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collConcreteNewss) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getConcreteNewss());
            }

            $query = ChildConcreteNewsQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByConcreteCategory($this)
                ->count($con);
        }

        return count($this->collConcreteNewss);
    }

    /**
     * Method called to associate a ChildConcreteNews object to this object
     * through the ChildConcreteNews foreign key attribute.
     *
     * @param  ChildConcreteNews $l ChildConcreteNews
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object (for fluent API support)
     */
    public function addConcreteNews(ChildConcreteNews $l)
    {
        if ($this->collConcreteNewss === null) {
            $this->initConcreteNewss();
            $this->collConcreteNewssPartial = true;
        }

        if (!$this->collConcreteNewss->contains($l)) {
            $this->doAddConcreteNews($l);
        }

        return $this;
    }

    /**
     * @param ChildConcreteNews $concreteNews The ChildConcreteNews object to add.
     */
    protected function doAddConcreteNews(ChildConcreteNews $concreteNews)
    {
        $this->collConcreteNewss[]= $concreteNews;
        $concreteNews->setConcreteCategory($this);
    }

    /**
     * @param  ChildConcreteNews $concreteNews The ChildConcreteNews object to remove.
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function removeConcreteNews(ChildConcreteNews $concreteNews)
    {
        if ($this->getConcreteNewss()->contains($concreteNews)) {
            $pos = $this->collConcreteNewss->search($concreteNews);
            $this->collConcreteNewss->remove($pos);
            if (null === $this->concreteNewssScheduledForDeletion) {
                $this->concreteNewssScheduledForDeletion = clone $this->collConcreteNewss;
                $this->concreteNewssScheduledForDeletion->clear();
            }
            $this->concreteNewssScheduledForDeletion[]= $concreteNews;
            $concreteNews->setConcreteCategory(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ConcreteCategory is new, it will return
     * an empty collection; or if this ConcreteCategory has previously
     * been saved, it will retrieve related ConcreteNewss from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ConcreteCategory.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildConcreteNews[] List of ChildConcreteNews objects
     */
    public function getConcreteNewssJoinConcreteArticle(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildConcreteNewsQuery::create(null, $criteria);
        $query->joinWith('ConcreteArticle', $joinBehavior);

        return $this->getConcreteNewss($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ConcreteCategory is new, it will return
     * an empty collection; or if this ConcreteCategory has previously
     * been saved, it will retrieve related ConcreteNewss from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ConcreteCategory.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildConcreteNews[] List of ChildConcreteNews objects
     */
    public function getConcreteNewssJoinConcreteAuthor(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildConcreteNewsQuery::create(null, $criteria);
        $query->joinWith('ConcreteAuthor', $joinBehavior);

        return $this->getConcreteNewss($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ConcreteCategory is new, it will return
     * an empty collection; or if this ConcreteCategory has previously
     * been saved, it will retrieve related ConcreteNewss from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ConcreteCategory.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildConcreteNews[] List of ChildConcreteNews objects
     */
    public function getConcreteNewssJoinConcreteContent(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildConcreteNewsQuery::create(null, $criteria);
        $query->joinWith('ConcreteContent', $joinBehavior);

        return $this->getConcreteNewss($query, $con);
    }

    /**
     * Clears out the collConcreteQuizzs collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addConcreteQuizzs()
     */
    public function clearConcreteQuizzs()
    {
        $this->collConcreteQuizzs = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collConcreteQuizzs collection loaded partially.
     */
    public function resetPartialConcreteQuizzs($v = true)
    {
        $this->collConcreteQuizzsPartial = $v;
    }

    /**
     * Initializes the collConcreteQuizzs collection.
     *
     * By default this just sets the collConcreteQuizzs collection to an empty array (like clearcollConcreteQuizzs());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initConcreteQuizzs($overrideExisting = true)
    {
        if (null !== $this->collConcreteQuizzs && !$overrideExisting) {
            return;
        }
        $this->collConcreteQuizzs = new ObjectCollection();
        $this->collConcreteQuizzs->setModel('\Propel\Tests\Bookstore\Behavior\ConcreteQuizz');
    }

    /**
     * Gets an array of ChildConcreteQuizz objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildConcreteCategory is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildConcreteQuizz[] List of ChildConcreteQuizz objects
     * @throws PropelException
     */
    public function getConcreteQuizzs(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteQuizzsPartial && !$this->isNew();
        if (null === $this->collConcreteQuizzs || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collConcreteQuizzs) {
                // return empty collection
                $this->initConcreteQuizzs();
            } else {
                $collConcreteQuizzs = ChildConcreteQuizzQuery::create(null, $criteria)
                    ->filterByConcreteCategory($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collConcreteQuizzsPartial && count($collConcreteQuizzs)) {
                        $this->initConcreteQuizzs(false);

                        foreach ($collConcreteQuizzs as $obj) {
                            if (false == $this->collConcreteQuizzs->contains($obj)) {
                                $this->collConcreteQuizzs->append($obj);
                            }
                        }

                        $this->collConcreteQuizzsPartial = true;
                    }

                    return $collConcreteQuizzs;
                }

                if ($partial && $this->collConcreteQuizzs) {
                    foreach ($this->collConcreteQuizzs as $obj) {
                        if ($obj->isNew()) {
                            $collConcreteQuizzs[] = $obj;
                        }
                    }
                }

                $this->collConcreteQuizzs = $collConcreteQuizzs;
                $this->collConcreteQuizzsPartial = false;
            }
        }

        return $this->collConcreteQuizzs;
    }

    /**
     * Sets a collection of ChildConcreteQuizz objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $concreteQuizzs A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function setConcreteQuizzs(Collection $concreteQuizzs, ConnectionInterface $con = null)
    {
        /** @var ChildConcreteQuizz[] $concreteQuizzsToDelete */
        $concreteQuizzsToDelete = $this->getConcreteQuizzs(new Criteria(), $con)->diff($concreteQuizzs);


        $this->concreteQuizzsScheduledForDeletion = $concreteQuizzsToDelete;

        foreach ($concreteQuizzsToDelete as $concreteQuizzRemoved) {
            $concreteQuizzRemoved->setConcreteCategory(null);
        }

        $this->collConcreteQuizzs = null;
        foreach ($concreteQuizzs as $concreteQuizz) {
            $this->addConcreteQuizz($concreteQuizz);
        }

        $this->collConcreteQuizzs = $concreteQuizzs;
        $this->collConcreteQuizzsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related ConcreteQuizz objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related ConcreteQuizz objects.
     * @throws PropelException
     */
    public function countConcreteQuizzs(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collConcreteQuizzsPartial && !$this->isNew();
        if (null === $this->collConcreteQuizzs || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collConcreteQuizzs) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getConcreteQuizzs());
            }

            $query = ChildConcreteQuizzQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByConcreteCategory($this)
                ->count($con);
        }

        return count($this->collConcreteQuizzs);
    }

    /**
     * Method called to associate a ChildConcreteQuizz object to this object
     * through the ChildConcreteQuizz foreign key attribute.
     *
     * @param  ChildConcreteQuizz $l ChildConcreteQuizz
     * @return $this|\Propel\Tests\Bookstore\Behavior\ConcreteCategory The current object (for fluent API support)
     */
    public function addConcreteQuizz(ChildConcreteQuizz $l)
    {
        if ($this->collConcreteQuizzs === null) {
            $this->initConcreteQuizzs();
            $this->collConcreteQuizzsPartial = true;
        }

        if (!$this->collConcreteQuizzs->contains($l)) {
            $this->doAddConcreteQuizz($l);
        }

        return $this;
    }

    /**
     * @param ChildConcreteQuizz $concreteQuizz The ChildConcreteQuizz object to add.
     */
    protected function doAddConcreteQuizz(ChildConcreteQuizz $concreteQuizz)
    {
        $this->collConcreteQuizzs[]= $concreteQuizz;
        $concreteQuizz->setConcreteCategory($this);
    }

    /**
     * @param  ChildConcreteQuizz $concreteQuizz The ChildConcreteQuizz object to remove.
     * @return $this|ChildConcreteCategory The current object (for fluent API support)
     */
    public function removeConcreteQuizz(ChildConcreteQuizz $concreteQuizz)
    {
        if ($this->getConcreteQuizzs()->contains($concreteQuizz)) {
            $pos = $this->collConcreteQuizzs->search($concreteQuizz);
            $this->collConcreteQuizzs->remove($pos);
            if (null === $this->concreteQuizzsScheduledForDeletion) {
                $this->concreteQuizzsScheduledForDeletion = clone $this->collConcreteQuizzs;
                $this->concreteQuizzsScheduledForDeletion->clear();
            }
            $this->concreteQuizzsScheduledForDeletion[]= $concreteQuizz;
            $concreteQuizz->setConcreteCategory(null);
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
            if ($this->collConcreteContents) {
                foreach ($this->collConcreteContents as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collConcreteArticles) {
                foreach ($this->collConcreteArticles as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collConcreteNewss) {
                foreach ($this->collConcreteNewss as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collConcreteQuizzs) {
                foreach ($this->collConcreteQuizzs as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collConcreteContents = null;
        $this->collConcreteArticles = null;
        $this->collConcreteNewss = null;
        $this->collConcreteQuizzs = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'name' column
     */
    public function __toString()
    {
        return (string) $this->getName();
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
