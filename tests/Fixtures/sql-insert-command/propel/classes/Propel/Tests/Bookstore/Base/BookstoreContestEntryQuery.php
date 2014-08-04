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
use Propel\Tests\Bookstore\BookstoreContestEntry as ChildBookstoreContestEntry;
use Propel\Tests\Bookstore\BookstoreContestEntryQuery as ChildBookstoreContestEntryQuery;
use Propel\Tests\Bookstore\Map\BookstoreContestEntryTableMap;

/**
 * Base class that represents a query for the 'bookstore_contest_entry' table.
 *
 *
 *
 * @method     ChildBookstoreContestEntryQuery orderByBookstoreId($order = Criteria::ASC) Order by the bookstore_id column
 * @method     ChildBookstoreContestEntryQuery orderByContestId($order = Criteria::ASC) Order by the contest_id column
 * @method     ChildBookstoreContestEntryQuery orderByCustomerId($order = Criteria::ASC) Order by the customer_id column
 * @method     ChildBookstoreContestEntryQuery orderByEntryDate($order = Criteria::ASC) Order by the entry_date column
 *
 * @method     ChildBookstoreContestEntryQuery groupByBookstoreId() Group by the bookstore_id column
 * @method     ChildBookstoreContestEntryQuery groupByContestId() Group by the contest_id column
 * @method     ChildBookstoreContestEntryQuery groupByCustomerId() Group by the customer_id column
 * @method     ChildBookstoreContestEntryQuery groupByEntryDate() Group by the entry_date column
 *
 * @method     ChildBookstoreContestEntryQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookstoreContestEntryQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookstoreContestEntryQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookstoreContestEntryQuery leftJoinBookstore($relationAlias = null) Adds a LEFT JOIN clause to the query using the Bookstore relation
 * @method     ChildBookstoreContestEntryQuery rightJoinBookstore($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Bookstore relation
 * @method     ChildBookstoreContestEntryQuery innerJoinBookstore($relationAlias = null) Adds a INNER JOIN clause to the query using the Bookstore relation
 *
 * @method     ChildBookstoreContestEntryQuery leftJoinCustomer($relationAlias = null) Adds a LEFT JOIN clause to the query using the Customer relation
 * @method     ChildBookstoreContestEntryQuery rightJoinCustomer($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Customer relation
 * @method     ChildBookstoreContestEntryQuery innerJoinCustomer($relationAlias = null) Adds a INNER JOIN clause to the query using the Customer relation
 *
 * @method     ChildBookstoreContestEntryQuery leftJoinBookstoreContest($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreContest relation
 * @method     ChildBookstoreContestEntryQuery rightJoinBookstoreContest($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreContest relation
 * @method     ChildBookstoreContestEntryQuery innerJoinBookstoreContest($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreContest relation
 *
 * @method     \Propel\Tests\Bookstore\BookstoreQuery|\Propel\Tests\Bookstore\CustomerQuery|\Propel\Tests\Bookstore\BookstoreContestQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookstoreContestEntry findOne(ConnectionInterface $con = null) Return the first ChildBookstoreContestEntry matching the query
 * @method     ChildBookstoreContestEntry findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookstoreContestEntry matching the query, or a new ChildBookstoreContestEntry object populated from the query conditions when no match is found
 *
 * @method     ChildBookstoreContestEntry findOneByBookstoreId(int $bookstore_id) Return the first ChildBookstoreContestEntry filtered by the bookstore_id column
 * @method     ChildBookstoreContestEntry findOneByContestId(int $contest_id) Return the first ChildBookstoreContestEntry filtered by the contest_id column
 * @method     ChildBookstoreContestEntry findOneByCustomerId(int $customer_id) Return the first ChildBookstoreContestEntry filtered by the customer_id column
 * @method     ChildBookstoreContestEntry findOneByEntryDate(string $entry_date) Return the first ChildBookstoreContestEntry filtered by the entry_date column
 *
 * @method     ChildBookstoreContestEntry[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookstoreContestEntry objects based on current ModelCriteria
 * @method     ChildBookstoreContestEntry[]|ObjectCollection findByBookstoreId(int $bookstore_id) Return ChildBookstoreContestEntry objects filtered by the bookstore_id column
 * @method     ChildBookstoreContestEntry[]|ObjectCollection findByContestId(int $contest_id) Return ChildBookstoreContestEntry objects filtered by the contest_id column
 * @method     ChildBookstoreContestEntry[]|ObjectCollection findByCustomerId(int $customer_id) Return ChildBookstoreContestEntry objects filtered by the customer_id column
 * @method     ChildBookstoreContestEntry[]|ObjectCollection findByEntryDate(string $entry_date) Return ChildBookstoreContestEntry objects filtered by the entry_date column
 * @method     ChildBookstoreContestEntry[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookstoreContestEntryQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookstoreContestEntryQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\BookstoreContestEntry', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookstoreContestEntryQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookstoreContestEntryQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookstoreContestEntryQuery) {
            return $criteria;
        }
        $query = new ChildBookstoreContestEntryQuery();
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
     * $obj = $c->findPk(array(12, 34, 56), $con);
     * </code>
     *
     * @param array[$bookstore_id, $contest_id, $customer_id] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildBookstoreContestEntry|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookstoreContestEntryTableMap::getInstanceFromPool(serialize(array((string) $key[0], (string) $key[1], (string) $key[2]))))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreContestEntryTableMap::DATABASE_NAME);
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
     * @return ChildBookstoreContestEntry A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT BOOKSTORE_ID, CONTEST_ID, CUSTOMER_ID, ENTRY_DATE FROM bookstore_contest_entry WHERE BOOKSTORE_ID = :p0 AND CONTEST_ID = :p1 AND CUSTOMER_ID = :p2';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_INT);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_INT);
            $stmt->bindValue(':p2', $key[2], PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildBookstoreContestEntry $obj */
            $obj = new ChildBookstoreContestEntry();
            $obj->hydrate($row);
            BookstoreContestEntryTableMap::addInstanceToPool($obj, serialize(array((string) $key[0], (string) $key[1], (string) $key[2])));
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
     * @return ChildBookstoreContestEntry|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CONTEST_ID, $key[1], Criteria::EQUAL);
        $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $key[2], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(BookstoreContestEntryTableMap::COL_CONTEST_ID, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $cton2 = $this->getNewCriterion(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $key[2], Criteria::EQUAL);
            $cton0->addAnd($cton2);
            $this->addOr($cton0);
        }

        return $this;
    }

    /**
     * Filter the query on the bookstore_id column
     *
     * Example usage:
     * <code>
     * $query->filterByBookstoreId(1234); // WHERE bookstore_id = 1234
     * $query->filterByBookstoreId(array(12, 34)); // WHERE bookstore_id IN (12, 34)
     * $query->filterByBookstoreId(array('min' => 12)); // WHERE bookstore_id > 12
     * </code>
     *
     * @see       filterByBookstore()
     *
     * @see       filterByBookstoreContest()
     *
     * @param     mixed $bookstoreId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByBookstoreId($bookstoreId = null, $comparison = null)
    {
        if (is_array($bookstoreId)) {
            $useMinMax = false;
            if (isset($bookstoreId['min'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $bookstoreId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bookstoreId['max'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $bookstoreId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $bookstoreId, $comparison);
    }

    /**
     * Filter the query on the contest_id column
     *
     * Example usage:
     * <code>
     * $query->filterByContestId(1234); // WHERE contest_id = 1234
     * $query->filterByContestId(array(12, 34)); // WHERE contest_id IN (12, 34)
     * $query->filterByContestId(array('min' => 12)); // WHERE contest_id > 12
     * </code>
     *
     * @see       filterByBookstoreContest()
     *
     * @param     mixed $contestId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByContestId($contestId = null, $comparison = null)
    {
        if (is_array($contestId)) {
            $useMinMax = false;
            if (isset($contestId['min'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CONTEST_ID, $contestId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($contestId['max'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CONTEST_ID, $contestId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CONTEST_ID, $contestId, $comparison);
    }

    /**
     * Filter the query on the customer_id column
     *
     * Example usage:
     * <code>
     * $query->filterByCustomerId(1234); // WHERE customer_id = 1234
     * $query->filterByCustomerId(array(12, 34)); // WHERE customer_id IN (12, 34)
     * $query->filterByCustomerId(array('min' => 12)); // WHERE customer_id > 12
     * </code>
     *
     * @see       filterByCustomer()
     *
     * @param     mixed $customerId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByCustomerId($customerId = null, $comparison = null)
    {
        if (is_array($customerId)) {
            $useMinMax = false;
            if (isset($customerId['min'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $customerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($customerId['max'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $customerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $customerId, $comparison);
    }

    /**
     * Filter the query on the entry_date column
     *
     * Example usage:
     * <code>
     * $query->filterByEntryDate('2011-03-14'); // WHERE entry_date = '2011-03-14'
     * $query->filterByEntryDate('now'); // WHERE entry_date = '2011-03-14'
     * $query->filterByEntryDate(array('max' => 'yesterday')); // WHERE entry_date > '2011-03-13'
     * </code>
     *
     * @param     mixed $entryDate The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByEntryDate($entryDate = null, $comparison = null)
    {
        if (is_array($entryDate)) {
            $useMinMax = false;
            if (isset($entryDate['min'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_ENTRY_DATE, $entryDate['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($entryDate['max'])) {
                $this->addUsingAlias(BookstoreContestEntryTableMap::COL_ENTRY_DATE, $entryDate['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreContestEntryTableMap::COL_ENTRY_DATE, $entryDate, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Bookstore object
     *
     * @param \Propel\Tests\Bookstore\Bookstore|ObjectCollection $bookstore The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByBookstore($bookstore, $comparison = null)
    {
        if ($bookstore instanceof \Propel\Tests\Bookstore\Bookstore) {
            return $this
                ->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $bookstore->getId(), $comparison);
        } elseif ($bookstore instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $bookstore->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByBookstore() only accepts arguments of type \Propel\Tests\Bookstore\Bookstore or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Bookstore relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function joinBookstore($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Bookstore');

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
            $this->addJoinObject($join, 'Bookstore');
        }

        return $this;
    }

    /**
     * Use the Bookstore relation Bookstore object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookstore($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Bookstore', '\Propel\Tests\Bookstore\BookstoreQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Customer object
     *
     * @param \Propel\Tests\Bookstore\Customer|ObjectCollection $customer The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByCustomer($customer, $comparison = null)
    {
        if ($customer instanceof \Propel\Tests\Bookstore\Customer) {
            return $this
                ->addUsingAlias(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $customer->getId(), $comparison);
        } elseif ($customer instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookstoreContestEntryTableMap::COL_CUSTOMER_ID, $customer->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByCustomer() only accepts arguments of type \Propel\Tests\Bookstore\Customer or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Customer relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function joinCustomer($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Customer');

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
            $this->addJoinObject($join, 'Customer');
        }

        return $this;
    }

    /**
     * Use the Customer relation Customer object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\CustomerQuery A secondary query class using the current class as primary query
     */
    public function useCustomerQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinCustomer($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Customer', '\Propel\Tests\Bookstore\CustomerQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreContest object
     *
     * @param \Propel\Tests\Bookstore\BookstoreContest $bookstoreContest The related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function filterByBookstoreContest($bookstoreContest, $comparison = null)
    {
        if ($bookstoreContest instanceof \Propel\Tests\Bookstore\BookstoreContest) {
            return $this
                ->addUsingAlias(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID, $bookstoreContest->getBookstoreId(), $comparison)
                ->addUsingAlias(BookstoreContestEntryTableMap::COL_CONTEST_ID, $bookstoreContest->getContestId(), $comparison);
        } else {
            throw new PropelException('filterByBookstoreContest() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreContest');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreContest relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
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
     * Exclude object from result
     *
     * @param   ChildBookstoreContestEntry $bookstoreContestEntry Object to remove from the list of results
     *
     * @return $this|ChildBookstoreContestEntryQuery The current query, for fluid interface
     */
    public function prune($bookstoreContestEntry = null)
    {
        if ($bookstoreContestEntry) {
            $this->addCond('pruneCond0', $this->getAliasedColName(BookstoreContestEntryTableMap::COL_BOOKSTORE_ID), $bookstoreContestEntry->getBookstoreId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(BookstoreContestEntryTableMap::COL_CONTEST_ID), $bookstoreContestEntry->getContestId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond2', $this->getAliasedColName(BookstoreContestEntryTableMap::COL_CUSTOMER_ID), $bookstoreContestEntry->getCustomerId(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1', 'pruneCond2'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the bookstore_contest_entry table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreContestEntryTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookstoreContestEntryTableMap::clearInstancePool();
            BookstoreContestEntryTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreContestEntryTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookstoreContestEntryTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookstoreContestEntryTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookstoreContestEntryTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookstoreContestEntryQuery
