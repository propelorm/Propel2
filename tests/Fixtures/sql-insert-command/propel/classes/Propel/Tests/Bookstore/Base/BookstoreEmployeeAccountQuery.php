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
use Propel\Tests\Bookstore\BookstoreEmployeeAccount as ChildBookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery as ChildBookstoreEmployeeAccountQuery;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeAccountTableMap;

/**
 * Base class that represents a query for the 'bookstore_employee_account' table.
 *
 * Bookstore employees login credentials.
 *
 * @method     ChildBookstoreEmployeeAccountQuery orderByEmployeeId($order = Criteria::ASC) Order by the employee_id column
 * @method     ChildBookstoreEmployeeAccountQuery orderByLogin($order = Criteria::ASC) Order by the login column
 * @method     ChildBookstoreEmployeeAccountQuery orderByPassword($order = Criteria::ASC) Order by the password column
 * @method     ChildBookstoreEmployeeAccountQuery orderByEnabled($order = Criteria::ASC) Order by the enabled column
 * @method     ChildBookstoreEmployeeAccountQuery orderByNotEnabled($order = Criteria::ASC) Order by the not_enabled column
 * @method     ChildBookstoreEmployeeAccountQuery orderByCreated($order = Criteria::ASC) Order by the created column
 * @method     ChildBookstoreEmployeeAccountQuery orderByRoleId($order = Criteria::ASC) Order by the role_id column
 * @method     ChildBookstoreEmployeeAccountQuery orderByAuthenticator($order = Criteria::ASC) Order by the authenticator column
 *
 * @method     ChildBookstoreEmployeeAccountQuery groupByEmployeeId() Group by the employee_id column
 * @method     ChildBookstoreEmployeeAccountQuery groupByLogin() Group by the login column
 * @method     ChildBookstoreEmployeeAccountQuery groupByPassword() Group by the password column
 * @method     ChildBookstoreEmployeeAccountQuery groupByEnabled() Group by the enabled column
 * @method     ChildBookstoreEmployeeAccountQuery groupByNotEnabled() Group by the not_enabled column
 * @method     ChildBookstoreEmployeeAccountQuery groupByCreated() Group by the created column
 * @method     ChildBookstoreEmployeeAccountQuery groupByRoleId() Group by the role_id column
 * @method     ChildBookstoreEmployeeAccountQuery groupByAuthenticator() Group by the authenticator column
 *
 * @method     ChildBookstoreEmployeeAccountQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookstoreEmployeeAccountQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookstoreEmployeeAccountQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookstoreEmployeeAccountQuery leftJoinBookstoreEmployee($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreEmployee relation
 * @method     ChildBookstoreEmployeeAccountQuery rightJoinBookstoreEmployee($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreEmployee relation
 * @method     ChildBookstoreEmployeeAccountQuery innerJoinBookstoreEmployee($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreEmployee relation
 *
 * @method     ChildBookstoreEmployeeAccountQuery leftJoinAcctAccessRole($relationAlias = null) Adds a LEFT JOIN clause to the query using the AcctAccessRole relation
 * @method     ChildBookstoreEmployeeAccountQuery rightJoinAcctAccessRole($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AcctAccessRole relation
 * @method     ChildBookstoreEmployeeAccountQuery innerJoinAcctAccessRole($relationAlias = null) Adds a INNER JOIN clause to the query using the AcctAccessRole relation
 *
 * @method     ChildBookstoreEmployeeAccountQuery leftJoinAcctAuditLog($relationAlias = null) Adds a LEFT JOIN clause to the query using the AcctAuditLog relation
 * @method     ChildBookstoreEmployeeAccountQuery rightJoinAcctAuditLog($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AcctAuditLog relation
 * @method     ChildBookstoreEmployeeAccountQuery innerJoinAcctAuditLog($relationAlias = null) Adds a INNER JOIN clause to the query using the AcctAuditLog relation
 *
 * @method     \Propel\Tests\Bookstore\BookstoreEmployeeQuery|\Propel\Tests\Bookstore\AcctAccessRoleQuery|\Propel\Tests\Bookstore\AcctAuditLogQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBookstoreEmployeeAccount findOne(ConnectionInterface $con = null) Return the first ChildBookstoreEmployeeAccount matching the query
 * @method     ChildBookstoreEmployeeAccount findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBookstoreEmployeeAccount matching the query, or a new ChildBookstoreEmployeeAccount object populated from the query conditions when no match is found
 *
 * @method     ChildBookstoreEmployeeAccount findOneByEmployeeId(int $employee_id) Return the first ChildBookstoreEmployeeAccount filtered by the employee_id column
 * @method     ChildBookstoreEmployeeAccount findOneByLogin(string $login) Return the first ChildBookstoreEmployeeAccount filtered by the login column
 * @method     ChildBookstoreEmployeeAccount findOneByPassword(string $password) Return the first ChildBookstoreEmployeeAccount filtered by the password column
 * @method     ChildBookstoreEmployeeAccount findOneByEnabled(boolean $enabled) Return the first ChildBookstoreEmployeeAccount filtered by the enabled column
 * @method     ChildBookstoreEmployeeAccount findOneByNotEnabled(boolean $not_enabled) Return the first ChildBookstoreEmployeeAccount filtered by the not_enabled column
 * @method     ChildBookstoreEmployeeAccount findOneByCreated(string $created) Return the first ChildBookstoreEmployeeAccount filtered by the created column
 * @method     ChildBookstoreEmployeeAccount findOneByRoleId(int $role_id) Return the first ChildBookstoreEmployeeAccount filtered by the role_id column
 * @method     ChildBookstoreEmployeeAccount findOneByAuthenticator(string $authenticator) Return the first ChildBookstoreEmployeeAccount filtered by the authenticator column
 *
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBookstoreEmployeeAccount objects based on current ModelCriteria
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByEmployeeId(int $employee_id) Return ChildBookstoreEmployeeAccount objects filtered by the employee_id column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByLogin(string $login) Return ChildBookstoreEmployeeAccount objects filtered by the login column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByPassword(string $password) Return ChildBookstoreEmployeeAccount objects filtered by the password column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByEnabled(boolean $enabled) Return ChildBookstoreEmployeeAccount objects filtered by the enabled column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByNotEnabled(boolean $not_enabled) Return ChildBookstoreEmployeeAccount objects filtered by the not_enabled column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByCreated(string $created) Return ChildBookstoreEmployeeAccount objects filtered by the created column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByRoleId(int $role_id) Return ChildBookstoreEmployeeAccount objects filtered by the role_id column
 * @method     ChildBookstoreEmployeeAccount[]|ObjectCollection findByAuthenticator(string $authenticator) Return ChildBookstoreEmployeeAccount objects filtered by the authenticator column
 * @method     ChildBookstoreEmployeeAccount[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookstoreEmployeeAccountQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookstoreEmployeeAccountQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\BookstoreEmployeeAccount', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookstoreEmployeeAccountQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookstoreEmployeeAccountQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookstoreEmployeeAccountQuery) {
            return $criteria;
        }
        $query = new ChildBookstoreEmployeeAccountQuery();
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
     * @return ChildBookstoreEmployeeAccount|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookstoreEmployeeAccountTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
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
     * @return ChildBookstoreEmployeeAccount A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT EMPLOYEE_ID, LOGIN, PASSWORD, ENABLED, NOT_ENABLED, CREATED, ROLE_ID, AUTHENTICATOR FROM bookstore_employee_account WHERE EMPLOYEE_ID = :p0';
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
            /** @var ChildBookstoreEmployeeAccount $obj */
            $obj = new ChildBookstoreEmployeeAccount();
            $obj->hydrate($row);
            BookstoreEmployeeAccountTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBookstoreEmployeeAccount|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the employee_id column
     *
     * Example usage:
     * <code>
     * $query->filterByEmployeeId(1234); // WHERE employee_id = 1234
     * $query->filterByEmployeeId(array(12, 34)); // WHERE employee_id IN (12, 34)
     * $query->filterByEmployeeId(array('min' => 12)); // WHERE employee_id > 12
     * </code>
     *
     * @see       filterByBookstoreEmployee()
     *
     * @param     mixed $employeeId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByEmployeeId($employeeId = null, $comparison = null)
    {
        if (is_array($employeeId)) {
            $useMinMax = false;
            if (isset($employeeId['min'])) {
                $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $employeeId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($employeeId['max'])) {
                $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $employeeId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $employeeId, $comparison);
    }

    /**
     * Filter the query on the login column
     *
     * Example usage:
     * <code>
     * $query->filterByLogin('fooValue');   // WHERE login = 'fooValue'
     * $query->filterByLogin('%fooValue%'); // WHERE login LIKE '%fooValue%'
     * </code>
     *
     * @param     string $login The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByLogin($login = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($login)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $login)) {
                $login = str_replace('*', '%', $login);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_LOGIN, $login, $comparison);
    }

    /**
     * Filter the query on the password column
     *
     * Example usage:
     * <code>
     * $query->filterByPassword('fooValue');   // WHERE password = 'fooValue'
     * $query->filterByPassword('%fooValue%'); // WHERE password LIKE '%fooValue%'
     * </code>
     *
     * @param     string $password The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByPassword($password = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($password)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $password)) {
                $password = str_replace('*', '%', $password);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_PASSWORD, $password, $comparison);
    }

    /**
     * Filter the query on the enabled column
     *
     * Example usage:
     * <code>
     * $query->filterByEnabled(true); // WHERE enabled = true
     * $query->filterByEnabled('yes'); // WHERE enabled = true
     * </code>
     *
     * @param     boolean|string $enabled The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByEnabled($enabled = null, $comparison = null)
    {
        if (is_string($enabled)) {
            $enabled = in_array(strtolower($enabled), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_ENABLED, $enabled, $comparison);
    }

    /**
     * Filter the query on the not_enabled column
     *
     * Example usage:
     * <code>
     * $query->filterByNotEnabled(true); // WHERE not_enabled = true
     * $query->filterByNotEnabled('yes'); // WHERE not_enabled = true
     * </code>
     *
     * @param     boolean|string $notEnabled The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByNotEnabled($notEnabled = null, $comparison = null)
    {
        if (is_string($notEnabled)) {
            $notEnabled = in_array(strtolower($notEnabled), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_NOT_ENABLED, $notEnabled, $comparison);
    }

    /**
     * Filter the query on the created column
     *
     * Example usage:
     * <code>
     * $query->filterByCreated('2011-03-14'); // WHERE created = '2011-03-14'
     * $query->filterByCreated('now'); // WHERE created = '2011-03-14'
     * $query->filterByCreated(array('max' => 'yesterday')); // WHERE created > '2011-03-13'
     * </code>
     *
     * @param     mixed $created The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByCreated($created = null, $comparison = null)
    {
        if (is_array($created)) {
            $useMinMax = false;
            if (isset($created['min'])) {
                $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_CREATED, $created['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($created['max'])) {
                $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_CREATED, $created['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_CREATED, $created, $comparison);
    }

    /**
     * Filter the query on the role_id column
     *
     * Example usage:
     * <code>
     * $query->filterByRoleId(1234); // WHERE role_id = 1234
     * $query->filterByRoleId(array(12, 34)); // WHERE role_id IN (12, 34)
     * $query->filterByRoleId(array('min' => 12)); // WHERE role_id > 12
     * </code>
     *
     * @see       filterByAcctAccessRole()
     *
     * @param     mixed $roleId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByRoleId($roleId = null, $comparison = null)
    {
        if (is_array($roleId)) {
            $useMinMax = false;
            if (isset($roleId['min'])) {
                $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_ROLE_ID, $roleId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($roleId['max'])) {
                $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_ROLE_ID, $roleId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_ROLE_ID, $roleId, $comparison);
    }

    /**
     * Filter the query on the authenticator column
     *
     * Example usage:
     * <code>
     * $query->filterByAuthenticator('fooValue');   // WHERE authenticator = 'fooValue'
     * $query->filterByAuthenticator('%fooValue%'); // WHERE authenticator LIKE '%fooValue%'
     * </code>
     *
     * @param     string $authenticator The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByAuthenticator($authenticator = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($authenticator)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $authenticator)) {
                $authenticator = str_replace('*', '%', $authenticator);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_AUTHENTICATOR, $authenticator, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreEmployee object
     *
     * @param \Propel\Tests\Bookstore\BookstoreEmployee|ObjectCollection $bookstoreEmployee The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByBookstoreEmployee($bookstoreEmployee, $comparison = null)
    {
        if ($bookstoreEmployee instanceof \Propel\Tests\Bookstore\BookstoreEmployee) {
            return $this
                ->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $bookstoreEmployee->getId(), $comparison);
        } elseif ($bookstoreEmployee instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $bookstoreEmployee->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByBookstoreEmployee() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreEmployee or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreEmployee relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function joinBookstoreEmployee($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookstoreEmployee');

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
            $this->addJoinObject($join, 'BookstoreEmployee');
        }

        return $this;
    }

    /**
     * Use the BookstoreEmployee relation BookstoreEmployee object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreEmployeeQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreEmployeeQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookstoreEmployee($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookstoreEmployee', '\Propel\Tests\Bookstore\BookstoreEmployeeQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\AcctAccessRole object
     *
     * @param \Propel\Tests\Bookstore\AcctAccessRole|ObjectCollection $acctAccessRole The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByAcctAccessRole($acctAccessRole, $comparison = null)
    {
        if ($acctAccessRole instanceof \Propel\Tests\Bookstore\AcctAccessRole) {
            return $this
                ->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_ROLE_ID, $acctAccessRole->getId(), $comparison);
        } elseif ($acctAccessRole instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_ROLE_ID, $acctAccessRole->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByAcctAccessRole() only accepts arguments of type \Propel\Tests\Bookstore\AcctAccessRole or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AcctAccessRole relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function joinAcctAccessRole($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AcctAccessRole');

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
            $this->addJoinObject($join, 'AcctAccessRole');
        }

        return $this;
    }

    /**
     * Use the AcctAccessRole relation AcctAccessRole object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\AcctAccessRoleQuery A secondary query class using the current class as primary query
     */
    public function useAcctAccessRoleQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAcctAccessRole($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AcctAccessRole', '\Propel\Tests\Bookstore\AcctAccessRoleQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\AcctAuditLog object
     *
     * @param \Propel\Tests\Bookstore\AcctAuditLog|ObjectCollection $acctAuditLog  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function filterByAcctAuditLog($acctAuditLog, $comparison = null)
    {
        if ($acctAuditLog instanceof \Propel\Tests\Bookstore\AcctAuditLog) {
            return $this
                ->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_LOGIN, $acctAuditLog->getUid(), $comparison);
        } elseif ($acctAuditLog instanceof ObjectCollection) {
            return $this
                ->useAcctAuditLogQuery()
                ->filterByPrimaryKeys($acctAuditLog->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByAcctAuditLog() only accepts arguments of type \Propel\Tests\Bookstore\AcctAuditLog or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AcctAuditLog relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function joinAcctAuditLog($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AcctAuditLog');

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
            $this->addJoinObject($join, 'AcctAuditLog');
        }

        return $this;
    }

    /**
     * Use the AcctAuditLog relation AcctAuditLog object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\AcctAuditLogQuery A secondary query class using the current class as primary query
     */
    public function useAcctAuditLogQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinAcctAuditLog($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AcctAuditLog', '\Propel\Tests\Bookstore\AcctAuditLogQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBookstoreEmployeeAccount $bookstoreEmployeeAccount Object to remove from the list of results
     *
     * @return $this|ChildBookstoreEmployeeAccountQuery The current query, for fluid interface
     */
    public function prune($bookstoreEmployeeAccount = null)
    {
        if ($bookstoreEmployeeAccount) {
            $this->addUsingAlias(BookstoreEmployeeAccountTableMap::COL_EMPLOYEE_ID, $bookstoreEmployeeAccount->getEmployeeId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the bookstore_employee_account table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookstoreEmployeeAccountTableMap::clearInstancePool();
            BookstoreEmployeeAccountTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookstoreEmployeeAccountTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookstoreEmployeeAccountTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookstoreEmployeeAccountTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookstoreEmployeeAccountQuery
