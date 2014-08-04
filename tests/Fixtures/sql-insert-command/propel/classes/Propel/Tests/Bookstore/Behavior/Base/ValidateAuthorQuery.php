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
use Propel\Tests\Bookstore\Behavior\ValidateAuthor as ChildValidateAuthor;
use Propel\Tests\Bookstore\Behavior\ValidateAuthorQuery as ChildValidateAuthorQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateAuthorTableMap;

/**
 * Base class that represents a query for the 'validate_author' table.
 *
 * Author Table
 *
 * @method     ChildValidateAuthorQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildValidateAuthorQuery orderByFirstName($order = Criteria::ASC) Order by the first_name column
 * @method     ChildValidateAuthorQuery orderByLastName($order = Criteria::ASC) Order by the last_name column
 * @method     ChildValidateAuthorQuery orderByEmail($order = Criteria::ASC) Order by the email column
 * @method     ChildValidateAuthorQuery orderByBirthday($order = Criteria::ASC) Order by the birthday column
 *
 * @method     ChildValidateAuthorQuery groupById() Group by the id column
 * @method     ChildValidateAuthorQuery groupByFirstName() Group by the first_name column
 * @method     ChildValidateAuthorQuery groupByLastName() Group by the last_name column
 * @method     ChildValidateAuthorQuery groupByEmail() Group by the email column
 * @method     ChildValidateAuthorQuery groupByBirthday() Group by the birthday column
 *
 * @method     ChildValidateAuthorQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildValidateAuthorQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildValidateAuthorQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildValidateAuthorQuery leftJoinValidateBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateBook relation
 * @method     ChildValidateAuthorQuery rightJoinValidateBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateBook relation
 * @method     ChildValidateAuthorQuery innerJoinValidateBook($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateBook relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ValidateBookQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildValidateAuthor findOne(ConnectionInterface $con = null) Return the first ChildValidateAuthor matching the query
 * @method     ChildValidateAuthor findOneOrCreate(ConnectionInterface $con = null) Return the first ChildValidateAuthor matching the query, or a new ChildValidateAuthor object populated from the query conditions when no match is found
 *
 * @method     ChildValidateAuthor findOneById(int $id) Return the first ChildValidateAuthor filtered by the id column
 * @method     ChildValidateAuthor findOneByFirstName(string $first_name) Return the first ChildValidateAuthor filtered by the first_name column
 * @method     ChildValidateAuthor findOneByLastName(string $last_name) Return the first ChildValidateAuthor filtered by the last_name column
 * @method     ChildValidateAuthor findOneByEmail(string $email) Return the first ChildValidateAuthor filtered by the email column
 * @method     ChildValidateAuthor findOneByBirthday(string $birthday) Return the first ChildValidateAuthor filtered by the birthday column
 *
 * @method     ChildValidateAuthor[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildValidateAuthor objects based on current ModelCriteria
 * @method     ChildValidateAuthor[]|ObjectCollection findById(int $id) Return ChildValidateAuthor objects filtered by the id column
 * @method     ChildValidateAuthor[]|ObjectCollection findByFirstName(string $first_name) Return ChildValidateAuthor objects filtered by the first_name column
 * @method     ChildValidateAuthor[]|ObjectCollection findByLastName(string $last_name) Return ChildValidateAuthor objects filtered by the last_name column
 * @method     ChildValidateAuthor[]|ObjectCollection findByEmail(string $email) Return ChildValidateAuthor objects filtered by the email column
 * @method     ChildValidateAuthor[]|ObjectCollection findByBirthday(string $birthday) Return ChildValidateAuthor objects filtered by the birthday column
 * @method     ChildValidateAuthor[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ValidateAuthorQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ValidateAuthorQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateAuthor', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildValidateAuthorQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildValidateAuthorQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildValidateAuthorQuery) {
            return $criteria;
        }
        $query = new ChildValidateAuthorQuery();
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
     * @return ChildValidateAuthor|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ValidateAuthorTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ValidateAuthorTableMap::DATABASE_NAME);
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
     * @return ChildValidateAuthor A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, FIRST_NAME, LAST_NAME, EMAIL, BIRTHDAY FROM validate_author WHERE ID = :p0';
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
            /** @var ChildValidateAuthor $obj */
            $obj = new ChildValidateAuthor();
            $obj->hydrate($row);
            ValidateAuthorTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildValidateAuthor|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ValidateAuthorTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ValidateAuthorTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the first_name column
     *
     * Example usage:
     * <code>
     * $query->filterByFirstName('fooValue');   // WHERE first_name = 'fooValue'
     * $query->filterByFirstName('%fooValue%'); // WHERE first_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $firstName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByFirstName($firstName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($firstName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $firstName)) {
                $firstName = str_replace('*', '%', $firstName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_FIRST_NAME, $firstName, $comparison);
    }

    /**
     * Filter the query on the last_name column
     *
     * Example usage:
     * <code>
     * $query->filterByLastName('fooValue');   // WHERE last_name = 'fooValue'
     * $query->filterByLastName('%fooValue%'); // WHERE last_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $lastName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByLastName($lastName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($lastName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $lastName)) {
                $lastName = str_replace('*', '%', $lastName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_LAST_NAME, $lastName, $comparison);
    }

    /**
     * Filter the query on the email column
     *
     * Example usage:
     * <code>
     * $query->filterByEmail('fooValue');   // WHERE email = 'fooValue'
     * $query->filterByEmail('%fooValue%'); // WHERE email LIKE '%fooValue%'
     * </code>
     *
     * @param     string $email The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByEmail($email = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($email)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $email)) {
                $email = str_replace('*', '%', $email);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_EMAIL, $email, $comparison);
    }

    /**
     * Filter the query on the birthday column
     *
     * Example usage:
     * <code>
     * $query->filterByBirthday('2011-03-14'); // WHERE birthday = '2011-03-14'
     * $query->filterByBirthday('now'); // WHERE birthday = '2011-03-14'
     * $query->filterByBirthday(array('max' => 'yesterday')); // WHERE birthday > '2011-03-13'
     * </code>
     *
     * @param     mixed $birthday The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByBirthday($birthday = null, $comparison = null)
    {
        if (is_array($birthday)) {
            $useMinMax = false;
            if (isset($birthday['min'])) {
                $this->addUsingAlias(ValidateAuthorTableMap::COL_BIRTHDAY, $birthday['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($birthday['max'])) {
                $this->addUsingAlias(ValidateAuthorTableMap::COL_BIRTHDAY, $birthday['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateAuthorTableMap::COL_BIRTHDAY, $birthday, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateBook object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateBook|ObjectCollection $validateBook  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function filterByValidateBook($validateBook, $comparison = null)
    {
        if ($validateBook instanceof \Propel\Tests\Bookstore\Behavior\ValidateBook) {
            return $this
                ->addUsingAlias(ValidateAuthorTableMap::COL_ID, $validateBook->getAuthorId(), $comparison);
        } elseif ($validateBook instanceof ObjectCollection) {
            return $this
                ->useValidateBookQuery()
                ->filterByPrimaryKeys($validateBook->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByValidateBook() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateBook or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateBook relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function joinValidateBook($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateBook');

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
            $this->addJoinObject($join, 'ValidateBook');
        }

        return $this;
    }

    /**
     * Use the ValidateBook relation ValidateBook object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateBookQuery A secondary query class using the current class as primary query
     */
    public function useValidateBookQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinValidateBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateBook', '\Propel\Tests\Bookstore\Behavior\ValidateBookQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildValidateAuthor $validateAuthor Object to remove from the list of results
     *
     * @return $this|ChildValidateAuthorQuery The current query, for fluid interface
     */
    public function prune($validateAuthor = null)
    {
        if ($validateAuthor) {
            $this->addUsingAlias(ValidateAuthorTableMap::COL_ID, $validateAuthor->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the validate_author table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateAuthorTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ValidateAuthorTableMap::clearInstancePool();
            ValidateAuthorTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateAuthorTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ValidateAuthorTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ValidateAuthorTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ValidateAuthorTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ValidateAuthorQuery
