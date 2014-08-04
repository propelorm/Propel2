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
use Propel\Tests\Bookstore\Behavior\ConcreteArticle as ChildConcreteArticle;
use Propel\Tests\Bookstore\Behavior\ConcreteArticleQuery as ChildConcreteArticleQuery;
use Propel\Tests\Bookstore\Behavior\ConcreteContentQuery as ChildConcreteContentQuery;
use Propel\Tests\Bookstore\Behavior\Map\ConcreteArticleTableMap;

/**
 * Base class that represents a query for the 'concrete_article' table.
 *
 *
 *
 * @method     ChildConcreteArticleQuery orderByBody($order = Criteria::ASC) Order by the body column
 * @method     ChildConcreteArticleQuery orderByAuthorId($order = Criteria::ASC) Order by the author_id column
 * @method     ChildConcreteArticleQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildConcreteArticleQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildConcreteArticleQuery orderByCategoryId($order = Criteria::ASC) Order by the category_id column
 * @method     ChildConcreteArticleQuery orderByDescendantClass($order = Criteria::ASC) Order by the descendant_class column
 *
 * @method     ChildConcreteArticleQuery groupByBody() Group by the body column
 * @method     ChildConcreteArticleQuery groupByAuthorId() Group by the author_id column
 * @method     ChildConcreteArticleQuery groupById() Group by the id column
 * @method     ChildConcreteArticleQuery groupByTitle() Group by the title column
 * @method     ChildConcreteArticleQuery groupByCategoryId() Group by the category_id column
 * @method     ChildConcreteArticleQuery groupByDescendantClass() Group by the descendant_class column
 *
 * @method     ChildConcreteArticleQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildConcreteArticleQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildConcreteArticleQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildConcreteArticleQuery leftJoinConcreteAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteAuthor relation
 * @method     ChildConcreteArticleQuery rightJoinConcreteAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteAuthor relation
 * @method     ChildConcreteArticleQuery innerJoinConcreteAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteAuthor relation
 *
 * @method     ChildConcreteArticleQuery leftJoinConcreteContent($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteContent relation
 * @method     ChildConcreteArticleQuery rightJoinConcreteContent($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteContent relation
 * @method     ChildConcreteArticleQuery innerJoinConcreteContent($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteContent relation
 *
 * @method     ChildConcreteArticleQuery leftJoinConcreteCategory($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteCategory relation
 * @method     ChildConcreteArticleQuery rightJoinConcreteCategory($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteCategory relation
 * @method     ChildConcreteArticleQuery innerJoinConcreteCategory($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteCategory relation
 *
 * @method     ChildConcreteArticleQuery leftJoinConcreteNews($relationAlias = null) Adds a LEFT JOIN clause to the query using the ConcreteNews relation
 * @method     ChildConcreteArticleQuery rightJoinConcreteNews($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ConcreteNews relation
 * @method     ChildConcreteArticleQuery innerJoinConcreteNews($relationAlias = null) Adds a INNER JOIN clause to the query using the ConcreteNews relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ConcreteAuthorQuery|\Propel\Tests\Bookstore\Behavior\ConcreteContentQuery|\Propel\Tests\Bookstore\Behavior\ConcreteCategoryQuery|\Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildConcreteArticle findOne(ConnectionInterface $con = null) Return the first ChildConcreteArticle matching the query
 * @method     ChildConcreteArticle findOneOrCreate(ConnectionInterface $con = null) Return the first ChildConcreteArticle matching the query, or a new ChildConcreteArticle object populated from the query conditions when no match is found
 *
 * @method     ChildConcreteArticle findOneByBody(string $body) Return the first ChildConcreteArticle filtered by the body column
 * @method     ChildConcreteArticle findOneByAuthorId(int $author_id) Return the first ChildConcreteArticle filtered by the author_id column
 * @method     ChildConcreteArticle findOneById(int $id) Return the first ChildConcreteArticle filtered by the id column
 * @method     ChildConcreteArticle findOneByTitle(string $title) Return the first ChildConcreteArticle filtered by the title column
 * @method     ChildConcreteArticle findOneByCategoryId(int $category_id) Return the first ChildConcreteArticle filtered by the category_id column
 * @method     ChildConcreteArticle findOneByDescendantClass(string $descendant_class) Return the first ChildConcreteArticle filtered by the descendant_class column
 *
 * @method     ChildConcreteArticle[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildConcreteArticle objects based on current ModelCriteria
 * @method     ChildConcreteArticle[]|ObjectCollection findByBody(string $body) Return ChildConcreteArticle objects filtered by the body column
 * @method     ChildConcreteArticle[]|ObjectCollection findByAuthorId(int $author_id) Return ChildConcreteArticle objects filtered by the author_id column
 * @method     ChildConcreteArticle[]|ObjectCollection findById(int $id) Return ChildConcreteArticle objects filtered by the id column
 * @method     ChildConcreteArticle[]|ObjectCollection findByTitle(string $title) Return ChildConcreteArticle objects filtered by the title column
 * @method     ChildConcreteArticle[]|ObjectCollection findByCategoryId(int $category_id) Return ChildConcreteArticle objects filtered by the category_id column
 * @method     ChildConcreteArticle[]|ObjectCollection findByDescendantClass(string $descendant_class) Return ChildConcreteArticle objects filtered by the descendant_class column
 * @method     ChildConcreteArticle[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ConcreteArticleQuery extends ChildConcreteContentQuery
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ConcreteArticleQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ConcreteArticle', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildConcreteArticleQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildConcreteArticleQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildConcreteArticleQuery) {
            return $criteria;
        }
        $query = new ChildConcreteArticleQuery();
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
     * @return ChildConcreteArticle|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ConcreteArticleTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ConcreteArticleTableMap::DATABASE_NAME);
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
     * @return ChildConcreteArticle A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT BODY, AUTHOR_ID, ID, TITLE, CATEGORY_ID, DESCENDANT_CLASS FROM concrete_article WHERE ID = :p0';
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
            /** @var ChildConcreteArticle $obj */
            $obj = new ChildConcreteArticle();
            $obj->hydrate($row);
            ConcreteArticleTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildConcreteArticle|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the body column
     *
     * Example usage:
     * <code>
     * $query->filterByBody('fooValue');   // WHERE body = 'fooValue'
     * $query->filterByBody('%fooValue%'); // WHERE body LIKE '%fooValue%'
     * </code>
     *
     * @param     string $body The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByBody($body = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($body)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $body)) {
                $body = str_replace('*', '%', $body);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_BODY, $body, $comparison);
    }

    /**
     * Filter the query on the author_id column
     *
     * Example usage:
     * <code>
     * $query->filterByAuthorId(1234); // WHERE author_id = 1234
     * $query->filterByAuthorId(array(12, 34)); // WHERE author_id IN (12, 34)
     * $query->filterByAuthorId(array('min' => 12)); // WHERE author_id > 12
     * </code>
     *
     * @see       filterByConcreteAuthor()
     *
     * @param     mixed $authorId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByAuthorId($authorId = null, $comparison = null)
    {
        if (is_array($authorId)) {
            $useMinMax = false;
            if (isset($authorId['min'])) {
                $this->addUsingAlias(ConcreteArticleTableMap::COL_AUTHOR_ID, $authorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($authorId['max'])) {
                $this->addUsingAlias(ConcreteArticleTableMap::COL_AUTHOR_ID, $authorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_AUTHOR_ID, $authorId, $comparison);
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
     * @see       filterByConcreteContent()
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ConcreteArticleTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ConcreteArticleTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_TITLE, $title, $comparison);
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
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByCategoryId($categoryId = null, $comparison = null)
    {
        if (is_array($categoryId)) {
            $useMinMax = false;
            if (isset($categoryId['min'])) {
                $this->addUsingAlias(ConcreteArticleTableMap::COL_CATEGORY_ID, $categoryId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($categoryId['max'])) {
                $this->addUsingAlias(ConcreteArticleTableMap::COL_CATEGORY_ID, $categoryId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_CATEGORY_ID, $categoryId, $comparison);
    }

    /**
     * Filter the query on the descendant_class column
     *
     * Example usage:
     * <code>
     * $query->filterByDescendantClass('fooValue');   // WHERE descendant_class = 'fooValue'
     * $query->filterByDescendantClass('%fooValue%'); // WHERE descendant_class LIKE '%fooValue%'
     * </code>
     *
     * @param     string $descendantClass The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByDescendantClass($descendantClass = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($descendantClass)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $descendantClass)) {
                $descendantClass = str_replace('*', '%', $descendantClass);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ConcreteArticleTableMap::COL_DESCENDANT_CLASS, $descendantClass, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteAuthor object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteAuthor|ObjectCollection $concreteAuthor The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByConcreteAuthor($concreteAuthor, $comparison = null)
    {
        if ($concreteAuthor instanceof \Propel\Tests\Bookstore\Behavior\ConcreteAuthor) {
            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_AUTHOR_ID, $concreteAuthor->getId(), $comparison);
        } elseif ($concreteAuthor instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_AUTHOR_ID, $concreteAuthor->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByConcreteAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ConcreteAuthor or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ConcreteAuthor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function joinConcreteAuthor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ConcreteAuthor');

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
            $this->addJoinObject($join, 'ConcreteAuthor');
        }

        return $this;
    }

    /**
     * Use the ConcreteAuthor relation ConcreteAuthor object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteAuthorQuery A secondary query class using the current class as primary query
     */
    public function useConcreteAuthorQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinConcreteAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ConcreteAuthor', '\Propel\Tests\Bookstore\Behavior\ConcreteAuthorQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteContent object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteContent|ObjectCollection $concreteContent The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByConcreteContent($concreteContent, $comparison = null)
    {
        if ($concreteContent instanceof \Propel\Tests\Bookstore\Behavior\ConcreteContent) {
            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_ID, $concreteContent->getId(), $comparison);
        } elseif ($concreteContent instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_ID, $concreteContent->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByConcreteContent() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ConcreteContent or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ConcreteContent relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function joinConcreteContent($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ConcreteContent');

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
            $this->addJoinObject($join, 'ConcreteContent');
        }

        return $this;
    }

    /**
     * Use the ConcreteContent relation ConcreteContent object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ConcreteContentQuery A secondary query class using the current class as primary query
     */
    public function useConcreteContentQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinConcreteContent($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ConcreteContent', '\Propel\Tests\Bookstore\Behavior\ConcreteContentQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteCategory object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteCategory|ObjectCollection $concreteCategory The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByConcreteCategory($concreteCategory, $comparison = null)
    {
        if ($concreteCategory instanceof \Propel\Tests\Bookstore\Behavior\ConcreteCategory) {
            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_CATEGORY_ID, $concreteCategory->getId(), $comparison);
        } elseif ($concreteCategory instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_CATEGORY_ID, $concreteCategory->toKeyValue('PrimaryKey', 'Id'), $comparison);
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
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
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
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ConcreteNews object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ConcreteNews|ObjectCollection $concreteNews  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function filterByConcreteNews($concreteNews, $comparison = null)
    {
        if ($concreteNews instanceof \Propel\Tests\Bookstore\Behavior\ConcreteNews) {
            return $this
                ->addUsingAlias(ConcreteArticleTableMap::COL_ID, $concreteNews->getId(), $comparison);
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
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function joinConcreteNews($relationAlias = null, $joinType = Criteria::INNER_JOIN)
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
    public function useConcreteNewsQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinConcreteNews($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ConcreteNews', '\Propel\Tests\Bookstore\Behavior\ConcreteNewsQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildConcreteArticle $concreteArticle Object to remove from the list of results
     *
     * @return $this|ChildConcreteArticleQuery The current query, for fluid interface
     */
    public function prune($concreteArticle = null)
    {
        if ($concreteArticle) {
            $this->addUsingAlias(ConcreteArticleTableMap::COL_ID, $concreteArticle->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the concrete_article table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteArticleTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ConcreteArticleTableMap::clearInstancePool();
            ConcreteArticleTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ConcreteArticleTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ConcreteArticleTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ConcreteArticleTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ConcreteArticleTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ConcreteArticleQuery
