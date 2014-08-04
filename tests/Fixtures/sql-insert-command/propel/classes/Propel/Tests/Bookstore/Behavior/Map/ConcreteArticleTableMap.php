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
use Propel\Tests\Bookstore\Behavior\ConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery;


/**
 * This class defines the structure of the 'concrete_article' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ConcreteArticleTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Propel.Tests.Bookstore.Behavior.Map.ConcreteArticleTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'bookstore-behavior';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'concrete_article';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteArticle';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Propel.Tests.Bookstore.Behavior.ConcreteArticle';

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
     * the column name for the BODY field
     */
    const COL_BODY = 'concrete_article.BODY';

    /**
     * the column name for the AUTHOR_ID field
     */
    const COL_AUTHOR_ID = 'concrete_article.AUTHOR_ID';

    /**
     * the column name for the ID field
     */
    const COL_ID = 'concrete_article.ID';

    /**
     * the column name for the TITLE field
     */
    const COL_TITLE = 'concrete_article.TITLE';

    /**
     * the column name for the CATEGORY_ID field
     */
    const COL_CATEGORY_ID = 'concrete_article.CATEGORY_ID';

    /**
     * the column name for the DESCENDANT_CLASS field
     */
    const COL_DESCENDANT_CLASS = 'concrete_article.DESCENDANT_CLASS';

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
        self::TYPE_PHPNAME       => array('Body', 'AuthorId', 'Id', 'Title', 'CategoryId', 'DescendantClass', ),
        self::TYPE_STUDLYPHPNAME => array('body', 'authorId', 'id', 'title', 'categoryId', 'descendantClass', ),
        self::TYPE_COLNAME       => array(ConcreteArticleTableMap::COL_BODY, ConcreteArticleTableMap::COL_AUTHOR_ID, ConcreteArticleTableMap::COL_ID, ConcreteArticleTableMap::COL_TITLE, ConcreteArticleTableMap::COL_CATEGORY_ID, ConcreteArticleTableMap::COL_DESCENDANT_CLASS, ),
        self::TYPE_RAW_COLNAME   => array('COL_BODY', 'COL_AUTHOR_ID', 'COL_ID', 'COL_TITLE', 'COL_CATEGORY_ID', 'COL_DESCENDANT_CLASS', ),
        self::TYPE_FIELDNAME     => array('body', 'author_id', 'id', 'title', 'category_id', 'descendant_class', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Body' => 0, 'AuthorId' => 1, 'Id' => 2, 'Title' => 3, 'CategoryId' => 4, 'DescendantClass' => 5, ),
        self::TYPE_STUDLYPHPNAME => array('body' => 0, 'authorId' => 1, 'id' => 2, 'title' => 3, 'categoryId' => 4, 'descendantClass' => 5, ),
        self::TYPE_COLNAME       => array(ConcreteArticleTableMap::COL_BODY => 0, ConcreteArticleTableMap::COL_AUTHOR_ID => 1, ConcreteArticleTableMap::COL_ID => 2, ConcreteArticleTableMap::COL_TITLE => 3, ConcreteArticleTableMap::COL_CATEGORY_ID => 4, ConcreteArticleTableMap::COL_DESCENDANT_CLASS => 5, ),
        self::TYPE_RAW_COLNAME   => array('COL_BODY' => 0, 'COL_AUTHOR_ID' => 1, 'COL_ID' => 2, 'COL_TITLE' => 3, 'COL_CATEGORY_ID' => 4, 'COL_DESCENDANT_CLASS' => 5, ),
        self::TYPE_FIELDNAME     => array('body' => 0, 'author_id' => 1, 'id' => 2, 'title' => 3, 'category_id' => 4, 'descendant_class' => 5, ),
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
        $this->setName('concrete_article');
        $this->setPhpName('ConcreteArticle');
        $this->setClassName('\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteArticle');
        $this->setPackage('Propel.Tests.Bookstore.Behavior');
        $this->setUseIdGenerator(false);
        // columns
        $this->addColumn('BODY', 'Body', 'LONGVARCHAR', false, null, null);
        $this->addForeignKey('AUTHOR_ID', 'AuthorId', 'INTEGER', 'concrete_author', 'ID', false, null, null);
        $this->addForeignPrimaryKey('ID', 'Id', 'INTEGER' , 'concrete_content', 'ID', true, null, null);
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
        $this->addRelation('ConcreteAuthor', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteAuthor', RelationMap::MANY_TO_ONE, array('author_id' => 'id', ), 'CASCADE', null);
        $this->addRelation('ConcreteContent', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteContent', RelationMap::MANY_TO_ONE, array('id' => 'id', ), 'CASCADE', null);
        $this->addRelation('ConcreteCategory', '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteCategory', RelationMap::MANY_TO_ONE, array('category_id' => 'id', ), 'CASCADE', null);
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
            'concrete_inheritance' => array('extends' => 'concrete_content', 'descendant_column' => 'descendant_class', 'copy_data_to_parent' => 'true', 'schema' => '', ),
            'concrete_inheritance_parent' => array('descendant_column' => 'descendant_class', ),
        );
    } // getBehaviors()
    /**
     * Method to invalidate the instance pool of all tables related to concrete_article     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
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
                ? 2 + $offset
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
        return $withPrefix ? ConcreteArticleTableMap::CLASS_DEFAULT : ConcreteArticleTableMap::OM_CLASS;
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
     * @return array           (ConcreteArticle object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = ConcreteArticleTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = ConcreteArticleTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + ConcreteArticleTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = ConcreteArticleTableMap::OM_CLASS;
            /** @var ConcreteArticle $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            ConcreteArticleTableMap::addInstanceToPool($obj, $key);
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
            $key = ConcreteArticleTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = ConcreteArticleTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var ConcreteArticle $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                ConcreteArticleTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(ConcreteArticleTableMap::COL_BODY);
            $criteria->addSelectColumn(ConcreteArticleTableMap::COL_AUTHOR_ID);
            $criteria->addSelectColumn(ConcreteArticleTableMap::COL_ID);
            $criteria->addSelectColumn(ConcreteArticleTableMap::COL_TITLE);
            $criteria->addSelectColumn(ConcreteArticleTableMap::COL_CATEGORY_ID);
            $criteria->addSelectColumn(ConcreteArticleTableMap::COL_DESCENDANT_CLASS);
        } else {
            $criteria->addSelectColumn($alias . '.BODY');
            $criteria->addSelectColumn($alias . '.AUTHOR_ID');
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
        return Propel::getServiceContainer()->getDatabaseMap(ConcreteArticleTableMap::DATABASE_NAME)->getTable(ConcreteArticleTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(ConcreteArticleTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(ConcreteArticleTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new ConcreteArticleTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a ConcreteArticle or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ConcreteArticle object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteArticleTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Propel\Tests\Bookstore\Behavior\ConcreteArticle) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(ConcreteArticleTableMap::DATABASE_NAME);
            $criteria->add(ConcreteArticleTableMap::COL_ID, (array) $values, Criteria::IN);
        }

        $query = ConcreteArticleQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            ConcreteArticleTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                ConcreteArticleTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the concrete_article table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return ConcreteArticleQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a ConcreteArticle or Criteria object.
     *
     * @param mixed               $criteria Criteria or ConcreteArticle object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteArticleTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from ConcreteArticle object
        }


        // Set the correct dbName
        $query = ConcreteArticleQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // ConcreteArticleTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
ConcreteArticleTableMap::buildTableMap();
