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
use Propel\Tests\Bookstore\Book as ChildBook;
use Propel\Tests\Bookstore\BookQuery as ChildBookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Base class that represents a query for the 'book' table.
 *
 * Book Table
 *
 * @method     ChildBookQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBookQuery orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildBookQuery orderByISBN($order = Criteria::ASC) Order by the isbn column
 * @method     ChildBookQuery orderByPrice($order = Criteria::ASC) Order by the price column
 * @method     ChildBookQuery orderByPublisherId($order = Criteria::ASC) Order by the publisher_id column
 * @method     ChildBookQuery orderByAuthorId($order = Criteria::ASC) Order by the author_id column
 *
 * @method     ChildBookQuery groupById() Group by the id column
 * @method     ChildBookQuery groupByTitle() Group by the title column
 * @method     ChildBookQuery groupByISBN() Group by the isbn column
 * @method     ChildBookQuery groupByPrice() Group by the price column
 * @method     ChildBookQuery groupByPublisherId() Group by the publisher_id column
 * @method     ChildBookQuery groupByAuthorId() Group by the author_id column
 *
 * @method     ChildBookQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBookQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBookQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBookQuery leftJoinPublisher($relationAlias = null) Adds a LEFT JOIN clause to the query using the Publisher relation
 * @method     ChildBookQuery rightJoinPublisher($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Publisher relation
 * @method     ChildBookQuery innerJoinPublisher($relationAlias = null) Adds a INNER JOIN clause to the query using the Publisher relation
 *
 * @method     ChildBookQuery leftJoinAuthor($relationAlias = null) Adds a LEFT JOIN clause to the query using the Author relation
 * @method     ChildBookQuery rightJoinAuthor($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Author relation
 * @method     ChildBookQuery innerJoinAuthor($relationAlias = null) Adds a INNER JOIN clause to the query using the Author relation
 *
 * @method     ChildBookQuery leftJoinBookSummary($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookSummary relation
 * @method     ChildBookQuery rightJoinBookSummary($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookSummary relation
 * @method     ChildBookQuery innerJoinBookSummary($relationAlias = null) Adds a INNER JOIN clause to the query using the BookSummary relation
 *
 * @method     ChildBookQuery leftJoinReview($relationAlias = null) Adds a LEFT JOIN clause to the query using the Review relation
 * @method     ChildBookQuery rightJoinReview($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Review relation
 * @method     ChildBookQuery innerJoinReview($relationAlias = null) Adds a INNER JOIN clause to the query using the Review relation
 *
 * @method     ChildBookQuery leftJoinMedia($relationAlias = null) Adds a LEFT JOIN clause to the query using the Media relation
 * @method     ChildBookQuery rightJoinMedia($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Media relation
 * @method     ChildBookQuery innerJoinMedia($relationAlias = null) Adds a INNER JOIN clause to the query using the Media relation
 *
 * @method     ChildBookQuery leftJoinBookListRel($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookListRel relation
 * @method     ChildBookQuery rightJoinBookListRel($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookListRel relation
 * @method     ChildBookQuery innerJoinBookListRel($relationAlias = null) Adds a INNER JOIN clause to the query using the BookListRel relation
 *
 * @method     ChildBookQuery leftJoinBookListFavorite($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookListFavorite relation
 * @method     ChildBookQuery rightJoinBookListFavorite($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookListFavorite relation
 * @method     ChildBookQuery innerJoinBookListFavorite($relationAlias = null) Adds a INNER JOIN clause to the query using the BookListFavorite relation
 *
 * @method     ChildBookQuery leftJoinBookOpinion($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookOpinion relation
 * @method     ChildBookQuery rightJoinBookOpinion($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookOpinion relation
 * @method     ChildBookQuery innerJoinBookOpinion($relationAlias = null) Adds a INNER JOIN clause to the query using the BookOpinion relation
 *
 * @method     ChildBookQuery leftJoinReaderFavorite($relationAlias = null) Adds a LEFT JOIN clause to the query using the ReaderFavorite relation
 * @method     ChildBookQuery rightJoinReaderFavorite($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ReaderFavorite relation
 * @method     ChildBookQuery innerJoinReaderFavorite($relationAlias = null) Adds a INNER JOIN clause to the query using the ReaderFavorite relation
 *
 * @method     ChildBookQuery leftJoinBookstoreContest($relationAlias = null) Adds a LEFT JOIN clause to the query using the BookstoreContest relation
 * @method     ChildBookQuery rightJoinBookstoreContest($relationAlias = null) Adds a RIGHT JOIN clause to the query using the BookstoreContest relation
 * @method     ChildBookQuery innerJoinBookstoreContest($relationAlias = null) Adds a INNER JOIN clause to the query using the BookstoreContest relation
 *
 * @method     \Propel\Tests\Bookstore\PublisherQuery|\Propel\Tests\Bookstore\AuthorQuery|\Propel\Tests\Bookstore\BookSummaryQuery|\Propel\Tests\Bookstore\ReviewQuery|\Propel\Tests\Bookstore\MediaQuery|\Propel\Tests\Bookstore\BookListRelQuery|\Propel\Tests\Bookstore\BookListFavoriteQuery|\Propel\Tests\Bookstore\BookOpinionQuery|\Propel\Tests\Bookstore\ReaderFavoriteQuery|\Propel\Tests\Bookstore\BookstoreContestQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildBook findOne(ConnectionInterface $con = null) Return the first ChildBook matching the query
 * @method     ChildBook findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBook matching the query, or a new ChildBook object populated from the query conditions when no match is found
 *
 * @method     ChildBook findOneById(int $id) Return the first ChildBook filtered by the id column
 * @method     ChildBook findOneByTitle(string $title) Return the first ChildBook filtered by the title column
 * @method     ChildBook findOneByISBN(string $isbn) Return the first ChildBook filtered by the isbn column
 * @method     ChildBook findOneByPrice(double $price) Return the first ChildBook filtered by the price column
 * @method     ChildBook findOneByPublisherId(int $publisher_id) Return the first ChildBook filtered by the publisher_id column
 * @method     ChildBook findOneByAuthorId(int $author_id) Return the first ChildBook filtered by the author_id column
 *
 * @method     ChildBook[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBook objects based on current ModelCriteria
 * @method     ChildBook[]|ObjectCollection findById(int $id) Return ChildBook objects filtered by the id column
 * @method     ChildBook[]|ObjectCollection findByTitle(string $title) Return ChildBook objects filtered by the title column
 * @method     ChildBook[]|ObjectCollection findByISBN(string $isbn) Return ChildBook objects filtered by the isbn column
 * @method     ChildBook[]|ObjectCollection findByPrice(double $price) Return ChildBook objects filtered by the price column
 * @method     ChildBook[]|ObjectCollection findByPublisherId(int $publisher_id) Return ChildBook objects filtered by the publisher_id column
 * @method     ChildBook[]|ObjectCollection findByAuthorId(int $author_id) Return ChildBook objects filtered by the author_id column
 * @method     ChildBook[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class BookQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Book', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBookQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBookQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBookQuery) {
            return $criteria;
        }
        $query = new ChildBookQuery();
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
     * @return ChildBook|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = BookTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookTableMap::DATABASE_NAME);
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
     * @return ChildBook A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, ISBN, PRICE, PUBLISHER_ID, AUTHOR_ID FROM book WHERE ID = :p0';
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
            /** @var ChildBook $obj */
            $obj = new ChildBook();
            $obj->hydrate($row);
            BookTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBook|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(BookTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(BookTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(BookTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(BookTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildBookQuery The current query, for fluid interface
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

        return $this->addUsingAlias(BookTableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the isbn column
     *
     * Example usage:
     * <code>
     * $query->filterByISBN('fooValue');   // WHERE isbn = 'fooValue'
     * $query->filterByISBN('%fooValue%'); // WHERE isbn LIKE '%fooValue%'
     * </code>
     *
     * @param     string $iSBN The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterByISBN($iSBN = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($iSBN)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $iSBN)) {
                $iSBN = str_replace('*', '%', $iSBN);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(BookTableMap::COL_ISBN, $iSBN, $comparison);
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
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterByPrice($price = null, $comparison = null)
    {
        if (is_array($price)) {
            $useMinMax = false;
            if (isset($price['min'])) {
                $this->addUsingAlias(BookTableMap::COL_PRICE, $price['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($price['max'])) {
                $this->addUsingAlias(BookTableMap::COL_PRICE, $price['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookTableMap::COL_PRICE, $price, $comparison);
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
     * @see       filterByPublisher()
     *
     * @param     mixed $publisherId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterByPublisherId($publisherId = null, $comparison = null)
    {
        if (is_array($publisherId)) {
            $useMinMax = false;
            if (isset($publisherId['min'])) {
                $this->addUsingAlias(BookTableMap::COL_PUBLISHER_ID, $publisherId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($publisherId['max'])) {
                $this->addUsingAlias(BookTableMap::COL_PUBLISHER_ID, $publisherId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookTableMap::COL_PUBLISHER_ID, $publisherId, $comparison);
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
     * @see       filterByAuthor()
     *
     * @param     mixed $authorId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function filterByAuthorId($authorId = null, $comparison = null)
    {
        if (is_array($authorId)) {
            $useMinMax = false;
            if (isset($authorId['min'])) {
                $this->addUsingAlias(BookTableMap::COL_AUTHOR_ID, $authorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($authorId['max'])) {
                $this->addUsingAlias(BookTableMap::COL_AUTHOR_ID, $authorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(BookTableMap::COL_AUTHOR_ID, $authorId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Publisher object
     *
     * @param \Propel\Tests\Bookstore\Publisher|ObjectCollection $publisher The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByPublisher($publisher, $comparison = null)
    {
        if ($publisher instanceof \Propel\Tests\Bookstore\Publisher) {
            return $this
                ->addUsingAlias(BookTableMap::COL_PUBLISHER_ID, $publisher->getId(), $comparison);
        } elseif ($publisher instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookTableMap::COL_PUBLISHER_ID, $publisher->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByPublisher() only accepts arguments of type \Propel\Tests\Bookstore\Publisher or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Publisher relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinPublisher($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Publisher');

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
            $this->addJoinObject($join, 'Publisher');
        }

        return $this;
    }

    /**
     * Use the Publisher relation Publisher object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\PublisherQuery A secondary query class using the current class as primary query
     */
    public function usePublisherQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinPublisher($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Publisher', '\Propel\Tests\Bookstore\PublisherQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Author object
     *
     * @param \Propel\Tests\Bookstore\Author|ObjectCollection $author The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByAuthor($author, $comparison = null)
    {
        if ($author instanceof \Propel\Tests\Bookstore\Author) {
            return $this
                ->addUsingAlias(BookTableMap::COL_AUTHOR_ID, $author->getId(), $comparison);
        } elseif ($author instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(BookTableMap::COL_AUTHOR_ID, $author->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByAuthor() only accepts arguments of type \Propel\Tests\Bookstore\Author or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Author relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinAuthor($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Author');

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
            $this->addJoinObject($join, 'Author');
        }

        return $this;
    }

    /**
     * Use the Author relation Author object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\AuthorQuery A secondary query class using the current class as primary query
     */
    public function useAuthorQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinAuthor($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Author', '\Propel\Tests\Bookstore\AuthorQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookSummary object
     *
     * @param \Propel\Tests\Bookstore\BookSummary|ObjectCollection $bookSummary  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByBookSummary($bookSummary, $comparison = null)
    {
        if ($bookSummary instanceof \Propel\Tests\Bookstore\BookSummary) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $bookSummary->getBookId(), $comparison);
        } elseif ($bookSummary instanceof ObjectCollection) {
            return $this
                ->useBookSummaryQuery()
                ->filterByPrimaryKeys($bookSummary->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookSummary() only accepts arguments of type \Propel\Tests\Bookstore\BookSummary or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookSummary relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinBookSummary($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookSummary');

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
            $this->addJoinObject($join, 'BookSummary');
        }

        return $this;
    }

    /**
     * Use the BookSummary relation BookSummary object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookSummaryQuery A secondary query class using the current class as primary query
     */
    public function useBookSummaryQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookSummary($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookSummary', '\Propel\Tests\Bookstore\BookSummaryQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Review object
     *
     * @param \Propel\Tests\Bookstore\Review|ObjectCollection $review  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByReview($review, $comparison = null)
    {
        if ($review instanceof \Propel\Tests\Bookstore\Review) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $review->getBookId(), $comparison);
        } elseif ($review instanceof ObjectCollection) {
            return $this
                ->useReviewQuery()
                ->filterByPrimaryKeys($review->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByReview() only accepts arguments of type \Propel\Tests\Bookstore\Review or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Review relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinReview($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Review');

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
            $this->addJoinObject($join, 'Review');
        }

        return $this;
    }

    /**
     * Use the Review relation Review object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\ReviewQuery A secondary query class using the current class as primary query
     */
    public function useReviewQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinReview($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Review', '\Propel\Tests\Bookstore\ReviewQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Media object
     *
     * @param \Propel\Tests\Bookstore\Media|ObjectCollection $media  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByMedia($media, $comparison = null)
    {
        if ($media instanceof \Propel\Tests\Bookstore\Media) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $media->getBookId(), $comparison);
        } elseif ($media instanceof ObjectCollection) {
            return $this
                ->useMediaQuery()
                ->filterByPrimaryKeys($media->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByMedia() only accepts arguments of type \Propel\Tests\Bookstore\Media or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Media relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinMedia($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Media');

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
            $this->addJoinObject($join, 'Media');
        }

        return $this;
    }

    /**
     * Use the Media relation Media object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\MediaQuery A secondary query class using the current class as primary query
     */
    public function useMediaQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinMedia($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Media', '\Propel\Tests\Bookstore\MediaQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookListRel object
     *
     * @param \Propel\Tests\Bookstore\BookListRel|ObjectCollection $bookListRel  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByBookListRel($bookListRel, $comparison = null)
    {
        if ($bookListRel instanceof \Propel\Tests\Bookstore\BookListRel) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $bookListRel->getBookId(), $comparison);
        } elseif ($bookListRel instanceof ObjectCollection) {
            return $this
                ->useBookListRelQuery()
                ->filterByPrimaryKeys($bookListRel->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookListRel() only accepts arguments of type \Propel\Tests\Bookstore\BookListRel or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookListRel relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinBookListRel($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookListRel');

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
            $this->addJoinObject($join, 'BookListRel');
        }

        return $this;
    }

    /**
     * Use the BookListRel relation BookListRel object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookListRelQuery A secondary query class using the current class as primary query
     */
    public function useBookListRelQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookListRel($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookListRel', '\Propel\Tests\Bookstore\BookListRelQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookListFavorite object
     *
     * @param \Propel\Tests\Bookstore\BookListFavorite|ObjectCollection $bookListFavorite  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByBookListFavorite($bookListFavorite, $comparison = null)
    {
        if ($bookListFavorite instanceof \Propel\Tests\Bookstore\BookListFavorite) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $bookListFavorite->getBookId(), $comparison);
        } elseif ($bookListFavorite instanceof ObjectCollection) {
            return $this
                ->useBookListFavoriteQuery()
                ->filterByPrimaryKeys($bookListFavorite->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookListFavorite() only accepts arguments of type \Propel\Tests\Bookstore\BookListFavorite or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookListFavorite relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinBookListFavorite($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookListFavorite');

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
            $this->addJoinObject($join, 'BookListFavorite');
        }

        return $this;
    }

    /**
     * Use the BookListFavorite relation BookListFavorite object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookListFavoriteQuery A secondary query class using the current class as primary query
     */
    public function useBookListFavoriteQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookListFavorite($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookListFavorite', '\Propel\Tests\Bookstore\BookListFavoriteQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookOpinion object
     *
     * @param \Propel\Tests\Bookstore\BookOpinion|ObjectCollection $bookOpinion  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByBookOpinion($bookOpinion, $comparison = null)
    {
        if ($bookOpinion instanceof \Propel\Tests\Bookstore\BookOpinion) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $bookOpinion->getBookId(), $comparison);
        } elseif ($bookOpinion instanceof ObjectCollection) {
            return $this
                ->useBookOpinionQuery()
                ->filterByPrimaryKeys($bookOpinion->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookOpinion() only accepts arguments of type \Propel\Tests\Bookstore\BookOpinion or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookOpinion relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinBookOpinion($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookOpinion');

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
            $this->addJoinObject($join, 'BookOpinion');
        }

        return $this;
    }

    /**
     * Use the BookOpinion relation BookOpinion object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookOpinionQuery A secondary query class using the current class as primary query
     */
    public function useBookOpinionQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinBookOpinion($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookOpinion', '\Propel\Tests\Bookstore\BookOpinionQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\ReaderFavorite object
     *
     * @param \Propel\Tests\Bookstore\ReaderFavorite|ObjectCollection $readerFavorite  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByReaderFavorite($readerFavorite, $comparison = null)
    {
        if ($readerFavorite instanceof \Propel\Tests\Bookstore\ReaderFavorite) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $readerFavorite->getBookId(), $comparison);
        } elseif ($readerFavorite instanceof ObjectCollection) {
            return $this
                ->useReaderFavoriteQuery()
                ->filterByPrimaryKeys($readerFavorite->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByReaderFavorite() only accepts arguments of type \Propel\Tests\Bookstore\ReaderFavorite or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ReaderFavorite relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinReaderFavorite($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ReaderFavorite');

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
            $this->addJoinObject($join, 'ReaderFavorite');
        }

        return $this;
    }

    /**
     * Use the ReaderFavorite relation ReaderFavorite object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\ReaderFavoriteQuery A secondary query class using the current class as primary query
     */
    public function useReaderFavoriteQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinReaderFavorite($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ReaderFavorite', '\Propel\Tests\Bookstore\ReaderFavoriteQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\BookstoreContest object
     *
     * @param \Propel\Tests\Bookstore\BookstoreContest|ObjectCollection $bookstoreContest  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByBookstoreContest($bookstoreContest, $comparison = null)
    {
        if ($bookstoreContest instanceof \Propel\Tests\Bookstore\BookstoreContest) {
            return $this
                ->addUsingAlias(BookTableMap::COL_ID, $bookstoreContest->getPrizeBookId(), $comparison);
        } elseif ($bookstoreContest instanceof ObjectCollection) {
            return $this
                ->useBookstoreContestQuery()
                ->filterByPrimaryKeys($bookstoreContest->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByBookstoreContest() only accepts arguments of type \Propel\Tests\Bookstore\BookstoreContest or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the BookstoreContest relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function joinBookstoreContest($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('BookstoreContest');

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
            $this->addJoinObject($join, 'BookstoreContest');
        }

        return $this;
    }

    /**
     * Use the BookstoreContest relation BookstoreContest object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\BookstoreContestQuery A secondary query class using the current class as primary query
     */
    public function useBookstoreContestQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinBookstoreContest($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'BookstoreContest', '\Propel\Tests\Bookstore\BookstoreContestQuery');
    }

    /**
     * Filter the query by a related BookClubList object
     * using the book_x_list table as cross reference
     *
     * @param BookClubList $bookClubList the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByBookClubList($bookClubList, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useBookListRelQuery()
            ->filterByBookClubList($bookClubList, $comparison)
            ->endUse();
    }

    /**
     * Filter the query by a related BookClubList object
     * using the book_club_list_favorite_books table as cross reference
     *
     * @param BookClubList $bookClubList the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildBookQuery The current query, for fluid interface
     */
    public function filterByFavoriteBookClubList($bookClubList, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useBookListFavoriteQuery()
            ->filterByFavoriteBookClubList($bookClubList, $comparison)
            ->endUse();
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBook $book Object to remove from the list of results
     *
     * @return $this|ChildBookQuery The current query, for fluid interface
     */
    public function prune($book = null)
    {
        if ($book) {
            $this->addUsingAlias(BookTableMap::COL_ID, $book->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the book table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            BookTableMap::clearInstancePool();
            BookTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(BookTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            BookTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            BookTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // BookQuery
