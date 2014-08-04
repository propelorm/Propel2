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
use Propel\Tests\Bookstore\RecordLabel as ChildRecordLabel;
use Propel\Tests\Bookstore\RecordLabelQuery as ChildRecordLabelQuery;
use Propel\Tests\Bookstore\Map\RecordLabelTableMap;

/**
 * Base class that represents a query for the 'record_label' table.
 *
 *
 *
 * @method     ChildRecordLabelQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildRecordLabelQuery orderByAbbr($order = Criteria::ASC) Order by the abbr column
 * @method     ChildRecordLabelQuery orderByName($order = Criteria::ASC) Order by the name column
 *
 * @method     ChildRecordLabelQuery groupById() Group by the id column
 * @method     ChildRecordLabelQuery groupByAbbr() Group by the abbr column
 * @method     ChildRecordLabelQuery groupByName() Group by the name column
 *
 * @method     ChildRecordLabelQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildRecordLabelQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildRecordLabelQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildRecordLabelQuery leftJoinReleasePool($relationAlias = null) Adds a LEFT JOIN clause to the query using the ReleasePool relation
 * @method     ChildRecordLabelQuery rightJoinReleasePool($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ReleasePool relation
 * @method     ChildRecordLabelQuery innerJoinReleasePool($relationAlias = null) Adds a INNER JOIN clause to the query using the ReleasePool relation
 *
 * @method     \Propel\Tests\Bookstore\ReleasePoolQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildRecordLabel findOne(ConnectionInterface $con = null) Return the first ChildRecordLabel matching the query
 * @method     ChildRecordLabel findOneOrCreate(ConnectionInterface $con = null) Return the first ChildRecordLabel matching the query, or a new ChildRecordLabel object populated from the query conditions when no match is found
 *
 * @method     ChildRecordLabel findOneById(int $id) Return the first ChildRecordLabel filtered by the id column
 * @method     ChildRecordLabel findOneByAbbr(string $abbr) Return the first ChildRecordLabel filtered by the abbr column
 * @method     ChildRecordLabel findOneByName(string $name) Return the first ChildRecordLabel filtered by the name column
 *
 * @method     ChildRecordLabel[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildRecordLabel objects based on current ModelCriteria
 * @method     ChildRecordLabel[]|ObjectCollection findById(int $id) Return ChildRecordLabel objects filtered by the id column
 * @method     ChildRecordLabel[]|ObjectCollection findByAbbr(string $abbr) Return ChildRecordLabel objects filtered by the abbr column
 * @method     ChildRecordLabel[]|ObjectCollection findByName(string $name) Return ChildRecordLabel objects filtered by the name column
 * @method     ChildRecordLabel[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class RecordLabelQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\RecordLabelQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\RecordLabel', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildRecordLabelQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildRecordLabelQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildRecordLabelQuery) {
            return $criteria;
        }
        $query = new ChildRecordLabelQuery();
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
     * $obj = $c->findPk(array(12, 34), $con);
     * </code>
     *
     * @param array[$id, $abbr] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildRecordLabel|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = RecordLabelTableMap::getInstanceFromPool(serialize(array((string) $key[0], (string) $key[1]))))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(RecordLabelTableMap::DATABASE_NAME);
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
     * @return ChildRecordLabel A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, ABBR, NAME FROM record_label WHERE ID = :p0 AND ABBR = :p1';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_INT);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildRecordLabel $obj */
            $obj = new ChildRecordLabel();
            $obj->hydrate($row);
            RecordLabelTableMap::addInstanceToPool($obj, serialize(array((string) $key[0], (string) $key[1])));
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
     * @return ChildRecordLabel|array|mixed the result, formatted by the current formatter
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
     * $objs = $c->findPks(array(array(12, 56), array(832, 123), array(123, 456)), $con);
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
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(RecordLabelTableMap::COL_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(RecordLabelTableMap::COL_ABBR, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(RecordLabelTableMap::COL_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(RecordLabelTableMap::COL_ABBR, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
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
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(RecordLabelTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(RecordLabelTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(RecordLabelTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the abbr column
     *
     * Example usage:
     * <code>
     * $query->filterByAbbr('fooValue');   // WHERE abbr = 'fooValue'
     * $query->filterByAbbr('%fooValue%'); // WHERE abbr LIKE '%fooValue%'
     * </code>
     *
     * @param     string $abbr The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
     */
    public function filterByAbbr($abbr = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($abbr)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $abbr)) {
                $abbr = str_replace('*', '%', $abbr);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(RecordLabelTableMap::COL_ABBR, $abbr, $comparison);
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
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
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

        return $this->addUsingAlias(RecordLabelTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\ReleasePool object
     *
     * @param \Propel\Tests\Bookstore\ReleasePool|ObjectCollection $releasePool  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildRecordLabelQuery The current query, for fluid interface
     */
    public function filterByReleasePool($releasePool, $comparison = null)
    {
        if ($releasePool instanceof \Propel\Tests\Bookstore\ReleasePool) {
            return $this
                ->addUsingAlias(RecordLabelTableMap::COL_ID, $releasePool->getRecordLabelId(), $comparison);
        } elseif ($releasePool instanceof ObjectCollection) {
            return $this
                ->useReleasePoolQuery()
                ->filterByPrimaryKeys($releasePool->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByReleasePool() only accepts arguments of type \Propel\Tests\Bookstore\ReleasePool or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ReleasePool relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
     */
    public function joinReleasePool($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ReleasePool');

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
            $this->addJoinObject($join, 'ReleasePool');
        }

        return $this;
    }

    /**
     * Use the ReleasePool relation ReleasePool object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\ReleasePoolQuery A secondary query class using the current class as primary query
     */
    public function useReleasePoolQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinReleasePool($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ReleasePool', '\Propel\Tests\Bookstore\ReleasePoolQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildRecordLabel $recordLabel Object to remove from the list of results
     *
     * @return $this|ChildRecordLabelQuery The current query, for fluid interface
     */
    public function prune($recordLabel = null)
    {
        if ($recordLabel) {
            $this->addCond('pruneCond0', $this->getAliasedColName(RecordLabelTableMap::COL_ID), $recordLabel->getId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(RecordLabelTableMap::COL_ABBR), $recordLabel->getAbbr(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the record_label table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(RecordLabelTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            RecordLabelTableMap::clearInstancePool();
            RecordLabelTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(RecordLabelTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(RecordLabelTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            RecordLabelTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            RecordLabelTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // RecordLabelQuery
