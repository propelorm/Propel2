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
use Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionQuery;


/**
 * This class defines the structure of the 'validate_trigger_fiction' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ValidateTriggerFictionTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Behavior.Map.ValidateTriggerFictionTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore-behavior';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'validate_trigger_fiction';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerFiction';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Behavior.ValidateTriggerFiction';

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
     * the column name for the FOO field
     */
    const COL_FOO = 'validate_trigger_fiction.FOO';

    /**
     * the column name for the ID field
     */
    const COL_ID = 'validate_trigger_fiction.ID';

    /**
     * the column name for the ISBN field
     */
    const COL_ISBN = 'validate_trigger_fiction.ISBN';

    /**
     * the column name for the PRICE field
     */
    const COL_PRICE = 'validate_trigger_fiction.PRICE';

    /**
     * the column name for the PUBLISHER_ID field
     */
    const COL_PUBLISHER_ID = 'validate_trigger_fiction.PUBLISHER_ID';

    /**
     * the column name for the AUTHOR_ID field
     */
    const COL_AUTHOR_ID = 'validate_trigger_fiction.AUTHOR_ID';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    // i18n behavior

    /**
     * The default locale to use for translations.
     *
     * @var string
     */
    const DEFAULT_LOCALE = 'en_US';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('Foo', 'Id', 'ISBN', 'Price', 'PublisherId', 'AuthorId', ),
        self::TYPE_STUDLYPHPNAME => array('foo', 'id', 'iSBN', 'price', 'publisherId', 'authorId', ),
        self::TYPE_COLNAME       => array(ValidateTriggerFictionTableMap::COL_FOO, ValidateTriggerFictionTableMap::COL_ID, ValidateTriggerFictionTableMap::COL_ISBN, ValidateTriggerFictionTableMap::COL_PRICE, ValidateTriggerFictionTableMap::COL_PUBLISHER_ID, ValidateTriggerFictionTableMap::COL_AUTHOR_ID, ),
        self::TYPE_RAW_COLNAME   => array('COL_FOO', 'COL_ID', 'COL_ISBN', 'COL_PRICE', 'COL_PUBLISHER_ID', 'COL_AUTHOR_ID', ),
        self::TYPE_FIELDNAME     => array('foo', 'id', 'isbn', 'price', 'publisher_id', 'author_id', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Foo' => 0, 'Id' => 1, 'ISBN' => 2, 'Price' => 3, 'PublisherId' => 4, 'AuthorId' => 5, ),
        self::TYPE_STUDLYPHPNAME => array('foo' => 0, 'id' => 1, 'iSBN' => 2, 'price' => 3, 'publisherId' => 4, 'authorId' => 5, ),
        self::TYPE_COLNAME       => array(ValidateTriggerFictionTableMap::COL_FOO => 0, ValidateTriggerFictionTableMap::COL_ID => 1, ValidateTriggerFictionTableMap::COL_ISBN => 2, ValidateTriggerFictionTableMap::COL_PRICE => 3, ValidateTriggerFictionTableMap::COL_PUBLISHER_ID => 4, ValidateTriggerFictionTableMap::COL_AUTHOR_ID => 5, ),
        self::TYPE_RAW_COLNAME   => array('COL_FOO' => 0, 'COL_ID' => 1, 'COL_ISBN' => 2, 'COL_PRICE' => 3, 'COL_PUBLISHER_ID' => 4, 'COL_AUTHOR_ID' => 5, ),
        self::TYPE_FIELDNAME     => array('foo' => 0, 'id' => 1, 'isbn' => 2, 'price' => 3, 'publisher_id' => 4, 'author_id' => 5, ),
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
        $this->setName('validate_trigger_fiction');
        $this->setPhpName('ValidateTriggerFiction');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerFiction');
        $this->setPackage('Propel.Tests.Bookstore.Behavior');
        $this->setUseIdGenerator(false);
        // columns
        $this->addColumn('FOO', 'Foo', 'VARCHAR', false, 100, null);
        $this->addForeignPrimaryKey('ID', 'Id', 'INTEGER' , 'validate_trigger_book', 'ID', true, null, null);
        $this->addColumn('ISBN', 'ISBN', 'VARCHAR', false, 24, null);
        $this->addColumn('PRICE', 'Price', 'FLOAT', false, null, null);
        $this->addColumn('PUBLISHER_ID', 'PublisherId', 'INTEGER', false, null, null);
        $this->addColumn('AUTHOR_ID', 'AuthorId', 'INTEGER', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('ValidateTriggerBook', '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerBook', RelationMap::MANY_TO_ONE, array('id' => 'id', ), 'CASCADE', null);
        $this->addRelation('ValidateTriggerFictionI18n', '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerFictionI18n', RelationMap::ONE_TO_MANY, array('id' => 'id', ), 'CASCADE', null, 'ValidateTriggerFictionI18ns');
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
            'concrete_inheritance' => array('extends' => 'validate_trigger_book', 'descendant_column' => 'descendant_class', 'copy_data_to_parent' => 'true', 'schema' => '', ),
            'validate' => array('rule2' => array ('column' => 'isbn','validator' => 'Regex','options' => array ('pattern' => '/[^\\d-]+/','match' => false,'message' => 'Please enter a valid ISBN',),), ),
            'i18n' => array('i18n_table' => '%TABLE%_i18n', 'i18n_phpname' => '%PHPNAME%I18n', 'i18n_columns' => 'title', 'locale_column' => 'locale', 'locale_length' => '5', 'default_locale' => '', 'locale_alias' => '', ),
        );
    } // getBehaviors()
    /**
     * Method to invalidate the instance pool of all tables related to validate_trigger_fiction     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        ValidateTriggerFictionI18nTableMap::clearInstancePool();
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
        return $withPrefix ? ValidateTriggerFictionTableMap::CLASS_DEFAULT : ValidateTriggerFictionTableMap::OM_CLASS;
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
     * @return array           (ValidateTriggerFiction object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = ValidateTriggerFictionTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = ValidateTriggerFictionTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + ValidateTriggerFictionTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = ValidateTriggerFictionTableMap::OM_CLASS;
            /** @var ValidateTriggerFiction $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            ValidateTriggerFictionTableMap::addInstanceToPool($obj, $key);
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
            $key = ValidateTriggerFictionTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = ValidateTriggerFictionTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var ValidateTriggerFiction $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                ValidateTriggerFictionTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(ValidateTriggerFictionTableMap::COL_FOO);
            $criteria->addSelectColumn(ValidateTriggerFictionTableMap::COL_ID);
            $criteria->addSelectColumn(ValidateTriggerFictionTableMap::COL_ISBN);
            $criteria->addSelectColumn(ValidateTriggerFictionTableMap::COL_PRICE);
            $criteria->addSelectColumn(ValidateTriggerFictionTableMap::COL_PUBLISHER_ID);
            $criteria->addSelectColumn(ValidateTriggerFictionTableMap::COL_AUTHOR_ID);
        } else {
            $criteria->addSelectColumn($alias . '.FOO');
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.ISBN');
            $criteria->addSelectColumn($alias . '.PRICE');
            $criteria->addSelectColumn($alias . '.PUBLISHER_ID');
            $criteria->addSelectColumn($alias . '.AUTHOR_ID');
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
        return Propel::getServiceContainer()->getDatabaseMap(ValidateTriggerFictionTableMap::DATABASE_NAME)->getTable(ValidateTriggerFictionTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(ValidateTriggerFictionTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(ValidateTriggerFictionTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new ValidateTriggerFictionTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a ValidateTriggerFiction or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ValidateTriggerFiction object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerFictionTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(ValidateTriggerFictionTableMap::DATABASE_NAME);
            $criteria->add(ValidateTriggerFictionTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = ValidateTriggerFictionQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            ValidateTriggerFictionTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                ValidateTriggerFictionTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the validate_trigger_fiction table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return ValidateTriggerFictionQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a ValidateTriggerFiction or Criteria object.
     *
     * @param mixed               $criteria Criteria or ValidateTriggerFiction object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerFictionTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from ValidateTriggerFiction object
        }


        // Set the correct dbName
        $query = ValidateTriggerFictionQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // ValidateTriggerFictionTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
ValidateTriggerFictionTableMap::buildTableMap();
