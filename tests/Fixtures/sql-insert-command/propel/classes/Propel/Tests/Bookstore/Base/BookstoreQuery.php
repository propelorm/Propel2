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
use Propel\Tests\Bookstore\Bookstore as ChildBookstore;
use Propel\Tests\Bookstore\BookstoreQuery as ChildBookstoreQuery;
use Propel\Tests\Bookstore\Map\BookstoreTableMap;

/**
 * Base class that represents a query for the 'bookstore' table.
 *
 *
 *
 * @method     ChildBookstoreQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBookstoreQuery orderByStoreName($order = Criteria::ASC) Order by the store_name column
 * @method     ChildBookstoreQuery orderByLocation($order = Criteria::ASC) Order by the location column
 * @method     ChildBookstoreQuery orderByPopulationServed($order = Criteria::ASC) Order by the population_served column
 * @method     ChildBookstoreQuery orderByTotalBooks($order = Criteria::ASC) Order by the total_books column
 * @method     ChildBookstoreQuery orderByStoreOpenTime($order = Criteria::ASC) Order by the store_open_time column
 * @method     ChildBookstoreQuery orderByWebsite($order = Criteria::ASC) Order by the website column
 *
 * @method     ChildBookstoreQuery groupById() Group by the id column
 * @method     ChildBookstoreQuery groupByStoreName() Group by the store_name column
 * @method     ChildBookstoreQuery groupByLocation() Group by the location column
 * @method     ChildBookstoreQuery groupByPopulationServed() Group by the population_served column
 * @method     ChildBookstoreQuery groupByTotalBooks() Group by the total_books column
 * @method     ChildBookstoreQuery groupByStoreOpenTime() Group by the store_open_time column
 * @method     ChildBookstoreQuery groupByWebsite() Group by the website column
 *
 * @method     ChildBookstoreQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookstoreQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookstoreQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookstoreQuery leftJoinBookstoreSale($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreSale relation
 * @method     ChildBookstoreQuery rightJoinBookstoreSale($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreSale relation
 * @method     ChildBookstoreQuery innerJoinBookstoreSale($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreSale relation
 *
 * @method     ChildBookstoreQuery leftJoinBookstoreContest($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreContest relation
 * @method     ChildBookstoreQuery rightJoinBookstoreContest($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreContest relation
 * @method     ChildBookstoreQuery innerJoinBookstoreContest($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreContest relation
 *
 * @method     ChildBookstoreQuery leftJoinBookstoreContestEntry($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreContestEntry relation
 * @method     ChildBookstoreQuery rightJoinBookstoreContestEntry($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreContestEntry relation
 * @method     ChildBookstoreQuery innerJoinBookstoreContestEntry($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreContestEntry relation
 *
 * @method     \Propel\Tests\Bookstore\BookstoreSaleQuery|\Propel\Tests\Bookstore\BookstoreContestQuery|\Propel\Tests\Bookstore\BookstoreContestEntryQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookstore findOne(ConnectionInterface $con = null) Return the first ChildBookstore matching the query
 * @method     ChildBookstore findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookstore matching the query, or a new ChildBookstore object populated from the query conditions when no match is found
 *
 * @method     ChildBookstore findOneById(int $id) Return the first ChildBookstore filtered by the id column
 * @method     ChildBookstore findOneByStoreName(string $store_name) Return the first ChildBookstore filtered by the store_name column
 * @method     ChildBookstore findOneByLocation(string $location) Return the first ChildBookstore filtered by the location column
 * @method     ChildBookstore findOneByPopulationServed(string $population_served) Return the first ChildBookstore filtered by the population_served column
 * @method     ChildBookstore findOneByTotalBooks(int $total_books) Return the first ChildBookstore filtered by the total_books column
 * @method     ChildBookstore findOneByStoreOpenTime(string $store_open_time) Return the first ChildBookstore filtered by the store_open_time column
 * @method     ChildBookstore findOneByWebsite(string $website) Return the first ChildBookstore filtered by the website column
 *
 * @method     ChildBookstore[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookstore objects based on current ModelCriteria
 * @method     ChildBookstore[]|ObjectCollection findById(int $id) Return ChildBookstore objects filtered by the id column
 * @method     ChildBookstore[]|ObjectCollection findByStoreName(string $store_name) Return ChildBookstore objects filtered by the store_name column
 * @method     ChildBookstore[]|ObjectCollection findByLocation(string $location) Return ChildBookstore objects filtered by the location column
 * @method     ChildBookstore[]|ObjectCollection findByPopulationServed(string $population_served) Return ChildBookstore objects filtered by the population_served column
 * @method     ChildBookstore[]|ObjectCollection findByTotalBooks(int $total_books) Return ChildBookstore objects filtered by the total_books column
 * @method     ChildBookstore[]|ObjectCollection findByStoreOpenTime(string $store_open_time) Return ChildBookstore objects filtered by the store_open_time column
 * @method     ChildBookstore[]|ObjectCollection findByWebsite(string $website) Return ChildBookstore objects filtered by the website column
 * @method     ChildBookstore[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookstoreQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookstoreQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Bookstore', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookstoreQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookstoreQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookstoreQuery) {
            return $criteria;
        }
        $query = new ChildBookstoreQuery();
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
     * @return ChildBookstore|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookstoreTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreTableMap::DATABASE_NAME);
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
     * @return ChildBookstore A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, STORE_NAME, LOCATION, POPULATION_SERVED, TOTAL_BOOKS, STORE_OPEN_TIME, WEBSITE FROM bookstore WHERE ID = :p0';
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
            /** @var ChildBookstore $obj */
            $obj = new ChildBookstore();
            $obj->hydrate($row);
            BookstoreTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBookstore|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(BookstoreTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(BookstoreTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the store_name column
     *
     * Example usage:
     * <code>
     * $query->filterByStoreName('fooValue');   // WHERE store_name = 'fooValue'
     * $query->filterByStoreName('%fooValue%'); // WHERE store_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $storeName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByStoreName($storeName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($storeName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $storeName)) {
                $storeName = str_replace('*', '%', $storeName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_STORE_NAME, $storeName, $comparison);
    }

    /**
     * Filter the query on the location column
     *
     * Example usage:
     * <code>
     * $query->filterByLocation('fooValue');   // WHERE location = 'fooValue'
     * $query->filterByLocation('%fooValue%'); // WHERE location LIKE '%fooValue%'
     * </code>
     *
     * @param     string $location The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByLocation($location = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($location)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $location)) {
                $location = str_replace('*', '%', $location);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_LOCATION, $location, $comparison);
    }

    /**
     * Filter the query on the population_served column
     *
     * Example usage:
     * <code>
     * $query->filterByPopulationServed(1234); // WHERE population_served = 1234
     * $query->filterByPopulationServed(array(12, 34)); // WHERE population_served IN (12, 34)
     * $query->filterByPopulationServed(array('min' => 12)); // WHERE population_served > 12
     * </code>
     *
     * @param     mixed $populationServed The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByPopulationServed($populationServed = null, $comparison = null)
    {
        if (is_array($populationServed)) {
            $useMinMax = false;
            if (isset($populationServed['min'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_POPULATION_SERVED, $populationServed['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($populationServed['max'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_POPULATION_SERVED, $populationServed['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_POPULATION_SERVED, $populationServed, $comparison);
    }

    /**
     * Filter the query on the total_books column
     *
     * Example usage:
     * <code>
     * $query->filterByTotalBooks(1234); // WHERE total_books = 1234
     * $query->filterByTotalBooks(array(12, 34)); // WHERE total_books IN (12, 34)
     * $query->filterByTotalBooks(array('min' => 12)); // WHERE total_books > 12
     * </code>
     *
     * @param     mixed $totalBooks The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByTotalBooks($totalBooks = null, $comparison = null)
    {
        if (is_array($totalBooks)) {
            $useMinMax = false;
            if (isset($totalBooks['min'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_TOTAL_BOOKS, $totalBooks['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($totalBooks['max'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_TOTAL_BOOKS, $totalBooks['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_TOTAL_BOOKS, $totalBooks, $comparison);
    }

    /**
     * Filter the query on the store_open_time column
     *
     * Example usage:
     * <code>
     * $query->filterByStoreOpenTime('2011-03-14'); // WHERE store_open_time = '2011-03-14'
     * $query->filterByStoreOpenTime('now'); // WHERE store_open_time = '2011-03-14'
     * $query->filterByStoreOpenTime(array('max' => 'yesterday')); // WHERE store_open_time > '2011-03-13'
     * </code>
     *
     * @param     mixed $storeOpenTime The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByStoreOpenTime($storeOpenTime = null, $comparison = null)
    {
        if (is_array($storeOpenTime)) {
            $useMinMax = false;
            if (isset($storeOpenTime['min'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_STORE_OPEN_TIME, $storeOpenTime['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($storeOpenTime['max'])) {
                $this->addUsingAlias(BookstoreTableMap::COL_STORE_OPEN_TIME, $storeOpenTime['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_STORE_OPEN_TIME, $storeOpenTime, $comparison);
    }

    /**
     * Filter the query on the website column
     *
     * Example usage:
     * <code>
     * $query->filterByWebsite('fooValue');   // WHERE website = 'fooValue'
     * $query->filterByWebsite('%fooValue%'); // WHERE website LIKE '%fooValue%'
     * </code>
     *
     * @param     string $website The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByWebsite($website = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($website)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $website)) {
                $website = str_replace('*', '%', $website);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreTableMap::COL_WEBSITE, $website, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreSale object
     *
     * @param \Propel\Tests\Bookstore\BookstoreSale|ObjectCollection $bookstoreSale  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByBookstoreSale($bookstoreSale, $comparison = null)
    {
        if ($bookstoreSale instanceof \Propel\Tests\Bookstore\BookstoreSale) {
            return $this
                ->addUsingAlias(BookstoreTableMap::COL_ID, $bookstoreSale->getBookstoreId(), $comparison);
        } elseif ($bookstoreSale instanceof ObjectCollection) {
            return $this
                ->useBookstoreSaleQuery()
                ->filterByPrimaryKeys($bookstoreSale->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookstoreSale() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreSale or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreSale relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function joinBookstoreSale($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookstoreSale');

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
            $this->addJoinObject($join, 'BookstoreSale');
        }

        return $this;
    }

    /**
     * Use the BookstoreSale relation BookstoreSale object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreSaleQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreSaleQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinBookstoreSale($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookstoreSale', '\Propel\Tests\Bookstore\BookstoreSaleQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreContest object
     *
     * @param \Propel\Tests\Bookstore\BookstoreContest|ObjectCollection $bookstoreContest  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByBookstoreContest($bookstoreContest, $comparison = null)
    {
        if ($bookstoreContest instanceof \Propel\Tests\Bookstore\BookstoreContest) {
            return $this
                ->addUsingAlias(BookstoreTableMap::COL_ID, $bookstoreContest->getBookstoreId(), $comparison);
        } elseif ($bookstoreContest instanceof ObjectCollection) {
            return $this
                ->useBookstoreContestQuery()
                ->filterByPrimaryKeys($bookstoreContest->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookstoreContest() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreContest or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreContest relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function joinBookstoreContest($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookstoreContest');

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
            $this->addJoinObject($join, 'BookstoreContest');
        }

        return $this;
    }

    /**
     * Use the BookstoreContest relation BookstoreContest object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreContestQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreContestQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookstoreContest($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookstoreContest', '\Propel\Tests\Bookstore\BookstoreContestQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreContestEntry object
     *
     * @param \Propel\Tests\Bookstore\BookstoreContestEntry|ObjectCollection $bookstoreContestEntry  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreQuery The current query, for fluid interface
     */
    public function filterByBookstoreContestEntry($bookstoreContestEntry, $comparison = null)
    {
        if ($bookstoreContestEntry instanceof \Propel\Tests\Bookstore\BookstoreContestEntry) {
            return $this
                ->addUsingAlias(BookstoreTableMap::COL_ID, $bookstoreContestEntry->getBookstoreId(), $comparison);
        } elseif ($bookstoreContestEntry instanceof ObjectCollection) {
            return $this
                ->useBookstoreContestEntryQuery()
                ->filterByPrimaryKeys($bookstoreContestEntry->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookstoreContestEntry() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreContestEntry or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreContestEntry relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function joinBookstoreContestEntry($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookstoreContestEntry');

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
            $this->addJoinObject($join, 'BookstoreContestEntry');
        }

        return $this;
    }

    /**
     * Use the BookstoreContestEntry relation BookstoreContestEntry object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreContestEntryQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreContestEntryQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookstoreContestEntry($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookstoreContestEntry', '\Propel\Tests\Bookstore\BookstoreContestEntryQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBookstore $bookstore Object to remove from the list of results
     *
     * @return $this|ChildBookstoreQuery The current query, for fluid interface
     */
    public function prune($bookstore = null)
    {
        if ($bookstore) {
            $this->addUsingAlias(BookstoreTableMap::COL_ID, $bookstore->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the bookstore table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookstoreTableMap::clearInstancePool();
            BookstoreTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookstoreTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookstoreTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookstoreTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookstoreQuery
