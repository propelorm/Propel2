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
use Propel\Tests\Bookstore\BookListFavorite as ChildBookListFavorite;
use Propel\Tests\Bookstore\BookListFavoriteQuery as ChildBookListFavoriteQuery;
use Propel\Tests\Bookstore\Map\BookListFavoriteTableMap;

/**
 * Base class that represents a query for the 'book_club_list_favorite_books' table.
 *
 * Another cross-reference table for many-to-many relationship between book rows and book_club_list rows for favorite books.
 *
 * @method     ChildBookListFavoriteQuery orderByBookId($order = Criteria::ASC) Order by the book_id column
 * @method     ChildBookListFavoriteQuery orderByBookClubListId($order = Criteria::ASC) Order by the book_club_list_id column
 *
 * @method     ChildBookListFavoriteQuery groupByBookId() Group by the book_id column
 * @method     ChildBookListFavoriteQuery groupByBookClubListId() Group by the book_club_list_id column
 *
 * @method     ChildBookListFavoriteQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookListFavoriteQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookListFavoriteQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookListFavoriteQuery leftJoinFavoriteBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the FavoriteBook relation
 * @method     ChildBookListFavoriteQuery rightJoinFavoriteBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the FavoriteBook relation
 * @method     ChildBookListFavoriteQuery innerJoinFavoriteBook($relationAlias = null) Adds a INNER JOIN clause to the query using the FavoriteBook relation
 *
 * @method     ChildBookListFavoriteQuery leftJoinFavoriteBookClubList($relationAlias = null) Adds a LEFT JOIN clause to the query using the FavoriteBookClubList relation
 * @method     ChildBookListFavoriteQuery rightJoinFavoriteBookClubList($relationAlias = null) Adds a RIGHT JOIN clause to the query using the FavoriteBookClubList relation
 * @method     ChildBookListFavoriteQuery innerJoinFavoriteBookClubList($relationAlias = null) Adds a INNER JOIN clause to the query using the FavoriteBookClubList relation
 *
 * @method     \Propel\Tests\Bookstore\BookQuery|\Propel\Tests\Bookstore\BookClubListQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookListFavorite findOne(ConnectionInterface $con = null) Return the first ChildBookListFavorite matching the query
 * @method     ChildBookListFavorite findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookListFavorite matching the query, or a new ChildBookListFavorite object populated from the query conditions when no match is found
 *
 * @method     ChildBookListFavorite findOneByBookId(int $book_id) Return the first ChildBookListFavorite filtered by the book_id column
 * @method     ChildBookListFavorite findOneByBookClubListId(int $book_club_list_id) Return the first ChildBookListFavorite filtered by the book_club_list_id column
 *
 * @method     ChildBookListFavorite[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookListFavorite objects based on current ModelCriteria
 * @method     ChildBookListFavorite[]|ObjectCollection findByBookId(int $book_id) Return ChildBookListFavorite objects filtered by the book_id column
 * @method     ChildBookListFavorite[]|ObjectCollection findByBookClubListId(int $book_club_list_id) Return ChildBookListFavorite objects filtered by the book_club_list_id column
 * @method     ChildBookListFavorite[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookListFavoriteQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookListFavoriteQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\BookListFavorite', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookListFavoriteQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookListFavoriteQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookListFavoriteQuery) {
            return $criteria;
        }
        $query = new ChildBookListFavoriteQuery();
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
     * $obj = $c->findPk(array(12, 34), $con);
     * </code>
     *
     * @param array[$book_id, $book_club_list_id] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildBookListFavorite|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookListFavoriteTableMap::getInstanceFromPool(serialize(array((string) $key[0], (string) $key[1]))))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookListFavoriteTableMap::DATABASE_NAME);
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
     * @return ChildBookListFavorite A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT BOOK_ID, BOOK_CLUB_LIST_ID FROM book_club_list_favorite_books WHERE BOOK_ID = :p0 AND BOOK_CLUB_LIST_ID = :p1';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_INT);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildBookListFavorite $obj */
            $obj = new ChildBookListFavorite();
            $obj->hydrate($row);
            BookListFavoriteTableMap::addInstanceToPool($obj, serialize(array((string) $key[0], (string) $key[1])));
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
     * @return ChildBookListFavorite|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(BookListFavoriteTableMap::COL_BOOK_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
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
     * @see       filterByFavoriteBook()
     *
     * @param     mixed $bookId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function filterByBookId($bookId = null, $comparison = null)
    {
        if (is_array($bookId)) {
            $useMinMax = false;
            if (isset($bookId['min'])) {
                $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_ID, $bookId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bookId['max'])) {
                $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_ID, $bookId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_ID, $bookId, $comparison);
    }

    /**
     * Filter the query on the book_club_list_id column
     *
     * Example usage:
     * <code>
     * $query->filterByBookClubListId(1234); // WHERE book_club_list_id = 1234
     * $query->filterByBookClubListId(array(12, 34)); // WHERE book_club_list_id IN (12, 34)
     * $query->filterByBookClubListId(array('min' => 12)); // WHERE book_club_list_id > 12
     * </code>
     *
     * @see       filterByFavoriteBookClubList()
     *
     * @param     mixed $bookClubListId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function filterByBookClubListId($bookClubListId = null, $comparison = null)
    {
        if (is_array($bookClubListId)) {
            $useMinMax = false;
            if (isset($bookClubListId['min'])) {
                $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $bookClubListId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bookClubListId['max'])) {
                $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $bookClubListId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $bookClubListId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Book object
     *
     * @param \Propel\Tests\Bookstore\Book|ObjectCollection $book The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function filterByFavoriteBook($book, $comparison = null)
    {
        if ($book instanceof \Propel\Tests\Bookstore\Book) {
            return $this
                ->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_ID, $book->getId(), $comparison);
        } elseif ($book instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_ID, $book->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByFavoriteBook() only accepts arguments of type \Propel\Tests\Bookstore\Book or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the FavoriteBook relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function joinFavoriteBook($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('FavoriteBook');

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
            $this->addJoinObject($join, 'FavoriteBook');
        }

        return $this;
    }

    /**
     * Use the FavoriteBook relation Book object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookQuery A secondary query class using the current class as primary query
     */
    public function useFavoriteBookQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinFavoriteBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'FavoriteBook', '\Propel\Tests\Bookstore\BookQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookClubList object
     *
     * @param \Propel\Tests\Bookstore\BookClubList|ObjectCollection $bookClubList The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function filterByFavoriteBookClubList($bookClubList, $comparison = null)
    {
        if ($bookClubList instanceof \Propel\Tests\Bookstore\BookClubList) {
            return $this
                ->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $bookClubList->getId(), $comparison);
        } elseif ($bookClubList instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID, $bookClubList->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByFavoriteBookClubList() only accepts arguments of type \Propel\Tests\Bookstore\BookClubList or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the FavoriteBookClubList relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function joinFavoriteBookClubList($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('FavoriteBookClubList');

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
            $this->addJoinObject($join, 'FavoriteBookClubList');
        }

        return $this;
    }

    /**
     * Use the FavoriteBookClubList relation BookClubList object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookClubListQuery A secondary query class using the current class as primary query
     */
    public function useFavoriteBookClubListQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinFavoriteBookClubList($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'FavoriteBookClubList', '\Propel\Tests\Bookstore\BookClubListQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBookListFavorite $bookListFavorite Object to remove from the list of results
     *
     * @return $this|ChildBookListFavoriteQuery The current query, for fluid interface
     */
    public function prune($bookListFavorite = null)
    {
        if ($bookListFavorite) {
            $this->addCond('pruneCond0', $this->getAliasedColName(BookListFavoriteTableMap::COL_BOOK_ID), $bookListFavorite->getBookId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(BookListFavoriteTableMap::COL_BOOK_CLUB_LIST_ID), $bookListFavorite->getBookClubListId(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the book_club_list_favorite_books table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookListFavoriteTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookListFavoriteTableMap::clearInstancePool();
            BookListFavoriteTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookListFavoriteTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookListFavoriteTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookListFavoriteTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookListFavoriteTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookListFavoriteQuery
