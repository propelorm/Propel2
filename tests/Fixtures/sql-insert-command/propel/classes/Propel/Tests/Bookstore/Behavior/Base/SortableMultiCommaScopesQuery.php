<?php

namespace Propel\Tests\Bookstore\Behavior\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\SortableMultiCommaScopes as ChildSortableMultiCommaScopes;
use Propel\Tests\Bookstore\Behavior\SortableMultiCommaScopesQuery as ChildSortableMultiCommaScopesQuery;
use Propel\Tests\Bookstore\Behavior\Map\SortableMultiCommaScopesTableMap;

/**
 * Base class that represents a query for the 'sortable_multi_comma_scopes' table.
 *
 *
 *
 * @method     ChildSortableMultiCommaScopesQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildSortableMultiCommaScopesQuery orderByCategoryId($order = Criteria::ASC) Order by the category_id column
 * @method     ChildSortableMultiCommaScopesQuery orderBySubCategoryId($order = Criteria::ASC) Order by the sub_category_id column
 * @method     ChildSortableMultiCommaScopesQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildSortableMultiCommaScopesQuery orderByPosition($order = Criteria::ASC) Order by the position column
 *
 * @method     ChildSortableMultiCommaScopesQuery groupById() Group by the id column
 * @method     ChildSortableMultiCommaScopesQuery groupByCategoryId() Group by the category_id column
 * @method     ChildSortableMultiCommaScopesQuery groupBySubCategoryId() Group by the sub_category_id column
 * @method     ChildSortableMultiCommaScopesQuery groupByTitle() Group by the title column
 * @method     ChildSortableMultiCommaScopesQuery groupByPosition() Group by the position column
 *
 * @method     ChildSortableMultiCommaScopesQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildSortableMultiCommaScopesQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildSortableMultiCommaScopesQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildSortableMultiCommaScopes findOne(ConnectionInterface $con = null) Return the first ChildSortableMultiCommaScopes matching the query
 * @method     ChildSortableMultiCommaScopes findOneOrCreate(ConnectionInterface $con = null) Return the first ChildSortableMultiCommaScopes matching the query, or a new ChildSortableMultiCommaScopes object populated from the query conditions when no match is found
 *
 * @method     ChildSortableMultiCommaScopes findOneById(int $id) Return the first ChildSortableMultiCommaScopes filtered by the id column
 * @method     ChildSortableMultiCommaScopes findOneByCategoryId(int $category_id) Return the first ChildSortableMultiCommaScopes filtered by the category_id column
 * @method     ChildSortableMultiCommaScopes findOneBySubCategoryId(int $sub_category_id) Return the first ChildSortableMultiCommaScopes filtered by the sub_category_id column
 * @method     ChildSortableMultiCommaScopes findOneByTitle(string $title) Return the first ChildSortableMultiCommaScopes filtered by the title column
 * @method     ChildSortableMultiCommaScopes findOneByPosition(int $position) Return the first ChildSortableMultiCommaScopes filtered by the position column
 *
 * @method     ChildSortableMultiCommaScopes[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildSortableMultiCommaScopes objects based on current ModelCriteria
 * @method     ChildSortableMultiCommaScopes[]|ObjectCollection findById(int $id) Return ChildSortableMultiCommaScopes objects filtered by the id column
 * @method     ChildSortableMultiCommaScopes[]|ObjectCollection findByCategoryId(int $category_id) Return ChildSortableMultiCommaScopes objects filtered by the category_id column
 * @method     ChildSortableMultiCommaScopes[]|ObjectCollection findBySubCategoryId(int $sub_category_id) Return ChildSortableMultiCommaScopes objects filtered by the sub_category_id column
 * @method     ChildSortableMultiCommaScopes[]|ObjectCollection findByTitle(string $title) Return ChildSortableMultiCommaScopes objects filtered by the title column
 * @method     ChildSortableMultiCommaScopes[]|ObjectCollection findByPosition(int $position) Return ChildSortableMultiCommaScopes objects filtered by the position column
 * @method     ChildSortableMultiCommaScopes[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class SortableMultiCommaScopesQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\SortableMultiCommaScopesQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\SortableMultiCommaScopes', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildSortableMultiCommaScopesQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildSortableMultiCommaScopesQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildSortableMultiCommaScopesQuery) {
            return $criteria;
        }
        $query = new ChildSortableMultiCommaScopesQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildSortableMultiCommaScopes|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = SortableMultiCommaScopesTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildSortableMultiCommaScopes A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, CATEGORY_ID, SUB_CATEGORY_ID, TITLE, POSITION FROM sortable_multi_comma_scopes WHERE ID = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildSortableMultiCommaScopes $obj */
            $obj = new ChildSortableMultiCommaScopes();
            $obj->hydrate($row);
            SortableMultiCommaScopesTableMap::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildSortableMultiCommaScopes|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, ConnectionInterface $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the category_id column
     *
     * Example usage:
     * <code>
     * $query->filterByCategoryId(1234); // WHERE category_id = 1234
     * $query->filterByCategoryId(array(12, 34)); // WHERE category_id IN (12, 34)
     * $query->filterByCategoryId(array('min' => 12)); // WHERE category_id > 12
     * </code>
     *
     * @param     mixed $categoryId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterByCategoryId($categoryId = null, $comparison = null)
    {
        if (is_array($categoryId)) {
            $useMinMax = false;
            if (isset($categoryId['min'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_CATEGORY_ID, $categoryId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($categoryId['max'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_CATEGORY_ID, $categoryId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_CATEGORY_ID, $categoryId, $comparison);
    }

    /**
     * Filter the query on the sub_category_id column
     *
     * Example usage:
     * <code>
     * $query->filterBySubCategoryId(1234); // WHERE sub_category_id = 1234
     * $query->filterBySubCategoryId(array(12, 34)); // WHERE sub_category_id IN (12, 34)
     * $query->filterBySubCategoryId(array('min' => 12)); // WHERE sub_category_id > 12
     * </code>
     *
     * @param     mixed $subCategoryId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterBySubCategoryId($subCategoryId = null, $comparison = null)
    {
        if (is_array($subCategoryId)) {
            $useMinMax = false;
            if (isset($subCategoryId['min'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID, $subCategoryId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($subCategoryId['max'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID, $subCategoryId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID, $subCategoryId, $comparison);
    }

    /**
     * Filter the query on the title column
     *
     * Example usage:
     * <code>
     * $query->filterByTitle('fooValue');   // WHERE title = 'fooValue'
     * $query->filterByTitle('%fooValue%'); // WHERE title LIKE '%fooValue%'
     * </code>
     *
     * @param     string $title The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterByTitle($title = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($title)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $title)) {
                $title = str_replace('*', '%', $title);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the position column
     *
     * Example usage:
     * <code>
     * $query->filterByPosition(1234); // WHERE position = 1234
     * $query->filterByPosition(array(12, 34)); // WHERE position IN (12, 34)
     * $query->filterByPosition(array('min' => 12)); // WHERE position > 12
     * </code>
     *
     * @param     mixed $position The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterByPosition($position = null, $comparison = null)
    {
        if (is_array($position)) {
            $useMinMax = false;
            if (isset($position['min'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_POSITION, $position['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($position['max'])) {
                $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_POSITION, $position['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_POSITION, $position, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildSortableMultiCommaScopes $sortableMultiCommaScopes Object to remove from the list of results
     *
     * @return $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function prune($sortableMultiCommaScopes = null)
    {
        if ($sortableMultiCommaScopes) {
            $this->addUsingAlias(SortableMultiCommaScopesTableMap::COL_ID, $sortableMultiCommaScopes->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the sortable_multi_comma_scopes table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            SortableMultiCommaScopesTableMap::clearInstancePool();
            SortableMultiCommaScopesTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    /**
     * Performs a DELETE on the database based on the current ModelCriteria
     *
     * @param ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public function delete(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(SortableMultiCommaScopesTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            SortableMultiCommaScopesTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            SortableMultiCommaScopesTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // sortable behavior

    /**
     * Returns the objects in a certain list, from the list scope
     *
     * @param     int $scopeCategoryId Scope value for column `CategoryId`
     * @param     int $scopeSubCategoryId Scope value for column `SubCategoryId`
     *
     * @return    $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function inList($scopeCategoryId, $scopeSubCategoryId = null)
    {

        $scope[] = $scopeCategoryId;
        $scope[] = $scopeSubCategoryId;


        static::sortableApplyScopeCriteria($this, $scope, 'addUsingAlias');

        return $this;
    }

    /**
     * Filter the query based on a rank in the list
     *
     * @param     integer   $rank rank
     * @param     int $scopeCategoryId Scope value for column `CategoryId`
     * @param     int $scopeSubCategoryId Scope value for column `SubCategoryId`

     *
     * @return    ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function filterByRank($rank, $scopeCategoryId, $scopeSubCategoryId = null)
    {

        return $this
            ->inList($scopeCategoryId, $scopeSubCategoryId)
            ->addUsingAlias(SortableMultiCommaScopesTableMap::RANK_COL, $rank, Criteria::EQUAL);
    }

    /**
     * Order the query based on the rank in the list.
     * Using the default $order, returns the item with the lowest rank first
     *
     * @param     string $order either Criteria::ASC (default) or Criteria::DESC
     *
     * @return    $this|ChildSortableMultiCommaScopesQuery The current query, for fluid interface
     */
    public function orderByRank($order = Criteria::ASC)
    {
        $order = strtoupper($order);
        switch ($order) {
            case Criteria::ASC:
                return $this->addAscendingOrderByColumn($this->getAliasedColName(SortableMultiCommaScopesTableMap::RANK_COL));
                break;
            case Criteria::DESC:
                return $this->addDescendingOrderByColumn($this->getAliasedColName(SortableMultiCommaScopesTableMap::RANK_COL));
                break;
            default:
                throw new \Propel\Runtime\Exception\PropelException('ChildSortableMultiCommaScopesQuery::orderBy() only accepts "asc" or "desc" as argument');
        }
    }

    /**
     * Get an item from the list based on its rank
     *
     * @param     integer   $rank rank
     * @param     int $scopeCategoryId Scope value for column `CategoryId`
     * @param     int $scopeSubCategoryId Scope value for column `SubCategoryId`
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildSortableMultiCommaScopes
     */
    public function findOneByRank($rank, $scopeCategoryId, $scopeSubCategoryId = null, ConnectionInterface $con = null)
    {

        return $this
            ->filterByRank($rank, $scopeCategoryId, $scopeSubCategoryId)
            ->findOne($con);
    }

    /**
     * Returns a list of objects
     *
     * @param     int $scopeCategoryId Scope value for column `CategoryId`
     * @param     int $scopeSubCategoryId Scope value for column `SubCategoryId`

     * @param      ConnectionInterface $con    Connection to use.
     *
     * @return     mixed the list of results, formatted by the current formatter
     */
    public function findList($scopeCategoryId, $scopeSubCategoryId = null, $con = null)
    {

        return $this
            ->inList($scopeCategoryId, $scopeSubCategoryId)
            ->orderByRank()
            ->find($con);
    }

    /**
     * Get the highest rank
     *
     * @param     int $scopeCategoryId Scope value for column `CategoryId`
     * @param     int $scopeSubCategoryId Scope value for column `SubCategoryId`
     * @param     ConnectionInterface optional connection
     *
     * @return    integer highest position
     */
    public function getMaxRank($scopeCategoryId, $scopeSubCategoryId = null, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }
        // shift the objects with a position lower than the one of object
        $this->addSelectColumn('MAX(' . SortableMultiCommaScopesTableMap::RANK_COL . ')');

        $scope[] = $scopeCategoryId;
        $scope[] = $scopeSubCategoryId;


                static::sortableApplyScopeCriteria($this, $scope);
        $stmt = $this->doSelect($con);

        return $stmt->fetchColumn();
    }

    /**
     * Get the highest rank by a scope with a array format.
     *
     * @param     mixed $scope      The scope value as scalar type or array($value1, ...).

     * @param     ConnectionInterface optional connection
     *
     * @return    integer highest position
     */
    public function getMaxRankArray($scope, ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }
        // shift the objects with a position lower than the one of object
        $this->addSelectColumn('MAX(' . SortableMultiCommaScopesTableMap::RANK_COL . ')');
        static::sortableApplyScopeCriteria($this, $scope);
        $stmt = $this->doSelect($con);

        return $stmt->fetchColumn();
    }

    /**
     * Get an item from the list based on its rank
     *
     * @param     integer   $rank rank
     * @param      int $scope        Scope to determine which suite to consider
     * @param     ConnectionInterface $con optional connection
     *
     * @return ChildSortableMultiCommaScopes
     */
    static public function retrieveByRank($rank, $scope = null, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        $c = new Criteria;
        $c->add(SortableMultiCommaScopesTableMap::RANK_COL, $rank);
                static::sortableApplyScopeCriteria($c, $scope);

        return static::create(null, $c)->findOne($con);
    }

    /**
     * Reorder a set of sortable objects based on a list of id/position
     * Beware that there is no check made on the positions passed
     * So incoherent positions will result in an incoherent list
     *
     * @param     mixed               $order id => rank pairs
     * @param     ConnectionInterface $con   optional connection
     *
     * @return    boolean true if the reordering took place, false if a database problem prevented it
     */
    public function reorder($order, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con, $order) {
            $ids = array_keys($order);
            $objects = $this->findPks($ids, $con);
            foreach ($objects as $object) {
                $pk = $object->getPrimaryKey();
                if ($object->getPosition() != $order[$pk]) {
                    $object->setPosition($order[$pk]);
                    $object->save($con);
                }
            }
        });

        return true;
    }

    /**
     * Return an array of sortable objects ordered by position
     *
     * @param     Criteria  $criteria  optional criteria object
     * @param     string    $order     sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
     * @param     ConnectionInterface $con       optional connection
     *
     * @return    array list of sortable objects
     */
    static public function doSelectOrderByRank(Criteria $criteria = null, $order = Criteria::ASC, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        if (null === $criteria) {
            $criteria = new Criteria();
        } elseif ($criteria instanceof Criteria) {
            $criteria = clone $criteria;
        }

        $criteria->clearOrderByColumns();

        if (Criteria::ASC == $order) {
            $criteria->addAscendingOrderByColumn(SortableMultiCommaScopesTableMap::RANK_COL);
        } else {
            $criteria->addDescendingOrderByColumn(SortableMultiCommaScopesTableMap::RANK_COL);
        }

        return ChildSortableMultiCommaScopesQuery::create(null, $criteria)->find($con);
    }

    /**
     * Return an array of sortable objects in the given scope ordered by position
     *
     * @param     int       $scope  the scope of the list
     * @param     string    $order  sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
     * @param     ConnectionInterface $con    optional connection
     *
     * @return    array list of sortable objects
     */
    static public function retrieveList($scope, $order = Criteria::ASC, ConnectionInterface $con = null)
    {
        $c = new Criteria();
        static::sortableApplyScopeCriteria($c, $scope);

        return ChildSortableMultiCommaScopesQuery::doSelectOrderByRank($c, $order, $con);
    }

    /**
     * Return the number of sortable objects in the given scope
     *
     * @param     int       $scope  the scope of the list
     * @param     ConnectionInterface $con    optional connection
     *
     * @return    array list of sortable objects
     */
    static public function countList($scope, ConnectionInterface $con = null)
    {
        $c = new Criteria();
        $c->add(SortableMultiCommaScopesTableMap::SCOPE_COL, $scope);

        return ChildSortableMultiCommaScopesQuery::create(null, $c)->count($con);
    }

    /**
     * Deletes the sortable objects in the given scope
     *
     * @param     int       $scope  the scope of the list
     * @param     ConnectionInterface $con    optional connection
     *
     * @return    int number of deleted objects
     */
    static public function deleteList($scope, ConnectionInterface $con = null)
    {
        $c = new Criteria();
        static::sortableApplyScopeCriteria($c, $scope);

        return SortableMultiCommaScopesTableMap::doDelete($c, $con);
    }

    /**
     * Applies all scope fields to the given criteria.
     *
     * @param  Criteria $criteria Applies the values directly to this criteria.
     * @param  mixed    $scope    The scope value as scalar type or array($value1, ...).
     * @param  string   $method   The method we use to apply the values.
     *
     */
    static public function sortableApplyScopeCriteria(Criteria $criteria, $scope, $method = 'add')
    {

        $criteria->$method(SortableMultiCommaScopesTableMap::COL_CATEGORY_ID, $scope[0], Criteria::EQUAL);

        $criteria->$method(SortableMultiCommaScopesTableMap::COL_SUB_CATEGORY_ID, $scope[1], Criteria::EQUAL);

    }

    /**
     * Adds $delta to all Rank values that are >= $first and <= $last.
     * '$delta' can also be negative.
     *
     * @param      int $delta Value to be shifted by, can be negative
     * @param      int $first First node to be shifted
     * @param      int $last  Last node to be shifted
     * @param      int $scope Scope to use for the shift
     * @param      ConnectionInterface $con Connection to use.
     */
    static public function sortableShiftRank($delta, $first, $last = null, $scope = null, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        }

        $whereCriteria = new Criteria(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        $criterion = $whereCriteria->getNewCriterion(SortableMultiCommaScopesTableMap::RANK_COL, $first, Criteria::GREATER_EQUAL);
        if (null !== $last) {
            $criterion->addAnd($whereCriteria->getNewCriterion(SortableMultiCommaScopesTableMap::RANK_COL, $last, Criteria::LESS_EQUAL));
        }
        $whereCriteria->add($criterion);
                static::sortableApplyScopeCriteria($whereCriteria, $scope);

        $valuesCriteria = new Criteria(SortableMultiCommaScopesTableMap::DATABASE_NAME);
        $valuesCriteria->add(SortableMultiCommaScopesTableMap::RANK_COL, array('raw' => SortableMultiCommaScopesTableMap::RANK_COL . ' + ?', 'value' => $delta), Criteria::CUSTOM_EQUAL);

        $whereCriteria->doUpdate($valuesCriteria, $con);
        SortableMultiCommaScopesTableMap::clearInstancePool();
    }

} // SortableMultiCommaScopesQuery
