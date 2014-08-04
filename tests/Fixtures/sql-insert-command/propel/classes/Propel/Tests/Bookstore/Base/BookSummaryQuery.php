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
use Propel\Tests\Bookstore\BookSummary as ChildBookSummary;
use Propel\Tests\Bookstore\BookSummaryQuery as ChildBookSummaryQuery;
use Propel\Tests\Bookstore\Map\BookSummaryTableMap;

/**
 * Base class that represents a query for the 'book_summary' table.
 *
 *
 *
 * @method     ChildBookSummaryQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBookSummaryQuery orderByBookId($order = Criteria::ASC) Order by the book_id column
 * @method     ChildBookSummaryQuery orderBySummary($order = Criteria::ASC) Order by the summary column
 *
 * @method     ChildBookSummaryQuery groupById() Group by the id column
 * @method     ChildBookSummaryQuery groupByBookId() Group by the book_id column
 * @method     ChildBookSummaryQuery groupBySummary() Group by the summary column
 *
 * @method     ChildBookSummaryQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookSummaryQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookSummaryQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookSummaryQuery leftJoinSummarizedBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the SummarizedBook relation
 * @method     ChildBookSummaryQuery rightJoinSummarizedBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the SummarizedBook relation
 * @method     ChildBookSummaryQuery innerJoinSummarizedBook($relationAlias = null) Adds a INNER JOIN clause to the query using the SummarizedBook relation
 *
 * @method     \Propel\Tests\Bookstore\BookQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookSummary findOne(ConnectionInterface $con = null) Return the first ChildBookSummary matching the query
 * @method     ChildBookSummary findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookSummary matching the query, or a new ChildBookSummary object populated from the query conditions when no match is found
 *
 * @method     ChildBookSummary findOneById(int $id) Return the first ChildBookSummary filtered by the id column
 * @method     ChildBookSummary findOneByBookId(int $book_id) Return the first ChildBookSummary filtered by the book_id column
 * @method     ChildBookSummary findOneBySummary(string $summary) Return the first ChildBookSummary filtered by the summary column
 *
 * @method     ChildBookSummary[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookSummary objects based on current ModelCriteria
 * @method     ChildBookSummary[]|ObjectCollection findById(int $id) Return ChildBookSummary objects filtered by the id column
 * @method     ChildBookSummary[]|ObjectCollection findByBookId(int $book_id) Return ChildBookSummary objects filtered by the book_id column
 * @method     ChildBookSummary[]|ObjectCollection findBySummary(string $summary) Return ChildBookSummary objects filtered by the summary column
 * @method     ChildBookSummary[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookSummaryQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookSummaryQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\BookSummary', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookSummaryQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookSummaryQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookSummaryQuery) {
            return $criteria;
        }
        $query = new ChildBookSummaryQuery();
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
     * @return ChildBookSummary|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookSummaryTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookSummaryTableMap::DATABASE_NAME);
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
     * @return ChildBookSummary A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, BOOK_ID, SUMMARY FROM book_summary WHERE ID = :p0';
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
            /** @var ChildBookSummary $obj */
            $obj = new ChildBookSummary();
            $obj->hydrate($row);
            BookSummaryTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBookSummary|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(BookSummaryTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(BookSummaryTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(BookSummaryTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(BookSummaryTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookSummaryTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the book_id column
     *
     * Example usage:
     * <code>
     * $query->filterByBookId(1234); // WHERE book_id = 1234
     * $query->filterByBookId(array(12, 34)); // WHERE book_id IN (12, 34)
     * $query->filterByBookId(array('min' => 12)); // WHERE book_id > 12
     * </code>
     *
     * @see       filterBySummarizedBook()
     *
     * @param     mixed $bookId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function filterByBookId($bookId = null, $comparison = null)
    {
        if (is_array($bookId)) {
            $useMinMax = false;
            if (isset($bookId['min'])) {
                $this->addUsingAlias(BookSummaryTableMap::COL_BOOK_ID, $bookId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bookId['max'])) {
                $this->addUsingAlias(BookSummaryTableMap::COL_BOOK_ID, $bookId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookSummaryTableMap::COL_BOOK_ID, $bookId, $comparison);
    }

    /**
     * Filter the query on the summary column
     *
     * Example usage:
     * <code>
     * $query->filterBySummary('fooValue');   // WHERE summary = 'fooValue'
     * $query->filterBySummary('%fooValue%'); // WHERE summary LIKE '%fooValue%'
     * </code>
     *
     * @param     string $summary The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function filterBySummary($summary = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($summary)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $summary)) {
                $summary = str_replace('*', '%', $summary);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookSummaryTableMap::COL_SUMMARY, $summary, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Book object
     *
     * @param \Propel\Tests\Bookstore\Book|ObjectCollection $book The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookSummaryQuery The current query, for fluid interface
     */
    public function filterBySummarizedBook($book, $comparison = null)
    {
        if ($book instanceof \Propel\Tests\Bookstore\Book) {
            return $this
                ->addUsingAlias(BookSummaryTableMap::COL_BOOK_ID, $book->getId(), $comparison);
        } elseif ($book instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookSummaryTableMap::COL_BOOK_ID, $book->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterBySummarizedBook() only accepts arguments of type \Propel\Tests\Bookstore\Book or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the SummarizedBook relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function joinSummarizedBook($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('SummarizedBook');

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
            $this->addJoinObject($join, 'SummarizedBook');
        }

        return $this;
    }

    /**
     * Use the SummarizedBook relation Book object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookQuery A secondary query class using the current class as primary query
     */
    public function useSummarizedBookQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinSummarizedBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'SummarizedBook', '\Propel\Tests\Bookstore\BookQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBookSummary $bookSummary Object to remove from the list of results
     *
     * @return $this|ChildBookSummaryQuery The current query, for fluid interface
     */
    public function prune($bookSummary = null)
    {
        if ($bookSummary) {
            $this->addUsingAlias(BookSummaryTableMap::COL_ID, $bookSummary->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the book_summary table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookSummaryTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookSummaryTableMap::clearInstancePool();
            BookSummaryTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookSummaryTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookSummaryTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookSummaryTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookSummaryTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookSummaryQuery
