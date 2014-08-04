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
use Propel\Tests\Bookstore\Behavior\Table7 as ChildTable7;
use Propel\Tests\Bookstore\Behavior\Table7Query as ChildTable7Query;
use Propel\Tests\Bookstore\Behavior\Map\Table7TableMap;

/**
 * Base class that represents a query for the 'table7' table.
 *
 *
 *
 * @method     ChildTable7Query orderByFoo($order = Criteria::ASC) Order by the foo column
 * @method     ChildTable7Query orderByTitle($order = Criteria::ASC) Order by the title column
 *
 * @method     ChildTable7Query groupByFoo() Group by the foo column
 * @method     ChildTable7Query groupByTitle() Group by the title column
 *
 * @method     ChildTable7Query leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildTable7Query rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildTable7Query innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildTable7 findOne(ConnectionInterface $con = null) Return the first ChildTable7 matching the query
 * @method     ChildTable7 findOneOrCreate(ConnectionInterface $con = null) Return the first ChildTable7 matching the query, or a new ChildTable7 object populated from the query conditions when no match is found
 *
 * @method     ChildTable7 findOneByFoo(int $foo) Return the first ChildTable7 filtered by the foo column
 * @method     ChildTable7 findOneByTitle(string $title) Return the first ChildTable7 filtered by the title column
 *
 * @method     ChildTable7[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildTable7 objects based on current ModelCriteria
 * @method     ChildTable7[]|ObjectCollection findByFoo(int $foo) Return ChildTable7 objects filtered by the foo column
 * @method     ChildTable7[]|ObjectCollection findByTitle(string $title) Return ChildTable7 objects filtered by the title column
 * @method     ChildTable7[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class Table7Query extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\Table7Query object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\Table7', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildTable7Query object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildTable7Query
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildTable7Query) {
            return $criteria;
        }
        $query = new ChildTable7Query();
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
     * @return ChildTable7|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = Table7TableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(Table7TableMap::DATABASE_NAME);
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
     * @return ChildTable7 A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT FOO, TITLE FROM table7 WHERE FOO = :p0';
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
            /** @var ChildTable7 $obj */
            $obj = new ChildTable7();
            $obj->hydrate($row);
            Table7TableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildTable7|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildTable7Query The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(Table7TableMap::COL_FOO, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildTable7Query The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(Table7TableMap::COL_FOO, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the foo column
     *
     * Example usage:
     * <code>
     * $query->filterByFoo(1234); // WHERE foo = 1234
     * $query->filterByFoo(array(12, 34)); // WHERE foo IN (12, 34)
     * $query->filterByFoo(array('min' => 12)); // WHERE foo > 12
     * </code>
     *
     * @param     mixed $foo The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTable7Query The current query, for fluid interface
     */
    public function filterByFoo($foo = null, $comparison = null)
    {
        if (is_array($foo)) {
            $useMinMax = false;
            if (isset($foo['min'])) {
                $this->addUsingAlias(Table7TableMap::COL_FOO, $foo['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($foo['max'])) {
                $this->addUsingAlias(Table7TableMap::COL_FOO, $foo['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Table7TableMap::COL_FOO, $foo, $comparison);
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
     * @return $this|ChildTable7Query The current query, for fluid interface
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

        return $this->addUsingAlias(Table7TableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildTable7 $table7 Object to remove from the list of results
     *
     * @return $this|ChildTable7Query The current query, for fluid interface
     */
    public function prune($table7 = null)
    {
        if ($table7) {
            $this->addUsingAlias(Table7TableMap::COL_FOO, $table7->getFoo(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the table7 table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(Table7TableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            Table7TableMap::clearInstancePool();
            Table7TableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(Table7TableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(Table7TableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            Table7TableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            Table7TableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // Table7Query
