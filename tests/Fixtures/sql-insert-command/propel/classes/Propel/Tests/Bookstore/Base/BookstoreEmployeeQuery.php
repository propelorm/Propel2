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
use Propel\Tests\Bookstore\BookstoreEmployee as ChildBookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery as ChildBookstoreEmployeeQuery;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;

/**
 * Base class that represents a query for the 'bookstore_employee' table.
 *
 * Hierarchical table to represent employees of a bookstore.
 *
 * @method     ChildBookstoreEmployeeQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBookstoreEmployeeQuery orderByClassKey($order = Criteria::ASC) Order by the class_key column
 * @method     ChildBookstoreEmployeeQuery orderByName($order = Criteria::ASC) Order by the name column
 * @method     ChildBookstoreEmployeeQuery orderByJobTitle($order = Criteria::ASC) Order by the job_title column
 * @method     ChildBookstoreEmployeeQuery orderBySupervisorId($order = Criteria::ASC) Order by the supervisor_id column
 * @method     ChildBookstoreEmployeeQuery orderByPhoto($order = Criteria::ASC) Order by the photo column
 *
 * @method     ChildBookstoreEmployeeQuery groupById() Group by the id column
 * @method     ChildBookstoreEmployeeQuery groupByClassKey() Group by the class_key column
 * @method     ChildBookstoreEmployeeQuery groupByName() Group by the name column
 * @method     ChildBookstoreEmployeeQuery groupByJobTitle() Group by the job_title column
 * @method     ChildBookstoreEmployeeQuery groupBySupervisorId() Group by the supervisor_id column
 * @method     ChildBookstoreEmployeeQuery groupByPhoto() Group by the photo column
 *
 * @method     ChildBookstoreEmployeeQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookstoreEmployeeQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookstoreEmployeeQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookstoreEmployeeQuery leftJoinSupervisor($relationAlias = null) Adds a LEFT JOIN clause to the query using the Supervisor relation
 * @method     ChildBookstoreEmployeeQuery rightJoinSupervisor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Supervisor relation
 * @method     ChildBookstoreEmployeeQuery innerJoinSupervisor($relationAlias = null) Adds a INNER JOIN clause to the query using the Supervisor relation
 *
 * @method     ChildBookstoreEmployeeQuery leftJoinSubordinate($relationAlias = null) Adds a LEFT JOIN clause to the query using the Subordinate relation
 * @method     ChildBookstoreEmployeeQuery rightJoinSubordinate($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Subordinate relation
 * @method     ChildBookstoreEmployeeQuery innerJoinSubordinate($relationAlias = null) Adds a INNER JOIN clause to the query using the Subordinate relation
 *
 * @method     ChildBookstoreEmployeeQuery leftJoinBookstoreEmployeeAccount($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreEmployeeAccount relation
 * @method     ChildBookstoreEmployeeQuery rightJoinBookstoreEmployeeAccount($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreEmployeeAccount relation
 * @method     ChildBookstoreEmployeeQuery innerJoinBookstoreEmployeeAccount($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreEmployeeAccount relation
 *
 * @method     \Propel\Tests\Bookstore\BookstoreEmployeeQuery|\Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookstoreEmployee findOne(ConnectionInterface $con = null) Return the first ChildBookstoreEmployee matching the query
 * @method     ChildBookstoreEmployee findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookstoreEmployee matching the query, or a new ChildBookstoreEmployee object populated from the query conditions when no match is found
 *
 * @method     ChildBookstoreEmployee findOneById(int $id) Return the first ChildBookstoreEmployee filtered by the id column
 * @method     ChildBookstoreEmployee findOneByClassKey(int $class_key) Return the first ChildBookstoreEmployee filtered by the class_key column
 * @method     ChildBookstoreEmployee findOneByName(string $name) Return the first ChildBookstoreEmployee filtered by the name column
 * @method     ChildBookstoreEmployee findOneByJobTitle(string $job_title) Return the first ChildBookstoreEmployee filtered by the job_title column
 * @method     ChildBookstoreEmployee findOneBySupervisorId(int $supervisor_id) Return the first ChildBookstoreEmployee filtered by the supervisor_id column
 * @method     ChildBookstoreEmployee findOneByPhoto(resource $photo) Return the first ChildBookstoreEmployee filtered by the photo column
 *
 * @method     ChildBookstoreEmployee[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookstoreEmployee objects based on current ModelCriteria
 * @method     ChildBookstoreEmployee[]|ObjectCollection findById(int $id) Return ChildBookstoreEmployee objects filtered by the id column
 * @method     ChildBookstoreEmployee[]|ObjectCollection findByClassKey(int $class_key) Return ChildBookstoreEmployee objects filtered by the class_key column
 * @method     ChildBookstoreEmployee[]|ObjectCollection findByName(string $name) Return ChildBookstoreEmployee objects filtered by the name column
 * @method     ChildBookstoreEmployee[]|ObjectCollection findByJobTitle(string $job_title) Return ChildBookstoreEmployee objects filtered by the job_title column
 * @method     ChildBookstoreEmployee[]|ObjectCollection findBySupervisorId(int $supervisor_id) Return ChildBookstoreEmployee objects filtered by the supervisor_id column
 * @method     ChildBookstoreEmployee[]|ObjectCollection findByPhoto(resource $photo) Return ChildBookstoreEmployee objects filtered by the photo column
 * @method     ChildBookstoreEmployee[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookstoreEmployeeQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookstoreEmployeeQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\BookstoreEmployee', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookstoreEmployeeQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookstoreEmployeeQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookstoreEmployeeQuery) {
            return $criteria;
        }
        $query = new ChildBookstoreEmployeeQuery();
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
     * @return ChildBookstoreEmployee|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookstoreEmployeeTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
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
     * @return ChildBookstoreEmployee A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, CLASS_KEY, NAME, JOB_TITLE, SUPERVISOR_ID FROM bookstore_employee WHERE ID = :p0';
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
            $cls = BookstoreEmployeeTableMap::getOMClass($row, 0, false);
            /** @var ChildBookstoreEmployee $obj */
            $obj = new $cls();
            $obj->hydrate($row);
            BookstoreEmployeeTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBookstoreEmployee|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the class_key column
     *
     * Example usage:
     * <code>
     * $query->filterByClassKey(1234); // WHERE class_key = 1234
     * $query->filterByClassKey(array(12, 34)); // WHERE class_key IN (12, 34)
     * $query->filterByClassKey(array('min' => 12)); // WHERE class_key > 12
     * </code>
     *
     * @param     mixed $classKey The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterByClassKey($classKey = null, $comparison = null)
    {
        if (is_array($classKey)) {
            $useMinMax = false;
            if (isset($classKey['min'])) {
                $this->addUsingAlias(BookstoreEmployeeTableMap::COL_CLASS_KEY, $classKey['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($classKey['max'])) {
                $this->addUsingAlias(BookstoreEmployeeTableMap::COL_CLASS_KEY, $classKey['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_CLASS_KEY, $classKey, $comparison);
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
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
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

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query on the job_title column
     *
     * Example usage:
     * <code>
     * $query->filterByJobTitle('fooValue');   // WHERE job_title = 'fooValue'
     * $query->filterByJobTitle('%fooValue%'); // WHERE job_title LIKE '%fooValue%'
     * </code>
     *
     * @param     string $jobTitle The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterByJobTitle($jobTitle = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobTitle)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $jobTitle)) {
                $jobTitle = str_replace('*', '%', $jobTitle);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_JOB_TITLE, $jobTitle, $comparison);
    }

    /**
     * Filter the query on the supervisor_id column
     *
     * Example usage:
     * <code>
     * $query->filterBySupervisorId(1234); // WHERE supervisor_id = 1234
     * $query->filterBySupervisorId(array(12, 34)); // WHERE supervisor_id IN (12, 34)
     * $query->filterBySupervisorId(array('min' => 12)); // WHERE supervisor_id > 12
     * </code>
     *
     * @see       filterBySupervisor()
     *
     * @param     mixed $supervisorId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterBySupervisorId($supervisorId = null, $comparison = null)
    {
        if (is_array($supervisorId)) {
            $useMinMax = false;
            if (isset($supervisorId['min'])) {
                $this->addUsingAlias(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, $supervisorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($supervisorId['max'])) {
                $this->addUsingAlias(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, $supervisorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, $supervisorId, $comparison);
    }

    /**
     * Filter the query on the photo column
     *
     * @param     mixed $photo The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterByPhoto($photo = null, $comparison = null)
    {

        return $this->addUsingAlias(BookstoreEmployeeTableMap::COL_PHOTO, $photo, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreEmployee object
     *
     * @param \Propel\Tests\Bookstore\BookstoreEmployee|ObjectCollection $bookstoreEmployee The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterBySupervisor($bookstoreEmployee, $comparison = null)
    {
        if ($bookstoreEmployee instanceof \Propel\Tests\Bookstore\BookstoreEmployee) {
            return $this
                ->addUsingAlias(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, $bookstoreEmployee->getId(), $comparison);
        } elseif ($bookstoreEmployee instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookstoreEmployeeTableMap::COL_SUPERVISOR_ID, $bookstoreEmployee->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterBySupervisor() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreEmployee or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Supervisor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function joinSupervisor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Supervisor');

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
            $this->addJoinObject($join, 'Supervisor');
        }

        return $this;
    }

    /**
     * Use the Supervisor relation BookstoreEmployee object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreEmployeeQuery A secondary query class using the current class as primary query
     */
    public function useSupervisorQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinSupervisor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Supervisor', '\Propel\Tests\Bookstore\BookstoreEmployeeQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreEmployee object
     *
     * @param \Propel\Tests\Bookstore\BookstoreEmployee|ObjectCollection $bookstoreEmployee  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterBySubordinate($bookstoreEmployee, $comparison = null)
    {
        if ($bookstoreEmployee instanceof \Propel\Tests\Bookstore\BookstoreEmployee) {
            return $this
                ->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $bookstoreEmployee->getSupervisorId(), $comparison);
        } elseif ($bookstoreEmployee instanceof ObjectCollection) {
            return $this
                ->useSubordinateQuery()
                ->filterByPrimaryKeys($bookstoreEmployee->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterBySubordinate() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreEmployee or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Subordinate relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function joinSubordinate($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Subordinate');

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
            $this->addJoinObject($join, 'Subordinate');
        }

        return $this;
    }

    /**
     * Use the Subordinate relation BookstoreEmployee object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreEmployeeQuery A secondary query class using the current class as primary query
     */
    public function useSubordinateQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinSubordinate($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Subordinate', '\Propel\Tests\Bookstore\BookstoreEmployeeQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreEmployeeAccount object
     *
     * @param \Propel\Tests\Bookstore\BookstoreEmployeeAccount|ObjectCollection $bookstoreEmployeeAccount  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function filterByBookstoreEmployeeAccount($bookstoreEmployeeAccount, $comparison = null)
    {
        if ($bookstoreEmployeeAccount instanceof \Propel\Tests\Bookstore\BookstoreEmployeeAccount) {
            return $this
                ->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $bookstoreEmployeeAccount->getEmployeeId(), $comparison);
        } elseif ($bookstoreEmployeeAccount instanceof ObjectCollection) {
            return $this
                ->useBookstoreEmployeeAccountQuery()
                ->filterByPrimaryKeys($bookstoreEmployeeAccount->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookstoreEmployeeAccount() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreEmployeeAccount or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreEmployeeAccount relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function joinBookstoreEmployeeAccount($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookstoreEmployeeAccount');

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
            $this->addJoinObject($join, 'BookstoreEmployeeAccount');
        }

        return $this;
    }

    /**
     * Use the BookstoreEmployeeAccount relation BookstoreEmployeeAccount object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreEmployeeAccountQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookstoreEmployeeAccount($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookstoreEmployeeAccount', '\Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBookstoreEmployee $bookstoreEmployee Object to remove from the list of results
     *
     * @return $this|ChildBookstoreEmployeeQuery The current query, for fluid interface
     */
    public function prune($bookstoreEmployee = null)
    {
        if ($bookstoreEmployee) {
            $this->addUsingAlias(BookstoreEmployeeTableMap::COL_ID, $bookstoreEmployee->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the bookstore_employee table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookstoreEmployeeTableMap::clearInstancePool();
            BookstoreEmployeeTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookstoreEmployeeTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookstoreEmployeeTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookstoreEmployeeTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookstoreEmployeeQuery
