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
use Propel\Tests\Bookstore\Behavior\SortableTable12 as ChildSortableTable12;
use Propel\Tests\Bookstore\Behavior\SortableTable12Query as ChildSortableTable12Query;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable12TableMap;

/**
 * Base class that represents a query for the 'sortable_table12' table.
 *
 *
 *
 * @method     ChildSortableTable12Query orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildSortableTable12Query orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildSortableTable12Query orderByPosition($order = Criteria::ASC) Order by the position column
 * @method     ChildSortableTable12Query orderByMyScopeColumn($order = Criteria::ASC) Order by the my_scope_column column
 *
 * @method     ChildSortableTable12Query groupById() Group by the id column
 * @method     ChildSortableTable12Query groupByTitle() Group by the title column
 * @method     ChildSortableTable12Query groupByPosition() Group by the position column
 * @method     ChildSortableTable12Query groupByMyScopeColumn() Group by the my_scope_column column
 *
 * @method     ChildSortableTable12Query leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildSortableTable12Query rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildSortableTable12Query innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildSortableTable12 findOne(ConnectionInterface $con = null) Return the first ChildSortableTable12 matching the query
 * @method     ChildSortableTable12 findOneOrCreate(ConnectionInterface $con = null) Return the first ChildSortableTable12 matching the query, or a new ChildSortableTable12 object populated from the query conditions when no match is found
 *
 * @method     ChildSortableTable12 findOneById(int $id) Return the first ChildSortableTable12 filtered by the id column
 * @method     ChildSortableTable12 findOneByTitle(string $title) Return the first ChildSortableTable12 filtered by the title column
 * @method     ChildSortableTable12 findOneByPosition(int $position) Return the first ChildSortableTable12 filtered by the position column
 * @method     ChildSortableTable12 findOneByMyScopeColumn(int $my_scope_column) Return the first ChildSortableTable12 filtered by the my_scope_column column
 *
 * @method     ChildSortableTable12[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildSortableTable12 objects based on current ModelCriteria
 * @method     ChildSortableTable12[]|ObjectCollection findById(int $id) Return ChildSortableTable12 objects filtered by the id column
 * @method     ChildSortableTable12[]|ObjectCollection findByTitle(string $title) Return ChildSortableTable12 objects filtered by the title column
 * @method     ChildSortableTable12[]|ObjectCollection findByPosition(int $position) Return ChildSortableTable12 objects filtered by the position column
 * @method     ChildSortableTable12[]|ObjectCollection findByMyScopeColumn(int $my_scope_column) Return ChildSortableTable12 objects filtered by the my_scope_column column
 * @method     ChildSortableTable12[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class SortableTable12Query extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\SortableTable12Query object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\SortableTable12', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildSortableTable12Query object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildSortableTable12Query
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildSortableTable12Query) {
            return $criteria;
        }
        $query = new ChildSortableTable12Query();
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
     * @return ChildSortableTable12|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = SortableTable12TableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableTable12TableMap::DATABASE_NAME);
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
     * @return ChildSortableTable12 A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, POSITION, MY_SCOPE_COLUMN FROM sortable_table12 WHERE ID = :p0';
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
            /** @var ChildSortableTable12 $obj */
            $obj = new ChildSortableTable12();
            $obj->hydrate($row);
            SortableTable12TableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildSortableTable12|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(SortableTable12TableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(SortableTable12TableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(SortableTable12TableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(SortableTable12TableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableTable12TableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
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

        return $this->addUsingAlias(SortableTable12TableMap::COL_TITLE, $title, $comparison);
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
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function filterByPosition($position = null, $comparison = null)
    {
        if (is_array($position)) {
            $useMinMax = false;
            if (isset($position['min'])) {
                $this->addUsingAlias(SortableTable12TableMap::COL_POSITION, $position['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($position['max'])) {
                $this->addUsingAlias(SortableTable12TableMap::COL_POSITION, $position['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableTable12TableMap::COL_POSITION, $position, $comparison);
    }

    /**
     * Filter the query on the my_scope_column column
     *
     * Example usage:
     * <code>
     * $query->filterByMyScopeColumn(1234); // WHERE my_scope_column = 1234
     * $query->filterByMyScopeColumn(array(12, 34)); // WHERE my_scope_column IN (12, 34)
     * $query->filterByMyScopeColumn(array('min' => 12)); // WHERE my_scope_column > 12
     * </code>
     *
     * @param     mixed $myScopeColumn The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function filterByMyScopeColumn($myScopeColumn = null, $comparison = null)
    {
        if (is_array($myScopeColumn)) {
            $useMinMax = false;
            if (isset($myScopeColumn['min'])) {
                $this->addUsingAlias(SortableTable12TableMap::COL_MY_SCOPE_COLUMN, $myScopeColumn['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($myScopeColumn['max'])) {
                $this->addUsingAlias(SortableTable12TableMap::COL_MY_SCOPE_COLUMN, $myScopeColumn['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SortableTable12TableMap::COL_MY_SCOPE_COLUMN, $myScopeColumn, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildSortableTable12 $sortableTable12 Object to remove from the list of results
     *
     * @return $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function prune($sortableTable12 = null)
    {
        if ($sortableTable12) {
            $this->addUsingAlias(SortableTable12TableMap::COL_ID, $sortableTable12->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the sortable_table12 table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SortableTable12TableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            SortableTable12TableMap::clearInstancePool();
            SortableTable12TableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(SortableTable12TableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(SortableTable12TableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            SortableTable12TableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            SortableTable12TableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // sortable behavior

    /**
     * Returns the objects in a certain list, from the list scope
     *
     * @param int $scope Scope to determine which objects node to return
     *
     * @return    $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function inList($scope = null)
    {

        static::sortableApplyScopeCriteria($this, $scope, 'addUsingAlias');

        return $this;
    }

    /**
     * Filter the query based on a rank in the list
     *
     * @param     integer   $rank rank
     * @param int $scope Scope to determine which objects node to return

     *
     * @return    ChildSortableTable12Query The current query, for fluid interface
     */
    public function filterByRank($rank, $scope = null)
    {

        return $this
            ->inList($scope)
            ->addUsingAlias(SortableTable12TableMap::RANK_COL, $rank, Criteria::EQUAL);
    }

    /**
     * Order the query based on the rank in the list.
     * Using the default $order, returns the item with the lowest rank first
     *
     * @param     string $order either Criteria::ASC (default) or Criteria::DESC
     *
     * @return    $this|ChildSortableTable12Query The current query, for fluid interface
     */
    public function orderByRank($order = Criteria::ASC)
    {
        $order = strtoupper($order);
        switch ($order) {
            case Criteria::ASC:
                return $this->addAscendingOrderByColumn($this->getAliasedColName(SortableTable12TableMap::RANK_COL));
                break;
            case Criteria::DESC:
                return $this->addDescendingOrderByColumn($this->getAliasedColName(SortableTable12TableMap::RANK_COL));
                break;
            default:
                throw new \Propel\Runtime\Exception\PropelException('ChildSortableTable12Query::orderBy() only accepts "asc" or "desc" as argument');
        }
    }

    /**
     * Get an item from the list based on its rank
     *
     * @param     integer   $rank rank
     * @param int $scope Scope to determine which objects node to return
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildSortableTable12
     */
    public function findOneByRank($rank, $scope = null, ConnectionInterface $con = null)
    {

        return $this
            ->filterByRank($rank, $scope)
            ->findOne($con);
    }

    /**
     * Returns a list of objects
     *
     * @param int $scope Scope to determine which objects node to return

     * @param      ConnectionInterface $con    Connection to use.
     *
     * @return     mixed the list of results, formatted by the current formatter
     */
    public function findList($scope = null, $con = null)
    {

        return $this
            ->inList($scope)
            ->orderByRank()
            ->find($con);
    }

    /**
     * Get the highest rank
     *
     * @param int $scope Scope to determine which objects node to return
     * @param     ConnectionInterface optional connection
     *
     * @return    integer highest position
     */
    public function getMaxRank($scope = null, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableTable12TableMap::DATABASE_NAME);
        }
        // shift the objects with a position lower than the one of object
        $this->addSelectColumn('MAX(' . SortableTable12TableMap::RANK_COL . ')');

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
            $con = Propel::getConnection(SortableTable12TableMap::DATABASE_NAME);
        }
        // shift the objects with a position lower than the one of object
        $this->addSelectColumn('MAX(' . SortableTable12TableMap::RANK_COL . ')');
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
     * @return ChildSortableTable12
     */
    static public function retrieveByRank($rank, $scope = null, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection(SortableTable12TableMap::DATABASE_NAME);
        }

        $c = new Criteria;
        $c->add(SortableTable12TableMap::RANK_COL, $rank);
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
            $con = Propel::getServiceContainer()->getReadConnection(SortableTable12TableMap::DATABASE_NAME);
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
            $con = Propel::getServiceContainer()->getReadConnection(SortableTable12TableMap::DATABASE_NAME);
        }

        if (null === $criteria) {
            $criteria = new Criteria();
        } elseif ($criteria instanceof Criteria) {
            $criteria = clone $criteria;
        }

        $criteria->clearOrderByColumns();

        if (Criteria::ASC == $order) {
            $criteria->addAscendingOrderByColumn(SortableTable12TableMap::RANK_COL);
        } else {
            $criteria->addDescendingOrderByColumn(SortableTable12TableMap::RANK_COL);
        }

        return ChildSortableTable12Query::create(null, $criteria)->find($con);
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

        return ChildSortableTable12Query::doSelectOrderByRank($c, $order, $con);
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
        $c->add(SortableTable12TableMap::SCOPE_COL, $scope);

        return ChildSortableTable12Query::create(null, $c)->count($con);
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

        return SortableTable12TableMap::doDelete($c, $con);
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

        $criteria->$method(SortableTable12TableMap::COL_MY_SCOPE_COLUMN, $scope, Criteria::EQUAL);

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
            $con = Propel::getServiceContainer()->getWriteConnection(SortableTable12TableMap::DATABASE_NAME);
        }

        $whereCriteria = new Criteria(SortableTable12TableMap::DATABASE_NAME);
        $criterion = $whereCriteria->getNewCriterion(SortableTable12TableMap::RANK_COL, $first, Criteria::GREATER_EQUAL);
        if (null !== $last) {
            $criterion->addAnd($whereCriteria->getNewCriterion(SortableTable12TableMap::RANK_COL, $last, Criteria::LESS_EQUAL));
        }
        $whereCriteria->add($criterion);
                static::sortableApplyScopeCriteria($whereCriteria, $scope);

        $valuesCriteria = new Criteria(SortableTable12TableMap::DATABASE_NAME);
        $valuesCriteria->add(SortableTable12TableMap::RANK_COL, array('raw' => SortableTable12TableMap::RANK_COL . ' + ?', 'value' => $delta), Criteria::CUSTOM_EQUAL);

        $whereCriteria->doUpdate($valuesCriteria, $con);
        SortableTable12TableMap::clearInstancePool();
    }

} // SortableTable12Query
