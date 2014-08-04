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
use Propel\Tests\Bookstore\AcctAccessRole as ChildAcctAccessRole;
use Propel\Tests\Bookstore\AcctAccessRoleQuery as ChildAcctAccessRoleQuery;
use Propel\Tests\Bookstore\AcctAuditLog as ChildAcctAuditLog;
use Propel\Tests\Bookstore\AcctAuditLogQuery as ChildAcctAuditLogQuery;
use Propel\Tests\Bookstore\BookstoreEmployee as ChildBookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount as ChildBookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery as ChildBookstoreEmployeeAccountQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery as ChildBookstoreEmployeeQuery;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeAccountTableMap;

/**
 * Base class that represents a row from the 'bookstore_employee_account' table.
 *
 * Bookstore employees login credentials.
 *
* @package    propel.generator.Propel.Tests.Bookstore.Base
*/
abstract class BookstoreEmployeeAccount implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Tests\\Bookstore\\Map\\BookstoreEmployeeAccountTableMap';


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
     * The value for the employee_id field.
     * @var        int
     */
    protected $employee_id;

    /**
     * The value for the login field.
     * @var        string
     */
    protected $login;

    /**
     * The value for the password field.
     * Note: this column has a database default value of: '\'@\'\'34"'
     * @var        string
     */
    protected $password;

    /**
     * The value for the enabled field.
     * Note: this column has a database default value of: true
     * @var        boolean
     */
    protected $enabled;

    /**
     * The value for the not_enabled field.
     * Note: this column has a database default value of: false
     * @var        boolean
     */
    protected $not_enabled;

    /**
     * The value for the created field.
     * Note: this column has a database default value of: (expression) CURRENT_TIMESTAMP
     * @var        \DateTime
     */
    protected $created;

    /**
     * The value for the role_id field.
     * @var        int
     */
    protected $role_id;

    /**
     * The value for the authenticator field.
     * Note: this column has a database default value of: (expression) 'Password'
     * @var        string
     */
    protected $authenticator;

    /**
     * @var        ChildBookstoreEmployee
     */
    protected $aBookstoreEmployee;

    /**
     * @var        ChildAcctAccessRole
     */
    protected $aAcctAccessRole;

    /**
     * @var        ObjectCollection|ChildAcctAuditLog[] Collection to store aggregation of ChildAcctAuditLog objects.
     */
    protected $collAcctAuditLogs;
    protected $collAcctAuditLogsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildAcctAuditLog[]
     */
    protected $acctAuditLogsScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->password = '\'@\'\'34"';
        $this->enabled = true;
        $this->not_enabled = false;
    }

    /**
     * Initializes internal state of Propel\Tests\Bookstore\Base\BookstoreEmployeeAccount object.
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
     * Compares this with another <code>BookstoreEmployeeAccount</code> instance.  If
     * <code>obj</code> is an instance of <code>BookstoreEmployeeAccount</code>, delegates to
     * <code>equals(BookstoreEmployeeAccount)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|BookstoreEmployeeAccount The current object, for fluid interface
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
     * Get the [employee_id] column value.
     * Primary key for the account ...
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->employee_id;
    }

    /**
     * Get the [login] column value.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Get the [password] column value.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get the [enabled] column value.
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get the [enabled] column value.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->getEnabled();
    }

    /**
     * Get the [not_enabled] column value.
     *
     * @return boolean
     */
    public function getNotEnabled()
    {
        return $this->not_enabled;
    }

    /**
     * Get the [not_enabled] column value.
     *
     * @return boolean
     */
    public function isNotEnabled()
    {
        return $this->getNotEnabled();
    }

    /**
     * Get the [optionally formatted] temporal [created] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreated($format = NULL)
    {
        if ($format === null) {
            return $this->created;
        } else {
            return $this->created instanceof \DateTime ? $this->created->format($format) : null;
        }
    }

    /**
     * Get the [role_id] column value.
     *
     * @return int
     */
    public function getRoleId()
    {
        return $this->role_id;
    }

    /**
     * Get the [authenticator] column value.
     *
     * @return string
     */
    public function getAuthenticator()
    {
        return $this->authenticator;
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
            if ($this->password !== '\'@\'\'34"') {
                return false;
            }

            if ($this->enabled !== true) {
                return false;
            }

            if ($this->not_enabled !== false) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('EmployeeId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->employee_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('Login', TableMap::TYPE_PHPNAME, $indexType)];
            $this->login = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('Password', TableMap::TYPE_PHPNAME, $indexType)];
            $this->password = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('Enabled', TableMap::TYPE_PHPNAME, $indexType)];
            $this->enabled = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('NotEnabled', TableMap::TYPE_PHPNAME, $indexType)];
            $this->not_enabled = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('Created', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('RoleId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->role_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : BookstoreEmployeeAccountTableMap::translateFieldName('Authenticator', TableMap::TYPE_PHPNAME, $indexType)];
            $this->authenticator = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 8; // 8 = BookstoreEmployeeAccountTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Propel\\Tests\\Bookstore\\BookstoreEmployeeAccount'), 0, $e);
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
        if ($this->aBookstoreEmployee !== null && $this->employee_id !== $this->aBookstoreEmployee->getId()) {
            $this->aBookstoreEmployee = null;
        }
        if ($this->aAcctAccessRole !== null && $this->role_id !== $this->aAcctAccessRole->getId()) {
            $this->aAcctAccessRole = null;
        }
    } // ensureConsistency

    /**
     * Set the value of [employee_id] column.
     * Primary key for the account ...
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setEmployeeId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->employee_id !== $v) {
            $this->employee_id = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID] = true;
        }

        if ($this->aBookstoreEmployee !== null && $this->aBookstoreEmployee->getId() !== $v) {
            $this->aBookstoreEmployee = null;
        }

        return $this;
    } // setEmployeeId()

    /**
     * Set the value of [login] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setLogin($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->login !== $v) {
            $this->login = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_LOGIN] = true;
        }

        return $this;
    } // setLogin()

    /**
     * Set the value of [password] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setPassword($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->password !== $v) {
            $this->password = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_PASSWORD] = true;
        }

        return $this;
    } // setPassword()

    /**
     * Sets the value of the [enabled] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setEnabled($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->enabled !== $v) {
            $this->enabled = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_ENABLED] = true;
        }

        return $this;
    } // setEnabled()

    /**
     * Sets the value of the [not_enabled] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setNotEnabled($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->not_enabled !== $v) {
            $this->not_enabled = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED] = true;
        }

        return $this;
    } // setNotEnabled()

    /**
     * Sets the value of [created] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setCreated($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->created !== null || $dt !== null) {
            if ($dt !== $this->created) {
                $this->created = $dt;
                $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_CREATED] = true;
            }
        } // if either are not null

        return $this;
    } // setCreated()

    /**
     * Set the value of [role_id] column.
     *
     * @param  int $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setRoleId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->role_id !== $v) {
            $this->role_id = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_ROLE_ID] = true;
        }

        if ($this->aAcctAccessRole !== null && $this->aAcctAccessRole->getId() !== $v) {
            $this->aAcctAccessRole = null;
        }

        return $this;
    } // setRoleId()

    /**
     * Set the value of [authenticator] column.
     *
     * @param  string $v new value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setAuthenticator($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->authenticator !== $v) {
            $this->authenticator = $v;
            $this->modifiedColumns[BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR] = true;
        }

        return $this;
    } // setAuthenticator()

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
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookstoreEmployeeAccountQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aBookstoreEmployee = null;
            $this->aAcctAccessRole = null;
            $this->collAcctAuditLogs = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see BookstoreEmployeeAccount::setDeleted()
     * @see BookstoreEmployeeAccount::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildBookstoreEmployeeAccountQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
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
                BookstoreEmployeeAccountTableMap::addInstanceToPool($this);
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

            if ($this->aBookstoreEmployee !== null) {
                if ($this->aBookstoreEmployee->isModified() || $this->aBookstoreEmployee->isNew()) {
                    $affectedRows += $this->aBookstoreEmployee->save($con);
                }
                $this->setBookstoreEmployee($this->aBookstoreEmployee);
            }

            if ($this->aAcctAccessRole !== null) {
                if ($this->aAcctAccessRole->isModified() || $this->aAcctAccessRole->isNew()) {
                    $affectedRows += $this->aAcctAccessRole->save($con);
                }
                $this->setAcctAccessRole($this->aAcctAccessRole);
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
                    if (!$skipReload) {
                        $reloadObject = true;
                    }
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->acctAuditLogsScheduledForDeletion !== null) {
                if (!$this->acctAuditLogsScheduledForDeletion->isEmpty()) {
                    \Propel\Tests\Bookstore\AcctAuditLogQuery::create()
                        ->filterByPrimaryKeys($this->acctAuditLogsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->acctAuditLogsScheduledForDeletion = null;
                }
            }

            if ($this->collAcctAuditLogs !== null) {
                foreach ($this->collAcctAuditLogs as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
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
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID)) {
            $modifiedColumns[':p' . $index++]  = 'EMPLOYEE_ID';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_LOGIN)) {
            $modifiedColumns[':p' . $index++]  = 'LOGIN';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_PASSWORD)) {
            $modifiedColumns[':p' . $index++]  = 'PASSWORD';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_ENABLED)) {
            $modifiedColumns[':p' . $index++]  = 'ENABLED';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED)) {
            $modifiedColumns[':p' . $index++]  = 'NOT_ENABLED';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_CREATED)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_ROLE_ID)) {
            $modifiedColumns[':p' . $index++]  = 'ROLE_ID';
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR)) {
            $modifiedColumns[':p' . $index++]  = 'AUTHENTICATOR';
        }

        $sql = sprintf(
            'INSERT INTO bookstore_employee_account (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'EMPLOYEE_ID':
                        $stmt->bindValue($identifier, $this->employee_id, PDO::PARAM_INT);
                        break;
                    case 'LOGIN':
                        $stmt->bindValue($identifier, $this->login, PDO::PARAM_STR);
                        break;
                    case 'PASSWORD':
                        $stmt->bindValue($identifier, $this->password, PDO::PARAM_STR);
                        break;
                    case 'ENABLED':
                        $stmt->bindValue($identifier, (int) $this->enabled, PDO::PARAM_INT);
                        break;
                    case 'NOT_ENABLED':
                        $stmt->bindValue($identifier, (int) $this->not_enabled, PDO::PARAM_INT);
                        break;
                    case 'CREATED':
                        $stmt->bindValue($identifier, $this->created ? $this->created->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'ROLE_ID':
                        $stmt->bindValue($identifier, $this->role_id, PDO::PARAM_INT);
                        break;
                    case 'AUTHENTICATOR':
                        $stmt->bindValue($identifier, $this->authenticator, PDO::PARAM_STR);
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
        $pos = BookstoreEmployeeAccountTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getEmployeeId();
                break;
            case 1:
                return $this->getLogin();
                break;
            case 2:
                return $this->getPassword();
                break;
            case 3:
                return $this->getEnabled();
                break;
            case 4:
                return $this->getNotEnabled();
                break;
            case 5:
                return $this->getCreated();
                break;
            case 6:
                return $this->getRoleId();
                break;
            case 7:
                return $this->getAuthenticator();
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
        if (isset($alreadyDumpedObjects['BookstoreEmployeeAccount'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['BookstoreEmployeeAccount'][$this->getPrimaryKey()] = true;
        $keys = BookstoreEmployeeAccountTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getEmployeeId(),
            $keys[1] => $this->getLogin(),
            $keys[2] => $this->getPassword(),
            $keys[3] => $this->getEnabled(),
            $keys[4] => $this->getNotEnabled(),
            $keys[5] => $this->getCreated(),
            $keys[6] => $this->getRoleId(),
            $keys[7] => $this->getAuthenticator(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aBookstoreEmployee) {

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

                $result[$key] = $this->aBookstoreEmployee->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aAcctAccessRole) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'acctAccessRole';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'acct_access_role';
                        break;
                    default:
                        $key = 'AcctAccessRole';
                }

                $result[$key] = $this->aAcctAccessRole->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collAcctAuditLogs) {

                switch ($keyType) {
                    case TableMap::TYPE_STUDLYPHPNAME:
                        $key = 'acctAuditLogs';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'acct_audit_logs';
                        break;
                    default:
                        $key = 'AcctAuditLogs';
                }

                $result[$key] = $this->collAcctAuditLogs->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = BookstoreEmployeeAccountTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setEmployeeId($value);
                break;
            case 1:
                $this->setLogin($value);
                break;
            case 2:
                $this->setPassword($value);
                break;
            case 3:
                $this->setEnabled($value);
                break;
            case 4:
                $this->setNotEnabled($value);
                break;
            case 5:
                $this->setCreated($value);
                break;
            case 6:
                $this->setRoleId($value);
                break;
            case 7:
                $this->setAuthenticator($value);
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
        $keys = BookstoreEmployeeAccountTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setEmployeeId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setLogin($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setPassword($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setEnabled($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setNotEnabled($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setCreated($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setRoleId($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setAuthenticator($arr[$keys[7]]);
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object, for fluid interface
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
        $criteria = new Criteria(BookstoreEmployeeAccountTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $this->employee_id);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_LOGIN)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_LOGIN, $this->login);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_PASSWORD)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_PASSWORD, $this->password);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_ENABLED)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_ENABLED, $this->enabled);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED, $this->not_enabled);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_CREATED)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_CREATED, $this->created);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_ROLE_ID)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_ROLE_ID, $this->role_id);
        }
        if ($this->isColumnModified(BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR)) {
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR, $this->authenticator);
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
        $criteria = new Criteria(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        $criteria->add(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $this->employee_id);

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
        $validPk = null !== $this->getEmployeeId();

        $validPrimaryKeyFKs = 1;
        $primaryKeyFKs = [];

        //relation bookstore_employee_account_fk_0ae967 to table bookstore_employee
        if ($this->aBookstoreEmployee && $hash = spl_object_hash($this->aBookstoreEmployee)) {
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
        return $this->getEmployeeId();
    }

    /**
     * Generic method to set the primary key (employee_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setEmployeeId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getEmployeeId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Propel\Tests\Bookstore\BookstoreEmployeeAccount (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setEmployeeId($this->getEmployeeId());
        $copyObj->setLogin($this->getLogin());
        $copyObj->setPassword($this->getPassword());
        $copyObj->setEnabled($this->getEnabled());
        $copyObj->setNotEnabled($this->getNotEnabled());
        $copyObj->setCreated($this->getCreated());
        $copyObj->setRoleId($this->getRoleId());
        $copyObj->setAuthenticator($this->getAuthenticator());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getAcctAuditLogs() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addAcctAuditLog($relObj->copy($deepCopy));
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
     * @return \Propel\Tests\Bookstore\BookstoreEmployeeAccount Clone of current object.
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
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     * @throws PropelException
     */
    public function setBookstoreEmployee(ChildBookstoreEmployee $v = null)
    {
        if ($v === null) {
            $this->setEmployeeId(NULL);
        } else {
            $this->setEmployeeId($v->getId());
        }

        $this->aBookstoreEmployee = $v;

        // Add binding for other direction of this 1:1 relationship.
        if ($v !== null) {
            $v->setBookstoreEmployeeAccount($this);
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
    public function getBookstoreEmployee(ConnectionInterface $con = null)
    {
        if ($this->aBookstoreEmployee === null && ($this->employee_id !== null)) {
            $this->aBookstoreEmployee = ChildBookstoreEmployeeQuery::create()->findPk($this->employee_id, $con);
            // Because this foreign key represents a one-to-one relationship, we will create a bi-directional association.
            $this->aBookstoreEmployee->setBookstoreEmployeeAccount($this);
        }

        return $this->aBookstoreEmployee;
    }

    /**
     * Declares an association between this object and a ChildAcctAccessRole object.
     *
     * @param  ChildAcctAccessRole $v
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     * @throws PropelException
     */
    public function setAcctAccessRole(ChildAcctAccessRole $v = null)
    {
        if ($v === null) {
            $this->setRoleId(NULL);
        } else {
            $this->setRoleId($v->getId());
        }

        $this->aAcctAccessRole = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildAcctAccessRole object, it will not be re-added.
        if ($v !== null) {
            $v->addBookstoreEmployeeAccount($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildAcctAccessRole object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildAcctAccessRole The associated ChildAcctAccessRole object.
     * @throws PropelException
     */
    public function getAcctAccessRole(ConnectionInterface $con = null)
    {
        if ($this->aAcctAccessRole === null && ($this->role_id !== null)) {
            $this->aAcctAccessRole = ChildAcctAccessRoleQuery::create()->findPk($this->role_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aAcctAccessRole->addBookstoreEmployeeAccounts($this);
             */
        }

        return $this->aAcctAccessRole;
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
        if ('AcctAuditLog' == $relationName) {
            return $this->initAcctAuditLogs();
        }
    }

    /**
     * Clears out the collAcctAuditLogs collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addAcctAuditLogs()
     */
    public function clearAcctAuditLogs()
    {
        $this->collAcctAuditLogs = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collAcctAuditLogs collection loaded partially.
     */
    public function resetPartialAcctAuditLogs($v = true)
    {
        $this->collAcctAuditLogsPartial = $v;
    }

    /**
     * Initializes the collAcctAuditLogs collection.
     *
     * By default this just sets the collAcctAuditLogs collection to an empty array (like clearcollAcctAuditLogs());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initAcctAuditLogs($overrideExisting = true)
    {
        if (null !== $this->collAcctAuditLogs && !$overrideExisting) {
            return;
        }
        $this->collAcctAuditLogs = new ObjectCollection();
        $this->collAcctAuditLogs->setModel('\Propel\Tests\Bookstore\AcctAuditLog');
    }

    /**
     * Gets an array of ChildAcctAuditLog objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildBookstoreEmployeeAccount is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildAcctAuditLog[] List of ChildAcctAuditLog objects
     * @throws PropelException
     */
    public function getAcctAuditLogs(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collAcctAuditLogsPartial && !$this->isNew();
        if (null === $this->collAcctAuditLogs || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collAcctAuditLogs) {
                // return empty collection
                $this->initAcctAuditLogs();
            } else {
                $collAcctAuditLogs = ChildAcctAuditLogQuery::create(null, $criteria)
                    ->filterByBookstoreEmployeeAccount($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collAcctAuditLogsPartial && count($collAcctAuditLogs)) {
                        $this->initAcctAuditLogs(false);

                        foreach ($collAcctAuditLogs as $obj) {
                            if (false == $this->collAcctAuditLogs->contains($obj)) {
                                $this->collAcctAuditLogs->append($obj);
                            }
                        }

                        $this->collAcctAuditLogsPartial = true;
                    }

                    return $collAcctAuditLogs;
                }

                if ($partial && $this->collAcctAuditLogs) {
                    foreach ($this->collAcctAuditLogs as $obj) {
                        if ($obj->isNew()) {
                            $collAcctAuditLogs[] = $obj;
                        }
                    }
                }

                $this->collAcctAuditLogs = $collAcctAuditLogs;
                $this->collAcctAuditLogsPartial = false;
            }
        }

        return $this->collAcctAuditLogs;
    }

    /**
     * Sets a collection of ChildAcctAuditLog objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $acctAuditLogs A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildBookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function setAcctAuditLogs(Collection $acctAuditLogs, ConnectionInterface $con = null)
    {
        /** @var ChildAcctAuditLog[] $acctAuditLogsToDelete */
        $acctAuditLogsToDelete = $this->getAcctAuditLogs(new Criteria(), $con)->diff($acctAuditLogs);


        $this->acctAuditLogsScheduledForDeletion = $acctAuditLogsToDelete;

        foreach ($acctAuditLogsToDelete as $acctAuditLogRemoved) {
            $acctAuditLogRemoved->setBookstoreEmployeeAccount(null);
        }

        $this->collAcctAuditLogs = null;
        foreach ($acctAuditLogs as $acctAuditLog) {
            $this->addAcctAuditLog($acctAuditLog);
        }

        $this->collAcctAuditLogs = $acctAuditLogs;
        $this->collAcctAuditLogsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related AcctAuditLog objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related AcctAuditLog objects.
     * @throws PropelException
     */
    public function countAcctAuditLogs(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collAcctAuditLogsPartial && !$this->isNew();
        if (null === $this->collAcctAuditLogs || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collAcctAuditLogs) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getAcctAuditLogs());
            }

            $query = ChildAcctAuditLogQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByBookstoreEmployeeAccount($this)
                ->count($con);
        }

        return count($this->collAcctAuditLogs);
    }

    /**
     * Method called to associate a ChildAcctAuditLog object to this object
     * through the ChildAcctAuditLog foreign key attribute.
     *
     * @param  ChildAcctAuditLog $l ChildAcctAuditLog
     * @return $this|\Propel\Tests\Bookstore\BookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function addAcctAuditLog(ChildAcctAuditLog $l)
    {
        if ($this->collAcctAuditLogs === null) {
            $this->initAcctAuditLogs();
            $this->collAcctAuditLogsPartial = true;
        }

        if (!$this->collAcctAuditLogs->contains($l)) {
            $this->doAddAcctAuditLog($l);
        }

        return $this;
    }

    /**
     * @param ChildAcctAuditLog $acctAuditLog The ChildAcctAuditLog object to add.
     */
    protected function doAddAcctAuditLog(ChildAcctAuditLog $acctAuditLog)
    {
        $this->collAcctAuditLogs[]= $acctAuditLog;
        $acctAuditLog->setBookstoreEmployeeAccount($this);
    }

    /**
     * @param  ChildAcctAuditLog $acctAuditLog The ChildAcctAuditLog object to remove.
     * @return $this|ChildBookstoreEmployeeAccount The current object (for fluent API support)
     */
    public function removeAcctAuditLog(ChildAcctAuditLog $acctAuditLog)
    {
        if ($this->getAcctAuditLogs()->contains($acctAuditLog)) {
            $pos = $this->collAcctAuditLogs->search($acctAuditLog);
            $this->collAcctAuditLogs->remove($pos);
            if (null === $this->acctAuditLogsScheduledForDeletion) {
                $this->acctAuditLogsScheduledForDeletion = clone $this->collAcctAuditLogs;
                $this->acctAuditLogsScheduledForDeletion->clear();
            }
            $this->acctAuditLogsScheduledForDeletion[]= clone $acctAuditLog;
            $acctAuditLog->setBookstoreEmployeeAccount(null);
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
        if (null !== $this->aBookstoreEmployee) {
            $this->aBookstoreEmployee->removeBookstoreEmployeeAccount($this);
        }
        if (null !== $this->aAcctAccessRole) {
            $this->aAcctAccessRole->removeBookstoreEmployeeAccount($this);
        }
        $this->employee_id = null;
        $this->login = null;
        $this->password = null;
        $this->enabled = null;
        $this->not_enabled = null;
        $this->created = null;
        $this->role_id = null;
        $this->authenticator = null;
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
            if ($this->collAcctAuditLogs) {
                foreach ($this->collAcctAuditLogs as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collAcctAuditLogs = null;
        $this->aBookstoreEmployee = null;
        $this->aAcctAccessRole = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(BookstoreEmployeeAccountTableMap::DEFAULT_STRING_FORMAT);
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
