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
use Propel\Tests\Bookstore\Behavior\AggregateComment as ChildAggregateComment;
use Propel\Tests\Bookstore\Behavior\AggregateCommentQuery as ChildAggregateCommentQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregateCommentTableMap;

/**
 * Base class that represents a query for the 'aggregate_comment' table.
 *
 *
 *
 * @method     ChildAggregateCommentQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildAggregateCommentQuery orderByPostId($order = Criteria::ASC) Order by the post_id column
 *
 * @method     ChildAggregateCommentQuery groupById() Group by the id column
 * @method     ChildAggregateCommentQuery groupByPostId() Group by the post_id column
 *
 * @method     ChildAggregateCommentQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildAggregateCommentQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildAggregateCommentQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildAggregateCommentQuery leftJoinAggregatePost($relationAlias = null) Adds a LEFT JOIN clause to the query using the AggregatePost relation
 * @method     ChildAggregateCommentQuery rightJoinAggregatePost($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AggregatePost relation
 * @method     ChildAggregateCommentQuery innerJoinAggregatePost($relationAlias = null) Adds a INNER JOIN clause to the query using the AggregatePost relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\AggregatePostQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildAggregateComment findOne(ConnectionInterface $con = null) Return the first ChildAggregateComment matching the query
 * @method     ChildAggregateComment findOneOrCreate(ConnectionInterface $con = null) Return the first ChildAggregateComment matching the query, or a new ChildAggregateComment object populated from the query conditions when no match is found
 *
 * @method     ChildAggregateComment findOneById(int $id) Return the first ChildAggregateComment filtered by the id column
 * @method     ChildAggregateComment findOneByPostId(int $post_id) Return the first ChildAggregateComment filtered by the post_id column
 *
 * @method     ChildAggregateComment[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildAggregateComment objects based on current ModelCriteria
 * @method     ChildAggregateComment[]|ObjectCollection findById(int $id) Return ChildAggregateComment objects filtered by the id column
 * @method     ChildAggregateComment[]|ObjectCollection findByPostId(int $post_id) Return ChildAggregateComment objects filtered by the post_id column
 * @method     ChildAggregateComment[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class AggregateCommentQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\AggregateCommentQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\AggregateComment', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildAggregateCommentQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildAggregateCommentQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildAggregateCommentQuery) {
            return $criteria;
        }
        $query = new ChildAggregateCommentQuery();
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
     * @return ChildAggregateComment|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = AggregateCommentTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(AggregateCommentTableMap::DATABASE_NAME);
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
     * @return ChildAggregateComment A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, POST_ID FROM aggregate_comment WHERE ID = :p0';
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
            /** @var ChildAggregateComment $obj */
            $obj = new ChildAggregateComment();
            $obj->hydrate($row);
            AggregateCommentTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildAggregateComment|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(AggregateCommentTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(AggregateCommentTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(AggregateCommentTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(AggregateCommentTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregateCommentTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the post_id column
     *
     * Example usage:
     * <code>
     * $query->filterByPostId(1234); // WHERE post_id = 1234
     * $query->filterByPostId(array(12, 34)); // WHERE post_id IN (12, 34)
     * $query->filterByPostId(array('min' => 12)); // WHERE post_id > 12
     * </code>
     *
     * @see       filterByAggregatePost()
     *
     * @param     mixed $postId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function filterByPostId($postId = null, $comparison = null)
    {
        if (is_array($postId)) {
            $useMinMax = false;
            if (isset($postId['min'])) {
                $this->addUsingAlias(AggregateCommentTableMap::COL_POST_ID, $postId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($postId['max'])) {
                $this->addUsingAlias(AggregateCommentTableMap::COL_POST_ID, $postId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AggregateCommentTableMap::COL_POST_ID, $postId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\AggregatePost object
     *
     * @param \Propel\Tests\Bookstore\Behavior\AggregatePost|ObjectCollection $aggregatePost The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function filterByAggregatePost($aggregatePost, $comparison = null)
    {
        if ($aggregatePost instanceof \Propel\Tests\Bookstore\Behavior\AggregatePost) {
            return $this
                ->addUsingAlias(AggregateCommentTableMap::COL_POST_ID, $aggregatePost->getId(), $comparison);
        } elseif ($aggregatePost instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(AggregateCommentTableMap::COL_POST_ID, $aggregatePost->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByAggregatePost() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\AggregatePost or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AggregatePost relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function joinAggregatePost($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AggregatePost');

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
            $this->addJoinObject($join, 'AggregatePost');
        }

        return $this;
    }

    /**
     * Use the AggregatePost relation AggregatePost object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\AggregatePostQuery A secondary query class using the current class as primary query
     */
    public function useAggregatePostQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAggregatePost($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AggregatePost', '\Propel\Tests\Bookstore\Behavior\AggregatePostQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildAggregateComment $aggregateComment Object to remove from the list of results
     *
     * @return $this|ChildAggregateCommentQuery The current query, for fluid interface
     */
    public function prune($aggregateComment = null)
    {
        if ($aggregateComment) {
            $this->addUsingAlias(AggregateCommentTableMap::COL_ID, $aggregateComment->getId(), Criteria::NOT_EQUAL);
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
        // aggregate_column_relation_aggregate_column behavior
        $this->findRelatedAggregatePostNbCommentss($con);

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
        // aggregate_column_relation_aggregate_column behavior
        $this->updateRelatedAggregatePostNbCommentss($con);

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
        // aggregate_column_relation_aggregate_column behavior
        $this->findRelatedAggregatePostNbCommentss($con);

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
        // aggregate_column_relation_aggregate_column behavior
        $this->updateRelatedAggregatePostNbCommentss($con);

        return $this->postUpdate($affectedRows, $con);
    }

    /**
     * Deletes all rows from the aggregate_comment table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AggregateCommentTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            AggregateCommentTableMap::clearInstancePool();
            AggregateCommentTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(AggregateCommentTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(AggregateCommentTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            AggregateCommentTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            AggregateCommentTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // aggregate_column_relation_aggregate_column behavior

    /**
     * Finds the related AggregatePost objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedAggregatePostNbCommentss($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->aggregatePostNbCommentss = \Propel\Tests\Bookstore\Behavior\AggregatePostQuery::create()
            ->joinAggregateComment($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedAggregatePostNbCommentss($con)
    {
        foreach ($this->aggregatePostNbCommentss as $aggregatePostNbComments) {
            $aggregatePostNbComments->updateNbComments($con);
        }
        $this->aggregatePostNbCommentss = array();
    }

} // AggregateCommentQuery
