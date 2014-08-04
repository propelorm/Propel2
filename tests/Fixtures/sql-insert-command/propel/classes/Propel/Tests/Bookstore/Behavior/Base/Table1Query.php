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
use Propel\Tests\Bookstore\Behavior\Table1 as ChildTable1;
use Propel\Tests\Bookstore\Behavior\Table1Query as ChildTable1Query;
use Propel\Tests\Bookstore\Behavior\Map\Table1TableMap;

/**
 * Base class that represents a query for the 'table1' table.
 *
 *
 *
 * @method     ChildTable1Query orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildTable1Query orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildTable1Query orderByCreatedOn($order = Criteria::ASC) Order by the created_on column
 * @method     ChildTable1Query orderByUpdatedOn($order = Criteria::ASC) Order by the updated_on column
 *
 * @method     ChildTable1Query groupById() Group by the id column
 * @method     ChildTable1Query groupByTitle() Group by the title column
 * @method     ChildTable1Query groupByCreatedOn() Group by the created_on column
 * @method     ChildTable1Query groupByUpdatedOn() Group by the updated_on column
 *
 * @method     ChildTable1Query leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildTable1Query rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildTable1Query innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildTable1 findOne(ConnectionInterface $con = null) Return the first ChildTable1 matching the query
 * @method     ChildTable1 findOneOrCreate(ConnectionInterface $con = null) Return the first ChildTable1 matching the query, or a new ChildTable1 object populated from the query conditions when no match is found
 *
 * @method     ChildTable1 findOneById(int $id) Return the first ChildTable1 filtered by the id column
 * @method     ChildTable1 findOneByTitle(string $title) Return the first ChildTable1 filtered by the title column
 * @method     ChildTable1 findOneByCreatedOn(string $created_on) Return the first ChildTable1 filtered by the created_on column
 * @method     ChildTable1 findOneByUpdatedOn(string $updated_on) Return the first ChildTable1 filtered by the updated_on column
 *
 * @method     ChildTable1[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildTable1 objects based on current ModelCriteria
 * @method     ChildTable1[]|ObjectCollection findById(int $id) Return ChildTable1 objects filtered by the id column
 * @method     ChildTable1[]|ObjectCollection findByTitle(string $title) Return ChildTable1 objects filtered by the title column
 * @method     ChildTable1[]|ObjectCollection findByCreatedOn(string $created_on) Return ChildTable1 objects filtered by the created_on column
 * @method     ChildTable1[]|ObjectCollection findByUpdatedOn(string $updated_on) Return ChildTable1 objects filtered by the updated_on column
 * @method     ChildTable1[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class Table1Query extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\Table1Query object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\Table1', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildTable1Query object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildTable1Query
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildTable1Query) {
            return $criteria;
        }
        $query = new ChildTable1Query();
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
     * @return ChildTable1|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = Table1TableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(Table1TableMap::DATABASE_NAME);
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
     * @return ChildTable1 A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, CREATED_ON, UPDATED_ON FROM table1 WHERE ID = :p0';
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
            /** @var ChildTable1 $obj */
            $obj = new ChildTable1();
            $obj->hydrate($row);
            Table1TableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildTable1|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildTable1Query The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(Table1TableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildTable1Query The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(Table1TableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildTable1Query The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(Table1TableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(Table1TableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Table1TableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildTable1Query The current query, for fluid interface
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

        return $this->addUsingAlias(Table1TableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the created_on column
     *
     * Example usage:
     * <code>
     * $query->filterByCreatedOn('2011-03-14'); // WHERE created_on = '2011-03-14'
     * $query->filterByCreatedOn('now'); // WHERE created_on = '2011-03-14'
     * $query->filterByCreatedOn(array('max' => 'yesterday')); // WHERE created_on > '2011-03-13'
     * </code>
     *
     * @param     mixed $createdOn The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTable1Query The current query, for fluid interface
     */
    public function filterByCreatedOn($createdOn = null, $comparison = null)
    {
        if (is_array($createdOn)) {
            $useMinMax = false;
            if (isset($createdOn['min'])) {
                $this->addUsingAlias(Table1TableMap::COL_CREATED_ON, $createdOn['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdOn['max'])) {
                $this->addUsingAlias(Table1TableMap::COL_CREATED_ON, $createdOn['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Table1TableMap::COL_CREATED_ON, $createdOn, $comparison);
    }

    /**
     * Filter the query on the updated_on column
     *
     * Example usage:
     * <code>
     * $query->filterByUpdatedOn('2011-03-14'); // WHERE updated_on = '2011-03-14'
     * $query->filterByUpdatedOn('now'); // WHERE updated_on = '2011-03-14'
     * $query->filterByUpdatedOn(array('max' => 'yesterday')); // WHERE updated_on > '2011-03-13'
     * </code>
     *
     * @param     mixed $updatedOn The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTable1Query The current query, for fluid interface
     */
    public function filterByUpdatedOn($updatedOn = null, $comparison = null)
    {
        if (is_array($updatedOn)) {
            $useMinMax = false;
            if (isset($updatedOn['min'])) {
                $this->addUsingAlias(Table1TableMap::COL_UPDATED_ON, $updatedOn['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedOn['max'])) {
                $this->addUsingAlias(Table1TableMap::COL_UPDATED_ON, $updatedOn['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Table1TableMap::COL_UPDATED_ON, $updatedOn, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildTable1 $table1 Object to remove from the list of results
     *
     * @return $this|ChildTable1Query The current query, for fluid interface
     */
    public function prune($table1 = null)
    {
        if ($table1) {
            $this->addUsingAlias(Table1TableMap::COL_ID, $table1->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the table1 table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(Table1TableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            Table1TableMap::clearInstancePool();
            Table1TableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(Table1TableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(Table1TableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            Table1TableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            Table1TableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // timestampable behavior

    /**
     * Filter by the latest updated
     *
     * @param      int $nbDays Maximum age of the latest update in days
     *
     * @return     $this|ChildTable1Query The current query, for fluid interface
     */
    public function recentlyUpdated($nbDays = 7)
    {
        return $this->addUsingAlias(Table1TableMap::COL_UPDATED_ON, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     $this|ChildTable1Query The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(Table1TableMap::COL_UPDATED_ON);
    }

    /**
     * Order by update date asc
     *
     * @return     $this|ChildTable1Query The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(Table1TableMap::COL_UPDATED_ON);
    }

    /**
     * Order by create date desc
     *
     * @return     $this|ChildTable1Query The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(Table1TableMap::COL_CREATED_ON);
    }

    /**
     * Filter by the latest created
     *
     * @param      int $nbDays Maximum age of in days
     *
     * @return     $this|ChildTable1Query The current query, for fluid interface
     */
    public function recentlyCreated($nbDays = 7)
    {
        return $this->addUsingAlias(Table1TableMap::COL_CREATED_ON, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by create date asc
     *
     * @return     $this|ChildTable1Query The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(Table1TableMap::COL_CREATED_ON);
    }

} // Table1Query
