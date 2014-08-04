<?php

namespace Propel\Tests\Bookstore\Behavior\Map;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;
use Propel\Tests\Bookstore\Behavior\SortableMultiCommaScopes;
use Propel\Tests\Bookstore\Behavior\SortableMultiCommaScopesQuery;


/**
 * This class defines the structure of the 'sortable_multi_comma_scopes' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class SortableMultiCommaScopesTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Behavior.Map.SortableMultiCommaScopesTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore-behavior';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'sortable_multi_comma_scopes';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Behavior\\SortableMultiCommaScopes';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Behavior.SortableMultiCommaScopes';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 5;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 5;

    /**
     * the column name for the ID field
     */
    const COL_ID = 'sortable_multi_comma_scopes.ID';

    /**
     * the column name for the CATEGORY_ID field
     */
    const COL_CATEGORY_ID = 'sortable_multi_comma_scopes.CATEGORY_ID';

    /**
     * the column name for the SUB_CATEGORY_ID field
     */
    const COL_SUB_CATEGORY_ID = 'sortable_multi_comma_scopes.SUB_CATEGORY_ID';

    /**
     * the column name for the TITLE field
     */
    const COL_TITLE = 'sortable_multi_comma_scopes.TITLE';

    /**
     * the column name for the POSITION field
     */
    const COL_POSITION = 'sortable_multi_comma_scopes.POSITION';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    // sortable behavior
    /**
     * rank column
     */
    const RANK_COL = "sortable_multi_comma_scopes.POSITION";


        /**
    * If defined, the `SCOPE_COL` contains a json_encoded array with all columns.
    * @var boolean
    */
    const MULTI_SCOPE_COL = true;


    /**
    * Scope column for the set
    */
    const SCOPE_COL = '["sortable_multi_comma_scopes.CATEGORY_ID","sortable_multi_comma_scopes.SUB_CATEGORY_ID"]';


    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('Id', 'CategoryId', 'SubCategoryId', 'Title', 'Position', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'categoryId', 'subCategoryId', 'title', 'position', ),
        self::TYPE_COLNAME       => array(SortableMultiCommaScopesTableMap::COL_ID, SortableMultiCommaScopesTableMap::COL_CATEGORY_ID, SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID, SortableMultiCommaScopesTableMap::COL_TITLE, SortableMultiCommaScopesTableMap::COL_POSITION, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_CATEGORY_ID', 'COL_SUB_CATEGORY_ID', 'COL_TITLE', 'COL_POSITION', ),
        self::TYPE_FIELDNAME     => array('id', 'category_id', 'sub_category_id', 'title', 'position', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'CategoryId' => 1, 'SubCategoryId' => 2, 'Title' => 3, 'Position' => 4, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'categoryId' => 1, 'subCategoryId' => 2, 'title' => 3, 'position' => 4, ),
        self::TYPE_COLNAME       => array(SortableMultiCommaScopesTableMap::COL_ID => 0, SortableMultiCommaScopesTableMap::COL_CATEGORY_ID => 1, SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID => 2, SortableMultiCommaScopesTableMap::COL_TITLE => 3, SortableMultiCommaScopesTableMap::COL_POSITION => 4, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_CATEGORY_ID' => 1, 'COL_SUB_CATEGORY_ID' => 2, 'COL_TITLE' => 3, 'COL_POSITION' => 4, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'category_id' => 1, 'sub_category_id' => 2, 'title' => 3, 'position' => 4, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
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
        $this->setName('sortable_multi_comma_scopes');
        $this->setPhpName('SortableMultiCommaScopes');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Behavior\\SortableMultiCommaScopes');
        $this->setPackage('Propel.Tests.Bookstore.Behavior');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('CATEGORY_ID', 'CategoryId', 'INTEGER', true, null, null);
        $this->addColumn('SUB_CATEGORY_ID', 'SubCategoryId', 'INTEGER', false, null, null);
        $this->addColumn('TITLE', 'Title', 'VARCHAR', false, 100, null);
        $this->getColumn('TITLE', false)->setPrimaryString(true);
        $this->addColumn('POSITION', 'Position', 'INTEGER', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
    } // buildRelations()

    /**
     *
     * Gets the list of behaviors registered for this table
     *
     * @return array Associative array (name => parameters) of behaviors
     */
    public function getBehaviors()
    {
        return array(
            'sortable' => array('rank_column' => 'position', 'use_scope' => 'true', 'scope_column' => 'category_id, sub_category_id', ),
        );
    } // getBehaviors()

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
        return $withPrefix ? SortableMultiCommaScopesTableMap::CLASS_DEFAULT : SortableMultiCommaScopesTableMap::OM_CLASS;
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
     * @return array           (SortableMultiCommaScopes object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = SortableMultiCommaScopesTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = SortableMultiCommaScopesTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + SortableMultiCommaScopesTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = SortableMultiCommaScopesTableMap::OM_CLASS;
            /** @var SortableMultiCommaScopes $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            SortableMultiCommaScopesTableMap::addInstanceToPool($obj, $key);
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
            $key = SortableMultiCommaScopesTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = SortableMultiCommaScopesTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var SortableMultiCommaScopes $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                SortableMultiCommaScopesTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(SortableMultiCommaScopesTableMap::COL_ID);
            $criteria->addSelectColumn(SortableMultiCommaScopesTableMap::COL_CATEGORY_ID);
            $criteria->addSelectColumn(SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID);
            $criteria->addSelectColumn(SortableMultiCommaScopesTableMap::COL_TITLE);
            $criteria->addSelectColumn(SortableMultiCommaScopesTableMap::COL_POSITION);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.CATEGORY_ID');
            $criteria->addSelectColumn($alias . '.SUB_CATEGORY_ID');
            $criteria->addSelectColumn($alias . '.TITLE');
            $criteria->addSelectColumn($alias . '.POSITION');
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
        return Propel::getServiceContainer()->getDatabaseMap(SortableMultiCommaScopesTableMap::DATABASE_NAME)->getTable(SortableMultiCommaScopesTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(SortableMultiCommaScopesTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new SortableMultiCommaScopesTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a SortableMultiCommaScopes or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or SortableMultiCommaScopes object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Behavior\SortableMultiCommaScopes) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(SortableMultiCommaScopesTableMap::DATABASE_NAME);
            $criteria->add(SortableMultiCommaScopesTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = SortableMultiCommaScopesQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            SortableMultiCommaScopesTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                SortableMultiCommaScopesTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the sortable_multi_comma_scopes table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return SortableMultiCommaScopesQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a SortableMultiCommaScopes or Criteria object.
     *
     * @param mixed               $criteria Criteria or SortableMultiCommaScopes object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from SortableMultiCommaScopes object
        }

        if ($criteria->containsKey(SortableMultiCommaScopesTableMap::COL_ID) && $criteria->keyContainsValue(SortableMultiCommaScopesTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.SortableMultiCommaScopesTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = SortableMultiCommaScopesQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // SortableMultiCommaScopesTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
SortableMultiCommaScopesTableMap::buildTableMap();
