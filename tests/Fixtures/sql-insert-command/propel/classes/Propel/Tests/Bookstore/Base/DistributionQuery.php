<?php

namespace Propel\Tests\Bookstore\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Distribution as ChildDistribution;
use Propel\Tests\Bookstore\DistributionQuery as ChildDistributionQuery;
use Propel\Tests\Bookstore\Map\DistributionTableMap;

/**
 * Base class that represents a query for the 'distribution' table.
 *
 *
 *
 * @method     ChildDistributionQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildDistributionQuery orderByName($order = Criteria::ASC) Order by the name column
 * @method     ChildDistributionQuery orderByType($order = Criteria::ASC) Order by the type column
 * @method     ChildDistributionQuery orderByDistributionManagerId($order = Criteria::ASC) Order by the distribution_manager_id column
 *
 * @method     ChildDistributionQuery groupById() Group by the id column
 * @method     ChildDistributionQuery groupByName() Group by the name column
 * @method     ChildDistributionQuery groupByType() Group by the type column
 * @method     ChildDistributionQuery groupByDistributionManagerId() Group by the distribution_manager_id column
 *
 * @method     ChildDistributionQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildDistributionQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildDistributionQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildDistributionQuery leftJoinDistributionManager($relationAlias = null) Adds a LEFT JOIN clause to the query using the DistributionManager relation
 * @method     ChildDistributionQuery rightJoinDistributionManager($relationAlias = null) Adds a RIGHT JOIN clause to the query using the DistributionManager relation
 * @method     ChildDistributionQuery innerJoinDistributionManager($relationAlias = null) Adds a INNER JOIN clause to the query using the DistributionManager relation
 *
 * @method     \Propel\Tests\Bookstore\DistributionManagerQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildDistribution findOne(ConnectionInterface $con = null) Return the first ChildDistribution matching the query
 * @method     ChildDistribution findOneOrCreate(ConnectionInterface $con = null) Return the first ChildDistribution matching the query, or a new ChildDistribution object populated from the query conditions when no match is found
 *
 * @method     ChildDistribution findOneById(int $id) Return the first ChildDistribution filtered by the id column
 * @method     ChildDistribution findOneByName(string $name) Return the first ChildDistribution filtered by the name column
 * @method     ChildDistribution findOneByType(int $type) Return the first ChildDistribution filtered by the type column
 * @method     ChildDistribution findOneByDistributionManagerId(int $distribution_manager_id) Return the first ChildDistribution filtered by the distribution_manager_id column
 *
 * @method     ChildDistribution[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildDistribution objects based on current ModelCriteria
 * @method     ChildDistribution[]|ObjectCollection findById(int $id) Return ChildDistribution objects filtered by the id column
 * @method     ChildDistribution[]|ObjectCollection findByName(string $name) Return ChildDistribution objects filtered by the name column
 * @method     ChildDistribution[]|ObjectCollection findByType(int $type) Return ChildDistribution objects filtered by the type column
 * @method     ChildDistribution[]|ObjectCollection findByDistributionManagerId(int $distribution_manager_id) Return ChildDistribution objects filtered by the distribution_manager_id column
 * @method     ChildDistribution[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class DistributionQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\DistributionQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Distribution', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildDistributionQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildDistributionQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildDistributionQuery) {
            return $criteria;
        }
        $query = new ChildDistributionQuery();
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
     * @return ChildDistribution|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = DistributionTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(DistributionTableMap::DATABASE_NAME);
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
     * @return ChildDistribution A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, NAME, TYPE, DISTRIBUTION_MANAGER_ID FROM distribution WHERE ID = :p0';
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
            $cls = DistributionTableMap::getOMClass($row, 0, false);
            /** @var ChildDistribution $obj */
            $obj = new $cls();
            $obj->hydrate($row);
            DistributionTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildDistribution|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(DistributionTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(DistributionTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(DistributionTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(DistributionTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DistributionTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the name column
     *
     * Example usage:
     * <code>
     * $query->filterByName('fooValue');   // WHERE name = 'fooValue'
     * $query->filterByName('%fooValue%'); // WHERE name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $name The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function filterByName($name = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($name)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $name)) {
                $name = str_replace('*', '%', $name);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(DistributionTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query on the type column
     *
     * Example usage:
     * <code>
     * $query->filterByType(1234); // WHERE type = 1234
     * $query->filterByType(array(12, 34)); // WHERE type IN (12, 34)
     * $query->filterByType(array('min' => 12)); // WHERE type > 12
     * </code>
     *
     * @param     mixed $type The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function filterByType($type = null, $comparison = null)
    {
        if (is_array($type)) {
            $useMinMax = false;
            if (isset($type['min'])) {
                $this->addUsingAlias(DistributionTableMap::COL_TYPE, $type['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($type['max'])) {
                $this->addUsingAlias(DistributionTableMap::COL_TYPE, $type['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DistributionTableMap::COL_TYPE, $type, $comparison);
    }

    /**
     * Filter the query on the distribution_manager_id column
     *
     * Example usage:
     * <code>
     * $query->filterByDistributionManagerId(1234); // WHERE distribution_manager_id = 1234
     * $query->filterByDistributionManagerId(array(12, 34)); // WHERE distribution_manager_id IN (12, 34)
     * $query->filterByDistributionManagerId(array('min' => 12)); // WHERE distribution_manager_id > 12
     * </code>
     *
     * @see       filterByDistributionManager()
     *
     * @param     mixed $distributionManagerId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function filterByDistributionManagerId($distributionManagerId = null, $comparison = null)
    {
        if (is_array($distributionManagerId)) {
            $useMinMax = false;
            if (isset($distributionManagerId['min'])) {
                $this->addUsingAlias(DistributionTableMap::COL_DISTRIBUTION_MANAGER_ID, $distributionManagerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($distributionManagerId['max'])) {
                $this->addUsingAlias(DistributionTableMap::COL_DISTRIBUTION_MANAGER_ID, $distributionManagerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DistributionTableMap::COL_DISTRIBUTION_MANAGER_ID, $distributionManagerId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\DistributionManager object
     *
     * @param \Propel\Tests\Bookstore\DistributionManager|ObjectCollection $distributionManager The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildDistributionQuery The current query, for fluid interface
     */
    public function filterByDistributionManager($distributionManager, $comparison = null)
    {
        if ($distributionManager instanceof \Propel\Tests\Bookstore\DistributionManager) {
            return $this
                ->addUsingAlias(DistributionTableMap::COL_DISTRIBUTION_MANAGER_ID, $distributionManager->getId(), $comparison);
        } elseif ($distributionManager instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(DistributionTableMap::COL_DISTRIBUTION_MANAGER_ID, $distributionManager->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByDistributionManager() only accepts arguments of type \Propel\Tests\Bookstore\DistributionManager or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the DistributionManager relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function joinDistributionManager($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('DistributionManager');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'DistributionManager');
        }

        return $this;
    }

    /**
     * Use the DistributionManager relation DistributionManager object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\DistributionManagerQuery A secondary query class using the current class as primary query
     */
    public function useDistributionManagerQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinDistributionManager($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'DistributionManager', '\Propel\Tests\Bookstore\DistributionManagerQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildDistribution $distribution Object to remove from the list of results
     *
     * @return $this|ChildDistributionQuery The current query, for fluid interface
     */
    public function prune($distribution = null)
    {
        if ($distribution) {
            $this->addUsingAlias(DistributionTableMap::COL_ID, $distribution->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the distribution table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(DistributionTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            DistributionTableMap::clearInstancePool();
            DistributionTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(DistributionTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(DistributionTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            DistributionTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            DistributionTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // DistributionQuery
