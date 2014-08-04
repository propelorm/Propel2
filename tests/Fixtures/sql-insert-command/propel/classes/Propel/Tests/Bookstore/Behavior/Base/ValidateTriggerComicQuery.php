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
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery as ChildValidateTriggerBookQuery;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerComic as ChildValidateTriggerComic;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18nQuery as ChildValidateTriggerComicI18nQuery;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerComicQuery as ChildValidateTriggerComicQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateTriggerComicTableMap;

/**
 * Base class that represents a query for the 'validate_trigger_comic' table.
 *
 *
 *
 * @method     ChildValidateTriggerComicQuery orderByBar($order = Criteria::ASC) Order by the bar column
 * @method     ChildValidateTriggerComicQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildValidateTriggerComicQuery orderByISBN($order = Criteria::ASC) Order by the isbn column
 * @method     ChildValidateTriggerComicQuery orderByPrice($order = Criteria::ASC) Order by the price column
 * @method     ChildValidateTriggerComicQuery orderByPublisherId($order = Criteria::ASC) Order by the publisher_id column
 * @method     ChildValidateTriggerComicQuery orderByAuthorId($order = Criteria::ASC) Order by the author_id column
 *
 * @method     ChildValidateTriggerComicQuery groupByBar() Group by the bar column
 * @method     ChildValidateTriggerComicQuery groupById() Group by the id column
 * @method     ChildValidateTriggerComicQuery groupByISBN() Group by the isbn column
 * @method     ChildValidateTriggerComicQuery groupByPrice() Group by the price column
 * @method     ChildValidateTriggerComicQuery groupByPublisherId() Group by the publisher_id column
 * @method     ChildValidateTriggerComicQuery groupByAuthorId() Group by the author_id column
 *
 * @method     ChildValidateTriggerComicQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildValidateTriggerComicQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildValidateTriggerComicQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildValidateTriggerComicQuery leftJoinValidateTriggerBook($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateTriggerBook relation
 * @method     ChildValidateTriggerComicQuery rightJoinValidateTriggerBook($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateTriggerBook relation
 * @method     ChildValidateTriggerComicQuery innerJoinValidateTriggerBook($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateTriggerBook relation
 *
 * @method     ChildValidateTriggerComicQuery leftJoinValidateTriggerComicI18n($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateTriggerComicI18n relation
 * @method     ChildValidateTriggerComicQuery rightJoinValidateTriggerComicI18n($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateTriggerComicI18n relation
 * @method     ChildValidateTriggerComicQuery innerJoinValidateTriggerComicI18n($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateTriggerComicI18n relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery|\Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18nQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildValidateTriggerComic findOne(ConnectionInterface $con = null) Return the first ChildValidateTriggerComic matching the query
 * @method     ChildValidateTriggerComic findOneOrCreate(ConnectionInterface $con = null) Return the first ChildValidateTriggerComic matching the query, or a new ChildValidateTriggerComic object populated from the query conditions when no match is found
 *
 * @method     ChildValidateTriggerComic findOneByBar(string $bar) Return the first ChildValidateTriggerComic filtered by the bar column
 * @method     ChildValidateTriggerComic findOneById(int $id) Return the first ChildValidateTriggerComic filtered by the id column
 * @method     ChildValidateTriggerComic findOneByISBN(string $isbn) Return the first ChildValidateTriggerComic filtered by the isbn column
 * @method     ChildValidateTriggerComic findOneByPrice(double $price) Return the first ChildValidateTriggerComic filtered by the price column
 * @method     ChildValidateTriggerComic findOneByPublisherId(int $publisher_id) Return the first ChildValidateTriggerComic filtered by the publisher_id column
 * @method     ChildValidateTriggerComic findOneByAuthorId(int $author_id) Return the first ChildValidateTriggerComic filtered by the author_id column
 *
 * @method     ChildValidateTriggerComic[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildValidateTriggerComic objects based on current ModelCriteria
 * @method     ChildValidateTriggerComic[]|ObjectCollection findByBar(string $bar) Return ChildValidateTriggerComic objects filtered by the bar column
 * @method     ChildValidateTriggerComic[]|ObjectCollection findById(int $id) Return ChildValidateTriggerComic objects filtered by the id column
 * @method     ChildValidateTriggerComic[]|ObjectCollection findByISBN(string $isbn) Return ChildValidateTriggerComic objects filtered by the isbn column
 * @method     ChildValidateTriggerComic[]|ObjectCollection findByPrice(double $price) Return ChildValidateTriggerComic objects filtered by the price column
 * @method     ChildValidateTriggerComic[]|ObjectCollection findByPublisherId(int $publisher_id) Return ChildValidateTriggerComic objects filtered by the publisher_id column
 * @method     ChildValidateTriggerComic[]|ObjectCollection findByAuthorId(int $author_id) Return ChildValidateTriggerComic objects filtered by the author_id column
 * @method     ChildValidateTriggerComic[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ValidateTriggerComicQuery extends ChildValidateTriggerBookQuery
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ValidateTriggerComicQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerComic', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildValidateTriggerComicQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildValidateTriggerComicQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildValidateTriggerComicQuery) {
            return $criteria;
        }
        $query = new ChildValidateTriggerComicQuery();
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
     * @return ChildValidateTriggerComic|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ValidateTriggerComicTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ValidateTriggerComicTableMap::DATABASE_NAME);
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
     * @return ChildValidateTriggerComic A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT BAR, ID, ISBN, PRICE, PUBLISHER_ID, AUTHOR_ID FROM validate_trigger_comic WHERE ID = :p0';
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
            /** @var ChildValidateTriggerComic $obj */
            $obj = new ChildValidateTriggerComic();
            $obj->hydrate($row);
            ValidateTriggerComicTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildValidateTriggerComic|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the bar column
     *
     * Example usage:
     * <code>
     * $query->filterByBar('fooValue');   // WHERE bar = 'fooValue'
     * $query->filterByBar('%fooValue%'); // WHERE bar LIKE '%fooValue%'
     * </code>
     *
     * @param     string $bar The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByBar($bar = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($bar)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $bar)) {
                $bar = str_replace('*', '%', $bar);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_BAR, $bar, $comparison);
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
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ISBN, $iSBN, $comparison);
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
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByPrice($price = null, $comparison = null)
    {
        if (is_array($price)) {
            $useMinMax = false;
            if (isset($price['min'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_PRICE, $price['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($price['max'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_PRICE, $price['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_PRICE, $price, $comparison);
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
     * @param     mixed $publisherId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByPublisherId($publisherId = null, $comparison = null)
    {
        if (is_array($publisherId)) {
            $useMinMax = false;
            if (isset($publisherId['min'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_PUBLISHER_ID, $publisherId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($publisherId['max'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_PUBLISHER_ID, $publisherId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_PUBLISHER_ID, $publisherId, $comparison);
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
     * @param     mixed $authorId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByAuthorId($authorId = null, $comparison = null)
    {
        if (is_array($authorId)) {
            $useMinMax = false;
            if (isset($authorId['min'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_AUTHOR_ID, $authorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($authorId['max'])) {
                $this->addUsingAlias(ValidateTriggerComicTableMap::COL_AUTHOR_ID, $authorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerComicTableMap::COL_AUTHOR_ID, $authorId, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook|ObjectCollection $validateTriggerBook The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByValidateTriggerBook($validateTriggerBook, $comparison = null)
    {
        if ($validateTriggerBook instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerBook) {
            return $this
                ->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $validateTriggerBook->getId(), $comparison);
        } elseif ($validateTriggerBook instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $validateTriggerBook->toKeyValue('PrimaryKey', 'Id'), $comparison);
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
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function joinValidateTriggerBook($relationAlias = null, $joinType = Criteria::INNER_JOIN)
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
    public function useValidateTriggerBookQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinValidateTriggerBook($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerBook', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18n object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18n|ObjectCollection $validateTriggerComicI18n  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function filterByValidateTriggerComicI18n($validateTriggerComicI18n, $comparison = null)
    {
        if ($validateTriggerComicI18n instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18n) {
            return $this
                ->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $validateTriggerComicI18n->getId(), $comparison);
        } elseif ($validateTriggerComicI18n instanceof ObjectCollection) {
            return $this
                ->useValidateTriggerComicI18nQuery()
                ->filterByPrimaryKeys($validateTriggerComicI18n->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByValidateTriggerComicI18n() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18n or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateTriggerComicI18n relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function joinValidateTriggerComicI18n($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateTriggerComicI18n');

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
            $this->addJoinObject($join, 'ValidateTriggerComicI18n');
        }

        return $this;
    }

    /**
     * Use the ValidateTriggerComicI18n relation ValidateTriggerComicI18n object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18nQuery A secondary query class using the current class as primary query
     */
    public function useValidateTriggerComicI18nQuery($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        return $this
            ->joinValidateTriggerComicI18n($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerComicI18n', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18nQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildValidateTriggerComic $validateTriggerComic Object to remove from the list of results
     *
     * @return $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function prune($validateTriggerComic = null)
    {
        if ($validateTriggerComic) {
            $this->addUsingAlias(ValidateTriggerComicTableMap::COL_ID, $validateTriggerComic->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the validate_trigger_comic table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerComicTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ValidateTriggerComicTableMap::clearInstancePool();
            ValidateTriggerComicTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerComicTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ValidateTriggerComicTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ValidateTriggerComicTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ValidateTriggerComicTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // i18n behavior

    /**
     * Adds a JOIN clause to the query using the i18n relation
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function joinI18n($locale = 'en_US', $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $relationName = $relationAlias ? $relationAlias : 'ValidateTriggerComicI18n';

        return $this
            ->joinValidateTriggerComicI18n($relationAlias, $joinType)
            ->addJoinCondition($relationName, $relationName . '.Locale = ?', $locale);
    }

    /**
     * Adds a JOIN clause to the query and hydrates the related I18n object.
     * Shortcut for $c->joinI18n($locale)->with()
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    $this|ChildValidateTriggerComicQuery The current query, for fluid interface
     */
    public function joinWithI18n($locale = 'en_US', $joinType = Criteria::LEFT_JOIN)
    {
        $this
            ->joinI18n($locale, null, $joinType)
            ->with('ValidateTriggerComicI18n');
        $this->with['ValidateTriggerComicI18n']->setIsWithOneToMany(false);

        return $this;
    }

    /**
     * Use the I18n relation query object
     *
     * @see       useQuery()
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    ChildValidateTriggerComicI18nQuery A secondary query class using the current class as primary query
     */
    public function useI18nQuery($locale = 'en_US', $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinI18n($locale, $relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerComicI18n', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerComicI18nQuery');
    }

} // ValidateTriggerComicQuery
