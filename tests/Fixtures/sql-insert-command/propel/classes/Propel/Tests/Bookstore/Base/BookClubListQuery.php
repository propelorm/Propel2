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
use Propel\Tests\Bookstore\BookClubList as ChildBookClubList;
use Propel\Tests\Bookstore\BookClubListQuery as ChildBookClubListQuery;
use Propel\Tests\Bookstore\Map\BookClubListTableMap;

/**
 * Base class that represents a query for the 'book_club_list' table.
 *
 * Reading list for a book club.
 *
 * @method     ChildBookClubListQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBookClubListQuery orderByGroupLeader($order = Criteria::ASC) Order by the group_leader column
 * @method     ChildBookClubListQuery orderByTheme($order = Criteria::ASC) Order by the theme column
 * @method     ChildBookClubListQuery orderByCreatedAt($order = Criteria::ASC) Order by the created_at column
 *
 * @method     ChildBookClubListQuery groupById() Group by the id column
 * @method     ChildBookClubListQuery groupByGroupLeader() Group by the group_leader column
 * @method     ChildBookClubListQuery groupByTheme() Group by the theme column
 * @method     ChildBookClubListQuery groupByCreatedAt() Group by the created_at column
 *
 * @method     ChildBookClubListQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookClubListQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookClubListQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookClubListQuery leftJoinBookListRel($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookListRel relation
 * @method     ChildBookClubListQuery rightJoinBookListRel($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookListRel relation
 * @method     ChildBookClubListQuery innerJoinBookListRel($relationAlias = null) Adds a INNER JOIN clause to the query using the BookListRel relation
 *
 * @method     ChildBookClubListQuery leftJoinBookListFavorite($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookListFavorite relation
 * @method     ChildBookClubListQuery rightJoinBookListFavorite($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookListFavorite relation
 * @method     ChildBookClubListQuery innerJoinBookListFavorite($relationAlias = null) Adds a INNER JOIN clause to the query using the BookListFavorite relation
 *
 * @method     \Propel\Tests\Bookstore\BookListRelQuery|\Propel\Tests\Bookstore\BookListFavoriteQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookClubList findOne(ConnectionInterface $con = null) Return the first ChildBookClubList matching the query
 * @method     ChildBookClubList findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookClubList matching the query, or a new ChildBookClubList object populated from the query conditions when no match is found
 *
 * @method     ChildBookClubList findOneById(int $id) Return the first ChildBookClubList filtered by the id column
 * @method     ChildBookClubList findOneByGroupLeader(string $group_leader) Return the first ChildBookClubList filtered by the group_leader column
 * @method     ChildBookClubList findOneByTheme(string $theme) Return the first ChildBookClubList filtered by the theme column
 * @method     ChildBookClubList findOneByCreatedAt(string $created_at) Return the first ChildBookClubList filtered by the created_at column
 *
 * @method     ChildBookClubList[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookClubList objects based on current ModelCriteria
 * @method     ChildBookClubList[]|ObjectCollection findById(int $id) Return ChildBookClubList objects filtered by the id column
 * @method     ChildBookClubList[]|ObjectCollection findByGroupLeader(string $group_leader) Return ChildBookClubList objects filtered by the group_leader column
 * @method     ChildBookClubList[]|ObjectCollection findByTheme(string $theme) Return ChildBookClubList objects filtered by the theme column
 * @method     ChildBookClubList[]|ObjectCollection findByCreatedAt(string $created_at) Return ChildBookClubList objects filtered by the created_at column
 * @method     ChildBookClubList[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookClubListQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookClubListQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\BookClubList', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookClubListQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookClubListQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookClubListQuery) {
            return $criteria;
        }
        $query = new ChildBookClubListQuery();
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
     * @return ChildBookClubList|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookClubListTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookClubListTableMap::DATABASE_NAME);
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
     * @return ChildBookClubList A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, GROUP_LEADER, THEME, CREATED_AT FROM book_club_list WHERE ID = :p0';
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
            /** @var ChildBookClubList $obj */
            $obj = new ChildBookClubList();
            $obj->hydrate($row);
            BookClubListTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBookClubList|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(BookClubListTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(BookClubListTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(BookClubListTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(BookClubListTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookClubListTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the group_leader column
     *
     * Example usage:
     * <code>
     * $query->filterByGroupLeader('fooValue');   // WHERE group_leader = 'fooValue'
     * $query->filterByGroupLeader('%fooValue%'); // WHERE group_leader LIKE '%fooValue%'
     * </code>
     *
     * @param     string $groupLeader The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByGroupLeader($groupLeader = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($groupLeader)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $groupLeader)) {
                $groupLeader = str_replace('*', '%', $groupLeader);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookClubListTableMap::COL_GROUP_LEADER, $groupLeader, $comparison);
    }

    /**
     * Filter the query on the theme column
     *
     * Example usage:
     * <code>
     * $query->filterByTheme('fooValue');   // WHERE theme = 'fooValue'
     * $query->filterByTheme('%fooValue%'); // WHERE theme LIKE '%fooValue%'
     * </code>
     *
     * @param     string $theme The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByTheme($theme = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($theme)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $theme)) {
                $theme = str_replace('*', '%', $theme);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookClubListTableMap::COL_THEME, $theme, $comparison);
    }

    /**
     * Filter the query on the created_at column
     *
     * Example usage:
     * <code>
     * $query->filterByCreatedAt('2011-03-14'); // WHERE created_at = '2011-03-14'
     * $query->filterByCreatedAt('now'); // WHERE created_at = '2011-03-14'
     * $query->filterByCreatedAt(array('max' => 'yesterday')); // WHERE created_at > '2011-03-13'
     * </code>
     *
     * @param     mixed $createdAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByCreatedAt($createdAt = null, $comparison = null)
    {
        if (is_array($createdAt)) {
            $useMinMax = false;
            if (isset($createdAt['min'])) {
                $this->addUsingAlias(BookClubListTableMap::COL_CREATED_AT, $createdAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdAt['max'])) {
                $this->addUsingAlias(BookClubListTableMap::COL_CREATED_AT, $createdAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookClubListTableMap::COL_CREATED_AT, $createdAt, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookListRel object
     *
     * @param \Propel\Tests\Bookstore\BookListRel|ObjectCollection $bookListRel  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByBookListRel($bookListRel, $comparison = null)
    {
        if ($bookListRel instanceof \Propel\Tests\Bookstore\BookListRel) {
            return $this
                ->addUsingAlias(BookClubListTableMap::COL_ID, $bookListRel->getBookClubListId(), $comparison);
        } elseif ($bookListRel instanceof ObjectCollection) {
            return $this
                ->useBookListRelQuery()
                ->filterByPrimaryKeys($bookListRel->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookListRel() only accepts arguments of type \Propel\Tests\Bookstore\BookListRel or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookListRel relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function joinBookListRel($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookListRel');

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
            $this->addJoinObject($join, 'BookListRel');
        }

        return $this;
    }

    /**
     * Use the BookListRel relation BookListRel object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookListRelQuery A secondary query class using the current class as primary query
     */
    public function useBookListRelQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookListRel($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookListRel', '\Propel\Tests\Bookstore\BookListRelQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookListFavorite object
     *
     * @param \Propel\Tests\Bookstore\BookListFavorite|ObjectCollection $bookListFavorite  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByBookListFavorite($bookListFavorite, $comparison = null)
    {
        if ($bookListFavorite instanceof \Propel\Tests\Bookstore\BookListFavorite) {
            return $this
                ->addUsingAlias(BookClubListTableMap::COL_ID, $bookListFavorite->getBookClubListId(), $comparison);
        } elseif ($bookListFavorite instanceof ObjectCollection) {
            return $this
                ->useBookListFavoriteQuery()
                ->filterByPrimaryKeys($bookListFavorite->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookListFavorite() only accepts arguments of type \Propel\Tests\Bookstore\BookListFavorite or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookListFavorite relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function joinBookListFavorite($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookListFavorite');

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
            $this->addJoinObject($join, 'BookListFavorite');
        }

        return $this;
    }

    /**
     * Use the BookListFavorite relation BookListFavorite object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookListFavoriteQuery A secondary query class using the current class as primary query
     */
    public function useBookListFavoriteQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookListFavorite($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookListFavorite', '\Propel\Tests\Bookstore\BookListFavoriteQuery');
    }

    /**
     * Filter the query by a related Book object
     * using the book_x_list table as cross reference
     *
     * @param Book $book the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByBook($book, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useBookListRelQuery()
            ->filterByBook($book, $comparison)
            ->endUse();
    }

    /**
     * Filter the query by a related Book object
     * using the book_club_list_favorite_books table as cross reference
     *
     * @param Book $book the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookClubListQuery The current query, for fluid interface
     */
    public function filterByFavoriteBook($book, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useBookListFavoriteQuery()
            ->filterByFavoriteBook($book, $comparison)
            ->endUse();
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBookClubList $bookClubList Object to remove from the list of results
     *
     * @return $this|ChildBookClubListQuery The current query, for fluid interface
     */
    public function prune($bookClubList = null)
    {
        if ($bookClubList) {
            $this->addUsingAlias(BookClubListTableMap::COL_ID, $bookClubList->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the book_club_list table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookClubListTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookClubListTableMap::clearInstancePool();
            BookClubListTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookClubListTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookClubListTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookClubListTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookClubListTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookClubListQuery
