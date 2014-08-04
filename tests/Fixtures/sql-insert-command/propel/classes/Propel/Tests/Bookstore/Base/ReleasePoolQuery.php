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
use Propel\Tests\Bookstore\ReleasePool as ChildReleasePool;
use Propel\Tests\Bookstore\ReleasePoolQuery as ChildReleasePoolQuery;
use Propel\Tests\Bookstore\Map\ReleasePoolTableMap;

/**
 * Base class that represents a query for the 'release_pool' table.
 *
 *
 *
 * @method     ChildReleasePoolQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildReleasePoolQuery orderByRecordLabelId($order = Criteria::ASC) Order by the record_label_id column
 * @method     ChildReleasePoolQuery orderByName($order = Criteria::ASC) Order by the name column
 *
 * @method     ChildReleasePoolQuery groupById() Group by the id column
 * @method     ChildReleasePoolQuery groupByRecordLabelId() Group by the record_label_id column
 * @method     ChildReleasePoolQuery groupByName() Group by the name column
 *
 * @method     ChildReleasePoolQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildReleasePoolQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildReleasePoolQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildReleasePoolQuery leftJoinRecordLabel($relationAlias = null) Adds a LEFT JOIN clause to the query using the RecordLabel relation
 * @method     ChildReleasePoolQuery rightJoinRecordLabel($relationAlias = null) Adds a RIGHT JOIN clause to the query using the RecordLabel relation
 * @method     ChildReleasePoolQuery innerJoinRecordLabel($relationAlias = null) Adds a INNER JOIN clause to the query using the RecordLabel relation
 *
 * @method     \Propel\Tests\Bookstore\RecordLabelQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildReleasePool findOne(ConnectionInterface $con = null) Return the first ChildReleasePool matching the query
 * @method     ChildReleasePool findOneOrCreate(ConnectionInterface $con = null) Return the first ChildReleasePool matching the query, or a new ChildReleasePool object populated from the query conditions when no match is found
 *
 * @method     ChildReleasePool findOneById(int $id) Return the first ChildReleasePool filtered by the id column
 * @method     ChildReleasePool findOneByRecordLabelId(int $record_label_id) Return the first ChildReleasePool filtered by the record_label_id column
 * @method     ChildReleasePool findOneByName(string $name) Return the first ChildReleasePool filtered by the name column
 *
 * @method     ChildReleasePool[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildReleasePool objects based on current ModelCriteria
 * @method     ChildReleasePool[]|ObjectCollection findById(int $id) Return ChildReleasePool objects filtered by the id column
 * @method     ChildReleasePool[]|ObjectCollection findByRecordLabelId(int $record_label_id) Return ChildReleasePool objects filtered by the record_label_id column
 * @method     ChildReleasePool[]|ObjectCollection findByName(string $name) Return ChildReleasePool objects filtered by the name column
 * @method     ChildReleasePool[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ReleasePoolQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\ReleasePoolQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\ReleasePool', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildReleasePoolQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildReleasePoolQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildReleasePoolQuery) {
            return $criteria;
        }
        $query = new ChildReleasePoolQuery();
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
     * @return ChildReleasePool|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ReleasePoolTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ReleasePoolTableMap::DATABASE_NAME);
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
     * @return ChildReleasePool A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, RECORD_LABEL_ID, NAME FROM release_pool WHERE ID = :p0';
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
            /** @var ChildReleasePool $obj */
            $obj = new ChildReleasePool();
            $obj->hydrate($row);
            ReleasePoolTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildReleasePool|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ReleasePoolTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ReleasePoolTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ReleasePoolTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ReleasePoolTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ReleasePoolTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the record_label_id column
     *
     * Example usage:
     * <code>
     * $query->filterByRecordLabelId(1234); // WHERE record_label_id = 1234
     * $query->filterByRecordLabelId(array(12, 34)); // WHERE record_label_id IN (12, 34)
     * $query->filterByRecordLabelId(array('min' => 12)); // WHERE record_label_id > 12
     * </code>
     *
     * @see       filterByRecordLabel()
     *
     * @param     mixed $recordLabelId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
     */
    public function filterByRecordLabelId($recordLabelId = null, $comparison = null)
    {
        if (is_array($recordLabelId)) {
            $useMinMax = false;
            if (isset($recordLabelId['min'])) {
                $this->addUsingAlias(ReleasePoolTableMap::COL_RECORD_LABEL_ID, $recordLabelId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($recordLabelId['max'])) {
                $this->addUsingAlias(ReleasePoolTableMap::COL_RECORD_LABEL_ID, $recordLabelId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ReleasePoolTableMap::COL_RECORD_LABEL_ID, $recordLabelId, $comparison);
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
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ReleasePoolTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\RecordLabel object
     *
     * @param \Propel\Tests\Bookstore\RecordLabel|ObjectCollection $recordLabel The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildReleasePoolQuery The current query, for fluid interface
     */
    public function filterByRecordLabel($recordLabel, $comparison = null)
    {
        if ($recordLabel instanceof \Propel\Tests\Bookstore\RecordLabel) {
            return $this
                ->addUsingAlias(ReleasePoolTableMap::COL_RECORD_LABEL_ID, $recordLabel->getId(), $comparison);
        } elseif ($recordLabel instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ReleasePoolTableMap::COL_RECORD_LABEL_ID, $recordLabel->toKeyValue('Id', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByRecordLabel() only accepts arguments of type \Propel\Tests\Bookstore\RecordLabel or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the RecordLabel relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
     */
    public function joinRecordLabel($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('RecordLabel');

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
            $this->addJoinObject($join, 'RecordLabel');
        }

        return $this;
    }

    /**
     * Use the RecordLabel relation RecordLabel object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\RecordLabelQuery A secondary query class using the current class as primary query
     */
    public function useRecordLabelQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinRecordLabel($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'RecordLabel', '\Propel\Tests\Bookstore\RecordLabelQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildReleasePool $releasePool Object to remove from the list of results
     *
     * @return $this|ChildReleasePoolQuery The current query, for fluid interface
     */
    public function prune($releasePool = null)
    {
        if ($releasePool) {
            $this->addUsingAlias(ReleasePoolTableMap::COL_ID, $releasePool->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the release_pool table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ReleasePoolTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ReleasePoolTableMap::clearInstancePool();
            ReleasePoolTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ReleasePoolTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ReleasePoolTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ReleasePoolTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ReleasePoolTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ReleasePoolQuery
