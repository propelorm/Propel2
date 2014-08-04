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
use Propel\Tests\Bookstore\Behavior\Table8 as ChildTable8;
use Propel\Tests\Bookstore\Behavior\Table8Query as ChildTable8Query;
use Propel\Tests\Bookstore\Behavior\Map\Table8TableMap;

/**
 * Base class that represents a query for the 'table8' table.
 *
 *
 *
 * @method     ChildTable8Query orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildTable8Query orderByFooId($order = Criteria::ASC) Order by the foo_id column
 * @method     ChildTable8Query orderByIdentifier($order = Criteria::ASC) Order by the identifier column
 *
 * @method     ChildTable8Query groupByTitle() Group by the title column
 * @method     ChildTable8Query groupByFooId() Group by the foo_id column
 * @method     ChildTable8Query groupByIdentifier() Group by the identifier column
 *
 * @method     ChildTable8Query leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildTable8Query rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildTable8Query innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildTable8Query leftJoinTable6($relationAlias = null) Adds a LEFT JOIN clause to the query using the Table6 relation
 * @method     ChildTable8Query rightJoinTable6($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Table6 relation
 * @method     ChildTable8Query innerJoinTable6($relationAlias = null) Adds a INNER JOIN clause to the query using the Table6 relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\Table6Query endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildTable8 findOne(ConnectionInterface $con = null) Return the first ChildTable8 matching the query
 * @method     ChildTable8 findOneOrCreate(ConnectionInterface $con = null) Return the first ChildTable8 matching the query, or a new ChildTable8 object populated from the query conditions when no match is found
 *
 * @method     ChildTable8 findOneByTitle(string $title) Return the first ChildTable8 filtered by the title column
 * @method     ChildTable8 findOneByFooId(int $foo_id) Return the first ChildTable8 filtered by the foo_id column
 * @method     ChildTable8 findOneByIdentifier(string $identifier) Return the first ChildTable8 filtered by the identifier column
 *
 * @method     ChildTable8[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildTable8 objects based on current ModelCriteria
 * @method     ChildTable8[]|ObjectCollection findByTitle(string $title) Return ChildTable8 objects filtered by the title column
 * @method     ChildTable8[]|ObjectCollection findByFooId(int $foo_id) Return ChildTable8 objects filtered by the foo_id column
 * @method     ChildTable8[]|ObjectCollection findByIdentifier(string $identifier) Return ChildTable8 objects filtered by the identifier column
 * @method     ChildTable8[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class Table8Query extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\Table8Query object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\Table8', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildTable8Query object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildTable8Query
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildTable8Query) {
            return $criteria;
        }
        $query = new ChildTable8Query();
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
     * @return ChildTable8|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = Table8TableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(Table8TableMap::DATABASE_NAME);
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
     * @return ChildTable8 A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT TITLE, FOO_ID, IDENTIFIER FROM table8 WHERE IDENTIFIER = :p0';
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
            /** @var ChildTable8 $obj */
            $obj = new ChildTable8();
            $obj->hydrate($row);
            Table8TableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildTable8|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildTable8Query The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(Table8TableMap::COL_IDENTIFIER, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildTable8Query The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(Table8TableMap::COL_IDENTIFIER, $keys, Criteria::IN);
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
     * @return $this|ChildTable8Query The current query, for fluid interface
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

        return $this->addUsingAlias(Table8TableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the foo_id column
     *
     * Example usage:
     * <code>
     * $query->filterByFooId(1234); // WHERE foo_id = 1234
     * $query->filterByFooId(array(12, 34)); // WHERE foo_id IN (12, 34)
     * $query->filterByFooId(array('min' => 12)); // WHERE foo_id > 12
     * </code>
     *
     * @see       filterByTable6()
     *
     * @param     mixed $fooId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTable8Query The current query, for fluid interface
     */
    public function filterByFooId($fooId = null, $comparison = null)
    {
        if (is_array($fooId)) {
            $useMinMax = false;
            if (isset($fooId['min'])) {
                $this->addUsingAlias(Table8TableMap::COL_FOO_ID, $fooId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($fooId['max'])) {
                $this->addUsingAlias(Table8TableMap::COL_FOO_ID, $fooId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Table8TableMap::COL_FOO_ID, $fooId, $comparison);
    }

    /**
     * Filter the query on the identifier column
     *
     * Example usage:
     * <code>
     * $query->filterByIdentifier(1234); // WHERE identifier = 1234
     * $query->filterByIdentifier(array(12, 34)); // WHERE identifier IN (12, 34)
     * $query->filterByIdentifier(array('min' => 12)); // WHERE identifier > 12
     * </code>
     *
     * @param     mixed $identifier The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTable8Query The current query, for fluid interface
     */
    public function filterByIdentifier($identifier = null, $comparison = null)
    {
        if (is_array($identifier)) {
            $useMinMax = false;
            if (isset($identifier['min'])) {
                $this->addUsingAlias(Table8TableMap::COL_IDENTIFIER, $identifier['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($identifier['max'])) {
                $this->addUsingAlias(Table8TableMap::COL_IDENTIFIER, $identifier['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Table8TableMap::COL_IDENTIFIER, $identifier, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\Table6 object
     *
     * @param \Propel\Tests\Bookstore\Behavior\Table6|ObjectCollection $table6 The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildTable8Query The current query, for fluid interface
     */
    public function filterByTable6($table6, $comparison = null)
    {
        if ($table6 instanceof \Propel\Tests\Bookstore\Behavior\Table6) {
            return $this
                ->addUsingAlias(Table8TableMap::COL_FOO_ID, $table6->getId(), $comparison);
        } elseif ($table6 instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(Table8TableMap::COL_FOO_ID, $table6->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByTable6() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\Table6 or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Table6 relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildTable8Query The current query, for fluid interface
     */
    public function joinTable6($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Table6');

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
            $this->addJoinObject($join, 'Table6');
        }

        return $this;
    }

    /**
     * Use the Table6 relation Table6 object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\Table6Query A secondary query class using the current class as primary query
     */
    public function useTable6Query($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinTable6($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Table6', '\Propel\Tests\Bookstore\Behavior\Table6Query');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildTable8 $table8 Object to remove from the list of results
     *
     * @return $this|ChildTable8Query The current query, for fluid interface
     */
    public function prune($table8 = null)
    {
        if ($table8) {
            $this->addUsingAlias(Table8TableMap::COL_IDENTIFIER, $table8->getIdentifier(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the table8 table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(Table8TableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            Table8TableMap::clearInstancePool();
            Table8TableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(Table8TableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(Table8TableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            Table8TableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            Table8TableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // Table8Query
