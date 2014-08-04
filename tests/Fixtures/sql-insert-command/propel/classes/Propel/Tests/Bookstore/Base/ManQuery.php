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
use Propel\Tests\Bookstore\Man as ChildMan;
use Propel\Tests\Bookstore\ManQuery as ChildManQuery;
use Propel\Tests\Bookstore\Map\ManTableMap;

/**
 * Base class that represents a query for the 'man' table.
 *
 *
 *
 * @method     ChildManQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildManQuery orderByWifeId($order = Criteria::ASC) Order by the wife_id column
 *
 * @method     ChildManQuery groupById() Group by the id column
 * @method     ChildManQuery groupByWifeId() Group by the wife_id column
 *
 * @method     ChildManQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildManQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildManQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildManQuery leftJoinWomanRelatedByWifeId($relationAlias = null) Adds a LEFT JOIN clause to the query using the WomanRelatedByWifeId relation
 * @method     ChildManQuery rightJoinWomanRelatedByWifeId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the WomanRelatedByWifeId relation
 * @method     ChildManQuery innerJoinWomanRelatedByWifeId($relationAlias = null) Adds a INNER JOIN clause to the query using the WomanRelatedByWifeId relation
 *
 * @method     ChildManQuery leftJoinWomanRelatedByHusbandId($relationAlias = null) Adds a LEFT JOIN clause to the query using the WomanRelatedByHusbandId relation
 * @method     ChildManQuery rightJoinWomanRelatedByHusbandId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the WomanRelatedByHusbandId relation
 * @method     ChildManQuery innerJoinWomanRelatedByHusbandId($relationAlias = null) Adds a INNER JOIN clause to the query using the WomanRelatedByHusbandId relation
 *
 * @method     \Propel\Tests\Bookstore\WomanQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildMan findOne(ConnectionInterface $con = null) Return the first ChildMan matching the query
 * @method     ChildMan findOneOrCreate(ConnectionInterface $con = null) Return the first ChildMan matching the query, or a new ChildMan object populated from the query conditions when no match is found
 *
 * @method     ChildMan findOneById(int $id) Return the first ChildMan filtered by the id column
 * @method     ChildMan findOneByWifeId(int $wife_id) Return the first ChildMan filtered by the wife_id column
 *
 * @method     ChildMan[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildMan objects based on current ModelCriteria
 * @method     ChildMan[]|ObjectCollection findById(int $id) Return ChildMan objects filtered by the id column
 * @method     ChildMan[]|ObjectCollection findByWifeId(int $wife_id) Return ChildMan objects filtered by the wife_id column
 * @method     ChildMan[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ManQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\ManQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Man', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildManQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildManQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildManQuery) {
            return $criteria;
        }
        $query = new ChildManQuery();
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
     * @return ChildMan|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ManTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ManTableMap::DATABASE_NAME);
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
     * @return ChildMan A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, WIFE_ID FROM man WHERE ID = :p0';
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
            /** @var ChildMan $obj */
            $obj = new ChildMan();
            $obj->hydrate($row);
            ManTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildMan|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ManTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ManTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ManTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ManTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ManTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the wife_id column
     *
     * Example usage:
     * <code>
     * $query->filterByWifeId(1234); // WHERE wife_id = 1234
     * $query->filterByWifeId(array(12, 34)); // WHERE wife_id IN (12, 34)
     * $query->filterByWifeId(array('min' => 12)); // WHERE wife_id > 12
     * </code>
     *
     * @see       filterByWomanRelatedByWifeId()
     *
     * @param     mixed $wifeId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function filterByWifeId($wifeId = null, $comparison = null)
    {
        if (is_array($wifeId)) {
            $useMinMax = false;
            if (isset($wifeId['min'])) {
                $this->addUsingAlias(ManTableMap::COL_WIFE_ID, $wifeId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($wifeId['max'])) {
                $this->addUsingAlias(ManTableMap::COL_WIFE_ID, $wifeId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ManTableMap::COL_WIFE_ID, $wifeId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Woman object
     *
     * @param \Propel\Tests\Bookstore\Woman|ObjectCollection $woman The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildManQuery The current query, for fluid interface
     */
    public function filterByWomanRelatedByWifeId($woman, $comparison = null)
    {
        if ($woman instanceof \Propel\Tests\Bookstore\Woman) {
            return $this
                ->addUsingAlias(ManTableMap::COL_WIFE_ID, $woman->getId(), $comparison);
        } elseif ($woman instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ManTableMap::COL_WIFE_ID, $woman->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByWomanRelatedByWifeId() only accepts arguments of type \Propel\Tests\Bookstore\Woman or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the WomanRelatedByWifeId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function joinWomanRelatedByWifeId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('WomanRelatedByWifeId');

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
            $this->addJoinObject($join, 'WomanRelatedByWifeId');
        }

        return $this;
    }

    /**
     * Use the WomanRelatedByWifeId relation Woman object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\WomanQuery A secondary query class using the current class as primary query
     */
    public function useWomanRelatedByWifeIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinWomanRelatedByWifeId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'WomanRelatedByWifeId', '\Propel\Tests\Bookstore\WomanQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Woman object
     *
     * @param \Propel\Tests\Bookstore\Woman|ObjectCollection $woman  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildManQuery The current query, for fluid interface
     */
    public function filterByWomanRelatedByHusbandId($woman, $comparison = null)
    {
        if ($woman instanceof \Propel\Tests\Bookstore\Woman) {
            return $this
                ->addUsingAlias(ManTableMap::COL_ID, $woman->getHusbandId(), $comparison);
        } elseif ($woman instanceof ObjectCollection) {
            return $this
                ->useWomanRelatedByHusbandIdQuery()
                ->filterByPrimaryKeys($woman->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByWomanRelatedByHusbandId() only accepts arguments of type \Propel\Tests\Bookstore\Woman or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the WomanRelatedByHusbandId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function joinWomanRelatedByHusbandId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('WomanRelatedByHusbandId');

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
            $this->addJoinObject($join, 'WomanRelatedByHusbandId');
        }

        return $this;
    }

    /**
     * Use the WomanRelatedByHusbandId relation Woman object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\WomanQuery A secondary query class using the current class as primary query
     */
    public function useWomanRelatedByHusbandIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinWomanRelatedByHusbandId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'WomanRelatedByHusbandId', '\Propel\Tests\Bookstore\WomanQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildMan $man Object to remove from the list of results
     *
     * @return $this|ChildManQuery The current query, for fluid interface
     */
    public function prune($man = null)
    {
        if ($man) {
            $this->addUsingAlias(ManTableMap::COL_ID, $man->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the man table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ManTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ManTableMap::clearInstancePool();
            ManTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ManTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ManTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ManTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ManTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ManQuery
