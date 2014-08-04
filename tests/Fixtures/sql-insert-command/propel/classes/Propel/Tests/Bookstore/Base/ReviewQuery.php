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
use Propel\Tests\Bookstore\Review as ChildReview;
use Propel\Tests\Bookstore\ReviewQuery as ChildReviewQuery;
use Propel\Tests\Bookstore\Map\ReviewTableMap;

/**
 * Base class that represents a query for the 'review' table.
 *
 * Book Review
 *
 * @method     ChildReviewQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildReviewQuery orderByReviewedBy($order = Criteria::ASC) Order by the reviewed_by column
 * @method     ChildReviewQuery orderByReviewDate($order = Criteria::ASC) Order by the review_date column
 * @method     ChildReviewQuery orderByRecommended($order = Criteria::ASC) Order by the recommended column
 * @method     ChildReviewQuery orderByStatus($order = Criteria::ASC) Order by the status column
 * @method     ChildReviewQuery orderByBookId($order = Criteria::ASC) Order by the book_id column
 *
 * @method     ChildReviewQuery groupById() Group by the id column
 * @method     ChildReviewQuery groupByReviewedBy() Group by the reviewed_by column
 * @method     ChildReviewQuery groupByReviewDate() Group by the review_date column
 * @method     ChildReviewQuery groupByRecommended() Group by the recommended column
 * @method     ChildReviewQuery groupByStatus() Group by the status column
 * @method     ChildReviewQuery groupByBookId() Group by the book_id column
 *
 * @method     ChildReviewQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildReviewQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildReviewQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildReviewQuery leftJoinBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the Book relation
 * @method     ChildReviewQuery rightJoinBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Book relation
 * @method     ChildReviewQuery innerJoinBook($relationAlias = null) Adds a INNER JOIN clause to the query using the Book relation
 *
 * @method     \Propel\Tests\Bookstore\BookQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildReview findOne(ConnectionInterface $con = null) Return the first ChildReview matching the query
 * @method     ChildReview findOneOrCreate(ConnectionInterface $con = null) Return the first ChildReview matching the query, or a new ChildReview object populated from the query conditions when no match is found
 *
 * @method     ChildReview findOneById(int $id) Return the first ChildReview filtered by the id column
 * @method     ChildReview findOneByReviewedBy(string $reviewed_by) Return the first ChildReview filtered by the reviewed_by column
 * @method     ChildReview findOneByReviewDate(string $review_date) Return the first ChildReview filtered by the review_date column
 * @method     ChildReview findOneByRecommended(boolean $recommended) Return the first ChildReview filtered by the recommended column
 * @method     ChildReview findOneByStatus(string $status) Return the first ChildReview filtered by the status column
 * @method     ChildReview findOneByBookId(int $book_id) Return the first ChildReview filtered by the book_id column
 *
 * @method     ChildReview[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildReview objects based on current ModelCriteria
 * @method     ChildReview[]|ObjectCollection findById(int $id) Return ChildReview objects filtered by the id column
 * @method     ChildReview[]|ObjectCollection findByReviewedBy(string $reviewed_by) Return ChildReview objects filtered by the reviewed_by column
 * @method     ChildReview[]|ObjectCollection findByReviewDate(string $review_date) Return ChildReview objects filtered by the review_date column
 * @method     ChildReview[]|ObjectCollection findByRecommended(boolean $recommended) Return ChildReview objects filtered by the recommended column
 * @method     ChildReview[]|ObjectCollection findByStatus(string $status) Return ChildReview objects filtered by the status column
 * @method     ChildReview[]|ObjectCollection findByBookId(int $book_id) Return ChildReview objects filtered by the book_id column
 * @method     ChildReview[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ReviewQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\ReviewQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Review', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildReviewQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildReviewQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildReviewQuery) {
            return $criteria;
        }
        $query = new ChildReviewQuery();
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
     * @return ChildReview|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ReviewTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ReviewTableMap::DATABASE_NAME);
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
     * @return ChildReview A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, REVIEWED_BY, REVIEW_DATE, RECOMMENDED, STATUS, BOOK_ID FROM review WHERE ID = :p0';
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
            /** @var ChildReview $obj */
            $obj = new ChildReview();
            $obj->hydrate($row);
            ReviewTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildReview|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ReviewTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ReviewTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ReviewTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ReviewTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ReviewTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the reviewed_by column
     *
     * Example usage:
     * <code>
     * $query->filterByReviewedBy('fooValue');   // WHERE reviewed_by = 'fooValue'
     * $query->filterByReviewedBy('%fooValue%'); // WHERE reviewed_by LIKE '%fooValue%'
     * </code>
     *
     * @param     string $reviewedBy The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByReviewedBy($reviewedBy = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($reviewedBy)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $reviewedBy)) {
                $reviewedBy = str_replace('*', '%', $reviewedBy);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ReviewTableMap::COL_REVIEWED_BY, $reviewedBy, $comparison);
    }

    /**
     * Filter the query on the review_date column
     *
     * Example usage:
     * <code>
     * $query->filterByReviewDate('2011-03-14'); // WHERE review_date = '2011-03-14'
     * $query->filterByReviewDate('now'); // WHERE review_date = '2011-03-14'
     * $query->filterByReviewDate(array('max' => 'yesterday')); // WHERE review_date > '2011-03-13'
     * </code>
     *
     * @param     mixed $reviewDate The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByReviewDate($reviewDate = null, $comparison = null)
    {
        if (is_array($reviewDate)) {
            $useMinMax = false;
            if (isset($reviewDate['min'])) {
                $this->addUsingAlias(ReviewTableMap::COL_REVIEW_DATE, $reviewDate['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($reviewDate['max'])) {
                $this->addUsingAlias(ReviewTableMap::COL_REVIEW_DATE, $reviewDate['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ReviewTableMap::COL_REVIEW_DATE, $reviewDate, $comparison);
    }

    /**
     * Filter the query on the recommended column
     *
     * Example usage:
     * <code>
     * $query->filterByRecommended(true); // WHERE recommended = true
     * $query->filterByRecommended('yes'); // WHERE recommended = true
     * </code>
     *
     * @param     boolean|string $recommended The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByRecommended($recommended = null, $comparison = null)
    {
        if (is_string($recommended)) {
            $recommended = in_array(strtolower($recommended), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(ReviewTableMap::COL_RECOMMENDED, $recommended, $comparison);
    }

    /**
     * Filter the query on the status column
     *
     * Example usage:
     * <code>
     * $query->filterByStatus('fooValue');   // WHERE status = 'fooValue'
     * $query->filterByStatus('%fooValue%'); // WHERE status LIKE '%fooValue%'
     * </code>
     *
     * @param     string $status The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByStatus($status = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($status)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $status)) {
                $status = str_replace('*', '%', $status);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ReviewTableMap::COL_STATUS, $status, $comparison);
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
     * @see       filterByBook()
     *
     * @param     mixed $bookId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function filterByBookId($bookId = null, $comparison = null)
    {
        if (is_array($bookId)) {
            $useMinMax = false;
            if (isset($bookId['min'])) {
                $this->addUsingAlias(ReviewTableMap::COL_BOOK_ID, $bookId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bookId['max'])) {
                $this->addUsingAlias(ReviewTableMap::COL_BOOK_ID, $bookId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ReviewTableMap::COL_BOOK_ID, $bookId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Book object
     *
     * @param \Propel\Tests\Bookstore\Book|ObjectCollection $book The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildReviewQuery The current query, for fluid interface
     */
    public function filterByBook($book, $comparison = null)
    {
        if ($book instanceof \Propel\Tests\Bookstore\Book) {
            return $this
                ->addUsingAlias(ReviewTableMap::COL_BOOK_ID, $book->getId(), $comparison);
        } elseif ($book instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ReviewTableMap::COL_BOOK_ID, $book->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByBook() only accepts arguments of type \Propel\Tests\Bookstore\Book or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Book relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function joinBook($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Book');

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
            $this->addJoinObject($join, 'Book');
        }

        return $this;
    }

    /**
     * Use the Book relation Book object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookQuery A secondary query class using the current class as primary query
     */
    public function useBookQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Book', '\Propel\Tests\Bookstore\BookQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildReview $review Object to remove from the list of results
     *
     * @return $this|ChildReviewQuery The current query, for fluid interface
     */
    public function prune($review = null)
    {
        if ($review) {
            $this->addUsingAlias(ReviewTableMap::COL_ID, $review->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the review table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ReviewTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ReviewTableMap::clearInstancePool();
            ReviewTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ReviewTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ReviewTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ReviewTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ReviewTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ReviewQuery
