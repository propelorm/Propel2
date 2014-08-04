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
use Propel\Tests\Bookstore\Behavior\ConcreteContent;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery;


/**
 * This class defines the structure of the 'concrete_content' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ConcreteContentTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Behavior.Map.ConcreteContentTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore-behavior';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'concrete_content';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteContent';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Behavior.ConcreteContent';

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
    const COL_ID = 'concrete_content.ID';

    /**
     * the column name for the TITLE field
     */
    const COL_TITLE = 'concrete_content.TITLE';

    /**
     * the column name for the CATEGORY_ID field
     */
    const COL_CATEGORY_ID = 'concrete_content.CATEGORY_ID';

    /**
     * the column name for the DESCENDANT_CLASS field
     */
    const COL_DESCENDANT_CLASS = 'concrete_content.DESCENDANT_CLASS';

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
        self::TYPE_PHPNAME       => array('Id', 'Title', 'CategoryId', 'DescendantClass', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'title', 'categoryId', 'descendantClass', ),
        self::TYPE_COLNAME       => array(ConcreteContentTableMap::COL_ID, ConcreteContentTableMap::COL_TITLE, ConcreteContentTableMap::COL_CATEGORY_ID, ConcreteContentTableMap::COL_DESCENDANT_CLASS, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_TITLE', 'COL_CATEGORY_ID', 'COL_DESCENDANT_CLASS', ),
        self::TYPE_FIELDNAME     => array('id', 'title', 'category_id', 'descendant_class', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'Title' => 1, 'CategoryId' => 2, 'DescendantClass' => 3, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'title' => 1, 'categoryId' => 2, 'descendantClass' => 3, ),
        self::TYPE_COLNAME       => array(ConcreteContentTableMap::COL_ID => 0, ConcreteContentTableMap::COL_TITLE => 1, ConcreteContentTableMap::COL_CATEGORY_ID => 2, ConcreteContentTableMap::COL_DESCENDANT_CLASS => 3, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_TITLE' => 1, 'COL_CATEGORY_ID' => 2, 'COL_DESCENDANT_CLASS' => 3, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'title' => 1, 'category_id' => 2, 'descendant_class' => 3, ),
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
        $this->setName('concrete_content');
        $this->setPhpName('ConcreteContent');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteContent');
        $this->setPackage('Propel.Tests.Bookstore.Behavior');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('TITLE', 'Title', 'VARCHAR', false, 100, null);
        $this->getColumn('TITLE', false)->setPrimaryString(true);
        $this->addForeignKey('CATEGORY_ID', 'CategoryId', 'INTEGER', 'concrete_category', 'ID', false, null, null);
        $this->addColumn('DESCENDANT_CLASS', 'DescendantClass', 'VARCHAR', false, 100, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('ConcreteCategory', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteCategory', RelationMap::MANY_TO_ONE, array('category_id' => 'id', ), 'CASCADE', null);
        $this->addRelation('ConcreteArticle', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteArticle', RelationMap::ONE_TO_ONE, array('id' => 'id', ), 'CASCADE', null);
        $this->addRelation('ConcreteNews', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteNews', RelationMap::ONE_TO_ONE, array('id' => 'id', ), 'CASCADE', null);
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
            'concrete_inheritance_parent' => array('descendant_column' => 'descendant_class', ),
        );
    } // getBehaviors()
    /**
     * Method to invalidate the instance pool of all tables related to concrete_content     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        ConcreteArticleTableMap::clearInstancePool();
        ConcreteNewsTableMap::clearInstancePool();
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
        return $withPrefix ? ConcreteContentTableMap::CLASS_DEFAULT : ConcreteContentTableMap::OM_CLASS;
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
     * @return array           (ConcreteContent object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = ConcreteContentTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = ConcreteContentTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + ConcreteContentTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = ConcreteContentTableMap::OM_CLASS;
            /** @var ConcreteContent $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            ConcreteContentTableMap::addInstanceToPool($obj, $key);
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
            $key = ConcreteContentTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = ConcreteContentTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var ConcreteContent $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                ConcreteContentTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(ConcreteContentTableMap::COL_ID);
            $criteria->addSelectColumn(ConcreteContentTableMap::COL_TITLE);
            $criteria->addSelectColumn(ConcreteContentTableMap::COL_CATEGORY_ID);
            $criteria->addSelectColumn(ConcreteContentTableMap::COL_DESCENDANT_CLASS);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.TITLE');
            $criteria->addSelectColumn($alias . '.CATEGORY_ID');
            $criteria->addSelectColumn($alias . '.DESCENDANT_CLASS');
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
        return Propel::getServiceContainer()->getDatabaseMap(ConcreteContentTableMap::DATABASE_NAME)->getTable(ConcreteContentTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(ConcreteContentTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(ConcreteContentTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new ConcreteContentTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a ConcreteContent or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ConcreteContent object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteContentTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Behavior\ConcreteContent) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(ConcreteContentTableMap::DATABASE_NAME);
            $criteria->add(ConcreteContentTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = ConcreteContentQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            ConcreteContentTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                ConcreteContentTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the concrete_content table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return ConcreteContentQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a ConcreteContent or Criteria object.
     *
     * @param mixed               $criteria Criteria or ConcreteContent object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteContentTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from ConcreteContent object
        }

        if ($criteria->containsKey(ConcreteContentTableMap::COL_ID) && $criteria->keyContainsValue(ConcreteContentTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.ConcreteContentTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = ConcreteContentQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // ConcreteContentTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
ConcreteContentTableMap::buildTableMap();
