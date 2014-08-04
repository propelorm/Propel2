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
use Propel\Tests\Bookstore\Author as ChildAuthor;
use Propel\Tests\Bookstore\AuthorQuery as ChildAuthorQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;

/**
 * Base class that represents a query for the 'author' table.
 *
 * Author Table
 *
 * @method     ChildAuthorQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildAuthorQuery orderByFirstName($order = Criteria::ASC) Order by the first_name column
 * @method     ChildAuthorQuery orderByLastName($order = Criteria::ASC) Order by the last_name column
 * @method     ChildAuthorQuery orderByEmail($order = Criteria::ASC) Order by the email column
 * @method     ChildAuthorQuery orderByAge($order = Criteria::ASC) Order by the age column
 *
 * @method     ChildAuthorQuery groupById() Group by the id column
 * @method     ChildAuthorQuery groupByFirstName() Group by the first_name column
 * @method     ChildAuthorQuery groupByLastName() Group by the last_name column
 * @method     ChildAuthorQuery groupByEmail() Group by the email column
 * @method     ChildAuthorQuery groupByAge() Group by the age column
 *
 * @method     ChildAuthorQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildAuthorQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildAuthorQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildAuthorQuery leftJoinBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the Book relation
 * @method     ChildAuthorQuery rightJoinBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Book relation
 * @method     ChildAuthorQuery innerJoinBook($relationAlias = null) Adds a INNER JOIN clause to the query using the Book relation
 *
 * @method     ChildAuthorQuery leftJoinEssayRelatedByFirstAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the EssayRelatedByFirstAuthor relation
 * @method     ChildAuthorQuery rightJoinEssayRelatedByFirstAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the EssayRelatedByFirstAuthor relation
 * @method     ChildAuthorQuery innerJoinEssayRelatedByFirstAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the EssayRelatedByFirstAuthor relation
 *
 * @method     ChildAuthorQuery leftJoinEssayRelatedBySecondAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the EssayRelatedBySecondAuthor relation
 * @method     ChildAuthorQuery rightJoinEssayRelatedBySecondAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the EssayRelatedBySecondAuthor relation
 * @method     ChildAuthorQuery innerJoinEssayRelatedBySecondAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the EssayRelatedBySecondAuthor relation
 *
 * @method     \Propel\Tests\Bookstore\BookQuery|\Propel\Tests\Bookstore\EssayQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildAuthor findOne(ConnectionInterface $con = null) Return the first ChildAuthor matching the query
 * @method     ChildAuthor findOneOrCreate(ConnectionInterface $con = null) Return the first ChildAuthor matching the query, or a new ChildAuthor object populated from the query conditions when no match is found
 *
 * @method     ChildAuthor findOneById(int $id) Return the first ChildAuthor filtered by the id column
 * @method     ChildAuthor findOneByFirstName(string $first_name) Return the first ChildAuthor filtered by the first_name column
 * @method     ChildAuthor findOneByLastName(string $last_name) Return the first ChildAuthor filtered by the last_name column
 * @method     ChildAuthor findOneByEmail(string $email) Return the first ChildAuthor filtered by the email column
 * @method     ChildAuthor findOneByAge(int $age) Return the first ChildAuthor filtered by the age column
 *
 * @method     ChildAuthor[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildAuthor objects based on current ModelCriteria
 * @method     ChildAuthor[]|ObjectCollection findById(int $id) Return ChildAuthor objects filtered by the id column
 * @method     ChildAuthor[]|ObjectCollection findByFirstName(string $first_name) Return ChildAuthor objects filtered by the first_name column
 * @method     ChildAuthor[]|ObjectCollection findByLastName(string $last_name) Return ChildAuthor objects filtered by the last_name column
 * @method     ChildAuthor[]|ObjectCollection findByEmail(string $email) Return ChildAuthor objects filtered by the email column
 * @method     ChildAuthor[]|ObjectCollection findByAge(int $age) Return ChildAuthor objects filtered by the age column
 * @method     ChildAuthor[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class AuthorQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\AuthorQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Author', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildAuthorQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildAuthorQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildAuthorQuery) {
            return $criteria;
        }
        $query = new ChildAuthorQuery();
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
     * @return ChildAuthor|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = AuthorTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(AuthorTableMap::DATABASE_NAME);
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
     * @return ChildAuthor A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, FIRST_NAME, LAST_NAME, EMAIL, AGE FROM author WHERE ID = :p0';
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
            /** @var ChildAuthor $obj */
            $obj = new ChildAuthor();
            $obj->hydrate($row);
            AuthorTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildAuthor|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(AuthorTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(AuthorTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(AuthorTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(AuthorTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AuthorTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildAuthorQuery The current query, for fluid interface
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

        return $this->addUsingAlias(AuthorTableMap::COL_FIRST_NAME, $firstName, $comparison);
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
     * @return $this|ChildAuthorQuery The current query, for fluid interface
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

        return $this->addUsingAlias(AuthorTableMap::COL_LAST_NAME, $lastName, $comparison);
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
     * @return $this|ChildAuthorQuery The current query, for fluid interface
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

        return $this->addUsingAlias(AuthorTableMap::COL_EMAIL, $email, $comparison);
    }

    /**
     * Filter the query on the age column
     *
     * Example usage:
     * <code>
     * $query->filterByAge(1234); // WHERE age = 1234
     * $query->filterByAge(array(12, 34)); // WHERE age IN (12, 34)
     * $query->filterByAge(array('min' => 12)); // WHERE age > 12
     * </code>
     *
     * @param     mixed $age The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function filterByAge($age = null, $comparison = null)
    {
        if (is_array($age)) {
            $useMinMax = false;
            if (isset($age['min'])) {
                $this->addUsingAlias(AuthorTableMap::COL_AGE, $age['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($age['max'])) {
                $this->addUsingAlias(AuthorTableMap::COL_AGE, $age['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(AuthorTableMap::COL_AGE, $age, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Book object
     *
     * @param \Propel\Tests\Bookstore\Book|ObjectCollection $book  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAuthorQuery The current query, for fluid interface
     */
    public function filterByBook($book, $comparison = null)
    {
        if ($book instanceof \Propel\Tests\Bookstore\Book) {
            return $this
                ->addUsingAlias(AuthorTableMap::COL_ID, $book->getAuthorId(), $comparison);
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
     * @return $this|ChildAuthorQuery The current query, for fluid interface
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
     * Filter the query by a related \Propel\Tests\Bookstore\Essay object
     *
     * @param \Propel\Tests\Bookstore\Essay|ObjectCollection $essay  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAuthorQuery The current query, for fluid interface
     */
    public function filterByEssayRelatedByFirstAuthor($essay, $comparison = null)
    {
        if ($essay instanceof \Propel\Tests\Bookstore\Essay) {
            return $this
                ->addUsingAlias(AuthorTableMap::COL_ID, $essay->getFirstAuthor(), $comparison);
        } elseif ($essay instanceof ObjectCollection) {
            return $this
                ->useEssayRelatedByFirstAuthorQuery()
                ->filterByPrimaryKeys($essay->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByEssayRelatedByFirstAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Essay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the EssayRelatedByFirstAuthor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function joinEssayRelatedByFirstAuthor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('EssayRelatedByFirstAuthor');

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
            $this->addJoinObject($join, 'EssayRelatedByFirstAuthor');
        }

        return $this;
    }

    /**
     * Use the EssayRelatedByFirstAuthor relation Essay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\EssayQuery A secondary query class using the current class as primary query
     */
    public function useEssayRelatedByFirstAuthorQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinEssayRelatedByFirstAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'EssayRelatedByFirstAuthor', '\Propel\Tests\Bookstore\EssayQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Essay object
     *
     * @param \Propel\Tests\Bookstore\Essay|ObjectCollection $essay  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildAuthorQuery The current query, for fluid interface
     */
    public function filterByEssayRelatedBySecondAuthor($essay, $comparison = null)
    {
        if ($essay instanceof \Propel\Tests\Bookstore\Essay) {
            return $this
                ->addUsingAlias(AuthorTableMap::COL_ID, $essay->getSecondAuthor(), $comparison);
        } elseif ($essay instanceof ObjectCollection) {
            return $this
                ->useEssayRelatedBySecondAuthorQuery()
                ->filterByPrimaryKeys($essay->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByEssayRelatedBySecondAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Essay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the EssayRelatedBySecondAuthor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function joinEssayRelatedBySecondAuthor($relationAlias = null, $joinType = 'INNER JOIN')
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('EssayRelatedBySecondAuthor');

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
            $this->addJoinObject($join, 'EssayRelatedBySecondAuthor');
        }

        return $this;
    }

    /**
     * Use the EssayRelatedBySecondAuthor relation Essay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\EssayQuery A secondary query class using the current class as primary query
     */
    public function useEssayRelatedBySecondAuthorQuery($relationAlias = null, $joinType = 'INNER JOIN')
    {
        return $this
            ->joinEssayRelatedBySecondAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'EssayRelatedBySecondAuthor', '\Propel\Tests\Bookstore\EssayQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildAuthor $author Object to remove from the list of results
     *
     * @return $this|ChildAuthorQuery The current query, for fluid interface
     */
    public function prune($author = null)
    {
        if ($author) {
            $this->addUsingAlias(AuthorTableMap::COL_ID, $author->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the author table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AuthorTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            AuthorTableMap::clearInstancePool();
            AuthorTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(AuthorTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(AuthorTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            AuthorTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            AuthorTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // AuthorQuery
