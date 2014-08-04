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
use Propel\Tests\Bookstore\Behavior\AggregatePost as ChildAggregatePost;
use Propel\Tests\Bookstore\Behavior\AggregatePostQuery as ChildAggregatePostQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregatePostTableMap;

/**
 * Base class that represents a query for the 'aggregate_post' table.
 *
 *
 *
 * @method     ChildAggregatePostQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildAggregatePostQuery orderByNbComments($order = Criteria::ASC) Order by the nb_comments column
 *
 * @method     ChildAggregatePostQuery groupById() Group by the id column
 * @method     ChildAggregatePostQuery groupByNbComments() Group by the nb_comments column
 *
 * @method     ChildAggregatePostQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildAggregatePostQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildAggregatePostQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildAggregatePostQuery leftJoinAggregateComment($relationAlias = null) Adds a LEFT JOIN clause to the query using the AggregateComment relation
 * @method     ChildAggregatePostQuery rightJoinAggregateComment($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AggregateComment relation
 * @method     ChildAggregatePostQuery innerJoinAggregateComment($relationAlias = null) Adds a INNER JOIN clause to the query using the AggregateComment relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\AggregateCommentQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildAggregatePost findOne(ConnectionInterface $con = null) Return the first ChildAggregatePost matching the query
 * @method     ChildAggregatePost findOneOrCreate(ConnectionInterface $con = null) Return the first ChildAggregatePost matching the query, or a new ChildAggregatePost object populated from the query conditions when no match is found
 *
 * @method     ChildAggregatePost findOneById(int $id) Return the first ChildAggregatePost filtered by the id column
 * @method     ChildAggregatePost findOneByNbComments(int $nb_comments) Return the first ChildAggregatePost filtered by the nb_comments column
 *
 * @method     ChildAggregatePost[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildAggregatePost objects based on current ModelCriteria
 * @method     ChildAggregatePost[]|ObjectCollection findById(int $id) Return ChildAggregatePost objects filtered by the id column
 * @method     ChildAggregatePost[]|ObjectCollection findByNbComments(int $nb_comments) Return ChildAggregatePost objects filtered by the nb_comments column
 * @method     ChildAggregatePost[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class AggregatePostQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\AggregatePostQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\AggregatePost', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildAggregatePostQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildAggregatePostQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildAggregatePostQuery) {
            return $criteria;
        }
        $query = new ChildAggregatePostQuery();
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
     * @return ChildAggregatePost|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = AggregatePostTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(AggregatePostTableMap::DATABASE_NAME);
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
     * @return ChildAggregatePost A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, NB_COMMENTS FROM aggregate_post WHERE ID = :p0';
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
            /** @var ChildAggregatePost $obj */
            $obj = new ChildAggregatePost();
            $obj->hydrate($row);
            AggregatePostTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildAggregatePost|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildAggregatePostQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(AggregatePostTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildAggregatePostQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(AggregatePostTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildAggregatePostQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(AggregatePostTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(AggregatePostTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregatePostTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the nb_comments column
     *
     * Example usage:
     * <code>
     * $query->filterByNbComments(1234); // WHERE nb_comments = 1234
     * $query->filterByNbComments(array(12, 34)); // WHERE nb_comments IN (12, 34)
     * $query->filterByNbComments(array('min' => 12)); // WHERE nb_comments > 12
     * </code>
     *
     * @param     mixed $nbComments The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAggregatePostQuery The current query, for fluid interface
     */
    public function filterByNbComments($nbComments = null, $comparison = null)
    {
        if (is_array($nbComments)) {
            $useMinMax = false;
            if (isset($nbComments['min'])) {
                $this->addUsingAlias(AggregatePostTableMap::COL_NB_COMMENTS, $nbComments['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($nbComments['max'])) {
                $this->addUsingAlias(AggregatePostTableMap::COL_NB_COMMENTS, $nbComments['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregatePostTableMap::COL_NB_COMMENTS, $nbComments, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\AggregateComment object
     *
     * @param \Propel\Tests\Bookstore\Behavior\AggregateComment|ObjectCollection $aggregateComment  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAggregatePostQuery The current query, for fluid interface
     */
    public function filterByAggregateComment($aggregateComment, $comparison = null)
    {
        if ($aggregateComment instanceof \Propel\Tests\Bookstore\Behavior\AggregateComment) {
            return $this
                ->addUsingAlias(AggregatePostTableMap::COL_ID, $aggregateComment->getPostId(), $comparison);
        } elseif ($aggregateComment instanceof ObjectCollection) {
            return $this
                ->useAggregateCommentQuery()
                ->filterByPrimaryKeys($aggregateComment->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByAggregateComment() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\AggregateComment or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AggregateComment relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildAggregatePostQuery The current query, for fluid interface
     */
    public function joinAggregateComment($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AggregateComment');

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
            $this->addJoinObject($join, 'AggregateComment');
        }

        return $this;
    }

    /**
     * Use the AggregateComment relation AggregateComment object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\AggregateCommentQuery A secondary query class using the current class as primary query
     */
    public function useAggregateCommentQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAggregateComment($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AggregateComment', '\Propel\Tests\Bookstore\Behavior\AggregateCommentQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildAggregatePost $aggregatePost Object to remove from the list of results
     *
     * @return $this|ChildAggregatePostQuery The current query, for fluid interface
     */
    public function prune($aggregatePost = null)
    {
        if ($aggregatePost) {
            $this->addUsingAlias(AggregatePostTableMap::COL_ID, $aggregatePost->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the aggregate_post table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AggregatePostTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            AggregatePostTableMap::clearInstancePool();
            AggregatePostTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(AggregatePostTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(AggregatePostTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            AggregatePostTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            AggregatePostTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // AggregatePostQuery
