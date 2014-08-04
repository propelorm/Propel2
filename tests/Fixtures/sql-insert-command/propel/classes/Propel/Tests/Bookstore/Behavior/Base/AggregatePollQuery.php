<?php

namespace Propel\Tests\Bookstore\Behavior\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\AggregatePoll as ChildAggregatePoll;
use Propel\Tests\Bookstore\Behavior\AggregatePollQuery as ChildAggregatePollQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregatePollTableMap;

/**
 * Base class that represents a query for the 'aggregate_poll' table.
 *
 *
 *
 * @method     ChildAggregatePollQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildAggregatePollQuery orderByTotalScore($order = Criteria::ASC) Order by the total_score column
 * @method     ChildAggregatePollQuery orderByNbVotes($order = Criteria::ASC) Order by the nb_votes column
 *
 * @method     ChildAggregatePollQuery groupById() Group by the id column
 * @method     ChildAggregatePollQuery groupByTotalScore() Group by the total_score column
 * @method     ChildAggregatePollQuery groupByNbVotes() Group by the nb_votes column
 *
 * @method     ChildAggregatePollQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildAggregatePollQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildAggregatePollQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildAggregatePollQuery leftJoinAggregateItem($relationAlias = null) Adds a LEFT JOIN clause to the query using the AggregateItem relation
 * @method     ChildAggregatePollQuery rightJoinAggregateItem($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AggregateItem relation
 * @method     ChildAggregatePollQuery innerJoinAggregateItem($relationAlias = null) Adds a INNER JOIN clause to the query using the AggregateItem relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\AggregateItemQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildAggregatePoll findOne(ConnectionInterface $con = null) Return the first ChildAggregatePoll matching the query
 * @method     ChildAggregatePoll findOneOrCreate(ConnectionInterface $con = null) Return the first ChildAggregatePoll matching the query, or a new ChildAggregatePoll object populated from the query conditions when no match is found
 *
 * @method     ChildAggregatePoll findOneById(int $id) Return the first ChildAggregatePoll filtered by the id column
 * @method     ChildAggregatePoll findOneByTotalScore(int $total_score) Return the first ChildAggregatePoll filtered by the total_score column
 * @method     ChildAggregatePoll findOneByNbVotes(int $nb_votes) Return the first ChildAggregatePoll filtered by the nb_votes column
 *
 * @method     ChildAggregatePoll[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildAggregatePoll objects based on current ModelCriteria
 * @method     ChildAggregatePoll[]|ObjectCollection findById(int $id) Return ChildAggregatePoll objects filtered by the id column
 * @method     ChildAggregatePoll[]|ObjectCollection findByTotalScore(int $total_score) Return ChildAggregatePoll objects filtered by the total_score column
 * @method     ChildAggregatePoll[]|ObjectCollection findByNbVotes(int $nb_votes) Return ChildAggregatePoll objects filtered by the nb_votes column
 * @method     ChildAggregatePoll[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class AggregatePollQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\AggregatePollQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\AggregatePoll', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildAggregatePollQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildAggregatePollQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildAggregatePollQuery) {
            return $criteria;
        }
        $query = new ChildAggregatePollQuery();
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
     * @return ChildAggregatePoll|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = AggregatePollTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(AggregatePollTableMap::DATABASE_NAME);
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
     * @return ChildAggregatePoll A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TOTAL_SCORE, NB_VOTES FROM aggregate_poll WHERE ID = :p0';
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
            /** @var ChildAggregatePoll $obj */
            $obj = new ChildAggregatePoll();
            $obj->hydrate($row);
            AggregatePollTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildAggregatePoll|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(AggregatePollTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(AggregatePollTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(AggregatePollTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(AggregatePollTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregatePollTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the total_score column
     *
     * Example usage:
     * <code>
     * $query->filterByTotalScore(1234); // WHERE total_score = 1234
     * $query->filterByTotalScore(array(12, 34)); // WHERE total_score IN (12, 34)
     * $query->filterByTotalScore(array('min' => 12)); // WHERE total_score > 12
     * </code>
     *
     * @param     mixed $totalScore The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function filterByTotalScore($totalScore = null, $comparison = null)
    {
        if (is_array($totalScore)) {
            $useMinMax = false;
            if (isset($totalScore['min'])) {
                $this->addUsingAlias(AggregatePollTableMap::COL_TOTAL_SCORE, $totalScore['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($totalScore['max'])) {
                $this->addUsingAlias(AggregatePollTableMap::COL_TOTAL_SCORE, $totalScore['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregatePollTableMap::COL_TOTAL_SCORE, $totalScore, $comparison);
    }

    /**
     * Filter the query on the nb_votes column
     *
     * Example usage:
     * <code>
     * $query->filterByNbVotes(1234); // WHERE nb_votes = 1234
     * $query->filterByNbVotes(array(12, 34)); // WHERE nb_votes IN (12, 34)
     * $query->filterByNbVotes(array('min' => 12)); // WHERE nb_votes > 12
     * </code>
     *
     * @param     mixed $nbVotes The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function filterByNbVotes($nbVotes = null, $comparison = null)
    {
        if (is_array($nbVotes)) {
            $useMinMax = false;
            if (isset($nbVotes['min'])) {
                $this->addUsingAlias(AggregatePollTableMap::COL_NB_VOTES, $nbVotes['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($nbVotes['max'])) {
                $this->addUsingAlias(AggregatePollTableMap::COL_NB_VOTES, $nbVotes['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregatePollTableMap::COL_NB_VOTES, $nbVotes, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\AggregateItem object
     *
     * @param \Propel\Tests\Bookstore\Behavior\AggregateItem|ObjectCollection $aggregateItem  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAggregatePollQuery The current query, for fluid interface
     */
    public function filterByAggregateItem($aggregateItem, $comparison = null)
    {
        if ($aggregateItem instanceof \Propel\Tests\Bookstore\Behavior\AggregateItem) {
            return $this
                ->addUsingAlias(AggregatePollTableMap::COL_ID, $aggregateItem->getPollId(), $comparison);
        } elseif ($aggregateItem instanceof ObjectCollection) {
            return $this
                ->useAggregateItemQuery()
                ->filterByPrimaryKeys($aggregateItem->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByAggregateItem() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\AggregateItem or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AggregateItem relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function joinAggregateItem($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AggregateItem');

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
            $this->addJoinObject($join, 'AggregateItem');
        }

        return $this;
    }

    /**
     * Use the AggregateItem relation AggregateItem object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\AggregateItemQuery A secondary query class using the current class as primary query
     */
    public function useAggregateItemQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAggregateItem($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AggregateItem', '\Propel\Tests\Bookstore\Behavior\AggregateItemQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildAggregatePoll $aggregatePoll Object to remove from the list of results
     *
     * @return $this|ChildAggregatePollQuery The current query, for fluid interface
     */
    public function prune($aggregatePoll = null)
    {
        if ($aggregatePoll) {
            $this->addUsingAlias(AggregatePollTableMap::COL_ID, $aggregatePoll->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the aggregate_poll table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AggregatePollTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            AggregatePollTableMap::clearInstancePool();
            AggregatePollTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(AggregatePollTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(AggregatePollTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            AggregatePollTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            AggregatePollTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // AggregatePollQuery
