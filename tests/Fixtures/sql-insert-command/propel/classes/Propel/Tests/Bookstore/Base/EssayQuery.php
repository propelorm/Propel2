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
use Propel\Tests\Bookstore\Essay as ChildEssay;
use Propel\Tests\Bookstore\EssayQuery as ChildEssayQuery;
use Propel\Tests\Bookstore\Map\EssayTableMap;

/**
 * Base class that represents a query for the 'essay' table.
 *
 *
 *
 * @method     ChildEssayQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildEssayQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildEssayQuery orderByFirstAuthor($order = Criteria::ASC) Order by the first_author column
 * @method     ChildEssayQuery orderBySecondAuthor($order = Criteria::ASC) Order by the second_author column
 * @method     ChildEssayQuery orderBySecondTitle($order = Criteria::ASC) Order by the subtitle column
 * @method     ChildEssayQuery orderByNextEssayId($order = Criteria::ASC) Order by the next_essay_id column
 *
 * @method     ChildEssayQuery groupById() Group by the id column
 * @method     ChildEssayQuery groupByTitle() Group by the title column
 * @method     ChildEssayQuery groupByFirstAuthor() Group by the first_author column
 * @method     ChildEssayQuery groupBySecondAuthor() Group by the second_author column
 * @method     ChildEssayQuery groupBySecondTitle() Group by the subtitle column
 * @method     ChildEssayQuery groupByNextEssayId() Group by the next_essay_id column
 *
 * @method     ChildEssayQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildEssayQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildEssayQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildEssayQuery leftJoinAuthorRelatedByFirstAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the AuthorRelatedByFirstAuthor relation
 * @method     ChildEssayQuery rightJoinAuthorRelatedByFirstAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AuthorRelatedByFirstAuthor relation
 * @method     ChildEssayQuery innerJoinAuthorRelatedByFirstAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the AuthorRelatedByFirstAuthor relation
 *
 * @method     ChildEssayQuery leftJoinAuthorRelatedBySecondAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the AuthorRelatedBySecondAuthor relation
 * @method     ChildEssayQuery rightJoinAuthorRelatedBySecondAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the AuthorRelatedBySecondAuthor relation
 * @method     ChildEssayQuery innerJoinAuthorRelatedBySecondAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the AuthorRelatedBySecondAuthor relation
 *
 * @method     ChildEssayQuery leftJoinEssayRelatedByNextEssayId($relationAlias = null) Adds a LEFT JOIN clause to the query using the EssayRelatedByNextEssayId relation
 * @method     ChildEssayQuery rightJoinEssayRelatedByNextEssayId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the EssayRelatedByNextEssayId relation
 * @method     ChildEssayQuery innerJoinEssayRelatedByNextEssayId($relationAlias = null) Adds a INNER JOIN clause to the query using the EssayRelatedByNextEssayId relation
 *
 * @method     ChildEssayQuery leftJoinEssayRelatedById($relationAlias = null) Adds a LEFT JOIN clause to the query using the EssayRelatedById relation
 * @method     ChildEssayQuery rightJoinEssayRelatedById($relationAlias = null) Adds a RIGHT JOIN clause to the query using the EssayRelatedById relation
 * @method     ChildEssayQuery innerJoinEssayRelatedById($relationAlias = null) Adds a INNER JOIN clause to the query using the EssayRelatedById relation
 *
 * @method     \Propel\Tests\Bookstore\AuthorQuery|\Propel\Tests\Bookstore\EssayQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildEssay findOne(ConnectionInterface $con = null) Return the first ChildEssay matching the query
 * @method     ChildEssay findOneOrCreate(ConnectionInterface $con = null) Return the first ChildEssay matching the query, or a new ChildEssay object populated from the query conditions when no match is found
 *
 * @method     ChildEssay findOneById(int $id) Return the first ChildEssay filtered by the id column
 * @method     ChildEssay findOneByTitle(string $title) Return the first ChildEssay filtered by the title column
 * @method     ChildEssay findOneByFirstAuthor(int $first_author) Return the first ChildEssay filtered by the first_author column
 * @method     ChildEssay findOneBySecondAuthor(int $second_author) Return the first ChildEssay filtered by the second_author column
 * @method     ChildEssay findOneBySecondTitle(string $subtitle) Return the first ChildEssay filtered by the subtitle column
 * @method     ChildEssay findOneByNextEssayId(int $next_essay_id) Return the first ChildEssay filtered by the next_essay_id column
 *
 * @method     ChildEssay[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildEssay objects based on current ModelCriteria
 * @method     ChildEssay[]|ObjectCollection findById(int $id) Return ChildEssay objects filtered by the id column
 * @method     ChildEssay[]|ObjectCollection findByTitle(string $title) Return ChildEssay objects filtered by the title column
 * @method     ChildEssay[]|ObjectCollection findByFirstAuthor(int $first_author) Return ChildEssay objects filtered by the first_author column
 * @method     ChildEssay[]|ObjectCollection findBySecondAuthor(int $second_author) Return ChildEssay objects filtered by the second_author column
 * @method     ChildEssay[]|ObjectCollection findBySecondTitle(string $subtitle) Return ChildEssay objects filtered by the subtitle column
 * @method     ChildEssay[]|ObjectCollection findByNextEssayId(int $next_essay_id) Return ChildEssay objects filtered by the next_essay_id column
 * @method     ChildEssay[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class EssayQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\EssayQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Essay', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildEssayQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildEssayQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildEssayQuery) {
            return $criteria;
        }
        $query = new ChildEssayQuery();
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
     * @return ChildEssay|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = EssayTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(EssayTableMap::DATABASE_NAME);
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
     * @return ChildEssay A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, FIRST_AUTHOR, SECOND_AUTHOR, SUBTITLE, NEXT_ESSAY_ID FROM essay WHERE ID = :p0';
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
            /** @var ChildEssay $obj */
            $obj = new ChildEssay();
            $obj->hydrate($row);
            EssayTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildEssay|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(EssayTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(EssayTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(EssayTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(EssayTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(EssayTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildEssayQuery The current query, for fluid interface
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

        return $this->addUsingAlias(EssayTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the first_author column
     *
     * Example usage:
     * <code>
     * $query->filterByFirstAuthor(1234); // WHERE first_author = 1234
     * $query->filterByFirstAuthor(array(12, 34)); // WHERE first_author IN (12, 34)
     * $query->filterByFirstAuthor(array('min' => 12)); // WHERE first_author > 12
     * </code>
     *
     * @see       filterByAuthorRelatedByFirstAuthor()
     *
     * @param     mixed $firstAuthor The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterByFirstAuthor($firstAuthor = null, $comparison = null)
    {
        if (is_array($firstAuthor)) {
            $useMinMax = false;
            if (isset($firstAuthor['min'])) {
                $this->addUsingAlias(EssayTableMap::COL_FIRST_AUTHOR, $firstAuthor['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($firstAuthor['max'])) {
                $this->addUsingAlias(EssayTableMap::COL_FIRST_AUTHOR, $firstAuthor['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(EssayTableMap::COL_FIRST_AUTHOR, $firstAuthor, $comparison);
    }

    /**
     * Filter the query on the second_author column
     *
     * Example usage:
     * <code>
     * $query->filterBySecondAuthor(1234); // WHERE second_author = 1234
     * $query->filterBySecondAuthor(array(12, 34)); // WHERE second_author IN (12, 34)
     * $query->filterBySecondAuthor(array('min' => 12)); // WHERE second_author > 12
     * </code>
     *
     * @see       filterByAuthorRelatedBySecondAuthor()
     *
     * @param     mixed $secondAuthor The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterBySecondAuthor($secondAuthor = null, $comparison = null)
    {
        if (is_array($secondAuthor)) {
            $useMinMax = false;
            if (isset($secondAuthor['min'])) {
                $this->addUsingAlias(EssayTableMap::COL_SECOND_AUTHOR, $secondAuthor['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($secondAuthor['max'])) {
                $this->addUsingAlias(EssayTableMap::COL_SECOND_AUTHOR, $secondAuthor['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(EssayTableMap::COL_SECOND_AUTHOR, $secondAuthor, $comparison);
    }

    /**
     * Filter the query on the subtitle column
     *
     * Example usage:
     * <code>
     * $query->filterBySecondTitle('fooValue');   // WHERE subtitle = 'fooValue'
     * $query->filterBySecondTitle('%fooValue%'); // WHERE subtitle LIKE '%fooValue%'
     * </code>
     *
     * @param     string $secondTitle The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterBySecondTitle($secondTitle = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($secondTitle)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $secondTitle)) {
                $secondTitle = str_replace('*', '%', $secondTitle);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(EssayTableMap::COL_SUBTITLE, $secondTitle, $comparison);
    }

    /**
     * Filter the query on the next_essay_id column
     *
     * Example usage:
     * <code>
     * $query->filterByNextEssayId(1234); // WHERE next_essay_id = 1234
     * $query->filterByNextEssayId(array(12, 34)); // WHERE next_essay_id IN (12, 34)
     * $query->filterByNextEssayId(array('min' => 12)); // WHERE next_essay_id > 12
     * </code>
     *
     * @see       filterByEssayRelatedByNextEssayId()
     *
     * @param     mixed $nextEssayId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function filterByNextEssayId($nextEssayId = null, $comparison = null)
    {
        if (is_array($nextEssayId)) {
            $useMinMax = false;
            if (isset($nextEssayId['min'])) {
                $this->addUsingAlias(EssayTableMap::COL_NEXT_ESSAY_ID, $nextEssayId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($nextEssayId['max'])) {
                $this->addUsingAlias(EssayTableMap::COL_NEXT_ESSAY_ID, $nextEssayId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(EssayTableMap::COL_NEXT_ESSAY_ID, $nextEssayId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Author object
     *
     * @param \Propel\Tests\Bookstore\Author|ObjectCollection $author The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildEssayQuery The current query, for fluid interface
     */
    public function filterByAuthorRelatedByFirstAuthor($author, $comparison = null)
    {
        if ($author instanceof \Propel\Tests\Bookstore\Author) {
            return $this
                ->addUsingAlias(EssayTableMap::COL_FIRST_AUTHOR, $author->getId(), $comparison);
        } elseif ($author instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(EssayTableMap::COL_FIRST_AUTHOR, $author->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByAuthorRelatedByFirstAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Author or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AuthorRelatedByFirstAuthor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function joinAuthorRelatedByFirstAuthor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AuthorRelatedByFirstAuthor');

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
            $this->addJoinObject($join, 'AuthorRelatedByFirstAuthor');
        }

        return $this;
    }

    /**
     * Use the AuthorRelatedByFirstAuthor relation Author object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\AuthorQuery A secondary query class using the current class as primary query
     */
    public function useAuthorRelatedByFirstAuthorQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAuthorRelatedByFirstAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AuthorRelatedByFirstAuthor', '\Propel\Tests\Bookstore\AuthorQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Author object
     *
     * @param \Propel\Tests\Bookstore\Author|ObjectCollection $author The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildEssayQuery The current query, for fluid interface
     */
    public function filterByAuthorRelatedBySecondAuthor($author, $comparison = null)
    {
        if ($author instanceof \Propel\Tests\Bookstore\Author) {
            return $this
                ->addUsingAlias(EssayTableMap::COL_SECOND_AUTHOR, $author->getId(), $comparison);
        } elseif ($author instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(EssayTableMap::COL_SECOND_AUTHOR, $author->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByAuthorRelatedBySecondAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Author or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the AuthorRelatedBySecondAuthor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function joinAuthorRelatedBySecondAuthor($relationAlias = null, $joinType = 'INNER JOIN')
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('AuthorRelatedBySecondAuthor');

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
            $this->addJoinObject($join, 'AuthorRelatedBySecondAuthor');
        }

        return $this;
    }

    /**
     * Use the AuthorRelatedBySecondAuthor relation Author object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\AuthorQuery A secondary query class using the current class as primary query
     */
    public function useAuthorRelatedBySecondAuthorQuery($relationAlias = null, $joinType = 'INNER JOIN')
    {
        return $this
            ->joinAuthorRelatedBySecondAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'AuthorRelatedBySecondAuthor', '\Propel\Tests\Bookstore\AuthorQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Essay object
     *
     * @param \Propel\Tests\Bookstore\Essay|ObjectCollection $essay The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildEssayQuery The current query, for fluid interface
     */
    public function filterByEssayRelatedByNextEssayId($essay, $comparison = null)
    {
        if ($essay instanceof \Propel\Tests\Bookstore\Essay) {
            return $this
                ->addUsingAlias(EssayTableMap::COL_NEXT_ESSAY_ID, $essay->getId(), $comparison);
        } elseif ($essay instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(EssayTableMap::COL_NEXT_ESSAY_ID, $essay->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByEssayRelatedByNextEssayId() only accepts arguments of type \Propel\Tests\Bookstore\Essay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the EssayRelatedByNextEssayId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function joinEssayRelatedByNextEssayId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('EssayRelatedByNextEssayId');

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
            $this->addJoinObject($join, 'EssayRelatedByNextEssayId');
        }

        return $this;
    }

    /**
     * Use the EssayRelatedByNextEssayId relation Essay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\EssayQuery A secondary query class using the current class as primary query
     */
    public function useEssayRelatedByNextEssayIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinEssayRelatedByNextEssayId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'EssayRelatedByNextEssayId', '\Propel\Tests\Bookstore\EssayQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Essay object
     *
     * @param \Propel\Tests\Bookstore\Essay|ObjectCollection $essay  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildEssayQuery The current query, for fluid interface
     */
    public function filterByEssayRelatedById($essay, $comparison = null)
    {
        if ($essay instanceof \Propel\Tests\Bookstore\Essay) {
            return $this
                ->addUsingAlias(EssayTableMap::COL_ID, $essay->getNextEssayId(), $comparison);
        } elseif ($essay instanceof ObjectCollection) {
            return $this
                ->useEssayRelatedByIdQuery()
                ->filterByPrimaryKeys($essay->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByEssayRelatedById() only accepts arguments of type \Propel\Tests\Bookstore\Essay or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the EssayRelatedById relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function joinEssayRelatedById($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('EssayRelatedById');

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
            $this->addJoinObject($join, 'EssayRelatedById');
        }

        return $this;
    }

    /**
     * Use the EssayRelatedById relation Essay object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\EssayQuery A secondary query class using the current class as primary query
     */
    public function useEssayRelatedByIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinEssayRelatedById($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'EssayRelatedById', '\Propel\Tests\Bookstore\EssayQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildEssay $essay Object to remove from the list of results
     *
     * @return $this|ChildEssayQuery The current query, for fluid interface
     */
    public function prune($essay = null)
    {
        if ($essay) {
            $this->addUsingAlias(EssayTableMap::COL_ID, $essay->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the essay table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(EssayTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            EssayTableMap::clearInstancePool();
            EssayTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(EssayTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(EssayTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            EssayTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            EssayTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // EssayQuery
