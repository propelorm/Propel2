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
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBook as ChildValidateTriggerBook;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18nQuery as ChildValidateTriggerBookI18nQuery;
use Propel\Tests\Bookstore\Behavior\ValidateTriggerBookQuery as ChildValidateTriggerBookQuery;
use Propel\Tests\Bookstore\Behavior\Map\ValidateTriggerBookTableMap;

/**
 * Base class that represents a query for the 'validate_trigger_book' table.
 *
 * Book Table
 *
 * @method     ChildValidateTriggerBookQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildValidateTriggerBookQuery orderByISBN($order = Criteria::ASC) Order by the isbn column
 * @method     ChildValidateTriggerBookQuery orderByPrice($order = Criteria::ASC) Order by the price column
 * @method     ChildValidateTriggerBookQuery orderByPublisherId($order = Criteria::ASC) Order by the publisher_id column
 * @method     ChildValidateTriggerBookQuery orderByAuthorId($order = Criteria::ASC) Order by the author_id column
 * @method     ChildValidateTriggerBookQuery orderByDescendantClass($order = Criteria::ASC) Order by the descendant_class column
 *
 * @method     ChildValidateTriggerBookQuery groupById() Group by the id column
 * @method     ChildValidateTriggerBookQuery groupByISBN() Group by the isbn column
 * @method     ChildValidateTriggerBookQuery groupByPrice() Group by the price column
 * @method     ChildValidateTriggerBookQuery groupByPublisherId() Group by the publisher_id column
 * @method     ChildValidateTriggerBookQuery groupByAuthorId() Group by the author_id column
 * @method     ChildValidateTriggerBookQuery groupByDescendantClass() Group by the descendant_class column
 *
 * @method     ChildValidateTriggerBookQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildValidateTriggerBookQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildValidateTriggerBookQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildValidateTriggerBookQuery leftJoinValidateTriggerFiction($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateTriggerFiction relation
 * @method     ChildValidateTriggerBookQuery rightJoinValidateTriggerFiction($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateTriggerFiction relation
 * @method     ChildValidateTriggerBookQuery innerJoinValidateTriggerFiction($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateTriggerFiction relation
 *
 * @method     ChildValidateTriggerBookQuery leftJoinValidateTriggerComic($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateTriggerComic relation
 * @method     ChildValidateTriggerBookQuery rightJoinValidateTriggerComic($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateTriggerComic relation
 * @method     ChildValidateTriggerBookQuery innerJoinValidateTriggerComic($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateTriggerComic relation
 *
 * @method     ChildValidateTriggerBookQuery leftJoinValidateTriggerBookI18n($relationAlias = null) Adds a LEFT JOIN clause to the query using the ValidateTriggerBookI18n relation
 * @method     ChildValidateTriggerBookQuery rightJoinValidateTriggerBookI18n($relationAlias = null) Adds a RIGHT JOIN clause to the query using the ValidateTriggerBookI18n relation
 * @method     ChildValidateTriggerBookQuery innerJoinValidateTriggerBookI18n($relationAlias = null) Adds a INNER JOIN clause to the query using the ValidateTriggerBookI18n relation
 *
 * @method     \Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionQuery|\Propel\Tests\Bookstore\Behavior\ValidateTriggerComicQuery|\Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18nQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildValidateTriggerBook findOne(ConnectionInterface $con = null) Return the first ChildValidateTriggerBook matching the query
 * @method     ChildValidateTriggerBook findOneOrCreate(ConnectionInterface $con = null) Return the first ChildValidateTriggerBook matching the query, or a new ChildValidateTriggerBook object populated from the query conditions when no match is found
 *
 * @method     ChildValidateTriggerBook findOneById(int $id) Return the first ChildValidateTriggerBook filtered by the id column
 * @method     ChildValidateTriggerBook findOneByISBN(string $isbn) Return the first ChildValidateTriggerBook filtered by the isbn column
 * @method     ChildValidateTriggerBook findOneByPrice(double $price) Return the first ChildValidateTriggerBook filtered by the price column
 * @method     ChildValidateTriggerBook findOneByPublisherId(int $publisher_id) Return the first ChildValidateTriggerBook filtered by the publisher_id column
 * @method     ChildValidateTriggerBook findOneByAuthorId(int $author_id) Return the first ChildValidateTriggerBook filtered by the author_id column
 * @method     ChildValidateTriggerBook findOneByDescendantClass(string $descendant_class) Return the first ChildValidateTriggerBook filtered by the descendant_class column
 *
 * @method     ChildValidateTriggerBook[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildValidateTriggerBook objects based on current ModelCriteria
 * @method     ChildValidateTriggerBook[]|ObjectCollection findById(int $id) Return ChildValidateTriggerBook objects filtered by the id column
 * @method     ChildValidateTriggerBook[]|ObjectCollection findByISBN(string $isbn) Return ChildValidateTriggerBook objects filtered by the isbn column
 * @method     ChildValidateTriggerBook[]|ObjectCollection findByPrice(double $price) Return ChildValidateTriggerBook objects filtered by the price column
 * @method     ChildValidateTriggerBook[]|ObjectCollection findByPublisherId(int $publisher_id) Return ChildValidateTriggerBook objects filtered by the publisher_id column
 * @method     ChildValidateTriggerBook[]|ObjectCollection findByAuthorId(int $author_id) Return ChildValidateTriggerBook objects filtered by the author_id column
 * @method     ChildValidateTriggerBook[]|ObjectCollection findByDescendantClass(string $descendant_class) Return ChildValidateTriggerBook objects filtered by the descendant_class column
 * @method     ChildValidateTriggerBook[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ValidateTriggerBookQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Behavior\Base\ValidateTriggerBookQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore-behavior', $modelName = '\\Propel\\Tests\\Bookstore\\Behavior\\ValidateTriggerBook', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildValidateTriggerBookQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildValidateTriggerBookQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildValidateTriggerBookQuery) {
            return $criteria;
        }
        $query = new ChildValidateTriggerBookQuery();
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
     * @return ChildValidateTriggerBook|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ValidateTriggerBookTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ValidateTriggerBookTableMap::DATABASE_NAME);
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
     * @return ChildValidateTriggerBook A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, ISBN, PRICE, PUBLISHER_ID, AUTHOR_ID, DESCENDANT_CLASS FROM validate_trigger_book WHERE ID = :p0';
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
            /** @var ChildValidateTriggerBook $obj */
            $obj = new ChildValidateTriggerBook();
            $obj->hydrate($row);
            ValidateTriggerBookTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildValidateTriggerBook|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ISBN, $iSBN, $comparison);
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByPrice($price = null, $comparison = null)
    {
        if (is_array($price)) {
            $useMinMax = false;
            if (isset($price['min'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_PRICE, $price['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($price['max'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_PRICE, $price['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_PRICE, $price, $comparison);
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByPublisherId($publisherId = null, $comparison = null)
    {
        if (is_array($publisherId)) {
            $useMinMax = false;
            if (isset($publisherId['min'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_PUBLISHER_ID, $publisherId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($publisherId['max'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_PUBLISHER_ID, $publisherId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_PUBLISHER_ID, $publisherId, $comparison);
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByAuthorId($authorId = null, $comparison = null)
    {
        if (is_array($authorId)) {
            $useMinMax = false;
            if (isset($authorId['min'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_AUTHOR_ID, $authorId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($authorId['max'])) {
                $this->addUsingAlias(ValidateTriggerBookTableMap::COL_AUTHOR_ID, $authorId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_AUTHOR_ID, $authorId, $comparison);
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
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
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

        return $this->addUsingAlias(ValidateTriggerBookTableMap::COL_DESCENDANT_CLASS, $descendantClass, $comparison);
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction|ObjectCollection $validateTriggerFiction  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByValidateTriggerFiction($validateTriggerFiction, $comparison = null)
    {
        if ($validateTriggerFiction instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction) {
            return $this
                ->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $validateTriggerFiction->getId(), $comparison);
        } elseif ($validateTriggerFiction instanceof ObjectCollection) {
            return $this
                ->useValidateTriggerFictionQuery()
                ->filterByPrimaryKeys($validateTriggerFiction->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByValidateTriggerFiction() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateTriggerFiction relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function joinValidateTriggerFiction($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateTriggerFiction');

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
            $this->addJoinObject($join, 'ValidateTriggerFiction');
        }

        return $this;
    }

    /**
     * Use the ValidateTriggerFiction relation ValidateTriggerFiction object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionQuery A secondary query class using the current class as primary query
     */
    public function useValidateTriggerFictionQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinValidateTriggerFiction($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerFiction', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerFictionQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateTriggerComic object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateTriggerComic|ObjectCollection $validateTriggerComic  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByValidateTriggerComic($validateTriggerComic, $comparison = null)
    {
        if ($validateTriggerComic instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerComic) {
            return $this
                ->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $validateTriggerComic->getId(), $comparison);
        } elseif ($validateTriggerComic instanceof ObjectCollection) {
            return $this
                ->useValidateTriggerComicQuery()
                ->filterByPrimaryKeys($validateTriggerComic->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByValidateTriggerComic() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateTriggerComic or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateTriggerComic relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function joinValidateTriggerComic($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateTriggerComic');

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
            $this->addJoinObject($join, 'ValidateTriggerComic');
        }

        return $this;
    }

    /**
     * Use the ValidateTriggerComic relation ValidateTriggerComic object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateTriggerComicQuery A secondary query class using the current class as primary query
     */
    public function useValidateTriggerComicQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinValidateTriggerComic($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerComic', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerComicQuery');
    }

    /**
     * Filter the query by a related \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n object
     *
     * @param \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n|ObjectCollection $validateTriggerBookI18n  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function filterByValidateTriggerBookI18n($validateTriggerBookI18n, $comparison = null)
    {
        if ($validateTriggerBookI18n instanceof \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n) {
            return $this
                ->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $validateTriggerBookI18n->getId(), $comparison);
        } elseif ($validateTriggerBookI18n instanceof ObjectCollection) {
            return $this
                ->useValidateTriggerBookI18nQuery()
                ->filterByPrimaryKeys($validateTriggerBookI18n->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByValidateTriggerBookI18n() only accepts arguments of type \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the ValidateTriggerBookI18n relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function joinValidateTriggerBookI18n($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('ValidateTriggerBookI18n');

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
            $this->addJoinObject($join, 'ValidateTriggerBookI18n');
        }

        return $this;
    }

    /**
     * Use the ValidateTriggerBookI18n relation ValidateTriggerBookI18n object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18nQuery A secondary query class using the current class as primary query
     */
    public function useValidateTriggerBookI18nQuery($relationAlias = null, $joinType = 'LEFT JOIN')
    {
        return $this
            ->joinValidateTriggerBookI18n($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerBookI18n', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18nQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildValidateTriggerBook $validateTriggerBook Object to remove from the list of results
     *
     * @return $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function prune($validateTriggerBook = null)
    {
        if ($validateTriggerBook) {
            $this->addUsingAlias(ValidateTriggerBookTableMap::COL_ID, $validateTriggerBook->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the validate_trigger_book table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerBookTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ValidateTriggerBookTableMap::clearInstancePool();
            ValidateTriggerBookTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ValidateTriggerBookTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ValidateTriggerBookTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ValidateTriggerBookTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ValidateTriggerBookTableMap::clearRelatedInstancePool();

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
     * @return    ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function joinI18n($locale = 'en_US', $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $relationName = $relationAlias ? $relationAlias : 'ValidateTriggerBookI18n';

        return $this
            ->joinValidateTriggerBookI18n($relationAlias, $joinType)
            ->addJoinCondition($relationName, $relationName . '.Locale = ?', $locale);
    }

    /**
     * Adds a JOIN clause to the query and hydrates the related I18n object.
     * Shortcut for $c->joinI18n($locale)->with()
     *
     * @param     string $locale Locale to use for the join condition, e.g. 'fr_FR'
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
     *
     * @return    $this|ChildValidateTriggerBookQuery The current query, for fluid interface
     */
    public function joinWithI18n($locale = 'en_US', $joinType = Criteria::LEFT_JOIN)
    {
        $this
            ->joinI18n($locale, null, $joinType)
            ->with('ValidateTriggerBookI18n');
        $this->with['ValidateTriggerBookI18n']->setIsWithOneToMany(false);

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
     * @return    ChildValidateTriggerBookI18nQuery A secondary query class using the current class as primary query
     */
    public function useI18nQuery($locale = 'en_US', $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinI18n($locale, $relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'ValidateTriggerBookI18n', '\Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18nQuery');
    }

} // ValidateTriggerBookQuery
