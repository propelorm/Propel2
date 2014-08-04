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
use Propel\Tests\Bookstore\Essay;
use Propel\Tests\Bookstore\EssayQuery;


/**
 * This class defines the structure of the 'essay' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class EssayTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Map.EssayTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'essay';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Essay';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Essay';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 6;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 6;

    /**
     * the column name for the ID field
     */
    const COL_ID = 'essay.ID';

    /**
     * the column name for the TITLE field
     */
    const COL_TITLE = 'essay.TITLE';

    /**
     * the column name for the FIRST_AUTHOR field
     */
    const COL_FIRST_AUTHOR = 'essay.FIRST_AUTHOR';

    /**
     * the column name for the SECOND_AUTHOR field
     */
    const COL_SECOND_AUTHOR = 'essay.SECOND_AUTHOR';

    /**
     * the column name for the SUBTITLE field
     */
    const COL_SUBTITLE = 'essay.SUBTITLE';

    /**
     * the column name for the NEXT_ESSAY_ID field
     */
    const COL_NEXT_ESSAY_ID = 'essay.NEXT_ESSAY_ID';

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
        self::TYPE_PHPNAME       => array('Id', 'Title', 'FirstAuthor', 'SecondAuthor', 'SecondTitle', 'NextEssayId', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'title', 'firstAuthor', 'secondAuthor', 'secondTitle', 'nextEssayId', ),
        self::TYPE_COLNAME       => array(EssayTableMap::COL_ID, EssayTableMap::COL_TITLE, EssayTableMap::COL_FIRST_AUTHOR, EssayTableMap::COL_SECOND_AUTHOR, EssayTableMap::COL_SUBTITLE, EssayTableMap::COL_NEXT_ESSAY_ID, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_TITLE', 'COL_FIRST_AUTHOR', 'COL_SECOND_AUTHOR', 'COL_SUBTITLE', 'COL_NEXT_ESSAY_ID', ),
        self::TYPE_FIELDNAME     => array('id', 'title', 'first_author', 'second_author', 'subtitle', 'next_essay_id', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'Title' => 1, 'FirstAuthor' => 2, 'SecondAuthor' => 3, 'SecondTitle' => 4, 'NextEssayId' => 5, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'title' => 1, 'firstAuthor' => 2, 'secondAuthor' => 3, 'secondTitle' => 4, 'nextEssayId' => 5, ),
        self::TYPE_COLNAME       => array(EssayTableMap::COL_ID => 0, EssayTableMap::COL_TITLE => 1, EssayTableMap::COL_FIRST_AUTHOR => 2, EssayTableMap::COL_SECOND_AUTHOR => 3, EssayTableMap::COL_SUBTITLE => 4, EssayTableMap::COL_NEXT_ESSAY_ID => 5, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_TITLE' => 1, 'COL_FIRST_AUTHOR' => 2, 'COL_SECOND_AUTHOR' => 3, 'COL_SUBTITLE' => 4, 'COL_NEXT_ESSAY_ID' => 5, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'title' => 1, 'first_author' => 2, 'second_author' => 3, 'subtitle' => 4, 'next_essay_id' => 5, ),
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
        $this->setName('essay');
        $this->setPhpName('Essay');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Essay');
        $this->setPackage('Propel.Tests.Bookstore');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('TITLE', 'Title', 'VARCHAR', true, 255, null);
        $this->getColumn('TITLE', false)->setPrimaryString(true);
        $this->addForeignKey('FIRST_AUTHOR', 'FirstAuthor', 'INTEGER', 'author', 'ID', false, null, null);
        $this->addForeignKey('SECOND_AUTHOR', 'SecondAuthor', 'INTEGER', 'author', 'ID', false, null, null);
        $this->addColumn('SUBTITLE', 'SecondTitle', 'VARCHAR', false, 255, null);
        $this->addForeignKey('NEXT_ESSAY_ID', 'NextEssayId', 'INTEGER', 'essay', 'ID', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('AuthorRelatedByFirstAuthor', '\\Propel\\Tests\\Bookstore\\Author', RelationMap::MANY_TO_ONE, array('first_author' => 'id', ), 'SET NULL', 'CASCADE');
        $this->addRelation('AuthorRelatedBySecondAuthor', '\\Propel\\Tests\\Bookstore\\Author', RelationMap::MANY_TO_ONE, array('second_author' => 'id', ), 'SET NULL', 'CASCADE');
        $this->addRelation('EssayRelatedByNextEssayId', '\\Propel\\Tests\\Bookstore\\Essay', RelationMap::MANY_TO_ONE, array('next_essay_id' => 'id', ), 'SET NULL', 'CASCADE');
        $this->addRelation('EssayRelatedById', '\\Propel\\Tests\\Bookstore\\Essay', RelationMap::ONE_TO_MANY, array('id' => 'next_essay_id', ), 'SET NULL', 'CASCADE', 'EssaysRelatedById');
    } // buildRelations()
    /**
     * Method to invalidate the instance pool of all tables related to essay     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        EssayTableMap::clearInstancePool();
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
        return $withPrefix ? EssayTableMap::CLASS_DEFAULT : EssayTableMap::OM_CLASS;
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
     * @return array           (Essay object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = EssayTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = EssayTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + EssayTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = EssayTableMap::OM_CLASS;
            /** @var Essay $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            EssayTableMap::addInstanceToPool($obj, $key);
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
            $key = EssayTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = EssayTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var Essay $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                EssayTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(EssayTableMap::COL_ID);
            $criteria->addSelectColumn(EssayTableMap::COL_TITLE);
            $criteria->addSelectColumn(EssayTableMap::COL_FIRST_AUTHOR);
            $criteria->addSelectColumn(EssayTableMap::COL_SECOND_AUTHOR);
            $criteria->addSelectColumn(EssayTableMap::COL_SUBTITLE);
            $criteria->addSelectColumn(EssayTableMap::COL_NEXT_ESSAY_ID);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.TITLE');
            $criteria->addSelectColumn($alias . '.FIRST_AUTHOR');
            $criteria->addSelectColumn($alias . '.SECOND_AUTHOR');
            $criteria->addSelectColumn($alias . '.SUBTITLE');
            $criteria->addSelectColumn($alias . '.NEXT_ESSAY_ID');
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
        return Propel::getServiceContainer()->getDatabaseMap(EssayTableMap::DATABASE_NAME)->getTable(EssayTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(EssayTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(EssayTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new EssayTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a Essay or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or Essay object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(EssayTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Essay) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(EssayTableMap::DATABASE_NAME);
            $criteria->add(EssayTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = EssayQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            EssayTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                EssayTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the essay table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return EssayQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a Essay or Criteria object.
     *
     * @param mixed               $criteria Criteria or Essay object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(EssayTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from Essay object
        }

        if ($criteria->containsKey(EssayTableMap::COL_ID) && $criteria->keyContainsValue(EssayTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.EssayTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = EssayQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // EssayTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
EssayTableMap::buildTableMap();
