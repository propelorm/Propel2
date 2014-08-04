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
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery as ChildConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizz as ChildConcreteQuizz;
use Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuery as ChildConcreteQuizzQuery;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteQuizzTableMap;

/**
 * Base class that represents a query for the 'concrete_quizz' table.
 *
 *
 *
 * @method     ChildConcreteQuizzQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildConcreteQuizzQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildConcreteQuizzQuery orderByCategoryId($order = Criteria::ASC) Order by the category_id column
 *
 * @method     ChildConcreteQuizzQuery groupByTitle() Group by the title column
 * @method     ChildConcreteQuizzQuery groupById() Group by the id column
 * @method     ChildConcreteQuizzQuery groupByCategoryId() Group by the category_id column
 *
 * @method     ChildConcreteQuizzQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildConcreteQuizzQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildConcreteQuizzQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildConcreteQuizzQuery leftJoinConcreteCategory($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteCategory relation
 * @method     ChildConcreteQuizzQuery rightJoinConcreteCategory($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteCategory relation
 * @method     ChildConcreteQuizzQuery innerJoinConcreteCategory($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteCategory relation
 *
 * @method     ChildConcreteQuizzQuery leftJoinConcreteQuizzQuestion($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteQuizzQuestion relation
 * @method     ChildConcreteQuizzQuery rightJoinConcreteQuizzQuestion($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteQuizzQuestion relation
 * @method     ChildConcreteQuizzQuery innerJoinConcreteQuizzQuestion($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteQuizzQuestion relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery|\Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestionQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildConcreteQuizz findOne(ConnectionInterface $con = null) Return the first ChildConcreteQuizz matching the query
 * @method     ChildConcreteQuizz findOneOrCreate(ConnectionInterface $con = null) Return the first ChildConcreteQuizz matching the query, or a new ChildConcreteQuizz object populated from the query conditions when no match is found
 *
 * @method     ChildConcreteQuizz findOneByTitle(string $title) Return the first ChildConcreteQuizz filtered by the title column
 * @method     ChildConcreteQuizz findOneById(int $id) Return the first ChildConcreteQuizz filtered by the id column
 * @method     ChildConcreteQuizz findOneByCategoryId(int $category_id) Return the first ChildConcreteQuizz filtered by the category_id column
 *
 * @method     ChildConcreteQuizz[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildConcreteQuizz objects based on current ModelCriteria
 * @method     ChildConcreteQuizz[]|ObjectCollection findByTitle(string $title) Return ChildConcreteQuizz objects filtered by the title column
 * @method     ChildConcreteQuizz[]|ObjectCollection findById(int $id) Return ChildConcreteQuizz objects filtered by the id column
 * @method     ChildConcreteQuizz[]|ObjectCollection findByCategoryId(int $category_id) Return ChildConcreteQuizz objects filtered by the category_id column
 * @method     ChildConcreteQuizz[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ConcreteQuizzQuery extends ChildConcreteContentQuery
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ConcreteQuizzQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteQuizz', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildConcreteQuizzQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildConcreteQuizzQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildConcreteQuizzQuery) {
            return $criteria;
        }
        $query = new ChildConcreteQuizzQuery();
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
     * @return ChildConcreteQuizz|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ConcreteQuizzTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ConcreteQuizzTableMap::DATABASE_NAME);
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
     * @return ChildConcreteQuizz A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT TITLE, ID, CATEGORY_ID FROM concrete_quizz WHERE ID = :p0';
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
            /** @var ChildConcreteQuizz $obj */
            $obj = new ChildConcreteQuizz();
            $obj->hydrate($row);
            ConcreteQuizzTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildConcreteQuizz|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ConcreteQuizzTableMap::COL_TITLE, $title, $comparison);
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
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the category_id column
     *
     * Example usage:
     * <code>
     * $query->filterByCategoryId(1234); // WHERE category_id = 1234
     * $query->filterByCategoryId(array(12, 34)); // WHERE category_id IN (12, 34)
     * $query->filterByCategoryId(array('min' => 12)); // WHERE category_id > 12
     * </code>
     *
     * @see       filterByConcreteCategory()
     *
     * @param     mixed $categoryId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function filterByCategoryId($categoryId = null, $comparison = null)
    {
        if (is_array($categoryId)) {
            $useMinMax = false;
            if (isset($categoryId['min'])) {
                $this->addUsingAlias(ConcreteQuizzTableMap::COL_CATEGORY_ID, $categoryId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($categoryId['max'])) {
                $this->addUsingAlias(ConcreteQuizzTableMap::COL_CATEGORY_ID, $categoryId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ConcreteQuizzTableMap::COL_CATEGORY_ID, $categoryId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteCategory object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteCategory|ObjectCollection $concreteCategory The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function filterByConcreteCategory($concreteCategory, $comparison = null)
    {
        if ($concreteCategory instanceof \Propel\Tests\Bookstore\Behavior\ConcreteCategory) {
            return $this
                ->addUsingAlias(ConcreteQuizzTableMap::COL_CATEGORY_ID, $concreteCategory->getId(), $comparison);
        } elseif ($concreteCategory instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ConcreteQuizzTableMap::COL_CATEGORY_ID, $concreteCategory->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByConcreteCategory() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ConcreteCategory or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ConcreteCategory relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function joinConcreteCategory($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ConcreteCategory');

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
            $this->addJoinObject($join, 'ConcreteCategory');
        }

        return $this;
    }

    /**
     * Use the ConcreteCategory relation ConcreteCategory object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery A secondary query class using the current class as primary query
     */
    public function useConcreteCategoryQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinConcreteCategory($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ConcreteCategory', '\Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestion object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestion|ObjectCollection $concreteQuizzQuestion  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function filterByConcreteQuizzQuestion($concreteQuizzQuestion, $comparison = null)
    {
        if ($concreteQuizzQuestion instanceof \Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestion) {
            return $this
                ->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $concreteQuizzQuestion->getQuizzId(), $comparison);
        } elseif ($concreteQuizzQuestion instanceof ObjectCollection) {
            return $this
                ->useConcreteQuizzQuestionQuery()
                ->filterByPrimaryKeys($concreteQuizzQuestion->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByConcreteQuizzQuestion() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestion or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ConcreteQuizzQuestion relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function joinConcreteQuizzQuestion($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ConcreteQuizzQuestion');

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
            $this->addJoinObject($join, 'ConcreteQuizzQuestion');
        }

        return $this;
    }

    /**
     * Use the ConcreteQuizzQuestion relation ConcreteQuizzQuestion object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestionQuery A secondary query class using the current class as primary query
     */
    public function useConcreteQuizzQuestionQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinConcreteQuizzQuestion($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ConcreteQuizzQuestion', '\Propel\Tests\Bookstore\Behavior\ConcreteQuizzQuestionQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildConcreteQuizz $concreteQuizz Object to remove from the list of results
     *
     * @return $this|ChildConcreteQuizzQuery The current query, for fluid interface
     */
    public function prune($concreteQuizz = null)
    {
        if ($concreteQuizz) {
            $this->addUsingAlias(ConcreteQuizzTableMap::COL_ID, $concreteQuizz->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the concrete_quizz table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteQuizzTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ConcreteQuizzTableMap::clearInstancePool();
            ConcreteQuizzTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteQuizzTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ConcreteQuizzTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ConcreteQuizzTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ConcreteQuizzTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ConcreteQuizzQuery
