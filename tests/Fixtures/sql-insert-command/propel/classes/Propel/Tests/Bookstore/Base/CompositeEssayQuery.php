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
use Propel\Tests\Bookstore\CompositeEssay as ChildCompositeEssay;
use Propel\Tests\Bookstore\CompositeEssayQuery as ChildCompositeEssayQuery;
use Propel\Tests\Bookstore\Map\CompositeEssayTableMap;

/**
 * Base class that represents a query for the 'composite_essay' table.
 *
 *
 *
 * @method     ChildCompositeEssayQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildCompositeEssayQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildCompositeEssayQuery orderByFirstEssayId($order = Criteria::ASC) Order by the first_essay_id column
 * @method     ChildCompositeEssayQuery orderBySecondEssayId($order = Criteria::ASC) Order by the second_essay_id column
 *
 * @method     ChildCompositeEssayQuery groupById() Group by the id column
 * @method     ChildCompositeEssayQuery groupByTitle() Group by the title column
 * @method     ChildCompositeEssayQuery groupByFirstEssayId() Group by the first_essay_id column
 * @method     ChildCompositeEssayQuery groupBySecondEssayId() Group by the second_essay_id column
 *
 * @method     ChildCompositeEssayQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildCompositeEssayQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildCompositeEssayQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildCompositeEssayQuery leftJoinCompositeEssayRelatedByFirstEssayId($relationAlias = null) Adds a LEFT JOIN clause to the query using the CompositeEssayRelatedByFirstEssayId relation
 * @method     ChildCompositeEssayQuery rightJoinCompositeEssayRelatedByFirstEssayId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the CompositeEssayRelatedByFirstEssayId relation
 * @method     ChildCompositeEssayQuery innerJoinCompositeEssayRelatedByFirstEssayId($relationAlias = null) Adds a INNER JOIN clause to the query using the CompositeEssayRelatedByFirstEssayId relation
 *
 * @method     ChildCompositeEssayQuery leftJoinCompositeEssayRelatedBySecondEssayId($relationAlias = null) Adds a LEFT JOIN clause to the query using the CompositeEssayRelatedBySecondEssayId relation
 * @method     ChildCompositeEssayQuery rightJoinCompositeEssayRelatedBySecondEssayId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the CompositeEssayRelatedBySecondEssayId relation
 * @method     ChildCompositeEssayQuery innerJoinCompositeEssayRelatedBySecondEssayId($relationAlias = null) Adds a INNER JOIN clause to the query using the CompositeEssayRelatedBySecondEssayId relation
 *
 * @method     ChildCompositeEssayQuery leftJoinCompositeEssayRelatedById0($relationAlias = null) Adds a LEFT JOIN clause to the query using the CompositeEssayRelatedById0 relation
 * @method     ChildCompositeEssayQuery rightJoinCompositeEssayRelatedById0($relationAlias = null) Adds a RIGHT JOIN clause to the query using the CompositeEssayRelatedById0 relation
 * @method     ChildCompositeEssayQuery innerJoinCompositeEssayRelatedById0($relationAlias = null) Adds a INNER JOIN clause to the query using the CompositeEssayRelatedById0 relation
 *
 * @method     ChildCompositeEssayQuery leftJoinCompositeEssayRelatedById1($relationAlias = null) Adds a LEFT JOIN clause to the query using the CompositeEssayRelatedById1 relation
 * @method     ChildCompositeEssayQuery rightJoinCompositeEssayRelatedById1($relationAlias = null) Adds a RIGHT JOIN clause to the query using the CompositeEssayRelatedById1 relation
 * @method     ChildCompositeEssayQuery innerJoinCompositeEssayRelatedById1($relationAlias = null) Adds a INNER JOIN clause to the query using the CompositeEssayRelatedById1 relation
 *
 * @method     \Propel\Tests\Bookstore\CompositeEssayQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildCompositeEssay findOne(ConnectionInterface $con = null) Return the first ChildCompositeEssay matching the query
 * @method     ChildCompositeEssay findOneOrCreate(ConnectionInterface $con = null) Return the first ChildCompositeEssay matching the query, or a new ChildCompositeEssay object populated from the query conditions when no match is found
 *
 * @method     ChildCompositeEssay findOneById(int $id) Return the first ChildCompositeEssay filtered by the id column
 * @method     ChildCompositeEssay findOneByTitle(string $title) Return the first ChildCompositeEssay filtered by the title column
 * @method     ChildCompositeEssay findOneByFirstEssayId(int $first_essay_id) Return the first ChildCompositeEssay filtered by the first_essay_id column
 * @method     ChildCompositeEssay findOneBySecondEssayId(int $second_essay_id) Return the first ChildCompositeEssay filtered by the second_essay_id column
 *
 * @method     ChildCompositeEssay[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildCompositeEssay objects based on current ModelCriteria
 * @method     ChildCompositeEssay[]|ObjectCollection findById(int $id) Return ChildCompositeEssay objects filtered by the id column
 * @method     ChildCompositeEssay[]|ObjectCollection findByTitle(string $title) Return ChildCompositeEssay objects filtered by the title column
 * @method     ChildCompositeEssay[]|ObjectCollection findByFirstEssayId(int $first_essay_id) Return ChildCompositeEssay objects filtered by the first_essay_id column
 * @method     ChildCompositeEssay[]|ObjectCollection findBySecondEssayId(int $second_essay_id) Return ChildCompositeEssay objects filtered by the second_essay_id column
 * @method     ChildCompositeEssay[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class CompositeEssayQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\CompositeEssayQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\CompositeEssay', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildCompositeEssayQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildCompositeEssayQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildCompositeEssayQuery) {
            return $criteria;
        }
        $query = new ChildCompositeEssayQuery();
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
     * @return ChildCompositeEssay|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = CompositeEssayTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(CompositeEssayTableMap::DATABASE_NAME);
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
     * @return ChildCompositeEssay A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, FIRST_ESSAY_ID, SECOND_ESSAY_ID FROM composite_essay WHERE ID = :p0';
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
            /** @var ChildCompositeEssay $obj */
            $obj = new ChildCompositeEssay();
            $obj->hydrate($row);
            CompositeEssayTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildCompositeEssay|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(CompositeEssayTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(CompositeEssayTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(CompositeEssayTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(CompositeEssayTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CompositeEssayTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
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

        return $this->addUsingAlias(CompositeEssayTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the first_essay_id column
     *
     * Example usage:
     * <code>
     * $query->filterByFirstEssayId(1234); // WHERE first_essay_id = 1234
     * $query->filterByFirstEssayId(array(12, 34)); // WHERE first_essay_id IN (12, 34)
     * $query->filterByFirstEssayId(array('min' => 12)); // WHERE first_essay_id > 12
     * </code>
     *
     * @see       filterByCompositeEssayRelatedByFirstEssayId()
     *
     * @param     mixed $firstEssayId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByFirstEssayId($firstEssayId = null, $comparison = null)
    {
        if (is_array($firstEssayId)) {
            $useMinMax = false;
            if (isset($firstEssayId['min'])) {
                $this->addUsingAlias(CompositeEssayTableMap::COL_FIRST_ESSAY_ID, $firstEssayId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($firstEssayId['max'])) {
                $this->addUsingAlias(CompositeEssayTableMap::COL_FIRST_ESSAY_ID, $firstEssayId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CompositeEssayTableMap::COL_FIRST_ESSAY_ID, $firstEssayId, $comparison);
    }

    /**
     * Filter the query on the second_essay_id column
     *
     * Example usage:
     * <code>
     * $query->filterBySecondEssayId(1234); // WHERE second_essay_id = 1234
     * $query->filterBySecondEssayId(array(12, 34)); // WHERE second_essay_id IN (12, 34)
     * $query->filterBySecondEssayId(array('min' => 12)); // WHERE second_essay_id > 12
     * </code>
     *
     * @see       filterByCompositeEssayRelatedBySecondEssayId()
     *
     * @param     mixed $secondEssayId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterBySecondEssayId($secondEssayId = null, $comparison = null)
    {
        if (is_array($secondEssayId)) {
            $useMinMax = false;
            if (isset($secondEssayId['min'])) {
                $this->addUsingAlias(CompositeEssayTableMap::COL_SECOND_ESSAY_ID, $secondEssayId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($secondEssayId['max'])) {
                $this->addUsingAlias(CompositeEssayTableMap::COL_SECOND_ESSAY_ID, $secondEssayId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CompositeEssayTableMap::COL_SECOND_ESSAY_ID, $secondEssayId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\CompositeEssay object
     *
     * @param \Propel\Tests\Bookstore\CompositeEssay|ObjectCollection $compositeEssay The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByCompositeEssayRelatedByFirstEssayId($compositeEssay, $comparison = null)
    {
        if ($compositeEssay instanceof \Propel\Tests\Bookstore\CompositeEssay) {
            return $this
                ->addUsingAlias(CompositeEssayTableMap::COL_FIRST_ESSAY_ID, $compositeEssay->getId(), $comparison);
        } elseif ($compositeEssay instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(CompositeEssayTableMap::COL_FIRST_ESSAY_ID, $compositeEssay->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByCompositeEssayRelatedByFirstEssayId() only accepts arguments of type \Propel\Tests\Bookstore\CompositeEssay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the CompositeEssayRelatedByFirstEssayId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function joinCompositeEssayRelatedByFirstEssayId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('CompositeEssayRelatedByFirstEssayId');

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
            $this->addJoinObject($join, 'CompositeEssayRelatedByFirstEssayId');
        }

        return $this;
    }

    /**
     * Use the CompositeEssayRelatedByFirstEssayId relation CompositeEssay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\CompositeEssayQuery A secondary query class using the current class as primary query
     */
    public function useCompositeEssayRelatedByFirstEssayIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinCompositeEssayRelatedByFirstEssayId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'CompositeEssayRelatedByFirstEssayId', '\Propel\Tests\Bookstore\CompositeEssayQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\CompositeEssay object
     *
     * @param \Propel\Tests\Bookstore\CompositeEssay|ObjectCollection $compositeEssay The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByCompositeEssayRelatedBySecondEssayId($compositeEssay, $comparison = null)
    {
        if ($compositeEssay instanceof \Propel\Tests\Bookstore\CompositeEssay) {
            return $this
                ->addUsingAlias(CompositeEssayTableMap::COL_SECOND_ESSAY_ID, $compositeEssay->getId(), $comparison);
        } elseif ($compositeEssay instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(CompositeEssayTableMap::COL_SECOND_ESSAY_ID, $compositeEssay->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByCompositeEssayRelatedBySecondEssayId() only accepts arguments of type \Propel\Tests\Bookstore\CompositeEssay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the CompositeEssayRelatedBySecondEssayId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function joinCompositeEssayRelatedBySecondEssayId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('CompositeEssayRelatedBySecondEssayId');

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
            $this->addJoinObject($join, 'CompositeEssayRelatedBySecondEssayId');
        }

        return $this;
    }

    /**
     * Use the CompositeEssayRelatedBySecondEssayId relation CompositeEssay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\CompositeEssayQuery A secondary query class using the current class as primary query
     */
    public function useCompositeEssayRelatedBySecondEssayIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinCompositeEssayRelatedBySecondEssayId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'CompositeEssayRelatedBySecondEssayId', '\Propel\Tests\Bookstore\CompositeEssayQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\CompositeEssay object
     *
     * @param \Propel\Tests\Bookstore\CompositeEssay|ObjectCollection $compositeEssay  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByCompositeEssayRelatedById0($compositeEssay, $comparison = null)
    {
        if ($compositeEssay instanceof \Propel\Tests\Bookstore\CompositeEssay) {
            return $this
                ->addUsingAlias(CompositeEssayTableMap::COL_ID, $compositeEssay->getFirstEssayId(), $comparison);
        } elseif ($compositeEssay instanceof ObjectCollection) {
            return $this
                ->useCompositeEssayRelatedById0Query()
                ->filterByPrimaryKeys($compositeEssay->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByCompositeEssayRelatedById0() only accepts arguments of type \Propel\Tests\Bookstore\CompositeEssay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the CompositeEssayRelatedById0 relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function joinCompositeEssayRelatedById0($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('CompositeEssayRelatedById0');

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
            $this->addJoinObject($join, 'CompositeEssayRelatedById0');
        }

        return $this;
    }

    /**
     * Use the CompositeEssayRelatedById0 relation CompositeEssay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\CompositeEssayQuery A secondary query class using the current class as primary query
     */
    public function useCompositeEssayRelatedById0Query($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinCompositeEssayRelatedById0($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'CompositeEssayRelatedById0', '\Propel\Tests\Bookstore\CompositeEssayQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\CompositeEssay object
     *
     * @param \Propel\Tests\Bookstore\CompositeEssay|ObjectCollection $compositeEssay  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function filterByCompositeEssayRelatedById1($compositeEssay, $comparison = null)
    {
        if ($compositeEssay instanceof \Propel\Tests\Bookstore\CompositeEssay) {
            return $this
                ->addUsingAlias(CompositeEssayTableMap::COL_ID, $compositeEssay->getSecondEssayId(), $comparison);
        } elseif ($compositeEssay instanceof ObjectCollection) {
            return $this
                ->useCompositeEssayRelatedById1Query()
                ->filterByPrimaryKeys($compositeEssay->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByCompositeEssayRelatedById1() only accepts arguments of type \Propel\Tests\Bookstore\CompositeEssay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the CompositeEssayRelatedById1 relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function joinCompositeEssayRelatedById1($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('CompositeEssayRelatedById1');

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
            $this->addJoinObject($join, 'CompositeEssayRelatedById1');
        }

        return $this;
    }

    /**
     * Use the CompositeEssayRelatedById1 relation CompositeEssay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\CompositeEssayQuery A secondary query class using the current class as primary query
     */
    public function useCompositeEssayRelatedById1Query($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinCompositeEssayRelatedById1($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'CompositeEssayRelatedById1', '\Propel\Tests\Bookstore\CompositeEssayQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildCompositeEssay $compositeEssay Object to remove from the list of results
     *
     * @return $this|ChildCompositeEssayQuery The current query, for fluid interface
     */
    public function prune($compositeEssay = null)
    {
        if ($compositeEssay) {
            $this->addUsingAlias(CompositeEssayTableMap::COL_ID, $compositeEssay->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the composite_essay table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CompositeEssayTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            CompositeEssayTableMap::clearInstancePool();
            CompositeEssayTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(CompositeEssayTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(CompositeEssayTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            CompositeEssayTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            CompositeEssayTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // CompositeEssayQuery
