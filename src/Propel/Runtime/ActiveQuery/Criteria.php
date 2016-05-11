<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Adapter\Pdo\PdoAdapter;
use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Util\PropelConditionalProxy;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\Criterion\BasicCriterion;
use Propel\Runtime\ActiveQuery\Criterion\InCriterion;
use Propel\Runtime\ActiveQuery\Criterion\CustomCriterion;
use Propel\Runtime\ActiveQuery\Criterion\LikeCriterion;
use Propel\Runtime\ActiveQuery\Criterion\RawCriterion;

/**
 * This is a utility class for holding criteria information for a query.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Kaspars Jaudzems <kaspars.jaudzems@inbox.lv> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Eric Dobbs <eric@dobbse.net> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Sam Joseph <sam@neurogrid.com> (Torque)
 */
class Criteria
{

    /** Comparison type. */
    const EQUAL = '=';

    /** Comparison type. */
    const NOT_EQUAL = '<>';

    /** Comparison type. */
    const ALT_NOT_EQUAL = '!=';

    /** Comparison type. */
    const GREATER_THAN = '>';

    /** Comparison type. */
    const LESS_THAN = '<';

    /** Comparison type. */
    const GREATER_EQUAL = '>=';

    /** Comparison type. */
    const LESS_EQUAL = '<=';

    /** Comparison type. */
    const LIKE = ' LIKE ';

    /** Comparison type. */
    const NOT_LIKE = ' NOT LIKE ';

    /** Comparison for array field types */
    const CONTAINS_ALL = 'CONTAINS_ALL';

    /** Comparison for array field types */
    const CONTAINS_SOME = 'CONTAINS_SOME';

    /** Comparison for array field types */
    const CONTAINS_NONE = 'CONTAINS_NONE';

    /** PostgreSQL comparison type */
    const ILIKE = ' ILIKE ';

    /** PostgreSQL comparison type */
    const NOT_ILIKE = ' NOT ILIKE ';

    /** Comparison type. */
    const CUSTOM = 'CUSTOM';

    /** Comparison type */
    const RAW = 'RAW';

    /** Comparison type for update */
    const CUSTOM_EQUAL = 'CUSTOM_EQUAL';

    /** Comparison type. */
    const DISTINCT = 'DISTINCT';

    /** Comparison type. */
    const IN = ' IN ';

    /** Comparison type. */
    const NOT_IN = ' NOT IN ';

    /** Comparison type. */
    const ALL = 'ALL';

    /** Comparison type. */
    const JOIN = 'JOIN';

    /** Binary math operator: AND */
    const BINARY_AND = '&';

    /** Binary math operator: OR */
    const BINARY_OR = '|';

    /** 'Order by' qualifier - ascending */
    const ASC = 'ASC';

    /** 'Order by' qualifier - descending */
    const DESC = 'DESC';

    /** 'IS NULL' null comparison */
    const ISNULL = ' IS NULL ';

    /** 'IS NOT NULL' null comparison */
    const ISNOTNULL = ' IS NOT NULL ';

    /** 'CURRENT_DATE' ANSI SQL function */
    const CURRENT_DATE = 'CURRENT_DATE';

    /** 'CURRENT_TIME' ANSI SQL function */
    const CURRENT_TIME = 'CURRENT_TIME';

    /** 'CURRENT_TIMESTAMP' ANSI SQL function */
    const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /** 'LEFT JOIN' SQL statement */
    const LEFT_JOIN = 'LEFT JOIN';

    /** 'RIGHT JOIN' SQL statement */
    const RIGHT_JOIN = 'RIGHT JOIN';

    /** 'INNER JOIN' SQL statement */
    const INNER_JOIN = 'INNER JOIN';

    /** logical OR operator */
    const LOGICAL_OR = 'OR';

    /** logical AND operator */
    const LOGICAL_AND = 'AND';

    protected $ignoreCase = false;

    protected $singleRecord = false;

    /**
     * Storage of select data. Collection of field names.
     * @var array
     */
    protected $selectFields = array();

    /**
     * Storage of aliased select data. Collection of field names.
     * @var string[]
     */
    protected $asFields = array();

    /**
     * Storage of select modifiers data. Collection of modifier names.
     * @var string[]
     */
    protected $selectModifiers = array();

    /**
     * Storage of conditions data. Collection of Criterion objects.
     * @var AbstractCriterion[]
     */
    protected $map = array();

    /**
     * Storage of ordering data. Collection of field names.
     * @var array
     */
    protected $orderByFields = array();

    /**
     * Storage of grouping data. Collection of field names.
     * @var array
     */
    protected $groupByFields = array();

    /**
     * Storage of having data.
     * @var AbstractCriterion
     */
    protected $having = null;

    /**
     * Storage of join data. collection of Join objects.
     *
     * @var Join[]
     */
    protected $joins = array();

    protected $selectQueries = array();

    /**
     * The name of the database.
     * @var string
     */
    protected $dbName;

    /**
     * The primary entity for this Criteria.
     * Useful in cases where there are no select or where
     * fields.
     * @var string
     */
    protected $primaryEntityName;

    /** The name of the database as given in the constructor. */
    protected $originalDbName;

    /**
     * To limit the number of rows to return.  <code>-1</code> means return all
     * rows.
     */
    protected $limit = -1;

    /** To start the results at a row other than the first one. */
    protected $offset = 0;

    /**
     * Comment to add to the SQL query
     * @var string
     */
    protected $queryComment;

    protected $aliases = array();

    protected $useTransaction = false;

    /**
     * Storage for Criterions expected to be combined
     * @var array
     */
    protected $namedCriterions = array();

    /**
     * Default operator for combination of criterions
     * @see addUsingOperator
     * @var string Criteria::LOGICAL_AND or Criteria::LOGICAL_OR
     */
    protected $defaultCombineOperator = Criteria::LOGICAL_AND;

    /**
     * @var PropelConditionalProxy
     */
    protected $conditionalProxy = null;

    /**
     * Whether identifier should be quoted.
     *
     * @var boolean
     */
    protected $identifierQuoting = null;

    /**
     * @var array
     */
    public $replacedFields = [];

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Creates a new instance with the default capacity which corresponds to
     * the specified database.
     *
     * @param String $dbName The database name.
     */
    public function __construct($dbName = null, Configuration $configuration = null)
    {
        $this->setDbName($dbName);
        $this->originalDbName = $dbName;
        $this->configuration = $configuration;
    }

    /**
     * Get the criteria map, i.e. the array of Criterions
     * @return AbstractCriterion[]
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * Brings this criteria back to its initial state, so that it
     * can be reused as if it was new. Except if the criteria has grown in
     * capacity, it is left at the current capacity.
     * @return void
     */
    public function clear()
    {
        $this->map = array();
        $this->namedCriterions = array();
        $this->ignoreCase = false;
        $this->singleRecord = false;
        $this->selectModifiers = array();
        $this->selectFields = array();
        $this->orderByFields = array();
        $this->groupByFields = array();
        $this->having = null;
        $this->asFields = array();
        $this->joins = array();
        $this->selectQueries = array();
        $this->dbName = $this->originalDbName;
        $this->offset = 0;
        $this->limit = 0;
        $this->aliases = array();
        $this->useTransaction = false;
        $this->ifLvlCount = false;
        $this->wasTrue = false;
    }

    /**
     * Add an AS clause to the select fields. Usage:
     *
     * <code>
     * Criteria myCrit = new Criteria();
     * myCrit->addAsField('alias', 'ALIAS('.MyEntityMap::ID.')');
     * </code>
     *
     * If the name already exists, it is replaced by the new clause.
     *
     * @param string $name   Wanted Name of the field (alias).
     * @param string $clause SQL clause to select from the entity
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAsField($name, $clause)
    {
        $this->asFields[$name] = $clause;

        return $this;
    }

    /**
     * Get the field aliases.
     *
     * @return array An assoc array which map the field alias names
     *               to the alias clauses.
     */
    public function getAsFields()
    {
        return $this->asFields;
    }

    /**
     * Returns the field name associated with an alias (AS-field).
     *
     * @param  string $alias
     * @return string $string
     */
    public function getFieldForAs($as)
    {
        if (isset($this->asFields[$as])) {
            return $this->asFields[$as];
        }
    }

    /**
     * Allows one to specify an alias for a entity that can
     * be used in various parts of the SQL.
     *
     * @param string $alias
     * @param string $entity
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAlias($alias, $entity)
    {
        $this->aliases[$alias] = $entity;

        return $this;
    }

    /**
     * Remove an alias for a entity (useful when merging Criterias).
     *
     * @param string $alias
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function removeAlias($alias)
    {
        unset($this->aliases[$alias]);

        return $this;
    }

    /**
     * Returns the aliases for this Criteria
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Returns the entity name associated with an alias.
     *
     * @param  string $alias
     * @return string $string
     */
    public function getEntityForAlias($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }
    }

    /**
     * @param string $entityName
     *
     * @return string
     */
    public function getTableName($entityName)
    {
        if (!$entityName) {
            throw new \InvalidArgumentException('entityName can not be empty.');
        }
        $entityMap = $this->getConfiguration()->getDatabase($this->getDbName())->getEntity($entityName);
        return $entityMap->getFQTableName();
    }

    /**
     * Returns the entity name and alias based on a entity alias or name.
     * Use this method to get the details of a entity name that comes in a clause,
     * which can be either a entity name or an alias name.
     *
     * @param  string            $entityAliasOrName
     * @return array($entityName, $entityAlias)
     */
    public function getEntityNameAndAlias($entityAliasOrName)
    {
        if (isset($this->aliases[$entityAliasOrName])) {
            return array($this->aliases[$entityAliasOrName], $entityAliasOrName);
        }

        return array($entityAliasOrName, null);
    }

    /**
     * Get the keys of the criteria map, i.e. the list of fields bearing a condition
     * <code>
     * print_r($c->keys());
     *  => array('book.price', 'book.title', 'author.first_name')
     * </code>
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->map);
    }

    /**
     * Does this Criteria object contain the specified key?
     *
     * @param  string  $field [entity.]field
     * @return boolean True if this Criteria object contain the specified key.
     */
    public function containsKey($field)
    {
        // must use array_key_exists() because the key could
        // exist but have a NULL value (that'd be valid).
        return isset($this->map[$field]);
    }

    /**
     * Does this Criteria object contain the specified key and does it have a value set for the key
     *
     * @param  string  $field [entity.]field
     * @return boolean True if this Criteria object contain the specified key and a value for that key
     */
    public function keyContainsValue($field)
    {
        // must use array_key_exists() because the key could
        // exist but have a NULL value (that'd be valid).
        return isset($this->map[$field]) && null !== $this->map[$field]->getValue();
    }

    /**
     * Whether this Criteria has any where fields.
     *
     * This counts conditions added with the add() method.
     *
     * @return boolean
     * @see add()
     */
    public function hasWhereClause()
    {
        return !empty($this->map);
    }

    /**
     * Will force the sql represented by this criteria to be executed within
     * a transaction.  This is here primarily to support the oid type in
     * postgresql.  Though it can be used to require any single sql statement
     * to use a transaction.
     * @return void
     */
    public function setUseTransaction($v)
    {
        $this->useTransaction = (Boolean) $v;
    }

    /**
     * Whether the sql command specified by this criteria must be wrapped
     * in a transaction.
     *
     * @return boolean
     */
    public function isUseTransaction()
    {
        return $this->useTransaction;
    }

    /**
     * Method to return criteria related to fields in a entity.
     *
     * Make sure you call containsKey($field) prior to calling this method,
     * since no check on the existence of the $field is made in this method.
     *
     * @param  string            $field Field name.
     * @return AbstractCriterion A Criterion object.
     */
    public function getCriterion($field)
    {
        return $this->map[$field];
    }

    /**
     * Method to return the latest Criterion in a entity.
     *
     * @return AbstractCriterion A Criterion or null no Criterion is added.
     */
    public function getLastCriterion()
    {
        if ($cnt = count($this->map)) {
            $map = array_values($this->map);

            return $map[$cnt - 1];
        }

        return null;
    }

    /**
     * Method to return a Criterion that is not added automatically
     * to this Criteria.  This can be used to chain the
     * Criterions to form a more complex where clause.
     *
     * @param  string            $field     Full name of field (for example TABLE.COLUMN).
     * @param  mixed             $value
     * @param  string            $comparison Criteria comparison constant or PDO binding type
     * @return AbstractCriterion
     */
    public function getNewCriterion($field, $value = null, $comparison = self::EQUAL)
    {
        if (is_int($comparison)) {
            // $comparison is a PDO::PARAM_* constant value
            // something like $c->add('foo like ?', '%bar%', PDO::PARAM_STR);
            return new RawCriterion($this, $field, $value, $comparison);
        }
        switch ($comparison) {
            case Criteria::CUSTOM:
                // custom expression with no parameter binding
                // something like $c->add(BookEntityMap::TITLE, "CONCAT(book.TITLE, 'bar') = 'foobar'", Criteria::CUSTOM);
                return new CustomCriterion($this, $value);
            case Criteria::IN:
            case Criteria::NOT_IN:
                // entity.field IN (?, ?) or entity.field NOT IN (?, ?)
                // something like $c->add(BookEntityMap::TITLE, array('foo', 'bar'), Criteria::IN);
                return new InCriterion($this, $field, $value, $comparison);
            case Criteria::LIKE:
            case Criteria::NOT_LIKE:
            case Criteria::ILIKE:
            case Criteria::NOT_ILIKE:
                // entity.field LIKE ? or entity.field NOT LIKE ?  (or ILIKE for Postgres)
                // something like $c->add(BookEntityMap::TITLE, 'foo%', Criteria::LIKE);
                return new LikeCriterion($this, $field, $value, $comparison);
                break;
            default:
                // simple comparison
                // something like $c->add(BookEntityMap::PRICE, 12, Criteria::GREATER_THAN);
                return new BasicCriterion($this, $field, $value, $comparison);
        }
    }

    /**
     * Method to return a String entity name.
     *
     * @param  string $name Name of the key.
     * @return string The value of the object at key.
     */
    public function getFieldName($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name]->getField();
        }

        return null;
    }

    /**
     * Shortcut method to get an array of fields indexed by entity.
     * <code>
     * print_r($c->getEntitiesFields());
     *  => array(
     *       'book'   => array('book.price', 'book.title'),
     *       'author' => array('author.first_name')
     *     )
     * </code>
     *
     * @return array array(entity => array(entity.field1, entity.field2))
     */
    public function getEntitiesFields()
    {
        $entities = array();
        foreach ($this->keys() as $key) {
            $entityName = substr($key, 0, strrpos($key, '.'));
            $entities[$entityName][] = $key;
        }

        return $entities;
    }

    /**
     * Method to return a comparison String.
     *
     * @param  string $key String name of the key.
     * @return string A String with the value of the object at key.
     */
    public function getComparison($key)
    {
        if (isset($this->map[$key])) {
            return $this->map[$key]->getComparison();
        }

        return null;
    }

    /**
     * Get the Database(Map) name.
     *
     * @return string A String with the Database(Map) name.
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Set the DatabaseMap name.  If <code>null</code> is supplied, uses value
     * provided by <code>Configuration::getDefaultDatasource()</code>.
     *
     * @param  string $dbName The Database (Map) name.
     * @return void
     */
    public function setDbName($dbName = null)
    {
        $this->dbName = (null === $dbName ? $this->getConfiguration()->getDefaultDatasource() : $dbName);
    }

    /**
     * Get the primary entity for this Criteria.
     *
     * This is useful for cases where a Criteria may not contain
     * any SELECT fields or WHERE fields.  This must be explicitly
     * set, of course, in order to be useful.
     *
     * @return string
     */
    public function getPrimaryEntityName()
    {
        return $this->primaryEntityName;
    }

    /**
     * Sets the primary entity for this Criteria.
     *
     * This is useful for cases where a Criteria may not contain
     * any SELECT fields or WHERE fields.  This must be explicitly
     * set, of course, in order to be useful.
     *
     * @param string $entityName
     */
    public function setPrimaryEntityName($entityName)
    {
        $this->primaryEntityName = $entityName;
    }

    /**
     * Method to return a String entity name.
     *
     * @param  string $name The name of the key.
     * @return string The value of entity for criterion at key.
     */
    public function getEntityNameFor($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name]->getEntityName();
        }

        return null;
    }

    /**
     * Method to return the value that was added to Criteria.
     *
     * @param  string $name A String with the name of the key.
     * @return mixed  The value of object at key.
     */
    public function getValue($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name]->getValue();
        }

        return null;
    }

    /**
     * An alias to getValue() -- exposing a Hashentity-like interface.
     *
     * @param  string $key An Object.
     * @return mixed  The value within the Criterion (not the Criterion object).
     */
    public function get($key)
    {
        return $this->getValue($key);
    }

    /**
     * Overrides Hashentity put, so that this object is returned
     * instead of the value previously in the Criteria object.
     * The reason is so that it more closely matches the behavior
     * of the add() methods. If you want to get the previous value
     * then you should first Criteria.get() it yourself. Note, if
     * you attempt to pass in an Object that is not a String, it will
     * throw a NPE. The reason for this is that none of the add()
     * methods support adding anything other than a String as a key.
     *
     * @param  string         $key
     * @param  mixed          $value
     * @return $this|Criteria Instance of self.
     */
    public function put($key, $value)
    {
        return $this->add($key, $value);
    }

    /**
     * Copies all of the mappings from the specified Map to this Criteria
     * These mappings will replace any mappings that this Criteria had for any
     * of the keys currently in the specified Map.
     *
     * if the map was another Criteria, its attributes are copied to this
     * Criteria, overwriting previous settings.
     *
     * @param mixed $t Mappings to be stored in this map.
     */
    public function putAll($t)
    {
        if (is_array($t)) {
            foreach ($t as $key => $value) {
                if ($value instanceof AbstractCriterion) {
                    $this->map[$key] = $value;
                } else {
                    $this->put($key, $value);
                }
            }
        } elseif ($t instanceof Criteria) {
            $this->joins = $t->joins;
        }
    }

    /**
     * This method adds a new criterion to the list of criterias.
     * If a criterion for the requested field already exists, it is
     * replaced. If is used as follow:
     *
     * <code>
     * $crit = new Criteria();
     * $crit->add($field, $value, Criteria::GREATER_THAN);
     * </code>
     *
     * Any comparison can be used.
     *
     * The name of the entity must be used implicitly in the field name,
     * so the Field name must be something like 'TABLE.id'.
     *
     * @param string $p1         The field to run the comparison on, or a Criterion object.
     * @param mixed  $value
     * @param string $comparison A String.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function add($p1, $value = null, $comparison = null)
    {
        if ($p1 instanceof AbstractCriterion) {
            $this->map[$p1->getEntityName() . '.' . $p1->getField()] = $p1;
        } else {
            $this->map[$p1] = $this->getCriterionForCondition($p1, $value, $comparison);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(', ', $this->map) . "\n";
    }

    /**
     * This method creates a new criterion but keeps it for later use with combine()
     * Until combine() is called, the condition is not added to the query
     *
     * <code>
     * $crit = new Criteria();
     * $crit->addCond('cond1', $field1, $value1, Criteria::GREATER_THAN);
     * $crit->addCond('cond2', $field2, $value2, Criteria::EQUAL);
     * $crit->combine(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
     * </code>
     *
     * Any comparison can be used.
     *
     * The name of the entity must be used implicitly in the field name,
     * so the Field name must be something like 'TABLE.id'.
     *
     * @param string $name       name to combine the criterion later
     * @param string $p1         The field to run the comparison on, or AbstractCriterion object.
     * @param mixed  $value
     * @param string $comparison A String.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addCond($name, $p1, $value = null, $comparison = null)
    {
        $this->namedCriterions[$name] = $this->getCriterionForCondition($p1, $value, $comparison);

        return $this;
    }

    /**
     * Combine several named criterions with a logical operator
     *
     * @param  array          $criterions array of the name of the criterions to combine
     * @param  string         $operator   logical operator, either Criteria::LOGICAL_AND, or Criteria::LOGICAL_OR
     * @param  string         $name       optional name to combine the criterion later
     * @return $this|Criteria
     */
    public function combine($criterions = array(), $operator = self::LOGICAL_AND, $name = null)
    {
        $operatorMethod = (self::LOGICAL_AND === strtoupper($operator)) ? 'addAnd' : 'addOr';
        $namedCriterions = array();
        foreach ($criterions as $key) {
            if (array_key_exists($key, $this->namedCriterions)) {
                $namedCriterions[]= $this->namedCriterions[$key];
                unset($this->namedCriterions[$key]);
            } else {
                throw new LogicException(sprintf('Cannot combine unknown condition %s', $key));
            }
        }
        $firstCriterion = array_shift($namedCriterions);
        foreach ($namedCriterions as $criterion) {
            $firstCriterion->$operatorMethod($criterion);
        }
        if (null === $name) {
            $this->addAnd($firstCriterion, null, null);
        } else {
            $this->addCond($name, $firstCriterion, null, null);
        }

        return $this;
    }

    /**
     * This is the way that you should add a join of two entities.
     * Example usage:
     * <code>
     * $c->addJoin(ProjectEntityMap::ID, FooEntityMap::PROJECT_ID, Criteria::LEFT_JOIN);
     * // LEFT JOIN FOO ON (PROJECT.ID = FOO.PROJECT_ID)
     * </code>
     *
     * @param mixed $left     A String with the left side of the join.
     * @param mixed $right    A String with the right side of the join.
     * @param mixed $joinType A String with the join operator
     *                        among Criteria::INNER_JOIN, Criteria::LEFT_JOIN,
     *                        and Criteria::RIGHT_JOIN
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addJoin($left, $right, $joinType = null)
    {
        if (is_array($left)) {
            $conditions = array();
            foreach ($left as $key => $value) {
                $condition = array($value, $right[$key]);
                $conditions[] = $condition;
            }

            return $this->addMultipleJoin($conditions, $joinType);
        }

        $join = new Join();
        $join->setIdentifierQuoting($this->isIdentifierQuotingEnabled());

        // is the left entity an alias ?
        $dotpos = strrpos($left, '.');
        $leftEntityAlias = substr($left, 0, $dotpos);
        $leftFieldName = substr($left, $dotpos + 1);
        list($leftEntityName, $leftEntityAlias) = $this->getEntityNameAndAlias($leftEntityAlias);

        // is the right entity an alias ?
        $dotpos = strrpos($right, '.');
        $rightEntityAlias = substr($right, 0, $dotpos);
        $rightFieldName = substr($right, $dotpos + 1);
        list($rightEntityName, $rightEntityAlias) = $this->getEntityNameAndAlias($rightEntityAlias);

        $join->addExplicitCondition(
            $leftEntityName, $leftFieldName, $leftEntityAlias,
            $rightEntityName, $rightFieldName, $rightEntityAlias,
            Join::EQUAL
        );

        $join->setJoinType($joinType);

        return $this->addJoinObject($join);
    }

    /**
     * Add a join with multiple conditions
     * @deprecated use Join::setJoinCondition($criterion) instead
     *
     * @see http://propel.phpdb.org/trac/ticket/167, http://propel.phpdb.org/trac/ticket/606
     *
     * Example usage:
     * $c->addMultipleJoin(array(
     *     array(LeftEntityMap::LEFT_COLUMN, RightEntityMap::RIGHT_COLUMN),  // if no third argument, defaults to Criteria::EQUAL
     *     array(FoldersEntityMap::alias( 'fo', FoldersEntityMap::LFT ), FoldersEntityMap::alias( 'parent', FoldersEntityMap::RGT ), Criteria::LESS_EQUAL )
     *   ),
     *   Criteria::LEFT_JOIN
      * );
     *
     * @see addJoin()
     * @param array  $conditions An array of conditions, each condition being an array (left, right, operator)
     * @param string $joinType   A String with the join operator. Defaults to an implicit join.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addMultipleJoin($conditions, $joinType = null)
    {
        $join = new Join();
        $join->setIdentifierQuoting($this->isIdentifierQuotingEnabled());
        $joinCondition = null;
        foreach ($conditions as $condition) {
            $left = $condition[0];
            $right = $condition[1];
            if ($pos = strrpos($left, '.')) {
                $leftEntityAlias = substr($left, 0, $pos);
                $leftFieldName = substr($left, $pos + 1);
                list($leftEntityName, $leftEntityAlias) = $this->getEntityNameAndAlias($leftEntityAlias);
            } else {
                list($leftEntityName, $leftEntityAlias) = array(null, null);
                $leftFieldName = $left;
            }

            if ($pos = strrpos($right, '.')) {
                $rightEntityAlias = substr($right, 0, $pos);
                $rightFieldName = substr($right, $pos + 1);
                list($rightEntityName, $rightEntityAlias) = $this->getEntityNameAndAlias($rightEntityAlias);
            } else {
                list($rightEntityName, $rightEntityAlias) = array(null, null);
                $rightFieldName = $right;
            }

            if (!$join->getRightEntityName()) {
                $join->setRightEntityName($rightEntityName);
            }

            if (!$join->getRightEntityAlias()) {
                $join->setRightEntityAlias($rightEntityAlias);
            }

            $conditionClause = $leftEntityAlias ? $leftEntityAlias . '.' : ($leftEntityName ? $leftEntityName . '.' : '');
            $conditionClause .= $leftFieldName;
            $conditionClause .= isset($condition[2]) ? $condition[2] : JOIN::EQUAL;
            $conditionClause .= $rightEntityAlias ? $rightEntityAlias . '.' : ($rightEntityName ? $rightEntityName . '.' : '');
            $conditionClause .= $rightFieldName;
            $criterion = $this->getNewCriterion($leftEntityName.'.'.$leftFieldName, $conditionClause, Criteria::CUSTOM);

            if (null === $joinCondition) {
                $joinCondition = $criterion;
            } else {
                $joinCondition = $joinCondition->addAnd($criterion);
            }
        }
        $join->setJoinType($joinType);
        $join->setJoinCondition($joinCondition);

        return $this->addJoinObject($join);
    }

    /**
     * Add a join object to the Criteria
     *
     * @param Join $join A join object
     *
     * @return $this|Criteria A modified Criteria object
     */
    public function addJoinObject(Join $join)
    {
        if (!in_array($join, $this->joins)) { // compare equality, NOT identity
            $this->joins[] = $join;
        }

        return $this;
    }

    /**
     * Get the array of Joins.
     * @return Join[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Adds a Criteria as subQuery in the From Clause.
     *
     * @param Criteria $subQueryCriteria Criteria to build the subquery from
     * @param string   $alias            alias for the subQuery
     *
     * @return $this|Criteria this modified Criteria object (Fluid API)
     */
    public function addSelectQuery(Criteria $subQueryCriteria, $alias = null)
    {
        if (null === $alias) {
            $alias = 'alias_' . ($subQueryCriteria->forgeSelectQueryAlias() + count($this->selectQueries));
        }
        $this->selectQueries[$alias] = $subQueryCriteria;

        return $this;
    }

    /**
     * Checks whether this Criteria has a subquery.
     *
     * @return boolean
     */
    public function hasSelectQueries()
    {
        return (Boolean) $this->selectQueries;
    }

    /**
     * Get the associative array of Criteria for the subQueries per alias.
     *
     * @return Criteria[]
     */
    public function getSelectQueries()
    {
        return $this->selectQueries;
    }

    /**
     * Get the Criteria for a specific subQuery.
     *
     * @param  string   $alias alias for the subQuery
     * @return Criteria
     */
    public function getSelectQuery($alias)
    {
        return $this->selectQueries[$alias];
    }

    /**
     * checks if the Criteria for a specific subQuery is set.
     *
     * @param  string  $alias alias for the subQuery
     * @return boolean
     */
    public function hasSelectQuery($alias)
    {
        return isset($this->selectQueries[$alias]);
    }

    public function forgeSelectQueryAlias()
    {
        $aliasNumber = 0;
        foreach ($this->getSelectQueries() as $c1) {
            $aliasNumber += $c1->forgeSelectQueryAlias();
        }

        return ++$aliasNumber;
    }

    /**
     * Adds 'ALL' modifier to the SQL statement.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setAll()
    {
        $this->removeSelectModifier(self::DISTINCT);
        $this->addSelectModifier(self::ALL);

        return $this;
    }

    /**
     * Adds 'DISTINCT' modifier to the SQL statement.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setDistinct()
    {
        $this->removeSelectModifier(self::ALL);
        $this->addSelectModifier(self::DISTINCT);

        return $this;
    }

    /**
     * Adds a modifier to the SQL statement.
     * e.g. self::ALL, self::DISTINCT, 'SQL_CALC_FOUND_ROWS', 'HIGH_PRIORITY', etc.
     *
     * @param string $modifier The modifier to add
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function addSelectModifier($modifier)
    {
        // only allow the keyword once
        if (!$this->hasSelectModifier($modifier)) {
            $this->selectModifiers[] = $modifier;
        }

        return $this;
    }

    /**
     * Removes a modifier to the SQL statement.
     * Checks for existence before removal
     *
     * @param string $modifier The modifier to add
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function removeSelectModifier($modifier)
    {
        $this->selectModifiers = array_values(array_diff($this->selectModifiers, array($modifier)));

        return $this;
    }

    /**
     * Checks the existence of a SQL select modifier
     *
     * @param string $modifier The modifier to add
     *
     * @return boolean
     */
    public function hasSelectModifier($modifier)
    {
        return in_array($modifier, $this->selectModifiers);
    }

    /**
     * Sets ignore case.
     *
     * @param  boolean        $b True if case should be ignored.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreCase = (Boolean) $b;

        return $this;
    }

    /**
     * Is ignore case on or off?
     *
     * @return boolean True if case is ignored.
     */
    public function isIgnoreCase()
    {
        return $this->ignoreCase;
    }

    /**
     * Set single record?  Set this to <code>true</code> if you expect the query
     * to result in only a single result record (the default behaviour is to
     * throw a PropelException if multiple records are returned when the query
     * is executed).  This should be used in situations where returning multiple
     * rows would indicate an error of some sort.  If your query might return
     * multiple records but you are only interested in the first one then you
     * should be using setLimit(1).
     *
     * @param  boolean        $b Set to TRUE if you expect the query to select just one record.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setSingleRecord($b)
    {
        $this->singleRecord = (Boolean) $b;

        return $this;
    }

    /**
     * Is single record?
     *
     * @return boolean True if a single record is being returned.
     */
    public function isSingleRecord()
    {
        return $this->singleRecord;
    }

    /**
     * Set limit.
     *
     * @param  int            $limit An int with the value for limit.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setLimit($limit)
    {
        // TODO: do we enforce int here? 32bit issue if we do
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get limit.
     *
     * @return int An int with the value for limit.
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set offset.
     *
     * @param  int            $offset An int with the value for offset.  (Note this values is
     *                        cast to a 32bit integer and may result in truncation)
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setOffset($offset)
    {
        $this->offset = (int) $offset;

        return $this;
    }

    /**
     * Get offset.
     *
     * @return int An int with the value for offset.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Add select field.
     *
     * @param  string         $name Name of the select field.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function addSelectField($name)
    {
        $this->selectFields[] = $name;

        return $this;
    }

    /**
     * Set the query comment, that appears after the first verb in the SQL query
     *
     * @param  string         $comment The comment to add to the query, without comment sign
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function setComment($comment = null)
    {
        $this->queryComment = $comment;

        return $this;
    }

    /**
     * Get the query comment, that appears after the first verb in the SQL query
     *
     * @return string The comment to add to the query, without comment sign
     */
    public function getComment()
    {
        return $this->queryComment;
    }

    /**
     * Whether this Criteria has any select fields.
     *
     * This will include fields added with addAsField() method.
     *
     * @return boolean
     * @see addAsField()
     * @see addSelectField()
     */
    public function hasSelectClause()
    {
        return !empty($this->selectFields) || !empty($this->asFields);
    }

    /**
     * Get select fields.
     *
     * @return array An array with the name of the select fields.
     */
    public function getSelectFields()
    {
        return $this->selectFields;
    }

    /**
     * Clears current select fields.
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function clearSelectFields()
    {
        $this->selectFields = $this->asFields = array();

        return $this;
    }

    /**
     * Get select modifiers.
     *
     * @return array An array with the select modifiers.
     */
    public function getSelectModifiers()
    {
        return $this->selectModifiers;
    }

    /**
     * Add group by field name.
     *
     * @param  string         $groupBy The name of the field to group by.
     * @return $this|Criteria A modified Criteria object.
     */
    public function addGroupByField($groupBy)
    {
        $this->groupByFields[] = $groupBy;

        return $this;
    }

    /**
     * Add order by field name, explicitly specifying ascending.
     *
     * @param  string         $name The name of the field to order by.
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAscendingOrderByField($name)
    {
        $this->orderByFields[] = $name . ' ' . self::ASC;

        return $this;
    }

    /**
     * Add order by field name, explicitly specifying descending.
     *
     * @param  string         $name The name of the field to order by.
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function addDescendingOrderByField($name)
    {
        $this->orderByFields[] = $name . ' ' . self::DESC;

        return $this;
    }

    /**
     * Get order by fields.
     *
     * @return array An array with the name of the order fields.
     */
    public function getOrderByFields()
    {
        return $this->orderByFields;
    }

    /**
     * Clear the order-by fields.
     *
     * @return $this|Criteria Modified Criteria object (for fluent API)
     */
    public function clearOrderByFields()
    {
        $this->orderByFields = array();

        return $this;
    }

    /**
     * Clear the group-by fields.
     *
     * @return $this|Criteria
     */
    public function clearGroupByFields()
    {
        $this->groupByFields = array();

        return $this;
    }

    /**
     * Get group by fields.
     *
     * @return array
     */
    public function getGroupByFields()
    {
        return $this->groupByFields;
    }

    /**
     * Get Having Criterion.
     *
     * @return AbstractCriterion A Criterion object that is the having clause.
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Remove an object from the criteria.
     *
     * @param  string $key A string with the key to be removed.
     * @return mixed  The removed value.
     */
    public function remove($key)
    {
        if (isset($this->map[$key])) {
            $removed = $this->map[$key];
            unset($this->map[$key]);
            if ($removed instanceof AbstractCriterion) {
                return $removed->getValue();
            }

            return $removed;
        }
    }

    /**
     * Build a string representation of the Criteria.
     *
     * @return string A String with the representation of the Criteria.
     */
    public function toString()
    {

        $sb = 'Criteria:';
        try {

            $params = array();
            $sb .= "\nSQL (may not be complete): ".$this->createSelectSql($params);

            $sb .= "\nParams: ";
            $paramstr = array();
            foreach ($params as $param) {
                $paramstr[] = $param['entity'] . '.' . $param['field'] . ' => ' . var_export($param['value'], true);
            }
            $sb .= implode(', ', $paramstr);

        } catch (\Exception $exc) {
            $sb .= '(Error: ' . $exc->getMessage() . ')';
        }

        return $sb;
    }

    /**
     * Returns the size (count) of this criteria.
     * @return int
     */
    public function size()
    {
        return count($this->map);
    }

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashentity entries.
     * @return boolean
     */
    public function equals($crit)
    {
        if (null === $crit || !($crit instanceof Criteria)) {
            return false;
        }

        /** @var Criteria $crit */
        if ($this === $crit) {
            return true;
        }

        if ($this->size() === $crit->size()) {

            // Important: nested criterion objects are checked

            $criteria = $crit; // alias
            if  ($this->offset            === $criteria->getOffset()
                && $this->limit           === $criteria->getLimit()
                && $this->ignoreCase      === $criteria->isIgnoreCase()
                && $this->singleRecord    === $criteria->isSingleRecord()
                && $this->dbName          === $criteria->getDbName()
                && $this->selectModifiers === $criteria->getSelectModifiers()
                && $this->selectFields   === $criteria->getSelectFields()
                && $this->asFields       === $criteria->getAsFields()
                && $this->orderByFields  === $criteria->getOrderByFields()
                && $this->groupByFields  === $criteria->getGroupByFields()
                && $this->aliases         === $criteria->getAliases()
               ) // what about having ??
            {
                foreach ($criteria->keys() as $key) {
                    if ($this->containsKey($key)) {
                        $a = $this->getCriterion($key);
                        $b = $criteria->getCriterion($key);
                        if (!$a->equals($b)) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }

                $joins = $criteria->getJoins();
                if (count($joins) != count($this->joins)) {
                    return false;
                }

                foreach ($joins as $key => $join) {
                    if (!$join->equals($this->joins[$key])) {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Add the content of a Criteria to the current Criteria
     * In case of conflict, the current Criteria keeps its properties
     *
     * @param Criteria $criteria The criteria to read properties from
     * @param string   $operator The logical operator used to combine conditions
     *                           Defaults to Criteria::LOGICAL_AND, also accepts Criteria::LOGICAL_OR
     *                           This parameter is deprecated, use _or() instead
     *
     * @return $this|Criteria The current criteria object
     */
    public function mergeWith(Criteria $criteria, $operator = null)
    {
        // merge limit
        $limit = $criteria->getLimit();
        if (0 != $limit && -1 === $this->getLimit()) {
            $this->limit = $limit;
        }

        // merge offset
        $offset = $criteria->getOffset();
        if (0 != $offset && 0 === $this->getOffset()) {
            $this->offset = $offset;
        }

        // merge select modifiers
        $selectModifiers = $criteria->getSelectModifiers();
        if ($selectModifiers && ! $this->selectModifiers) {
            $this->selectModifiers = $selectModifiers;
        }

        // merge select fields
        $this->selectFields = array_merge($this->getSelectFields(), $criteria->getSelectFields());

        // merge as fields
        $commonAsFields = array_intersect_key($this->getAsFields(), $criteria->getAsFields());
        if (!empty($commonAsFields)) {
            throw new LogicException('The given criteria contains an AsField with an alias already existing in the current object');
        }
        $this->asFields = array_merge($this->getAsFields(), $criteria->getAsFields());

        // merge orderByFields
        $orderByFields = array_merge($this->getOrderByFields(), $criteria->getOrderByFields());
        $this->orderByFields = array_unique($orderByFields);

        // merge groupByFields
        $groupByFields = array_merge($this->getGroupByFields(), $criteria->getGroupByFields());
        $this->groupByFields = array_unique($groupByFields);

        // merge where conditions
        if (Criteria::LOGICAL_OR === $operator) {
            $this->_or();
        }
        $isFirstCondition = true;
        foreach ($criteria->getMap() as $key => $criterion) {
            if ($isFirstCondition && Criteria::LOGICAL_OR === $this->defaultCombineOperator) {
                $this->addOr($criterion, null, null, false);
                $this->defaultCombineOperator = Criteria::LOGICAL_AND;
            } elseif ($this->containsKey($key)) {
                $this->addAnd($criterion);
            } else {
                $this->add($criterion);
            }
            $isFirstCondition = false;
        }

        // merge having
        if ($having = $criteria->getHaving()) {
            if ($this->getHaving()) {
                $this->addHaving($this->getHaving()->addAnd($having));
            } else {
                $this->addHaving($having);
            }
        }

        // merge alias
        $commonAliases = array_intersect_key($this->getAliases(), $criteria->getAliases());
        if (!empty($commonAliases)) {
            throw new LogicException('The given criteria contains an alias already existing in the current object');
        }
        $this->aliases = array_merge($this->getAliases(), $criteria->getAliases());

        // merge join
        $this->joins = array_merge($this->getJoins(), $criteria->getJoins());

        return $this;
    }

    /**
     * This method adds a prepared Criterion object to the Criteria as a having clause.
     * You can get a new, empty Criterion object with the
     * getNewCriterion() method.
     *
     * <p>
     * <code>
     * $crit = new Criteria();
     * $c = $crit->getNewCriterion(BaseEntityMap::ID, 5, Criteria::LESS_THAN);
     * $crit->addHaving($c);
     * </code>
     *
     * @param mixed $p1         A Criterion, or a SQL clause with a question mark placeholder, or a field name
     * @param mixed $value      The value to bind in the condition
     * @param mixed $comparison A PDO::PARAM_ class constant
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addHaving($p1, $value = null, $comparison = null)
    {
        $this->having = $this->getCriterionForCondition($p1, $value, $comparison);

        return $this;
    }

    /**
     * Build a Criterion.
     *
     * This method has multiple signatures, and behaves differently according to it:
     *
     *  - If the first argument is a Criterion, it just returns this Criterion.
     *    <code>$c->getCriterionForCondition($criterion); // returns $criterion</code>
     *
     *  - If the last argument is a PDO::PARAM_* constant value, create a Criterion
     *    using Criteria::RAW and $comparison as a type.
     *    <code>$c->getCriterionForCondition('foo like ?', '%bar%', PDO::PARAM_STR);</code>
     *
     *  - Otherwise, create a classic Criterion based on a field name and a comparison.
     *    <code>$c->getCriterionForCondition(BookEntityMap::TITLE, 'War%', Criteria::LIKE);</code>
     *
     * @param mixed $p1         A Criterion, or a SQL clause with a question mark placeholder, or a field name
     * @param mixed $value      The value to bind in the condition
     * @param mixed $comparison A Criteria class constant, or a PDO::PARAM_ class constant
     *
     * @return AbstractCriterion
     */
    protected function getCriterionForCondition($p1, $value = null, $comparison = null)
    {
        if ($p1 instanceof AbstractCriterion) {
            // it's already a Criterion, so ignore $value and $comparison
            return $p1;
        }

        // $comparison is one of Criteria's constants, or a PDO binding type
        // something like $c->add(BookEntityMap::TITLE, 'War%', Criteria::LIKE);
        return $this->getNewCriterion($p1, $value, $comparison);
    }

    /**
     * If a criterion for the requested field already exists, the condition is "AND"ed to the existing criterion (necessary for Propel 1.4 compatibility).
     * If no criterion for the requested field already exists, the condition is "AND"ed to the latest criterion.
     * If no criterion exist, the condition is added a new criterion
     *
     * Any comparison can be used.
     *
     * Supports a number of different signatures:
     *  - addAnd(field, value, comparison)
     *  - addAnd(field, value)
     *  - addAnd(Criterion)
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addAnd($p1, $p2 = null, $p3 = null, $preferFieldCondition = true)
    {
        $criterion = $this->getCriterionForCondition($p1, $p2, $p3);

        $key = $criterion->getEntityName() . '.' . $criterion->getField();
        if ($preferFieldCondition && $this->containsKey($key)) {
            // FIXME: addAnd() operates preferably on existing conditions on the same field
            // this may cause unexpected results, but it's there for BC with Propel 14
            $this->getCriterion($key)->addAnd($criterion);
        } else {
            // simply add the condition to the list - this is the expected behavior
            $this->add($criterion);
        }

        return $this;
    }

    /**
     * If a prior criterion exists, the condition is "OR"ed to it.
     * If no criterion exist, the condition is added a new criterion
     *
     * Any comparison can be used.
     *
     * Supports a number of different signatures:
     *  - addOr(field, value, comparison)
     *  - addOr(field, value)
     *  - addOr(Criterion)
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addOr($p1, $p2 = null, $p3 = null, $preferFieldCondition = true)
    {
        $rightCriterion = $this->getCriterionForCondition($p1, $p2, $p3);

        $leftCriterion = $this->getLastCriterion();

        if (null !== $leftCriterion) {
            // combine the given criterion with the existing one with an 'OR'
            $leftCriterion->addOr($rightCriterion);
        } else {
            // nothing to do OR / AND with, so make it first condition
            $this->add($rightCriterion);
        }

        return $this;
    }

    /**
     * Overrides Criteria::add() to use the default combine operator
     * @see Criteria::add()
     *
     * @param string|AbstractCriterion $p1                    The field to run the comparison on (e.g. BookEntityMap::ID), or Criterion object
     * @param mixed                    $value
     * @param string                   $operator              A String, like Criteria::EQUAL.
     * @param boolean                  $preferFieldCondition If true, the condition is combined with an existing condition on the same field
    *                      (necessary for Propel 1.4 compatibility).
     *                     If false, the condition is combined with the last existing condition.
     *
     * @return $this|Criteria A modified Criteria object.
     */
    public function addUsingOperator($p1, $value = null, $operator = null, $preferFieldCondition = true)
    {
        if (Criteria::LOGICAL_OR === $this->defaultCombineOperator) {
            $this->defaultCombineOperator = Criteria::LOGICAL_AND;

            return $this->addOr($p1, $value, $operator, $preferFieldCondition);
        }

        return $this->addAnd($p1, $value, $operator, $preferFieldCondition);
    }

    /**
     * Method to create an SQL query based on values in a Criteria.
     *
     * This method creates only prepared statement SQL (using ? where values
     * will go).  The second parameter ($params) stores the values that need
     * to be set before the statement is executed.  The reason we do it this way
     * is to let the PDO layer handle all escaping & value formatting.
     *
     * @param  array  &$params Parameters that are to be replaced in prepared statement.
     * @return string
     *
     * @throws \Propel\Runtime\Exception\PropelException Trouble creating the query string.
     */
    public function createSelectSql(&$params)
    {
        $adapter = $this->getConfiguration()->getAdapter($this->getDbName());
        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());

        $fromClause = array();
        $joinClause = array();
        $joinEntities = array();
        $whereClause = array();
        $orderByClause = array();

        $orderBy = $this->getOrderByFields();

        // get the first part of the SQL statement, the SELECT part
        $selectSql = $adapter->createSelectSqlPart($this);
        $this->replaceNames($selectSql);

        if ($this->getPrimaryEntityName()) {
            $fromClause[] = $this->getTableName($this->getPrimaryEntityName());
        } else {
            if ($this instanceof BaseModelCriteria) {
                $fromClause[] = $this->getTableName($this->getEntityName());
            }
        }

        // Handle joins
        // joins with a null join type will be added to the FROM clause and the condition added to the WHERE clause.
        // joins of a specified type: the LEFT side will be added to the fromClause and the RIGHT to the joinClause
        foreach ($this->getJoins() as $join) {
            $join->setAdapter($adapter);

            $fromClause[] = $join->getLeftTableWithAlias();
            $joinEntities[] = $join->getRightTableWithAlias();
            $joinClauseString = $join->getClause($params);
            $this->replaceNames($joinClauseString);
            $joinClause[] = $joinClauseString;
        }

        // add the criteria to WHERE clause
        // this will also add the entity names to the FROM clause if they are not already
        // included via a LEFT JOIN
        foreach ($this->keys() as $key) {
            $criterion = $this->getCriterion($key);
            $entity = null;
            foreach ($criterion->getAttachedCriterion() as $attachedCriterion) {
                if ($entityName = $attachedCriterion->getEntityName()) {

                    $entity = $this->getEntityForAlias($entityName);
                    if ($entity !== null) {
                        $table = $this->getTableName($entity);
                        $fromClause[] = $table . ' ' . $entityName;
                    } else {
                        $table = $this->getTableName($entityName);
                        $fromClause[] = $table;
                        $entity = $entityName;
                    }
                }

                if ($this->isIgnoreCase() && method_exists($attachedCriterion, 'setIgnoreCase')
                    && $dbMap->getEntity($entity)->getField($attachedCriterion->getField())->isText()) {
                    $attachedCriterion->setIgnoreCase(true);
                }
            }

            $criterion->setAdapter($adapter);

            $sb = '';
            $criterion->appendPsTo($sb, $params);
            $this->replaceNames($sb);

            $whereClause[] = $sb;
        }

        // Unique from clause elements
        $fromClause = array_unique($fromClause);
        $fromClause = array_diff($fromClause, array(''));

        // entities should not exist in both the from and join clauses
        if ($joinEntities && $fromClause) {
            foreach ($fromClause as $fi => $fentity) {
                if (in_array($fentity, $joinEntities)) {
                    unset($fromClause[$fi]);
                }
            }
        }

        $having = $this->getHaving();
        $havingString = null;
        if (null !== $having) {
            $sb = '';
            $having->appendPsTo($sb, $params);
            $this->replaceNames($sb);
            $havingString = $sb;
        }

        if (!empty($orderBy)) {

            foreach ($orderBy as $orderByField) {

                // Add function expression as-is.
                if (strpos($orderByField, '(') !== false) {
                    $orderByClause[] = $orderByField;
                    continue;
                }

                // Split orderByField (i.e. "entity.field DESC")
                $dotPos = strrpos($orderByField, '.');

                if ($dotPos !== false) {
                    $entityName = substr($orderByField, 0, $dotPos);
                    $fieldName = substr($orderByField, $dotPos + 1);
                } else {
                    $entityName = '';
                    $fieldName = $orderByField;
                }

                $spacePos = strpos($fieldName, ' ');

                if ($spacePos !== false) {
                    $direction = substr($fieldName, $spacePos);
                    $fieldName = substr($fieldName, 0, $spacePos);
                } else {
                    $direction = '';
                }

                $entityAlias = $entityName;
                if ($aliasEntityName = $this->getEntityForAlias($entityName)) {
                    $entityName = $aliasEntityName;
                }

                $fieldAlias = $fieldName;
                if ($asFieldName = $this->getFieldForAs($fieldName)) {
                    $fieldName = $asFieldName;
                }

                $field = $dbMap->hasEntity($entityName) ? $dbMap->getEntity($entityName)->getField($fieldName) : null;

                if ($this->isIgnoreCase() && $field && $field->isText()) {
                    $ignoreCaseField = $adapter->ignoreCaseInOrderBy("$entityAlias.$fieldAlias");
                    $this->replaceNames($ignoreCaseField);
                    $orderByClause[] =  $ignoreCaseField . $direction;
                    $selectSql .= ', ' . $ignoreCaseField;
                } else {
                    $this->replaceNames($orderByField);
                    $orderByClause[] = $orderByField;
                }
            }
        }

        if (empty($fromClause) && $this->getPrimaryEntityName()) {
            $fromClause[] = $this->getTableName($this->getPrimaryEntityName());
        }

        // entities should not exist as alias of subQuery
        if ($this->hasSelectQueries()) {
            foreach ($fromClause as $key => $fentity) {
                if (false !== strpos($fentity, ' ')) {
                    list(, $entityName) = explode(' ', $fentity);
                } else {
                    $entityName = $fentity;
                }
                if ($this->hasSelectQuery($entityName)) {
                    unset($fromClause[$key]);
                }
            }
        }

        // from / join entities quoted if it is necessary
        $fromClause = array_map(array($this, 'quoteTableIdentifierForEntity'), $fromClause);
        $joinClause = $joinClause ? $joinClause : array_map(array($this, 'quoteTableIdentifierForEntity'), $joinClause);

        // add subQuery to From after adding quotes
        foreach ($this->getSelectQueries() as $subQueryAlias => $subQueryCriteria) {
            $fromClause[] = '(' . $subQueryCriteria->createSelectSql($params) . ') AS ' . $subQueryAlias;
        }

        // build from-clause
        $from = '';
        if (!empty($joinClause) && count($fromClause) > 1) {
            $from .= implode(" CROSS JOIN ", $fromClause);
        } else {
            $from .= implode(", ", $fromClause);
        }

        $from .= $joinClause ? ' ' . implode(' ', $joinClause) : '';

        // Build the SQL from the arrays we compiled
        $sql =  $selectSql
            .' FROM '  . $from
            .($whereClause ? ' WHERE '.implode(' AND ', $whereClause) : '');

        if ($groupBy = $adapter->getGroupBy($this)) {
            $this->replaceNames($groupBy);
            $sql .= $groupBy;
        }

        $sql .= ($havingString ? ' HAVING '.$havingString : '')
             .($orderByClause ? ' ORDER BY '.implode(',', $orderByClause) : '');

        if ($this->getLimit() >= 0 || $this->getOffset()) {
            $adapter->applyLimit($sql, $this->getOffset(), $this->getLimit(), $this);
        }

        return $sql;
    }

    /**
     * This method does only quote identifier, the method doReplaceNameInExpression of child ModelCriteria class does more.
     *
     * @param array $matches Matches found by preg_replace_callback
     *
     * @return string the field name replacement
     */
    protected function doReplaceNameInExpression($matches)
    {
        return $this->quoteIdentifier($matches[0]);
    }

    /**
     * Quotes identifier based on $this->isIdentifierQuotingEnabled() and $entityMap->isIdentifierQuotingEnabled.
     *
     * @param string $string
     * @return string
     */
    public function quoteIdentifier($string, $entityName = '')
    {
/*        if ($this->isIdentifierQuotingEnabled()) {
            $adapter = $this->getConfiguration()->getAdapter($this->getDbName());

            return $adapter->quote($string);
        }*/

        $rightSide = '';
        //find entity name and ask entityMap if quoting is enabled
        if (!$entityName && false !== ($pos = strpos($string, '.'))) {
            $entityName = substr($string, 0, $pos);
            $rightSide = substr($string, $pos);
        }

        $entityMapName = $entityName;
        $quoteIdentifier = false;

        if ($entityMapName) {
            $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());
            if ($dbMap->hasEntity($entityMapName)) {
                $entityMap = $dbMap->getEntity($entityMapName);
                $quoteIdentifier = $entityMap->isIdentifierQuotingEnabled();
                if ($rightSide) {
                    $string = $entityMap->getTableName();
                    $string .= $rightSide;
                }
            }
        }

        if ($quoteIdentifier || $this->isIdentifierQuotingEnabled()) {
            $adapter = $this->getConfiguration()->getAdapter($this->getDbName());

            return $adapter->quote($string);
        }

        return $string;
    }

    public function quoteTableIdentifierForEntity($string)
    {
        $realEntityName = $string;
        $alias = null;
        if (false !== ($pos = strrpos($string, ' '))) {
            $realEntityName = substr($string, 0, $pos);
            $alias = substr($string, $pos + 1);
        }

        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());
        $quoteIdentifier = false;

        if ($dbMap->hasEntity($realEntityName)) {
            $entityMap = $dbMap->getEntity($realEntityName);

            $quoteIdentifier = $entityMap->isIdentifierQuotingEnabled();

            $string = $entityMap->getTableName();
            if ($alias) {
                $string .= " $alias";
            }
        }

        if ($quoteIdentifier || $this->isIdentifierQuotingEnabled()) {
            $adapter = $this->getConfiguration()->getAdapter($this->getDbName());

            return $adapter->quoteTableIdentifier($string);
        }

        return $string;
    }
    /**
     * Replaces complete field names (like Article.AuthorId) in an SQL clause
     * by their exact Propel field fully qualified name (e.g. article.author_id)
     * but ignores the field names inside quotes
     * e.g. 'CONCAT(Book.AuthorID, "Book.AuthorID") = ?'
     *   => 'CONCAT(book.author_id, "Book.AuthorID") = ?'
     *
     * @param string $sql SQL clause to inspect (modified by the method)
     *
     * @return boolean Whether the method managed to find and replace at least one field name
     */
    public function replaceNames(&$sql)
    {
        $this->replacedFields = array();
        $this->currentAlias = '';
        $this->foundMatch = false;
        $isAfterBackslash = false;
        $isInString = false;
        $stringQuotes = '';
        $parsedString = '';
        $stringToTransform = '';
        $len = strlen($sql);
        $pos = 0;
        while ($pos < $len) {
            $char = $sql[$pos];
            // check flags for strings or escaper
            switch ($char) {
                case '\\':
                    $isAfterBackslash = true;
                    break;
                case "'":
                case '"':
                    if ($isInString && $stringQuotes == $char) {
                        if (!$isAfterBackslash) {
                            $isInString = false;
                        }
                    } elseif (!$isInString) {
                        $parsedString .= preg_replace_callback("/[\w\\\]+\.\w+/", array($this, 'doReplaceNameInExpression'), $stringToTransform);
                        $stringToTransform = '';
                        $stringQuotes = $char;
                        $isInString = true;
                    }
                    break;
            }

            if ('\\' !== $char) {
                $isAfterBackslash = false;
            }

            if ($isInString) {
                $parsedString .= $char;
            } else {
                $stringToTransform .= $char;
            }

            $pos++;
        }

        if ($stringToTransform) {
            $parsedString .= preg_replace_callback("/[\w\\\]+\.\w+/", array($this, 'doReplaceNameInExpression'), $stringToTransform);
        }

        $sql = $parsedString;

        return $this->foundMatch;
    }

    /**
     * Method to perform inserts based on values and keys in a
     * Criteria.
     * <p>
     * If the primary key is auto incremented the data in Criteria
     * will be inserted and the auto increment value will be returned.
     * <p>
     * If the primary key is included in Criteria then that value will
     * be used to insert the row.
     * <p>
     * If no primary key is included in Criteria then we will try to
     * figure out the primary key from the database map and insert the
     * row with the next available id using util.db.IDBroker.
     * <p>
     * If no primary key is defined for the entity the values will be
     * inserted as specified in Criteria and null will be returned.
     *
     * @return mixed               The primary key for the new row if the primary key is auto-generated. Otherwise will return null.
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function doInsert()
    {
        // The primary key
        $id = null;
        $con = $this->getConfiguration()->getConnectionManager($this->getDbName())->getWriteConnection();
        $db = $this->getConfiguration()->getAdapter($this->getDbName());

        // Get the entity name and method for determining the primary
        // key value.
        $keys = $this->keys();
        if (empty($keys)) {
            throw new PropelException('Database insert attempted without anything specified to insert.');
        }

        $entityName = $this->getEntityNameFor($keys[0]);
        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());
        $entityMap = $dbMap->getEntity($entityName);
        $keyInfo = $entityMap->getPrimaryKeyMethodInfo();
        $useIdGen = $entityMap->isUseIdGenerator();
        //$keyGen = $con->getIdGenerator();

        $pk = $this->getPrimaryKey();

        // only get a new key value if you need to
        // the reason is that a primary key might be defined
        // but you are still going to set its value. for example:
        // a join entity where both keys are primary and you are
        // setting both fields with your own values

        // pk will be null if there is no primary key defined for the entity
        // we're inserting into.
        if (null !== $pk && $useIdGen && !$this->keyContainsValue($pk->getFullyQualifiedName()) && $db->isGetIdBeforeInsert()) {
            try {
                $id = $db->getId($con, $keyInfo);
            } catch (\Exception $e) {
                throw new PropelException('Unable to get sequence id.', 0, $e);
            }
            $this->add($pk->getFullyQualifiedName(), $id);
        }

        try {
            $qualifiedCols = $this->keys(); // we need entity.field cols when populating values
            $fields = array(); // but just 'field' cols for the SQL
            foreach ($qualifiedCols as $qualifiedCol) {
                $fields[] = substr($qualifiedCol, strrpos($qualifiedCol, '.') + 1);
            }

            // add identifiers
            $fields = array_map(array($this, 'quoteIdentifier'), $fields);
            $entityName = $this->quoteTableIdentifierForEntity($entityName);

            $sql = 'INSERT INTO ' . $entityName
                . ' (' . implode(',', $fields) . ')'
                . ' VALUES (';
            // . substr(str_repeat("?,", count($fields)), 0, -1) .
            for ($p = 1, $cnt = count($fields); $p <= $cnt; $p++) {
                $sql .= ':p'.$p;
                if ($p !== $cnt) {
                    $sql .= ',';
                }
            }
            $sql .= ')';

            $params = $this->buildParams($qualifiedCols);

            $db->cleanupSQL($sql, $params, $this, $dbMap);

            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap, $db);
            $stmt->execute();

        } catch (\Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        // If the primary key field is auto-incremented, get the id now.
        if (null !== $pk && $useIdGen && $db->isGetIdAfterInsert()) {
            try {
                $id = $db->getId($con, $keyInfo);
            } catch (\Exception $e) {
                throw new PropelException("Unable to get autoincrement id.", 0, $e);
            }
        }

        return $id;
    }

    public function getPrimaryKey(Criteria $criteria = null)
    {
        if (!$criteria) {
            $criteria = $this;
        }
        // Assume all the keys are for the same entity.
        $keys = $criteria->keys();
        $key = $keys[0];
        $entity = $criteria->getEntityNameFor($key);

        $pk = null;

        if (!empty($entity)) {
            $dbMap = $this->getConfiguration()->getDatabase($criteria->getDbName());

            $pks = $dbMap->getEntity($entity)->getPrimaryKeys();
            if (!empty($pks)) {
                $pk = array_shift($pks);
            }
        }

        return $pk;
    }

    /**
     * Method used to update rows in the DB.  Rows are selected based
     * on selectCriteria and updated using values in updateValues.
     * <p>
     * Use this method for performing an update of the kind:
     * <p>
     * WHERE some_field = some value AND could_have_another_field =
     * another value AND so on.
     *
     * @param Criteria            $updateValues A Criteria object containing values used in set clause.
     *
     * @return int The number of rows affected by last update statement.
     *             For most uses there is only one update statement executed, so this number will
     *             correspond to the number of rows affected by the call to this method.
     *             Note that the return value does require that this information is returned
     *             (supported) by the Propel db driver.
     *
     * @throws PropelException
     */
    public function doUpdate($updateValues)
    {
        $con = $this->getConfiguration()->getConnectionManager($this->getDbName())->getWriteConnection();

        /** @var PdoAdapter $db */
        $db = $this->getConfiguration()->getAdapter($this->getDbName());
        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());

        // Get list of required entities, containing all fields
        $entitiesFields = $this->getEntitiesFields();
        if (empty($entitiesFields) && ($entity = $this->getPrimaryEntityName())) {
            $entitiesFields = array($entity => array());
        }

        // we also need the fields for the update SQL
        $updateEntitiesFields = $updateValues->getEntitiesFields();

        // If no fields are changing values, we may get here with
        // an empty array in $updateEntitiesFields.  In that case,
        // there is nothing to do, so we return the rows affected,
        // which is 0.  Fixes a bug in which an UPDATE statement
        // would fail in this instance.

        if (empty($updateEntitiesFields)) {
            return 0;
        }

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        foreach ($entitiesFields as $entityName => $fields) {

            $whereClause = array();
            $params = array();
            $stmt = null;
            try {
                $sql = 'UPDATE ';
                if ($queryComment = $this->getComment()) {
                    $sql .= '/* ' . $queryComment . ' */ ';
                }
                // is it a entity alias?
                if ($realEntityName = $this->getEntityForAlias($entityName)) {
                    $updateEntity = $realEntityName . ' ' . $entityName;
                    $entityName = $realEntityName;
                } else {
                    $updateEntity = $entityName;
                }
                $sql .= $this->quoteTableIdentifierForEntity($updateEntity);
                $sql .= " SET ";
                $p = 1;
                foreach ($updateEntitiesFields[$entityName] as $col) {
                    $updateFieldName = substr($col, strrpos($col, '.') + 1);
                    // add identifiers for the actual database?
                    $updateFieldName = $this->quoteIdentifier($updateFieldName, $entityName);
                    if ($updateValues->getComparison($col) != Criteria::CUSTOM_EQUAL) {
                        $sql .= $updateFieldName . '=:p'.$p++.', ';
                    } else {
                        $param = $updateValues->get($col);
                        $sql .= $updateFieldName . ' = ';
                        if (is_array($param)) {
                            if (isset($param['raw'])) {
                                $raw = $param['raw'];
                                $rawcvt = '';
                                // parse the $params['raw'] for ? chars
                                for ($r = 0, $len = strlen($raw); $r < $len; $r++) {
                                    if ($raw{$r} == '?') {
                                        $rawcvt .= ':p'.$p++;
                                    } else {
                                        $rawcvt .= $raw{$r};
                                    }
                                }
                                $sql .= $rawcvt . ', ';
                            } else {
                                $sql .= ':p'.$p++.', ';
                            }
                            if (isset($param['value'])) {
                                $updateValues->put($col, $param['value']);
                            }
                        } else {
                            $updateValues->remove($col);
                            $sql .= $param . ', ';
                        }
                    }
                }
                //remove the comma from the last condition
                $sql = substr($sql, 0, -2) . ' ';

                $params = $this->buildParams($updateEntitiesFields[$entityName], $updateValues);

                if (!empty($fields)) {
                    foreach ($fields as $colName) {
                        $sb = '';
                        $this->getCriterion($colName)->appendPsTo($sb, $params);
                        $this->replaceNames($sb);
                        $whereClause[] = $sb;
                    }
                    $sql .= ' WHERE ' .  implode(' AND ', $whereClause);
                }

                $db->cleanupSQL($sql, $params, $updateValues, $dbMap);

                $paramsReplace = $params;
                $readable = preg_replace_callback('/\?/', function() use (&$paramsReplace) {
                        return var_export(array_shift($paramsReplace), true);
                    }, $sql);
                $this->getConfiguration()->debug("sql-update: $readable");

                $stmt = $con->prepare($sql);

                // Replace ':p?' with the actual values
                $db->bindValues($stmt, $params, $dbMap);

                $stmt->execute();

                $affectedRows = $stmt->rowCount();

                $stmt = null; // close

            } catch (\Exception $e) {
                if ($stmt) {
                    $stmt = null; // close
                }
//                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException(sprintf('Unable to execute UPDATE statement [%s]', $sql), 0, $e);
            }

        } // foreach entity in the criteria

        return $affectedRows;
    }

    public function buildParams($fields, Criteria $values = null)
    {
        if (!$values) {
            $values = $this;
        }
        $params = array();
        foreach ($fields as $key) {
            if ($values->containsKey($key)) {
                $crit = $values->getCriterion($key);
                $params[] = array(
                    'field' => $crit->getField(),
                    'entity' => $crit->getEntityName(),
                    'value' => $crit->getValue()
                );
            }
        }

        return $params;
    }

    public function doCount()
    {
        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());
        $db = $this->getConfiguration()->getAdapter($this->getDbName());

        $con = $this->getConfiguration()->getConnectionManager($this->getDbName())->getWriteConnection();

        $needsComplexCount = $this->getGroupByFields()
            || $this->getOffset()
            || $this->getLimit() >= 0
            || $this->getHaving()
            || in_array(Criteria::DISTINCT, $this->getSelectModifiers())
            || count($this->selectQueries) > 0
        ;

        $params = array();
        if ($needsComplexCount) {
            if ($this->needsSelectAliases()) {
                if ($this->getHaving()) {
                    throw new LogicException('Propel cannot create a COUNT query when using HAVING and  duplicate field names in the SELECT part');
                }
                $db->turnSelectFieldsToAliases($this);
            }
            $selectSql = $this->createSelectSql($params);
            $sql = 'SELECT COUNT(*) FROM (' . $selectSql . ') propelmatch4cnt';
        } else {
            // Replace SELECT fields with COUNT(*)
            $this->clearSelectFields()->addSelectField('COUNT(*)');
            $sql = $this->createSelectSql($params);
        }
        try {
            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->getConfiguration()->log($e->getMessage(), Configuration::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute COUNT statement [%s]', $sql));
        }

        return $con->getDataFetcher($stmt);
    }

    /**
     * Checks whether the Criteria needs to use field aliasing
     * This is implemented in a service class rather than in Criteria itself
     * in order to avoid doing the tests when it's not necessary (e.g. for SELECTs)
     */
    public function needsSelectAliases()
    {
        $fieldNames = array();
        foreach ($this->getSelectFields() as $fullyQualifiedFieldName) {
            if ($pos = strrpos($fullyQualifiedFieldName, '.')) {
                $fieldName = substr($fullyQualifiedFieldName, $pos);
                if (isset($fieldNames[$fieldName])) {
                    // more than one field with the same name, so aliasing is required
                    return true;
                }
                $fieldNames[$fieldName] = true;
            }
        }

        return false;
    }

    /**
     * Issue a DELETE query based on the current ModelCriteria
     * This method is called by ModelCriteria::delete() inside a transaction
     *
     * @return int             the number of deleted rows
     * @throws PropelException
     */
    public function doDelete()
    {
        $con = $this->getConfiguration()->getConnectionManager($this->getDbName())->getWriteConnection();

        $adapter = $this->getConfiguration()->getAdapter($this->getDbName());
        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());

        // join are not supported with DELETE statement
        if (count($this->getJoins())) {
            throw new PropelException('Delete does not support join');
        }

        // Set up a list of required entities (one DELETE statement will
        // be executed per entity)
        $entities = $this->getEntitiesFields();
        if (empty($entities)) {
            throw new PropelException("Cannot delete from an empty Criteria");
        }

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        foreach ($entities as $entityName => $fields) {

            $whereClause = array();
            $params = array();
            $stmt = null;
            try {
                $sql = $adapter->getDeleteFromClause($this, $entityName);

                foreach ($fields as $colName) {
                    $sb = '';
                    $this->getCriterion($colName)->appendPsTo($sb, $params);
                    $this->getConfiguration()->debug("delete-sb: $sb");
                    $this->replaceNames($sb);
                    $whereClause[] = $sb;
                }
                $sql .= ' WHERE ' .  implode(' AND ', $whereClause);

                $this->getConfiguration()->debug("delete-sql: $sql");
                $stmt = $con->prepare($sql);

                $adapter->bindValues($stmt, $params, $dbMap);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
            } catch (\Exception $e) {
                $this->getConfiguration()->log($e->getMessage(), Configuration::LOG_ERR);
                throw new PropelException(sprintf('Unable to execute DELETE statement [%s]', $sql), 0, $e);
            }

        } // for each entity

        return $affectedRows;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        if ($this->configuration) {
            return $this->configuration;
        }

        return Configuration::getCurrentConfiguration();
    }

    /**
     * @return bool
     */
    public function hasConfiguration()
    {
        return null !== $this->configuration;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Builds, binds and executes a SELECT query based on the current object.
     *
     * @return DataFetcherInterface A dataFetcher using the connection, ready to be fetched
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function doSelect()
    {
        $dbMap = $this->getConfiguration()->getDatabase($this->getDbName());

        $con = $this->getConfiguration()->getConnectionManager($this->getDbName())->getReadConnection();
        $adapter = $this->getConfiguration()->getAdapter($this->getDbName());

        $params = array();
        $sql = $this->createSelectSql($params);
        try {
            $stmt = $con->prepare($sql);
            $p = [];
            foreach ($params as $param) {
                $p[] = $param['value'];
            }
            $this->getConfiguration()->debug("doSelect() sql: $sql [" . implode(',', $p). "]");
            $adapter->bindValues($stmt, $params, $dbMap);
            $stmt->execute();
        } catch (\Exception $e) {
            if (isset($stmt)) {
                $stmt = null; // close
            }
            $this->getConfiguration()->log($e->getMessage(), Configuration::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), null, $e);
        }

        return $con->getDataFetcher($stmt);
    }

    // Fluid operators

    public function _or()
    {
        $this->defaultCombineOperator = Criteria::LOGICAL_OR;

        return $this;
    }

    public function _and()
    {
        $this->defaultCombineOperator = Criteria::LOGICAL_AND;

        return $this;
    }

    // Fluid Conditions

    /**
     * Returns the current object if the condition is true,
     * or a PropelConditionalProxy instance otherwise.
     * Allows for conditional statements in a fluid interface.
     *
     * @param boolean $cond
     *
     * @return PropelConditionalProxy|$this|Criteria
     */
    public function _if($cond)
    {
        $this->conditionalProxy = new PropelConditionalProxy($this, $cond, $this->conditionalProxy);

        return $this->conditionalProxy->getCriteriaOrProxy();
    }

    /**
     * Returns a PropelConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @param boolean $cond ignored
     *
     * @return PropelConditionalProxy|$this|Criteria
     */
    public function _elseif($cond)
    {
        if (!$this->conditionalProxy) {
            throw new LogicException(__METHOD__ .' must be called after _if()');
        }

        return $this->conditionalProxy->_elseif($cond);
    }

    /**
     * Returns a PropelConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @return PropelConditionalProxy|$this|Criteria
     */
    public function _else()
    {
        if (!$this->conditionalProxy) {
            throw new LogicException(__METHOD__ .' must be called after _if()');
        }

        return $this->conditionalProxy->_else();
    }

    /**
     * Returns the current object
     * Allows for conditional statements in a fluid interface.
     *
     * @return $this|Criteria
     */
    public function _endif()
    {
        if (!$this->conditionalProxy) {
            throw new LogicException(__METHOD__ .' must be called after _if()');
        }

        $this->conditionalProxy = $this->conditionalProxy->getParentProxy();

        if ($this->conditionalProxy) {
            return $this->conditionalProxy->getCriteriaOrProxy();
        }

        // reached last level
        return $this;
    }

    /**
     * Ensures deep cloning of attached objects
     */
    public function __clone()
    {
        foreach ($this->map as $key => $criterion) {
            $this->map[$key] = clone $criterion;
        }

        foreach ($this->joins as $key => $join) {
            $this->joins[$key] = clone $join;
        }

        if (null !== $this->having) {
            $this->having = clone $this->having;
        }
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $identifierQuoting
     */
    public function setIdentifierQuoting($identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
    }

}
