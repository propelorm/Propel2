<?php

namespace Propel\Tests\Bookstore\Behavior\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\ConcreteAuthor as ChildConcreteAuthor;
use Propel\Tests\Bookstore\Behavior\ConcreteAuthorQuery as ChildConcreteAuthorQuery;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteAuthorTableMap;

/**
 * Base class that represents a query for the 'concrete_author' table.
 *
 *
 *
 * @method     ChildConcreteAuthorQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildConcreteAuthorQuery orderByName($order = Criteria::ASC) Order by the name column
 *
 * @method     ChildConcreteAuthorQuery groupById() Group by the id column
 * @method     ChildConcreteAuthorQuery groupByName() Group by the name column
 *
 * @method     ChildConcreteAuthorQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildConcreteAuthorQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildConcreteAuthorQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildConcreteAuthorQuery leftJoinArticle($relationAlias = null) Adds a LEFT JOIN clause to the query using the Article relation
 * @method     ChildConcreteAuthorQuery rightJoinArticle($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Article relation
 * @method     ChildConcreteAuthorQuery innerJoinArticle($relationAlias = null) Adds a INNER JOIN clause to the query using the Article relation
 *
 * @method     ChildConcreteAuthorQuery leftJoinConcreteNews($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteNews relation
 * @method     ChildConcreteAuthorQuery rightJoinConcreteNews($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteNews relation
 * @method     ChildConcreteAuthorQuery innerJoinConcreteNews($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteNews relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery|\Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildConcreteAuthor findOne(ConnectionInterface $con = null) Return the first ChildConcreteAuthor matching the query
 * @method     ChildConcreteAuthor findOneOrCreate(ConnectionInterface $con = null) Return the first ChildConcreteAuthor matching the query, or a new ChildConcreteAuthor object populated from the query conditions when no match is found
 *
 * @method     ChildConcreteAuthor findOneById(int $id) Return the first ChildConcreteAuthor filtered by the id column
 * @method     ChildConcreteAuthor findOneByName(string $name) Return the first ChildConcreteAuthor filtered by the name column
 *
 * @method     ChildConcreteAuthor[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildConcreteAuthor objects based on current ModelCriteria
 * @method     ChildConcreteAuthor[]|ObjectCollection findById(int $id) Return ChildConcreteAuthor objects filtered by the id column
 * @method     ChildConcreteAuthor[]|ObjectCollection findByName(string $name) Return ChildConcreteAuthor objects filtered by the name column
 * @method     ChildConcreteAuthor[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ConcreteAuthorQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ConcreteAuthorQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteAuthor', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildConcreteAuthorQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildConcreteAuthorQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildConcreteAuthorQuery) {
            return $criteria;
        }
        $query = new ChildConcreteAuthorQuery();
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
     * @return ChildConcreteAuthor|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ConcreteAuthorTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ConcreteAuthorTableMap::DATABASE_NAME);
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
     * @return ChildConcreteAuthor A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, NAME FROM concrete_author WHERE ID = :p0';
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
            /** @var ChildConcreteAuthor $obj */
            $obj = new ChildConcreteAuthor();
            $obj->hydrate($row);
            ConcreteAuthorTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildConcreteAuthor|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ConcreteAuthorTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteArticle object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteArticle|ObjectCollection $concreteArticle  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function filterByArticle($concreteArticle, $comparison = null)
    {
        if ($concreteArticle instanceof \Propel\Tests\Bookstore\Behavior\ConcreteArticle) {
            return $this
                ->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $concreteArticle->getAuthorId(), $comparison);
        } elseif ($concreteArticle instanceof ObjectCollection) {
            return $this
                ->useArticleQuery()
                ->filterByPrimaryKeys($concreteArticle->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByArticle() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ConcreteArticle or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Article relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function joinArticle($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Article');

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
            $this->addJoinObject($join, 'Article');
        }

        return $this;
    }

    /**
     * Use the Article relation ConcreteArticle object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery A secondary query class using the current class as primary query
     */
    public function useArticleQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinArticle($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Article', '\Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteNews object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteNews|ObjectCollection $concreteNews  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function filterByConcreteNews($concreteNews, $comparison = null)
    {
        if ($concreteNews instanceof \Propel\Tests\Bookstore\Behavior\ConcreteNews) {
            return $this
                ->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $concreteNews->getAuthorId(), $comparison);
        } elseif ($concreteNews instanceof ObjectCollection) {
            return $this
                ->useConcreteNewsQuery()
                ->filterByPrimaryKeys($concreteNews->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByConcreteNews() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ConcreteNews or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ConcreteNews relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function joinConcreteNews($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ConcreteNews');

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
            $this->addJoinObject($join, 'ConcreteNews');
        }

        return $this;
    }

    /**
     * Use the ConcreteNews relation ConcreteNews object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery A secondary query class using the current class as primary query
     */
    public function useConcreteNewsQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinConcreteNews($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ConcreteNews', '\Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildConcreteAuthor $concreteAuthor Object to remove from the list of results
     *
     * @return $this|ChildConcreteAuthorQuery The current query, for fluid interface
     */
    public function prune($concreteAuthor = null)
    {
        if ($concreteAuthor) {
            $this->addUsingAlias(ConcreteAuthorTableMap::COL_ID, $concreteAuthor->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the concrete_author table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteAuthorTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ConcreteAuthorTableMap::clearInstancePool();
            ConcreteAuthorTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteAuthorTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ConcreteAuthorTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ConcreteAuthorTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ConcreteAuthorTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ConcreteAuthorQuery
