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
use Propel\Tests\Bookstore\Behavior\ValidateBook as ChildValidateBook;
use Propel\Tests\Bookstore\Behavior\ValidateBookQuery as ChildValidateBookQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateBookTableMap;

/**
 * Base class that represents a query for the 'validate_book' table.
 *
 * Book Table
 *
 * @method     ChildValidateBookQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildValidateBookQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildValidateBookQuery orderByIsbn($order = Criteria::ASC) Order by the isbn column
 * @method     ChildValidateBookQuery orderByPrice($order = Criteria::ASC) Order by the price column
 * @method     ChildValidateBookQuery orderByPublisherId($order = Criteria::ASC) Order by the publisher_id column
 * @method     ChildValidateBookQuery orderByAuthorId($order = Criteria::ASC) Order by the author_id column
 *
 * @method     ChildValidateBookQuery groupById() Group by the id column
 * @method     ChildValidateBookQuery groupByTitle() Group by the title column
 * @method     ChildValidateBookQuery groupByIsbn() Group by the isbn column
 * @method     ChildValidateBookQuery groupByPrice() Group by the price column
 * @method     ChildValidateBookQuery groupByPublisherId() Group by the publisher_id column
 * @method     ChildValidateBookQuery groupByAuthorId() Group by the author_id column
 *
 * @method     ChildValidateBookQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildValidateBookQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildValidateBookQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildValidateBookQuery leftJoinValidatePublisher($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidatePublisher relation
 * @method     ChildValidateBookQuery rightJoinValidatePublisher($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidatePublisher relation
 * @method     ChildValidateBookQuery innerJoinValidatePublisher($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidatePublisher relation
 *
 * @method     ChildValidateBookQuery leftJoinValidateAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateAuthor relation
 * @method     ChildValidateBookQuery rightJoinValidateAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateAuthor relation
 * @method     ChildValidateBookQuery innerJoinValidateAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateAuthor relation
 *
 * @method     ChildValidateBookQuery leftJoinValidateReaderBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateReaderBook relation
 * @method     ChildValidateBookQuery rightJoinValidateReaderBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateReaderBook relation
 * @method     ChildValidateBookQuery innerJoinValidateReaderBook($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateReaderBook relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ValidatePublisherQuery|\Propel\Tests\Bookstore\Behavior\ValidateAuthorQuery|\Propel\Tests\Bookstore\Behavior\ValidateReaderBookQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildValidateBook findOne(ConnectionInterface $con = null) Return the first ChildValidateBook matching the query
 * @method     ChildValidateBook findOneOrCreate(ConnectionInterface $con = null) Return the first ChildValidateBook matching the query, or a new ChildValidateBook object populated from the query conditions when no match is found
 *
 * @method     ChildValidateBook findOneById(int $id) Return the first ChildValidateBook filtered by the id column
 * @method     ChildValidateBook findOneByTitle(string $title) Return the first ChildValidateBook filtered by the title column
 * @method     ChildValidateBook findOneByIsbn(string $isbn) Return the first ChildValidateBook filtered by the isbn column
 * @method     ChildValidateBook findOneByPrice(double $price) Return the first ChildValidateBook filtered by the price column
 * @method     ChildValidateBook findOneByPublisherId(int $publisher_id) Return the first ChildValidateBook filtered by the publisher_id column
 * @method     ChildValidateBook findOneByAuthorId(int $author_id) Return the first ChildValidateBook filtered by the author_id column
 *
 * @method     ChildValidateBook[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildValidateBook objects based on current ModelCriteria
 * @method     ChildValidateBook[]|ObjectCollection findById(int $id) Return ChildValidateBook objects filtered by the id column
 * @method     ChildValidateBook[]|ObjectCollection findByTitle(string $title) Return ChildValidateBook objects filtered by the title column
 * @method     ChildValidateBook[]|ObjectCollection findByIsbn(string $isbn) Return ChildValidateBook objects filtered by the isbn column
 * @method     ChildValidateBook[]|ObjectCollection findByPrice(double $price) Return ChildValidateBook objects filtered by the price column
 * @method     ChildValidateBook[]|ObjectCollection findByPublisherId(int $publisher_id) Return ChildValidateBook objects filtered by the publisher_id column
 * @method     ChildValidateBook[]|ObjectCollection findByAuthorId(int $author_id) Return ChildValidateBook objects filtered by the author_id column
 * @method     ChildValidateBook[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ValidateBookQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ValidateBookQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateBook', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildValidateBookQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildValidateBookQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildValidateBookQuery) {
            return $criteria;
        }
        $query = new ChildValidateBookQuery();
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
     * @return ChildValidateBook|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ValidateBookTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ValidateBookTableMap::DATABASE_NAME);
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
     * @return ChildValidateBook A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, ISBN, PRICE, PUBLISHER_ID, AUTHOR_ID FROM validate_book WHERE ID = :p0';
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
            /** @var ChildValidateBook $obj */
            $obj = new ChildValidateBook();
            $obj->hydrate($row);
            ValidateBookTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildValidateBook|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ValidateBookTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ValidateBookTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateBookTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ValidateBookTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the isbn column
     *
     * Example usage:
     * <code>
     * $query->filterByIsbn('fooValue');   // WHERE isbn = 'fooValue'
     * $query->filterByIsbn('%fooValue%'); // WHERE isbn LIKE '%fooValue%'
     * </code>
     *
     * @param     string $isbn The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByIsbn($isbn = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($isbn)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $isbn)) {
                $isbn = str_replace('*', '%', $isbn);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ValidateBookTableMap::COL_ISBN, $isbn, $comparison);
    }

    /**
     * Filter the query on the price column
     *
     * Example usage:
     * <code>
     * $query->filterByPrice(1234); // WHERE price = 1234
     * $query->filterByPrice(array(12, 34)); // WHERE price IN (12, 34)
     * $query->filterByPrice(array('min' => 12)); // WHERE price > 12
     * </code>
     *
     * @param     mixed $price The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByPrice($price = null, $comparison = null)
    {
        if (is_array($price)) {
            $useMinMax = false;
            if (isset($price['min'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_PRICE, $price['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($price['max'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_PRICE, $price['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateBookTableMap::COL_PRICE, $price, $comparison);
    }

    /**
     * Filter the query on the publisher_id column
     *
     * Example usage:
     * <code>
     * $query->filterByPublisherId(1234); // WHERE publisher_id = 1234
     * $query->filterByPublisherId(array(12, 34)); // WHERE publisher_id IN (12, 34)
     * $query->filterByPublisherId(array('min' => 12)); // WHERE publisher_id > 12
     * </code>
     *
     * @see       filterByValidatePublisher()
     *
     * @param     mixed $publisherId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByPublisherId($publisherId = null, $comparison = null)
    {
        if (is_array($publisherId)) {
            $useMinMax = false;
            if (isset($publisherId['min'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_PUBLISHER_ID, $publisherId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($publisherId['max'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_PUBLISHER_ID, $publisherId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateBookTableMap::COL_PUBLISHER_ID, $publisherId, $comparison);
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
     * @see       filterByValidateAuthor()
     *
     * @param     mixed $authorId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByAuthorId($authorId = null, $comparison = null)
    {
        if (is_array($authorId)) {
            $useMinMax = false;
            if (isset($authorId['min'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_AUTHOR_ID, $authorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($authorId['max'])) {
                $this->addUsingAlias(ValidateBookTableMap::COL_AUTHOR_ID, $authorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateBookTableMap::COL_AUTHOR_ID, $authorId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidatePublisher object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidatePublisher|ObjectCollection $validatePublisher The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByValidatePublisher($validatePublisher, $comparison = null)
    {
        if ($validatePublisher instanceof \Propel\Tests\Bookstore\Behavior\ValidatePublisher) {
            return $this
                ->addUsingAlias(ValidateBookTableMap::COL_PUBLISHER_ID, $validatePublisher->getId(), $comparison);
        } elseif ($validatePublisher instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ValidateBookTableMap::COL_PUBLISHER_ID, $validatePublisher->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByValidatePublisher() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidatePublisher or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidatePublisher relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function joinValidatePublisher($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidatePublisher');

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
            $this->addJoinObject($join, 'ValidatePublisher');
        }

        return $this;
    }

    /**
     * Use the ValidatePublisher relation ValidatePublisher object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidatePublisherQuery A secondary query class using the current class as primary query
     */
    public function useValidatePublisherQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinValidatePublisher($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidatePublisher', '\Propel\Tests\Bookstore\Behavior\ValidatePublisherQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateAuthor object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateAuthor|ObjectCollection $validateAuthor The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByValidateAuthor($validateAuthor, $comparison = null)
    {
        if ($validateAuthor instanceof \Propel\Tests\Bookstore\Behavior\ValidateAuthor) {
            return $this
                ->addUsingAlias(ValidateBookTableMap::COL_AUTHOR_ID, $validateAuthor->getId(), $comparison);
        } elseif ($validateAuthor instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ValidateBookTableMap::COL_AUTHOR_ID, $validateAuthor->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByValidateAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateAuthor or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateAuthor relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function joinValidateAuthor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateAuthor');

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
            $this->addJoinObject($join, 'ValidateAuthor');
        }

        return $this;
    }

    /**
     * Use the ValidateAuthor relation ValidateAuthor object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateAuthorQuery A secondary query class using the current class as primary query
     */
    public function useValidateAuthorQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinValidateAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateAuthor', '\Propel\Tests\Bookstore\Behavior\ValidateAuthorQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateReaderBook object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateReaderBook|ObjectCollection $validateReaderBook  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByValidateReaderBook($validateReaderBook, $comparison = null)
    {
        if ($validateReaderBook instanceof \Propel\Tests\Bookstore\Behavior\ValidateReaderBook) {
            return $this
                ->addUsingAlias(ValidateBookTableMap::COL_ID, $validateReaderBook->getBookId(), $comparison);
        } elseif ($validateReaderBook instanceof ObjectCollection) {
            return $this
                ->useValidateReaderBookQuery()
                ->filterByPrimaryKeys($validateReaderBook->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByValidateReaderBook() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateReaderBook or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateReaderBook relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function joinValidateReaderBook($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateReaderBook');

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
            $this->addJoinObject($join, 'ValidateReaderBook');
        }

        return $this;
    }

    /**
     * Use the ValidateReaderBook relation ValidateReaderBook object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateReaderBookQuery A secondary query class using the current class as primary query
     */
    public function useValidateReaderBookQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinValidateReaderBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateReaderBook', '\Propel\Tests\Bookstore\Behavior\ValidateReaderBookQuery');
    }

    /**
     * Filter the query by a related ValidateReader object
     * using the validate_reader_book table as cross reference
     *
     * @param ValidateReader $validateReader the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateBookQuery The current query, for fluid interface
     */
    public function filterByValidateReader($validateReader, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useValidateReaderBookQuery()
            ->filterByValidateReader($validateReader, $comparison)
            ->endUse();
    }

    /**
     * Exclude object from result
     *
     * @param   ChildValidateBook $validateBook Object to remove from the list of results
     *
     * @return $this|ChildValidateBookQuery The current query, for fluid interface
     */
    public function prune($validateBook = null)
    {
        if ($validateBook) {
            $this->addUsingAlias(ValidateBookTableMap::COL_ID, $validateBook->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the validate_book table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateBookTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ValidateBookTableMap::clearInstancePool();
            ValidateBookTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateBookTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ValidateBookTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ValidateBookTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ValidateBookTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ValidateBookQuery
