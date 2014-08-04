<?php

namespace Propel\Tests\Bookstore\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Book2 as ChildBook2;
use Propel\Tests\Bookstore\Book2Query as ChildBook2Query;
use Propel\Tests\Bookstore\Map\Book2TableMap;

/**
 * Base class that represents a query for the 'book2' table.
 *
 *
 *
 * @method     ChildBook2Query orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildBook2Query orderByTitle($order = Criteria::ASC) Order by the title column
 * @method     ChildBook2Query orderByStyle($order = Criteria::ASC) Order by the style column
 * @method     ChildBook2Query orderByTags($order = Criteria::ASC) Order by the tags column
 *
 * @method     ChildBook2Query groupById() Group by the id column
 * @method     ChildBook2Query groupByTitle() Group by the title column
 * @method     ChildBook2Query groupByStyle() Group by the style column
 * @method     ChildBook2Query groupByTags() Group by the tags column
 *
 * @method     ChildBook2Query leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildBook2Query rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildBook2Query innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildBook2 findOne(ConnectionInterface $con = null) Return the first ChildBook2 matching the query
 * @method     ChildBook2 findOneOrCreate(ConnectionInterface $con = null) Return the first ChildBook2 matching the query, or a new ChildBook2 object populated from the query conditions when no match is found
 *
 * @method     ChildBook2 findOneById(int $id) Return the first ChildBook2 filtered by the id column
 * @method     ChildBook2 findOneByTitle(string $title) Return the first ChildBook2 filtered by the title column
 * @method     ChildBook2 findOneByStyle(int $style) Return the first ChildBook2 filtered by the style column
 * @method     ChildBook2 findOneByTags(array $tags) Return the first ChildBook2 filtered by the tags column
 *
 * @method     ChildBook2[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildBook2 objects based on current ModelCriteria
 * @method     ChildBook2[]|ObjectCollection findById(int $id) Return ChildBook2 objects filtered by the id column
 * @method     ChildBook2[]|ObjectCollection findByTitle(string $title) Return ChildBook2 objects filtered by the title column
 * @method     ChildBook2[]|ObjectCollection findByStyle(int $style) Return ChildBook2 objects filtered by the style column
 * @method     ChildBook2[]|ObjectCollection findByTags(array $tags) Return ChildBook2 objects filtered by the tags column
 * @method     ChildBook2[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class Book2Query extends ModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\Book2Query object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'bookstore', $modelName = '\\Propel\\Tests\\Bookstore\\Book2', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildBook2Query object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildBook2Query
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildBook2Query) {
            return $criteria;
        }
        $query = new ChildBook2Query();
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
     * @return ChildBook2|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = Book2TableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(Book2TableMap::DATABASE_NAME);
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
     * @return ChildBook2 A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, TITLE, STYLE, TAGS FROM book2 WHERE ID = :p0';
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
            /** @var ChildBook2 $obj */
            $obj = new ChildBook2();
            $obj->hydrate($row);
            Book2TableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildBook2|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(Book2TableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(Book2TableMap::COL_ID, $keys, Criteria::IN);
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
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(Book2TableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(Book2TableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Book2TableMap::COL_ID, $id, $comparison);
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
     * @return $this|ChildBook2Query The current query, for fluid interface
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

        return $this->addUsingAlias(Book2TableMap::COL_TITLE, $title, $comparison);
    }

    /**
     * Filter the query on the style column
     *
     * @param     mixed $style The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function filterByStyle($style = null, $comparison = null)
    {
        $valueSet = Book2TableMap::getValueSet(Book2TableMap::COL_STYLE);
        if (is_scalar($style)) {
            if (!in_array($style, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $style));
            }
            $style = array_search($style, $valueSet);
        } elseif (is_array($style)) {
            $convertedValues = array();
            foreach ($style as $value) {
                if (!in_array($value, $valueSet)) {
                    throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $value));
                }
                $convertedValues []= array_search($value, $valueSet);
            }
            $style = $convertedValues;
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(Book2TableMap::COL_STYLE, $style, $comparison);
    }

    /**
     * Filter the query on the tags column
     *
     * @param     array $tags The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function filterByTags($tags = null, $comparison = null)
    {
        $key = $this->getAliasedColName(Book2TableMap::COL_TAGS);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($tags as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($tags as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($tags as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::NOT_LIKE);
                } else {
                    $this->add($key, $value, Criteria::NOT_LIKE);
                }
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(Book2TableMap::COL_TAGS, $tags, $comparison);
    }

    /**
     * Filter the query on the tags column
     * @param     mixed $tags The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function filterByTag($tags = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($tags)) {
                $tags = '%| ' . $tags . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $tags = '%| ' . $tags . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(Book2TableMap::COL_TAGS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $tags, $comparison);
            } else {
                $this->addAnd($key, $tags, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(Book2TableMap::COL_TAGS, $tags, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildBook2 $book2 Object to remove from the list of results
     *
     * @return $this|ChildBook2Query The current query, for fluid interface
     */
    public function prune($book2 = null)
    {
        if ($book2) {
            $this->addUsingAlias(Book2TableMap::COL_ID, $book2->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the book2 table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(Book2TableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            Book2TableMap::clearInstancePool();
            Book2TableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(Book2TableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(Book2TableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            Book2TableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            Book2TableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // Book2Query
