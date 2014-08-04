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
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n as ChildValidateTriggerBookI18n;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18nQuery as ChildValidateTriggerBookI18nQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateTriggerBookI18nTableMap;

/**
 * Base class that represents a query for the 'validate_trigger_book_i18n' table.
 *
 *
 *
 * @method     ChildValidateTriggerBookI18nQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildValidateTriggerBookI18nQuery orderByLocale($order = Criteria::ASC) Order by the locale column
 * @method     ChildValidateTriggerBookI18nQuery orderByTitle($order = Criteria::ASC) Order by the title column
 *
 * @method     ChildValidateTriggerBookI18nQuery groupById() Group by the id column
 * @method     ChildValidateTriggerBookI18nQuery groupByLocale() Group by the locale column
 * @method     ChildValidateTriggerBookI18nQuery groupByTitle() Group by the title column
 *
 * @method     ChildValidateTriggerBookI18nQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildValidateTriggerBookI18nQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildValidateTriggerBookI18nQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildValidateTriggerBookI18nQuery leftJoinValidateTriggerBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateTriggerBook relation
 * @method     ChildValidateTriggerBookI18nQuery rightJoinValidateTriggerBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateTriggerBook relation
 * @method     ChildValidateTriggerBookI18nQuery innerJoinValidateTriggerBook($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateTriggerBook relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildValidateTriggerBookI18n findOne(ConnectionInterface $con = null) Return the first ChildValidateTriggerBookI18n matching the query
 * @method     ChildValidateTriggerBookI18n findOneOrCreate(ConnectionInterface $con = null) Return the first ChildValidateTriggerBookI18n matching the query, or a new ChildValidateTriggerBookI18n object populated from the query conditions when no match is found
 *
 * @method     ChildValidateTriggerBookI18n findOneById(int $id) Return the first ChildValidateTriggerBookI18n filtered by the id column
 * @method     ChildValidateTriggerBookI18n findOneByLocale(string $locale) Return the first ChildValidateTriggerBookI18n filtered by the locale column
 * @method     ChildValidateTriggerBookI18n findOneByTitle(string $title) Return the first ChildValidateTriggerBookI18n filtered by the title column
 *
 * @method     ChildValidateTriggerBookI18n[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildValidateTriggerBookI18n objects based on current ModelCriteria
 * @method     ChildValidateTriggerBookI18n[]|ObjectCollection findById(int $id) Return ChildValidateTriggerBookI18n objects filtered by the id column
 * @method     ChildValidateTriggerBookI18n[]|ObjectCollection findByLocale(string $locale) Return ChildValidateTriggerBookI18n objects filtered by the locale column
 * @method     ChildValidateTriggerBookI18n[]|ObjectCollection findByTitle(string $title) Return ChildValidateTriggerBookI18n objects filtered by the title column
 * @method     ChildValidateTriggerBookI18n[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ValidateTriggerBookI18nQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ValidateTriggerBookI18nQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerBookI18n', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildValidateTriggerBookI18nQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildValidateTriggerBookI18nQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildValidateTriggerBookI18nQuery) {
            return $criteria;
        }
        $query = new ChildValidateTriggerBookI18nQuery();
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
     * @param array[$id, $locale] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildValidateTriggerBookI18n|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ValidateTriggerBookI18nTableMap::getInstanceFromPool(serialize(array((string) $key[0], (string) $key[1]))))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ValidateTriggerBookI18nTableMap::DATABASE_NAME);
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
     * @return ChildValidateTriggerBookI18n A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, LOCALE, TITLE FROM validate_trigger_book_i18n WHERE ID = :p0 AND LOCALE = :p1';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_INT);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildValidateTriggerBookI18n $obj */
            $obj = new ChildValidateTriggerBookI18n();
            $obj->hydrate($row);
            ValidateTriggerBookI18nTableMap::addInstanceToPool($obj, serialize(array((string) $key[0], (string) $key[1])));
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
     * @return ChildValidateTriggerBookI18n|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_LOCALE, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(ValidateTriggerBookI18nTableMap::COL_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(ValidateTriggerBookI18nTableMap::COL_LOCALE, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
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
     * @see       filterByValidateTriggerBook()
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the locale column
     *
     * Example usage:
     * <code>
     * $query->filterByLocale('fooValue');   // WHERE locale = 'fooValue'
     * $query->filterByLocale('%fooValue%'); // WHERE locale LIKE '%fooValue%'
     * </code>
     *
     * @param     string $locale The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function filterByLocale($locale = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($locale)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $locale)) {
                $locale = str_replace('*', '%', $locale);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_LOCALE, $locale, $comparison);
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
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook|ObjectCollection $validateTriggerBook The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function filterByValidateTriggerBook($validateTriggerBook, $comparison = null)
    {
        if ($validateTriggerBook instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook) {
            return $this
                ->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_ID, $validateTriggerBook->getId(), $comparison);
        } elseif ($validateTriggerBook instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ValidateTriggerBookI18nTableMap::COL_ID, $validateTriggerBook->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByValidateTriggerBook() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateTriggerBook relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function joinValidateTriggerBook($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateTriggerBook');

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
            $this->addJoinObject($join, 'ValidateTriggerBook');
        }

        return $this;
    }

    /**
     * Use the ValidateTriggerBook relation ValidateTriggerBook object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery A secondary query class using the current class as primary query
     */
    public function useValidateTriggerBookQuery($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        return $this
            ->joinValidateTriggerBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerBook', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildValidateTriggerBookI18n $validateTriggerBookI18n Object to remove from the list of results
     *
     * @return $this|ChildValidateTriggerBookI18nQuery The current query, for fluid interface
     */
    public function prune($validateTriggerBookI18n = null)
    {
        if ($validateTriggerBookI18n) {
            $this->addCond('pruneCond0', $this->getAliasedColName(ValidateTriggerBookI18nTableMap::COL_ID), $validateTriggerBookI18n->getId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(ValidateTriggerBookI18nTableMap::COL_LOCALE), $validateTriggerBookI18n->getLocale(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the validate_trigger_book_i18n table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerBookI18nTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ValidateTriggerBookI18nTableMap::clearInstancePool();
            ValidateTriggerBookI18nTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerBookI18nTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ValidateTriggerBookI18nTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ValidateTriggerBookI18nTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ValidateTriggerBookI18nTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ValidateTriggerBookI18nQuery
