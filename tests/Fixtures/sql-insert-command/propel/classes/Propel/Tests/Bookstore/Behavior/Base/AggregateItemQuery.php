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
use Propel\Tests\Bookstore\Behavior\AggregateItem as ChildAggregateItem;
use Propel\Tests\Bookstore\Behavior\AggregateItemQuery as ChildAggregateItemQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregateItemTableMap;

/**
 * Base class that represents a query for the 'aggregate_item' table.
 *
 *
 *
 * @method     ChildAggregateItemQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildAggregateItemQuery orderByScore($order = Criteria::ASC) Order by the score column
 * @method     ChildAggregateItemQuery orderByPollId($order = Criteria::ASC) Order by the poll_id column
 *
 * @method     ChildAggregateItemQuery groupById() Group by the id column
 * @method     ChildAggregateItemQuery groupByScore() Group by the score column
 * @method     ChildAggregateItemQuery groupByPollId() Group by the poll_id column
 *
 * @method     ChildAggregateItemQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildAggregateItemQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildAggregateItemQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildAggregateItemQuery leftJoinAggregatePoll($relationAlias = null) Adds a LEFT JOIN clause to the query using the AggregatePoll relation
 * @method     ChildAggregateItemQuery rightJoinAggregatePoll($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AggregatePoll relation
 * @method     ChildAggregateItemQuery innerJoinAggregatePoll($relationAlias = null) Adds a INNER JOIN clause to the query using the AggregatePoll relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\AggregatePollQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildAggregateItem findOne(ConnectionInterface $con = null) Return the first ChildAggregateItem matching the query
 * @method     ChildAggregateItem findOneOrCreate(ConnectionInterface $con = null) Return the first ChildAggregateItem matching the query, or a new ChildAggregateItem object populated from the query conditions when no match is found
 *
 * @method     ChildAggregateItem findOneById(int $id) Return the first ChildAggregateItem filtered by the id column
 * @method     ChildAggregateItem findOneByScore(int $score) Return the first ChildAggregateItem filtered by the score column
 * @method     ChildAggregateItem findOneByPollId(int $poll_id) Return the first ChildAggregateItem filtered by the poll_id column
 *
 * @method     ChildAggregateItem[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildAggregateItem objects based on current ModelCriteria
 * @method     ChildAggregateItem[]|ObjectCollection findById(int $id) Return ChildAggregateItem objects filtered by the id column
 * @method     ChildAggregateItem[]|ObjectCollection findByScore(int $score) Return ChildAggregateItem objects filtered by the score column
 * @method     ChildAggregateItem[]|ObjectCollection findByPollId(int $poll_id) Return ChildAggregateItem objects filtered by the poll_id column
 * @method     ChildAggregateItem[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class AggregateItemQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\AggregateItemQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\AggregateItem', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildAggregateItemQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildAggregateItemQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildAggregateItemQuery) {
            return $criteria;
        }
        $query = new ChildAggregateItemQuery();
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
     * @return ChildAggregateItem|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = AggregateItemTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(AggregateItemTableMap::DATABASE_NAME);
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
     * @return ChildAggregateItem A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, SCORE, POLL_ID FROM aggregate_item WHERE ID = :p0';
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
            /** @var ChildAggregateItem $obj */
            $obj = new ChildAggregateItem();
            $obj->hydrate($row);
            AggregateItemTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildAggregateItem|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(AggregateItemTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(AggregateItemTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(AggregateItemTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(AggregateItemTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregateItemTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the score column
     *
     * Example usage:
     * <code>
     * $query->filterByScore(1234); // WHERE score = 1234
     * $query->filterByScore(array(12, 34)); // WHERE score IN (12, 34)
     * $query->filterByScore(array('min' => 12)); // WHERE score > 12
     * </code>
     *
     * @param     mixed $score The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function filterByScore($score = null, $comparison = null)
    {
        if (is_array($score)) {
            $useMinMax = false;
            if (isset($score['min'])) {
                $this->addUsingAlias(AggregateItemTableMap::COL_SCORE, $score['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($score['max'])) {
                $this->addUsingAlias(AggregateItemTableMap::COL_SCORE, $score['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregateItemTableMap::COL_SCORE, $score, $comparison);
    }

    /**
     * Filter the query on the poll_id column
     *
     * Example usage:
     * <code>
     * $query->filterByPollId(1234); // WHERE poll_id = 1234
     * $query->filterByPollId(array(12, 34)); // WHERE poll_id IN (12, 34)
     * $query->filterByPollId(array('min' => 12)); // WHERE poll_id > 12
     * </code>
     *
     * @see       filterByAggregatePoll()
     *
     * @param     mixed $pollId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function filterByPollId($pollId = null, $comparison = null)
    {
        if (is_array($pollId)) {
            $useMinMax = false;
            if (isset($pollId['min'])) {
                $this->addUsingAlias(AggregateItemTableMap::COL_POLL_ID, $pollId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($pollId['max'])) {
                $this->addUsingAlias(AggregateItemTableMap::COL_POLL_ID, $pollId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregateItemTableMap::COL_POLL_ID, $pollId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\AggregatePoll object
     *
     * @param \Propel\Tests\Bookstore\Behavior\AggregatePoll|ObjectCollection $aggregatePoll The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAggregateItemQuery The current query, for fluid interface
     */
    public function filterByAggregatePoll($aggregatePoll, $comparison = null)
    {
        if ($aggregatePoll instanceof \Propel\Tests\Bookstore\Behavior\AggregatePoll) {
            return $this
                ->addUsingAlias(AggregateItemTableMap::COL_POLL_ID, $aggregatePoll->getId(), $comparison);
        } elseif ($aggregatePoll instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(AggregateItemTableMap::COL_POLL_ID, $aggregatePoll->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByAggregatePoll() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\AggregatePoll or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AggregatePoll relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function joinAggregatePoll($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AggregatePoll');

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
            $this->addJoinObject($join, 'AggregatePoll');
        }

        return $this;
    }

    /**
     * Use the AggregatePoll relation AggregatePoll object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\AggregatePollQuery A secondary query class using the current class as primary query
     */
    public function useAggregatePollQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAggregatePoll($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AggregatePoll', '\Propel\Tests\Bookstore\Behavior\AggregatePollQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildAggregateItem $aggregateItem Object to remove from the list of results
     *
     * @return $this|ChildAggregateItemQuery The current query, for fluid interface
     */
    public function prune($aggregateItem = null)
    {
        if ($aggregateItem) {
            $this->addUsingAlias(AggregateItemTableMap::COL_ID, $aggregateItem->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Code to execute before every DELETE statement
     *
     * @param     ConnectionInterface $con The connection object used by the query
     */
    protected function basePreDelete(ConnectionInterface $con)
    {
        // aggregate_column_relation_aggregate_total_score behavior
        $this->findRelatedAggregatePollTotalScores($con);
        // aggregate_column_relation_aggregate_nb_votes behavior
        $this->findRelatedAggregatePollNbVotess($con);

        return $this->preDelete($con);
    }

    /**
     * Code to execute after every DELETE statement
     *
     * @param     int $affectedRows the number of deleted rows
     * @param     ConnectionInterface $con The connection object used by the query
     */
    protected function basePostDelete($affectedRows, ConnectionInterface $con)
    {
        // aggregate_column_relation_aggregate_total_score behavior
        $this->updateRelatedAggregatePollTotalScores($con);
        // aggregate_column_relation_aggregate_nb_votes behavior
        $this->updateRelatedAggregatePollNbVotess($con);

        return $this->postDelete($affectedRows, $con);
    }

    /**
     * Code to execute before every UPDATE statement
     *
     * @param     array $values The associative array of columns and values for the update
     * @param     ConnectionInterface $con The connection object used by the query
     * @param     boolean $forceIndividualSaves If false (default), the resulting call is a Criteria::doUpdate(), otherwise it is a series of save() calls on all the found objects
     */
    protected function basePreUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        // aggregate_column_relation_aggregate_total_score behavior
        $this->findRelatedAggregatePollTotalScores($con);
        // aggregate_column_relation_aggregate_nb_votes behavior
        $this->findRelatedAggregatePollNbVotess($con);

        return $this->preUpdate($values, $con, $forceIndividualSaves);
    }

    /**
     * Code to execute after every UPDATE statement
     *
     * @param     int $affectedRows the number of updated rows
     * @param     ConnectionInterface $con The connection object used by the query
     */
    protected function basePostUpdate($affectedRows, ConnectionInterface $con)
    {
        // aggregate_column_relation_aggregate_total_score behavior
        $this->updateRelatedAggregatePollTotalScores($con);
        // aggregate_column_relation_aggregate_nb_votes behavior
        $this->updateRelatedAggregatePollNbVotess($con);

        return $this->postUpdate($affectedRows, $con);
    }

    /**
     * Deletes all rows from the aggregate_item table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AggregateItemTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            AggregateItemTableMap::clearInstancePool();
            AggregateItemTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(AggregateItemTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(AggregateItemTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            AggregateItemTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            AggregateItemTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // aggregate_column_relation_aggregate_total_score behavior

    /**
     * Finds the related AggregatePoll objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedAggregatePollTotalScores($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->aggregatePollTotalScores = \Propel\Tests\Bookstore\Behavior\AggregatePollQuery::create()
            ->joinAggregateItem($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedAggregatePollTotalScores($con)
    {
        foreach ($this->aggregatePollTotalScores as $aggregatePollTotalScore) {
            $aggregatePollTotalScore->updateTotalScore($con);
        }
        $this->aggregatePollTotalScores = array();
    }

    // aggregate_column_relation_aggregate_nb_votes behavior

    /**
     * Finds the related AggregatePoll objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedAggregatePollNbVotess($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->aggregatePollNbVotess = \Propel\Tests\Bookstore\Behavior\AggregatePollQuery::create()
            ->joinAggregateItem($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedAggregatePollNbVotess($con)
    {
        foreach ($this->aggregatePollNbVotess as $aggregatePollNbVotes) {
            $aggregatePollNbVotes->updateNbVotes($con);
        }
        $this->aggregatePollNbVotess = array();
    }

} // AggregateItemQuery
