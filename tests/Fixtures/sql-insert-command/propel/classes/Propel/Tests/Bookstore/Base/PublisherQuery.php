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
use Propel\Tests\Bookstore\Publisher as ChildPublisher;
use Propel\Tests\Bookstore\PublisherQuery as ChildPublisherQuery;
use Propel\Tests\Bookstore\Map\PublisherTableMap;

/**
 * Base class that represents a query for the 'publisher' table.
 *
 * Publisher Table
 *
 * @method     ChildPublisherQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildPublisherQuery orderByName($order = Criteria::ASC) Order by the name column
 *
 * @method     ChildPublisherQuery groupById() Group by the id column
 * @method     ChildPublisherQuery groupByName() Group by the name column
 *
 * @method     ChildPublisherQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildPublisherQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildPublisherQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildPublisherQuery leftJoinBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the Book relation
 * @method     ChildPublisherQuery rightJoinBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Book relation
 * @method     ChildPublisherQuery innerJoinBook($relationAlias = null) Adds a INNER JOIN clause to the query using the Book relation
 *
 * @method     ChildPublisherQuery leftJoinBookstoreSale($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreSale relation
 * @method     ChildPublisherQuery rightJoinBookstoreSale($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreSale relation
 * @method     ChildPublisherQuery innerJoinBookstoreSale($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreSale relation
 *
 * @method     \Propel\Tests\Bookstore\BookQuery|\Propel\Tests\Bookstore\BookstoreSaleQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildPublisher findOne(ConnectionInterface $con = null) Return the first ChildPublisher matching the query
 * @method     ChildPublisher findOneOrCreate(ConnectionInterface $con = null) Return the first ChildPublisher matching the query, or a new ChildPublisher object populated from the query conditions when no match is found
 *
 * @method     ChildPublisher findOneById(int $id) Return the first ChildPublisher filtered by the id column
 * @method     ChildPublisher findOneByName(string $name) Return the first ChildPublisher filtered by the name column
 *
 * @method     ChildPublisher[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildPublisher objects based on current ModelCriteria
 * @method     ChildPublisher[]|ObjectCollection findById(int $id) Return ChildPublisher objects filtered by the id column
 * @method     ChildPublisher[]|ObjectCollection findByName(string $name) Return ChildPublisher objects filtered by the name column
 * @method     ChildPublisher[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class PublisherQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\PublisherQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Publisher', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildPublisherQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildPublisherQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildPublisherQuery) {
            return $criteria;
        }
        $query = new ChildPublisherQuery();
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
     * @return ChildPublisher|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = PublisherTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(PublisherTableMap::DATABASE_NAME);
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
     * @return ChildPublisher A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, NAME FROM publisher WHERE ID = :p0';
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
            /** @var ChildPublisher $obj */
            $obj = new ChildPublisher();
            $obj->hydrate($row);
            PublisherTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildPublisher|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildPublisherQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(PublisherTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildPublisherQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(PublisherTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildPublisherQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(PublisherTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(PublisherTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PublisherTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the name column
     *
     * Example usage:
     * <code>
     * $query->filterByName('fooValue');   // WHERE name = 'fooValue'
     * $query->filterByName('%fooValue%'); // WHERE name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $name The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPublisherQuery The current query, for fluid interface
     */
    public function filterByName($name = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($name)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $name)) {
                $name = str_replace('*', '%', $name);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(PublisherTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Book object
     *
     * @param \Propel\Tests\Bookstore\Book|ObjectCollection $book  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildPublisherQuery The current query, for fluid interface
     */
    public function filterByBook($book, $comparison = null)
    {
        if ($book instanceof \Propel\Tests\Bookstore\Book) {
            return $this
                ->addUsingAlias(PublisherTableMap::COL_ID, $book->getPublisherId(), $comparison);
        } elseif ($book instanceof ObjectCollection) {
            return $this
                ->useBookQuery()
                ->filterByPrimaryKeys($book->getPrimaryKeys())
                ->endUse();
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
     * @return $this|ChildPublisherQuery The current query, for fluid interface
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
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreSale object
     *
     * @param \Propel\Tests\Bookstore\BookstoreSale|ObjectCollection $bookstoreSale  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildPublisherQuery The current query, for fluid interface
     */
    public function filterByBookstoreSale($bookstoreSale, $comparison = null)
    {
        if ($bookstoreSale instanceof \Propel\Tests\Bookstore\BookstoreSale) {
            return $this
                ->addUsingAlias(PublisherTableMap::COL_ID, $bookstoreSale->getPublisherId(), $comparison);
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
     * @return $this|ChildPublisherQuery The current query, for fluid interface
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
     * Exclude object from result
     *
     * @param   ChildPublisher $publisher Object to remove from the list of results
     *
     * @return $this|ChildPublisherQuery The current query, for fluid interface
     */
    public function prune($publisher = null)
    {
        if ($publisher) {
            $this->addUsingAlias(PublisherTableMap::COL_ID, $publisher->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the publisher table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(PublisherTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            PublisherTableMap::clearInstancePool();
            PublisherTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(PublisherTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(PublisherTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            PublisherTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            PublisherTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // PublisherQuery
