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
use Propel\Tests\Bookstore\Behavior\ConcreteQuizz;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery;


/**
 * This class defines the structure of the 'concrete_quizz' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ConcreteQuizzTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Behavior.Map.ConcreteQuizzTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore-behavior';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'concrete_quizz';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteQuizz';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Behavior.ConcreteQuizz';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 3;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 3;

    /**
     * the column name for the TITLE field
     */
    const COL_TITLE = 'concrete_quizz.TITLE';

    /**
     * the column name for the ID field
     */
    const COL_ID = 'concrete_quizz.ID';

    /**
     * the column name for the CATEGORY_ID field
     */
    const COL_CATEGORY_ID = 'concrete_quizz.CATEGORY_ID';

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
        self::TYPE_PHPNAME       => array('Title', 'Id', 'CategoryId', ),
        self::TYPE_STUDLYPHPNAME => array('title', 'id', 'categoryId', ),
        self::TYPE_COLNAME       => array(ConcreteQuizzTableMap::COL_TITLE, ConcreteQuizzTableMap::COL_ID, ConcreteQuizzTableMap::COL_CATEGORY_ID, ),
        self::TYPE_RAW_COLNAME   => array('COL_TITLE', 'COL_ID', 'COL_CATEGORY_ID', ),
        self::TYPE_FIELDNAME     => array('title', 'id', 'category_id', ),
        self::TYPE_NUM           => array(0, 1, 2, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Title' => 0, 'Id' => 1, 'CategoryId' => 2, ),
        self::TYPE_STUDLYPHPNAME => array('title' => 0, 'id' => 1, 'categoryId' => 2, ),
        self::TYPE_COLNAME       => array(ConcreteQuizzTableMap::COL_TITLE => 0, ConcreteQuizzTableMap::COL_ID => 1, ConcreteQuizzTableMap::COL_CATEGORY_ID => 2, ),
        self::TYPE_RAW_COLNAME   => array('COL_TITLE' => 0, 'COL_ID' => 1, 'COL_CATEGORY_ID' => 2, ),
        self::TYPE_FIELDNAME     => array('title' => 0, 'id' => 1, 'category_id' => 2, ),
        self::TYPE_NUM           => array(0, 1, 2, )
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
        $this->setName('concrete_quizz');
        $this->setPhpName('ConcreteQuizz');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteQuizz');
        $this->setPackage('Propel.Tests.Bookstore.Behavior');
        $this->setUseIdGenerator(true);
        // columns
        $this->addColumn('TITLE', 'Title', 'VARCHAR', false, 200, null);
        $this->getColumn('TITLE', false)->setPrimaryString(true);
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addForeignKey('CATEGORY_ID', 'CategoryId', 'INTEGER', 'concrete_category', 'ID', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('ConcreteCategory', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteCategory', RelationMap::MANY_TO_ONE, array('category_id' => 'id', ), 'CASCADE', null);
        $this->addRelation('ConcreteQuizzQuestion', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteQuizzQuestion', RelationMap::ONE_TO_MANY, array('id' => 'quizz_id', ), 'CASCADE', null, 'ConcreteQuizzQuestions');
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
            'concrete_inheritance' => array('extends' => 'concrete_content', 'descendant_column' => 'descendant_class', 'copy_data_to_parent' => 'false', 'schema' => '', ),
        );
    } // getBehaviors()
    /**
     * Method to invalidate the instance pool of all tables related to concrete_quizz     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        ConcreteQuizzQuestionTableMap::clearInstancePool();
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
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
                ? 1 + $offset
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
        return $withPrefix ? ConcreteQuizzTableMap::CLASS_DEFAULT : ConcreteQuizzTableMap::OM_CLASS;
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
     * @return array           (ConcreteQuizz object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = ConcreteQuizzTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = ConcreteQuizzTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + ConcreteQuizzTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = ConcreteQuizzTableMap::OM_CLASS;
            /** @var ConcreteQuizz $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            ConcreteQuizzTableMap::addInstanceToPool($obj, $key);
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
            $key = ConcreteQuizzTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = ConcreteQuizzTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var ConcreteQuizz $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                ConcreteQuizzTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(ConcreteQuizzTableMap::COL_TITLE);
            $criteria->addSelectColumn(ConcreteQuizzTableMap::COL_ID);
            $criteria->addSelectColumn(ConcreteQuizzTableMap::COL_CATEGORY_ID);
        } else {
            $criteria->addSelectColumn($alias . '.TITLE');
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.CATEGORY_ID');
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
        return Propel::getServiceContainer()->getDatabaseMap(ConcreteQuizzTableMap::DATABASE_NAME)->getTable(ConcreteQuizzTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(ConcreteQuizzTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(ConcreteQuizzTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new ConcreteQuizzTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a ConcreteQuizz or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ConcreteQuizz object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteQuizzTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Behavior\ConcreteQuizz) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(ConcreteQuizzTableMap::DATABASE_NAME);
            $criteria->add(ConcreteQuizzTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = ConcreteQuizzQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            ConcreteQuizzTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                ConcreteQuizzTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the concrete_quizz table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return ConcreteQuizzQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a ConcreteQuizz or Criteria object.
     *
     * @param mixed               $criteria Criteria or ConcreteQuizz object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteQuizzTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from ConcreteQuizz object
        }

        if ($criteria->containsKey(ConcreteQuizzTableMap::COL_ID) && $criteria->keyContainsValue(ConcreteQuizzTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.ConcreteQuizzTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = ConcreteQuizzQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // ConcreteQuizzTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
ConcreteQuizzTableMap::buildTableMap();
