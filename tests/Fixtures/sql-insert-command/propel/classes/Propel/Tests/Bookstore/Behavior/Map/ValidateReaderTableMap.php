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
use Propel\Tests\Bookstore\Behavior\ValidateReader;
use Propel\Tests\Bookstore\Behavior\ValidateReaderQuery;


/**
 * This class defines the structure of the 'validate_reader' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ValidateReaderTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Behavior.Map.ValidateReaderTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore-behavior';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'validate_reader';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateReader';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Behavior.ValidateReader';

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
    const COL_ID = 'validate_reader.ID';

    /**
     * the column name for the FIRST_NAME field
     */
    const COL_FIRST_NAME = 'validate_reader.FIRST_NAME';

    /**
     * the column name for the LAST_NAME field
     */
    const COL_LAST_NAME = 'validate_reader.LAST_NAME';

    /**
     * the column name for the EMAIL field
     */
    const COL_EMAIL = 'validate_reader.EMAIL';

    /**
     * the column name for the BIRTHDAY field
     */
    const COL_BIRTHDAY = 'validate_reader.BIRTHDAY';

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
        self::TYPE_PHPNAME       => array('Id', 'FirstName', 'LastName', 'Email', 'Birthday', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'firstName', 'lastName', 'email', 'birthday', ),
        self::TYPE_COLNAME       => array(ValidateReaderTableMap::COL_ID, ValidateReaderTableMap::COL_FIRST_NAME, ValidateReaderTableMap::COL_LAST_NAME, ValidateReaderTableMap::COL_EMAIL, ValidateReaderTableMap::COL_BIRTHDAY, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID', 'COL_FIRST_NAME', 'COL_LAST_NAME', 'COL_EMAIL', 'COL_BIRTHDAY', ),
        self::TYPE_FIELDNAME     => array('id', 'first_name', 'last_name', 'email', 'birthday', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'FirstName' => 1, 'LastName' => 2, 'Email' => 3, 'Birthday' => 4, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'firstName' => 1, 'lastName' => 2, 'email' => 3, 'birthday' => 4, ),
        self::TYPE_COLNAME       => array(ValidateReaderTableMap::COL_ID => 0, ValidateReaderTableMap::COL_FIRST_NAME => 1, ValidateReaderTableMap::COL_LAST_NAME => 2, ValidateReaderTableMap::COL_EMAIL => 3, ValidateReaderTableMap::COL_BIRTHDAY => 4, ),
        self::TYPE_RAW_COLNAME   => array('COL_ID' => 0, 'COL_FIRST_NAME' => 1, 'COL_LAST_NAME' => 2, 'COL_EMAIL' => 3, 'COL_BIRTHDAY' => 4, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'first_name' => 1, 'last_name' => 2, 'email' => 3, 'birthday' => 4, ),
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
        $this->setName('validate_reader');
        $this->setPhpName('ValidateReader');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Behavior\\ValidateReader');
        $this->setPackage('Propel.Tests.Bookstore.Behavior');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('FIRST_NAME', 'FirstName', 'VARCHAR', true, 128, null);
        $this->addColumn('LAST_NAME', 'LastName', 'VARCHAR', true, 128, null);
        $this->addColumn('EMAIL', 'Email', 'VARCHAR', false, 128, null);
        $this->addColumn('BIRTHDAY', 'Birthday', 'DATE', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('ValidateReaderBook', '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateReaderBook', RelationMap::ONE_TO_MANY, array('id' => 'reader_id', ), null, null, 'ValidateReaderBooks');
        $this->addRelation('ValidateBook', '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateBook', RelationMap::MANY_TO_MANY, array(), null, null, 'ValidateBooks');
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
            'validate' => array('rule1' => array ('column' => 'first_name','validator' => 'NotNull',), 'rule2' => array ('column' => 'first_name','validator' => 'Length','options' => array ('min' => 4,),), 'rule3' => array ('column' => 'last_name','validator' => 'NotNull',), 'rule4' => array ('column' => 'last_name','validator' => 'Length','options' => array ('max' => 128,),), 'rule5' => array ('column' => 'email','validator' => 'Email',), 'rule6' => array ('column' => 'birthday','validator' => 'Date',), ),
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
        return $withPrefix ? ValidateReaderTableMap::CLASS_DEFAULT : ValidateReaderTableMap::OM_CLASS;
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
     * @return array           (ValidateReader object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = ValidateReaderTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = ValidateReaderTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + ValidateReaderTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = ValidateReaderTableMap::OM_CLASS;
            /** @var ValidateReader $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            ValidateReaderTableMap::addInstanceToPool($obj, $key);
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
            $key = ValidateReaderTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = ValidateReaderTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var ValidateReader $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                ValidateReaderTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(ValidateReaderTableMap::COL_ID);
            $criteria->addSelectColumn(ValidateReaderTableMap::COL_FIRST_NAME);
            $criteria->addSelectColumn(ValidateReaderTableMap::COL_LAST_NAME);
            $criteria->addSelectColumn(ValidateReaderTableMap::COL_EMAIL);
            $criteria->addSelectColumn(ValidateReaderTableMap::COL_BIRTHDAY);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.FIRST_NAME');
            $criteria->addSelectColumn($alias . '.LAST_NAME');
            $criteria->addSelectColumn($alias . '.EMAIL');
            $criteria->addSelectColumn($alias . '.BIRTHDAY');
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
        return Propel::getServiceContainer()->getDatabaseMap(ValidateReaderTableMap::DATABASE_NAME)->getTable(ValidateReaderTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(ValidateReaderTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(ValidateReaderTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new ValidateReaderTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a ValidateReader or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ValidateReader object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateReaderTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Behavior\ValidateReader) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(ValidateReaderTableMap::DATABASE_NAME);
            $criteria->add(ValidateReaderTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = ValidateReaderQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            ValidateReaderTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                ValidateReaderTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the validate_reader table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return ValidateReaderQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a ValidateReader or Criteria object.
     *
     * @param mixed               $criteria Criteria or ValidateReader object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateReaderTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from ValidateReader object
        }

        if ($criteria->containsKey(ValidateReaderTableMap::COL_ID) && $criteria->keyContainsValue(ValidateReaderTableMap::COL_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.ValidateReaderTableMap::COL_ID.')');
        }


        // Set the correct dbName
        $query = ValidateReaderQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // ValidateReaderTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
ValidateReaderTableMap::buildTableMap();
