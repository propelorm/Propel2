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
use Propel\Tests\Bookstore\BookClubList;
use Propel\Tests\Bookstore\BookClubListQuery;


/**
 * This class defines the structure of the 'book_club_list' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class BookClubListTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Map.BookClubListTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'book_club_list';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\BookClubList';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.BookClubList';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 4;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 4;

    /**
     * the column name for the ID field
     */
    const COL_ID = 'book_club_list.ID';

    /**
     * the column name for the GROUP_LEADER field
     */
    const COL_GROUP_LEADER = 'book_club_list.GROUP_LEADER';

    /**
     * the column name for the THEME field
     */
    const COL_THEME = 'book_club_list.THEME';

    /**
     * the column name for the CREATED_AT field
     */
    const COL_CREATED_AT = 'book_club_list.CREATED_AT';

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
        self::TYPE_PHPNAME       => array('Id', 'GroupLeader', 'Theme', 'CreatedAt', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'groupLeader', 'theme', 'createdAt', ),
        self::TYPE_COLNAME       => array(BookClubListTableMap::COL_ID, BookClubListTableMap::COL_GROUP_LEADER, BookClubListTableMap::COL_THEME, BookClubListTableMap::COL_CREATED_AT, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_GROUP_LEADER', 'COL_THEME', 'COL_CREATED_AT', ),
        self::TYPE_FIELDNAME     => array('id', 'group_leader', 'theme', 'created_at', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'GroupLeader' => 1, 'Theme' => 2, 'CreatedAt' => 3, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'groupLeader' => 1, 'theme' => 2, 'createdAt' => 3, ),
        self::TYPE_COLNAME       => array(BookClubListTableMap::COL_ID => 0, BookClubListTableMap::COL_GROUP_LEADER => 1, BookClubListTableMap::COL_THEME => 2, BookClubListTableMap::COL_CREATED_AT => 3, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_GROUP_LEADER' => 1, 'COL_THEME' => 2, 'COL_CREATED_AT' => 3, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'group_leader' => 1, 'theme' => 2, 'created_at' => 3, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, )
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
        $this->setName('book_club_list');
        $this->setPhpName('BookClubList');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\BookClubList');
        $this->setPackage('Propel.Tests.Bookstore');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('GROUP_LEADER', 'GroupLeader', 'VARCHAR', true, 100, null);
        $this->addColumn('THEME', 'Theme', 'VARCHAR', false, 50, null);
        $this->addColumn('CREATED_AT', 'CreatedAt', 'TIMESTAMP', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('BookListRel', '\\Propel\\Tests\\Bookstore\\BookListRel', RelationMap::ONE_TO_MANY, array('id' => 'book_club_list_id', ), 'CASCADE', null, 'BookListRels');
        $this->addRelation('BookListFavorite', '\\Propel\\Tests\\Bookstore\\BookListFavorite', RelationMap::ONE_TO_MANY, array('id' => 'book_club_list_id', ), 'CASCADE', null, 'BookListFavorites');
        $this->addRelation('Book', '\\Propel\\Tests\\Bookstore\\Book', RelationMap::MANY_TO_MANY, array(), 'CASCADE', null, 'Books');
        $this->addRelation('FavoriteBook', '\\Propel\\Tests\\Bookstore\\Book', RelationMap::MANY_TO_MANY, array(), 'CASCADE', null, 'FavoriteBooks');
    } // buildRelations()
    /**
     * Method to invalidate the instance pool of all tables related to book_club_list     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        BookListRelTableMap::clearInstancePool();
        BookListFavoriteTableMap::clearInstancePool();
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
        return $withPrefix ? BookClubListTableMap::CLASS_DEFAULT : BookClubListTableMap::OM_CLASS;
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
     * @return array           (BookClubList object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = BookClubListTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = BookClubListTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + BookClubListTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = BookClubListTableMap::OM_CLASS;
            /** @var BookClubList $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            BookClubListTableMap::addInstanceToPool($obj, $key);
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
            $key = BookClubListTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = BookClubListTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var BookClubList $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                BookClubListTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(BookClubListTableMap::COL_ID);
            $criteria->addSelectColumn(BookClubListTableMap::COL_GROUP_LEADER);
            $criteria->addSelectColumn(BookClubListTableMap::COL_THEME);
            $criteria->addSelectColumn(BookClubListTableMap::COL_CREATED_AT);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.GROUP_LEADER');
            $criteria->addSelectColumn($alias . '.THEME');
            $criteria->addSelectColumn($alias . '.CREATED_AT');
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
        return Propel::getServiceContainer()->getDatabaseMap(BookClubListTableMap::DATABASE_NAME)->getTable(BookClubListTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(BookClubListTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(BookClubListTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new BookClubListTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a BookClubList or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or BookClubList object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(BookClubListTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\BookClubList) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(BookClubListTableMap::DATABASE_NAME);
            $criteria->add(BookClubListTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = BookClubListQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            BookClubListTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                BookClubListTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the book_club_list table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return BookClubListQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a BookClubList or Criteria object.
     *
     * @param mixed               $criteria Criteria or BookClubList object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookClubListTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from BookClubList object
        }

        if ($criteria->containsKey(BookClubListTableMap::COL_ID) && $criteria->keyContainsValue(BookClubListTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.BookClubListTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = BookClubListQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // BookClubListTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
BookClubListTableMap::buildTableMap();
