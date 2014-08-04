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
use Propel\Tests\Bookstore\Behavior\TableWithScope as ChildTableWithScope;
use Propel\Tests\Bookstore\Behavior\TableWithScopeQuery as ChildTableWithScopeQuery;
use Propel\Tests\Bookstore\Behavior\Map\TableWithScopeTableMap;

/**
 * Base class that represents a query for the 'table_with_scope' table.
 *
 *
 *
 * @method     ChildTableWithScopeQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildTableWithScopeQuery orderByScope($order = Criteria::ASC) Order by the scope column
 * @method     ChildTableWithScopeQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildTableWithScopeQuery orderBySlug($order = Criteria::ASC) Order by the slug column
 *
 * @method     ChildTableWithScopeQuery groupById() Group by the id column
 * @method     ChildTableWithScopeQuery groupByScope() Group by the scope column
 * @method     ChildTableWithScopeQuery groupByTitle() Group by the title column
 * @method     ChildTableWithScopeQuery groupBySlug() Group by the slug column
 *
 * @method     ChildTableWithScopeQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildTableWithScopeQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildTableWithScopeQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildTableWithScope findOne(ConnectionInterface $con = null) Return the first ChildTableWithScope matching the query
 * @method     ChildTableWithScope findOneOrCreate(ConnectionInterface $con = null) Return the first ChildTableWithScope matching the query, or a new ChildTableWithScope object populated from the query conditions when no match is found
 *
 * @method     ChildTableWithScope findOneById(int $id) Return the first ChildTableWithScope filtered by the id column
 * @method     ChildTableWithScope findOneByScope(int $scope) Return the first ChildTableWithScope filtered by the scope column
 * @method     ChildTableWithScope findOneByTitle(string $title) Return the first ChildTableWithScope filtered by the title column
 * @method     ChildTableWithScope findOneBySlug(string $slug) Return the first ChildTableWithScope filtered by the slug column
 *
 * @method     ChildTableWithScope[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildTableWithScope objects based on current ModelCriteria
 * @method     ChildTableWithScope[]|ObjectCollection findById(int $id) Return ChildTableWithScope objects filtered by the id column
 * @method     ChildTableWithScope[]|ObjectCollection findByScope(int $scope) Return ChildTableWithScope objects filtered by the scope column
 * @method     ChildTableWithScope[]|ObjectCollection findByTitle(string $title) Return ChildTableWithScope objects filtered by the title column
 * @method     ChildTableWithScope[]|ObjectCollection findBySlug(string $slug) Return ChildTableWithScope objects filtered by the slug column
 * @method     ChildTableWithScope[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class TableWithScopeQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\TableWithScopeQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\TableWithScope', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildTableWithScopeQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildTableWithScopeQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildTableWithScopeQuery) {
            return $criteria;
        }
        $query = new ChildTableWithScopeQuery();
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
     * @return ChildTableWithScope|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = TableWithScopeTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(TableWithScopeTableMap::DATABASE_NAME);
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
     * @return ChildTableWithScope A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, SCOPE, TITLE, SLUG FROM table_with_scope WHERE ID = :p0';
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
            /** @var ChildTableWithScope $obj */
            $obj = new ChildTableWithScope();
            $obj->hydrate($row);
            TableWithScopeTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildTableWithScope|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(TableWithScopeTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(TableWithScopeTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(TableWithScopeTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(TableWithScopeTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TableWithScopeTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the scope column
     *
     * Example usage:
     * <code>
     * $query->filterByScope(1234); // WHERE scope = 1234
     * $query->filterByScope(array(12, 34)); // WHERE scope IN (12, 34)
     * $query->filterByScope(array('min' => 12)); // WHERE scope > 12
     * </code>
     *
     * @param     mixed $scope The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
     */
    public function filterByScope($scope = null, $comparison = null)
    {
        if (is_array($scope)) {
            $useMinMax = false;
            if (isset($scope['min'])) {
                $this->addUsingAlias(TableWithScopeTableMap::COL_SCOPE, $scope['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($scope['max'])) {
                $this->addUsingAlias(TableWithScopeTableMap::COL_SCOPE, $scope['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TableWithScopeTableMap::COL_SCOPE, $scope, $comparison);
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
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
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

        return $this->addUsingAlias(TableWithScopeTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the slug column
     *
     * Example usage:
     * <code>
     * $query->filterBySlug('fooValue');   // WHERE slug = 'fooValue'
     * $query->filterBySlug('%fooValue%'); // WHERE slug LIKE '%fooValue%'
     * </code>
     *
     * @param     string $slug The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
     */
    public function filterBySlug($slug = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($slug)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $slug)) {
                $slug = str_replace('*', '%', $slug);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(TableWithScopeTableMap::COL_SLUG, $slug, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildTableWithScope $tableWithScope Object to remove from the list of results
     *
     * @return $this|ChildTableWithScopeQuery The current query, for fluid interface
     */
    public function prune($tableWithScope = null)
    {
        if ($tableWithScope) {
            $this->addUsingAlias(TableWithScopeTableMap::COL_ID, $tableWithScope->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the table_with_scope table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(TableWithScopeTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            TableWithScopeTableMap::clearInstancePool();
            TableWithScopeTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(TableWithScopeTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(TableWithScopeTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            TableWithScopeTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            TableWithScopeTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // TableWithScopeQuery
