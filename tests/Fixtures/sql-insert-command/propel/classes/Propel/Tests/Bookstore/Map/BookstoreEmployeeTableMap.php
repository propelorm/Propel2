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
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;


/**
 * This class defines the structure of the 'bookstore_employee' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class BookstoreEmployeeTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Map.BookstoreEmployeeTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'bookstore_employee';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\BookstoreEmployee';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.BookstoreEmployee';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 6;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 1;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 5;

    /**
     * the column name for the ID field
     */
    const COL_ID = 'bookstore_employee.ID';

    /**
     * the column name for the CLASS_KEY field
     */
    const COL_CLASS_KEY = 'bookstore_employee.CLASS_KEY';

    /**
     * the column name for the NAME field
     */
    const COL_NAME = 'bookstore_employee.NAME';

    /**
     * the column name for the JOB_TITLE field
     */
    const COL_JOB_TITLE = 'bookstore_employee.JOB_TITLE';

    /**
     * the column name for the SUPERVISOR_ID field
     */
    const COL_SUPERVISOR_ID = 'bookstore_employee.SUPERVISOR_ID';

    /**
     * the column name for the PHOTO field
     */
    const COL_PHOTO = 'bookstore_employee.PHOTO';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** A key representing a particular subclass */
    const CLASSKEY_0 = '0';

    /** A key representing a particular subclass */
    const CLASSKEY_BOOKSTOREEMPLOYEE = '\\Propel\\Tests\\Bookstore\\BookstoreEmployee';

    /** A class that can be returned by this tableMap. */
    const CLASSNAME_0 = '\\Propel\\Tests\\Bookstore\\BookstoreEmployee';

    /** A key representing a particular subclass */
    const CLASSKEY_1 = '1';

    /** A key representing a particular subclass */
    const CLASSKEY_BOOKSTOREMANAGER = '\\Propel\\Tests\\Bookstore\\BookstoreManager';

    /** A class that can be returned by this tableMap. */
    const CLASSNAME_1 = '\\Propel\\Tests\\Bookstore\\BookstoreManager';

    /** A key representing a particular subclass */
    const CLASSKEY_2 = '2';

    /** A key representing a particular subclass */
    const CLASSKEY_BOOKSTORECASHIER = '\\Propel\\Tests\\Bookstore\\BookstoreCashier';

    /** A class that can be returned by this tableMap. */
    const CLASSNAME_2 = '\\Propel\\Tests\\Bookstore\\BookstoreCashier';

    /** A key representing a particular subclass */
    const CLASSKEY_3 = '3';

    /** A key representing a particular subclass */
    const CLASSKEY_BOOKSTOREHEAD = '\\Propel\\Tests\\Bookstore\\BookstoreHead';

    /** A class that can be returned by this tableMap. */
    const CLASSNAME_3 = '\\Propel\\Tests\\Bookstore\\BookstoreHead';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('Id', 'ClassKey', 'Name', 'JobTitle', 'SupervisorId', 'Photo', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'classKey', 'name', 'jobTitle', 'supervisorId', 'photo', ),
        self::TYPE_COLNAME       => array(BookstoreEmployeeTableMap::COL_ID, BookstoreEmployeeTableMap::COL_CLASS_KEY, BookstoreEmployeeTableMap::COL_NAME, BookstoreEmployeeTableMap::COL_JOB_TITLE, BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, BookstoreEmployeeTableMap::COL_PHOTO, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_CLASS_KEY', 'COL_NAME', 'COL_JOB_TITLE', 'COL_SUPERVISOR_ID', 'COL_PHOTO', ),
        self::TYPE_FIELDNAME     => array('id', 'class_key', 'name', 'job_title', 'supervisor_id', 'photo', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'ClassKey' => 1, 'Name' => 2, 'JobTitle' => 3, 'SupervisorId' => 4, 'Photo' => 5, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'classKey' => 1, 'name' => 2, 'jobTitle' => 3, 'supervisorId' => 4, 'photo' => 5, ),
        self::TYPE_COLNAME       => array(BookstoreEmployeeTableMap::COL_ID => 0, BookstoreEmployeeTableMap::COL_CLASS_KEY => 1, BookstoreEmployeeTableMap::COL_NAME => 2, BookstoreEmployeeTableMap::COL_JOB_TITLE => 3, BookstoreEmployeeTableMap::COL_SUPERVISOR_ID => 4, BookstoreEmployeeTableMap::COL_PHOTO => 5, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_CLASS_KEY' => 1, 'COL_NAME' => 2, 'COL_JOB_TITLE' => 3, 'COL_SUPERVISOR_ID' => 4, 'COL_PHOTO' => 5, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'class_key' => 1, 'name' => 2, 'job_title' => 3, 'supervisor_id' => 4, 'photo' => 5, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
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
        $this->setName('bookstore_employee');
        $this->setPhpName('BookstoreEmployee');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\BookstoreEmployee');
        $this->setPackage('Propel.Tests.Bookstore');
        $this->setUseIdGenerator(true);
        $this->setSingleTableInheritance(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('CLASS_KEY', 'ClassKey', 'INTEGER', true, null, 0);
        $this->addColumn('NAME', 'Name', 'VARCHAR', false, 32, null);
        $this->addColumn('JOB_TITLE', 'JobTitle', 'VARCHAR', false, 32, null);
        $this->addForeignKey('SUPERVISOR_ID', 'SupervisorId', 'INTEGER', 'bookstore_employee', 'ID', false, null, null);
        $this->addColumn('PHOTO', 'Photo', 'BLOB', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('Supervisor', '\\Propel\\Tests\\Bookstore\\BookstoreEmployee', RelationMap::MANY_TO_ONE, array('supervisor_id' => 'id', ), 'SET NULL', null);
        $this->addRelation('Subordinate', '\\Propel\\Tests\\Bookstore\\BookstoreEmployee', RelationMap::ONE_TO_MANY, array('id' => 'supervisor_id', ), 'SET NULL', null, 'Subordinates');
        $this->addRelation('BookstoreEmployeeAccount', '\\Propel\\Tests\\Bookstore\\BookstoreEmployeeAccount', RelationMap::ONE_TO_ONE, array('id' => 'employee_id', ), 'CASCADE', null);
    } // buildRelations()
    /**
     * Method to invalidate the instance pool of all tables related to bookstore_employee     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        BookstoreEmployeeTableMap::clearInstancePool();
        BookstoreEmployeeAccountTableMap::clearInstancePool();
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)
        ];
    }

    /**
     * The returned Class will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param array   $row ConnectionInterface result row.
     * @param int     $colnum Column to examine for OM class information (first is 0).
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     *
     * @return string The OM class
     */
    public static function getOMClass($row, $colnum, $withPrefix = true)
    {
        try {

            $omClass = null;
            $classKey = $row[$colnum + 1];

            switch ($classKey) {

                case BookstoreEmployeeTableMap::CLASSKEY_0:
                    $omClass = BookstoreEmployeeTableMap::CLASSNAME_0;
                    break;

                case BookstoreEmployeeTableMap::CLASSKEY_1:
                    $omClass = BookstoreEmployeeTableMap::CLASSNAME_1;
                    break;

                case BookstoreEmployeeTableMap::CLASSKEY_2:
                    $omClass = BookstoreEmployeeTableMap::CLASSNAME_2;
                    break;

                case BookstoreEmployeeTableMap::CLASSKEY_3:
                    $omClass = BookstoreEmployeeTableMap::CLASSNAME_3;
                    break;

                default:
                    $omClass = BookstoreEmployeeTableMap::CLASS_DEFAULT;

            } // switch
            if (!$withPrefix) {
                $omClass = preg_replace('#\.#', '\\', $omClass);
            }

        } catch (\Exception $e) {
            throw new PropelException('Unable to get OM class.', $e);
        }

        return $omClass;
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
     * @return array           (BookstoreEmployee object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = BookstoreEmployeeTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = BookstoreEmployeeTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + BookstoreEmployeeTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = static::getOMClass($row, $offset, false);
            /** @var BookstoreEmployee $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            BookstoreEmployeeTableMap::addInstanceToPool($obj, $key);
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

        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = BookstoreEmployeeTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = BookstoreEmployeeTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                // class must be set each time from the record row
                $cls = static::getOMClass($row, 0);
                $cls = preg_replace('#\.#', '\\', $cls);
                /** @var BookstoreEmployee $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                BookstoreEmployeeTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(BookstoreEmployeeTableMap::COL_ID);
            $criteria->addSelectColumn(BookstoreEmployeeTableMap::COL_CLASS_KEY);
            $criteria->addSelectColumn(BookstoreEmployeeTableMap::COL_NAME);
            $criteria->addSelectColumn(BookstoreEmployeeTableMap::COL_JOB_TITLE);
            $criteria->addSelectColumn(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.CLASS_KEY');
            $criteria->addSelectColumn($alias . '.NAME');
            $criteria->addSelectColumn($alias . '.JOB_TITLE');
            $criteria->addSelectColumn($alias . '.SUPERVISOR_ID');
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
        return Propel::getServiceContainer()->getDatabaseMap(BookstoreEmployeeTableMap::DATABASE_NAME)->getTable(BookstoreEmployeeTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(BookstoreEmployeeTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(BookstoreEmployeeTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new BookstoreEmployeeTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a BookstoreEmployee or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or BookstoreEmployee object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\BookstoreEmployee) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(BookstoreEmployeeTableMap::DATABASE_NAME);
            $criteria->add(BookstoreEmployeeTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = BookstoreEmployeeQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            BookstoreEmployeeTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                BookstoreEmployeeTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the bookstore_employee table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return BookstoreEmployeeQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a BookstoreEmployee or Criteria object.
     *
     * @param mixed               $criteria Criteria or BookstoreEmployee object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from BookstoreEmployee object
        }

        if ($criteria->containsKey(BookstoreEmployeeTableMap::COL_ID) && $criteria->keyContainsValue(BookstoreEmployeeTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.BookstoreEmployeeTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = BookstoreEmployeeQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // BookstoreEmployeeTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
BookstoreEmployeeTableMap::buildTableMap();
