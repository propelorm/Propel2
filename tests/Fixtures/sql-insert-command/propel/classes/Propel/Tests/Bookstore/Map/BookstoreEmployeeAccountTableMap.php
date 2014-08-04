<?php

namespace Propel\Tests\Bookstore\Map;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery;


/**
 * This class defines the structure of the 'bookstore_employee_account' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class BookstoreEmployeeAccountTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Map.BookstoreEmployeeAccountTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'bookstore_employee_account';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\BookstoreEmployeeAccount';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.BookstoreEmployeeAccount';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 8;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 8;

    /**
     * the column name for the EMPLOYEE_ID field
     */
    const COL_EMPLOYEE_ID = 'bookstore_employee_account.EMPLOYEE_ID';

    /**
     * the column name for the LOGIN field
     */
    const COL_LOGIN = 'bookstore_employee_account.LOGIN';

    /**
     * the column name for the PASSWORD field
     */
    const COL_PASSWORD = 'bookstore_employee_account.PASSWORD';

    /**
     * the column name for the ENABLED field
     */
    const COL_ENABLED = 'bookstore_employee_account.ENABLED';

    /**
     * the column name for the NOT_ENABLED field
     */
    const COL_NOT_ENABLED = 'bookstore_employee_account.NOT_ENABLED';

    /**
     * the column name for the CREATED field
     */
    const COL_CREATED = 'bookstore_employee_account.CREATED';

    /**
     * the column name for the ROLE_ID field
     */
    const COL_ROLE_ID = 'bookstore_employee_account.ROLE_ID';

    /**
     * the column name for the AUTHENTICATOR field
     */
    const COL_AUTHENTICATOR = 'bookstore_employee_account.AUTHENTICATOR';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('EmployeeId', 'Login', 'Password', 'Enabled', 'NotEnabled', 'Created', 'RoleId', 'Authenticator', ),
        self::TYPE_STUDLYPHPNAME => array('employeeId', 'login', 'password', 'enabled', 'notEnabled', 'created', 'roleId', 'authenticator', ),
        self::TYPE_COLNAME       => array(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, BookstoreEmployeeAccountTableMap::COL_LOGIN, BookstoreEmployeeAccountTableMap::COL_PASSWORD, BookstoreEmployeeAccountTableMap::COL_ENABLED, BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED, BookstoreEmployeeAccountTableMap::COL_CREATED, BookstoreEmployeeAccountTableMap::COL_ROLE_ID, BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR, ),
        self::TYPE_RAW_COLNAME   => array('COL_EMPLOYEE_ID', 'COL_LOGIN', 'COL_PASSWORD', 'COL_ENABLED', 'COL_NOT_ENABLED', 'COL_CREATED', 'COL_ROLE_ID', 'COL_AUTHENTICATOR', ),
        self::TYPE_FIELDNAME     => array('employee_id', 'login', 'password', 'enabled', 'not_enabled', 'created', 'role_id', 'authenticator', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('EmployeeId' => 0, 'Login' => 1, 'Password' => 2, 'Enabled' => 3, 'NotEnabled' => 4, 'Created' => 5, 'RoleId' => 6, 'Authenticator' => 7, ),
        self::TYPE_STUDLYPHPNAME => array('employeeId' => 0, 'login' => 1, 'password' => 2, 'enabled' => 3, 'notEnabled' => 4, 'created' => 5, 'roleId' => 6, 'authenticator' => 7, ),
        self::TYPE_COLNAME       => array(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID => 0, BookstoreEmployeeAccountTableMap::COL_LOGIN => 1, BookstoreEmployeeAccountTableMap::COL_PASSWORD => 2, BookstoreEmployeeAccountTableMap::COL_ENABLED => 3, BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED => 4, BookstoreEmployeeAccountTableMap::COL_CREATED => 5, BookstoreEmployeeAccountTableMap::COL_ROLE_ID => 6, BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR => 7, ),
        self::TYPE_RAW_COLNAME   => array('COL_EMPLOYEE_ID' => 0, 'COL_LOGIN' => 1, 'COL_PASSWORD' => 2, 'COL_ENABLED' => 3, 'COL_NOT_ENABLED' => 4, 'COL_CREATED' => 5, 'COL_ROLE_ID' => 6, 'COL_AUTHENTICATOR' => 7, ),
        self::TYPE_FIELDNAME     => array('employee_id' => 0, 'login' => 1, 'password' => 2, 'enabled' => 3, 'not_enabled' => 4, 'created' => 5, 'role_id' => 6, 'authenticator' => 7, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, )
    );

    /**
     * Initialize the table attributes and columns
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('bookstore_employee_account');
        $this->setPhpName('BookstoreEmployeeAccount');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\BookstoreEmployeeAccount');
        $this->setPackage('Propel.Tests.Bookstore');
        $this->setUseIdGenerator(false);
        // columns
        $this->addForeignPrimaryKey('EMPLOYEE_ID', 'EmployeeId', 'INTEGER' , 'bookstore_employee', 'ID', true, null, null);
        $this->addColumn('LOGIN', 'Login', 'VARCHAR', false, 32, null);
        $this->addColumn('PASSWORD', 'Password', 'VARCHAR', false, 100, '\'@\'\'34"');
        $this->addColumn('ENABLED', 'Enabled', 'BOOLEAN', false, 1, true);
        $this->addColumn('NOT_ENABLED', 'NotEnabled', 'BOOLEAN', false, 1, false);
        $this->addColumn('CREATED', 'Created', 'TIMESTAMP', false, null, 'CURRENT_TIMESTAMP');
        $this->addForeignKey('ROLE_ID', 'RoleId', 'INTEGER', 'acct_access_role', 'ID', false, null, null);
        $this->addColumn('AUTHENTICATOR', 'Authenticator', 'VARCHAR', false, 32, '\'Password\'');
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('BookstoreEmployee', '\\Propel\\Tests\\Bookstore\\BookstoreEmployee', RelationMap::MANY_TO_ONE, array('employee_id' => 'id', ), 'CASCADE', null);
        $this->addRelation('AcctAccessRole', '\\Propel\\Tests\\Bookstore\\AcctAccessRole', RelationMap::MANY_TO_ONE, array('role_id' => 'id', ), 'SET NULL', null);
        $this->addRelation('AcctAuditLog', '\\Propel\\Tests\\Bookstore\\AcctAuditLog', RelationMap::ONE_TO_MANY, array('login' => 'uid', ), 'CASCADE', null, 'AcctAuditLogs');
    } // buildRelations()
    /**
     * Method to invalidate the instance pool of all tables related to bookstore_employee_account     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        AcctAuditLogTableMap::clearInstancePool();
    }

    /**
     * Retrieves a string version of the primary key from the DB resultset row that can be used to uniquely identify a row in this table.
     *
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, a serialize()d version of the primary key will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return string The primary key hash of the row
     */
    public static function getPrimaryKeyHashFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        // If the PK cannot be derived from the row, return NULL.
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('EmployeeId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('EmployeeId', TableMap::TYPE_PHPNAME, $indexType)];
    }

    /**
     * Retrieves the primary key from the DB resultset row
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, an array of the primary key columns will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return mixed The primary key of the row
     */
    public static function getPrimaryKeyFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        return (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('EmployeeId', TableMap::TYPE_PHPNAME, $indexType)
        ];
    }

    /**
     * The class that the tableMap will make instances of.
     *
     * If $withPrefix is true, the returned path
     * uses a dot-path notation which is translated into a path
     * relative to a location on the PHP include_path.
     * (e.g. path.to.MyClass -> 'path/to/MyClass.php')
     *
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @return string path.to.ClassName
     */
    public static function getOMClass($withPrefix = true)
    {
        return $withPrefix ? BookstoreEmployeeAccountTableMap::CLASS_DEFAULT : BookstoreEmployeeAccountTableMap::OM_CLASS;
    }

    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array  $row       row returned by DataFetcher->fetch().
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                 One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     * @return array           (BookstoreEmployeeAccount object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = BookstoreEmployeeAccountTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = BookstoreEmployeeAccountTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + BookstoreEmployeeAccountTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = BookstoreEmployeeAccountTableMap::OM_CLASS;
            /** @var BookstoreEmployeeAccount $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            BookstoreEmployeeAccountTableMap::addInstanceToPool($obj, $key);
        }

        return array($obj, $col);
    }

    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param DataFetcherInterface $dataFetcher
     * @return array
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function populateObjects(DataFetcherInterface $dataFetcher)
    {
        $results = array();

        // set the class once to avoid overhead in the loop
        $cls = static::getOMClass(false);
        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = BookstoreEmployeeAccountTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = BookstoreEmployeeAccountTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var BookstoreEmployeeAccount $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                BookstoreEmployeeAccountTableMap::addInstanceToPool($obj, $key);
            } // if key exists
        }

        return $results;
    }
    /**
     * Add all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be added to the select list and only loaded
     * on demand.
     *
     * @param Criteria $criteria object containing the columns to add.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function addSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_LOGIN);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_PASSWORD);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_ENABLED);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_CREATED);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_ROLE_ID);
            $criteria->addSelectColumn(BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR);
        } else {
            $criteria->addSelectColumn($alias . '.EMPLOYEE_ID');
            $criteria->addSelectColumn($alias . '.LOGIN');
            $criteria->addSelectColumn($alias . '.PASSWORD');
            $criteria->addSelectColumn($alias . '.ENABLED');
            $criteria->addSelectColumn($alias . '.NOT_ENABLED');
            $criteria->addSelectColumn($alias . '.CREATED');
            $criteria->addSelectColumn($alias . '.ROLE_ID');
            $criteria->addSelectColumn($alias . '.AUTHENTICATOR');
        }
    }

    /**
     * Returns the TableMap related to this object.
     * This method is not needed for general use but a specific application could have a need.
     * @return TableMap
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap(BookstoreEmployeeAccountTableMap::DATABASE_NAME)->getTable(BookstoreEmployeeAccountTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(BookstoreEmployeeAccountTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new BookstoreEmployeeAccountTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a BookstoreEmployeeAccount or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or BookstoreEmployeeAccount object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param  ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
     public static function doDelete($values, ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\BookstoreEmployeeAccount) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
            $criteria->add(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, (array) $values, Criteria::IN);
        }

        $query = BookstoreEmployeeAccountQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            BookstoreEmployeeAccountTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                BookstoreEmployeeAccountTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the bookstore_employee_account table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return BookstoreEmployeeAccountQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a BookstoreEmployeeAccount or Criteria object.
     *
     * @param mixed               $criteria Criteria or BookstoreEmployeeAccount object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from BookstoreEmployeeAccount object
        }


        // Set the correct dbName
        $query = BookstoreEmployeeAccountQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // BookstoreEmployeeAccountTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
BookstoreEmployeeAccountTableMap::buildTableMap();
