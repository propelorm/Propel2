<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Map\FieldMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Persister\SqlPersister;
use Propel\Runtime\Util\PropelDateTime;
use Propel\Generator\Model\PropelTypes;

/**
 * Base for PDO database adapters.
 */
abstract class PdoAdapter implements SqlAdapterInterface
{
    public function getPersister($session)
    {
        return new SqlPersister($session);
    }

    /**
     * Build database connection
     *
     * @param array $conparams connection parameters
     *
     * @return PdoConnection
     *
     * @throws InvalidArgumentException
     * @throws AdapterException
     */
    public function getConnection($conparams)
    {
        $conparams = $this->prepareParams($conparams);

        if (!isset($conparams['dsn'])) {
            throw new InvalidArgumentException('No dsn specified in your connection parameters');
        }

        $dsn      = $conparams['dsn'];
        $user     = isset($conparams['user']) ? $conparams['user'] : null;
        $password = isset($conparams['password']) ? $conparams['password'] : null;

        // load any driver options from the config file
        // driver options are those PDO settings that have to be passed during the connection construction
        $driver_options = array();
        if (isset($conparams['options']) && is_array($conparams['options'])) {
            foreach ($conparams['options'] as $option => $optiondata) {
                $value = $optiondata;
                if (is_string($value) && false !== strpos($value, '::')) {
                    if (!defined($value)) {
                        throw new InvalidArgumentException(sprintf('Error processing driver options for dsn "%s"', $dsn));
                    }
                    $value = constant($value);
                }
                $driver_options[$option] = $value;
            }
        }

        try {
            $con = new PdoConnection($dsn, $user, $password, $driver_options);
            $this->initConnection($con, isset($conparams['settings']) && is_array($conparams['settings']) ? $conparams['settings'] : array());
        } catch (\PDOException $e) {
            throw new AdapterException("Unable to open PDO connection", 0, $e);
        }

        return $con;
    }

    /**
     * {@inheritDoc}
     */
    public function compareRegex($left, $right)
    {
        return sprintf("%s REGEXP %s", $left, $right);
    }

    /**
     * @return string
     */
    public function getAdapterId()
    {
        $class = str_replace('Adapter', '', get_called_class());
        $lastSlash = strrpos($class, '\\');

        return strtolower(substr($class, $lastSlash + 1));
    }

    /**
     * Prepare the parameters for a Connection
     *
     * @param array $conparams the connection parameters from the configuration
     *
     * @return array the modified parameters
     */
    protected function prepareParams($conparams)
    {
        return $conparams;
    }

    /**
     * This method is called after a connection was created to run necessary
     * post-initialization queries or code.
     *
     * If a charset was specified, this will be set before any other queries
     * are executed.
     *
     * This base method runs queries specified using the "query" setting.
     *
     * @see setCharset()
     *
     * @param ConnectionInterface $con
     * @param array               $settings An array of settings.
     */
    public function initConnection(ConnectionInterface $con, array $settings)
    {
        if (isset($settings['charset'])) {
            $this->setCharset($con, $settings['charset']);
        }

        if (isset($settings['queries']) && is_array($settings['queries'])) {
            foreach ($settings['queries'] as $query) {
                $con->exec($query);
            }
        }
    }

    /**
     * Sets the character encoding using SQL standard SET NAMES statement.
     *
     * This method is invoked from the default initConnection() method and must
     * be overridden for an RDMBS which does _not_ support this SQL standard.
     *
     * @see initConnection()
     *
     * @param ConnectionInterface $con
     * @param string              $charset The $string charset encoding.
     */
    public function setCharset(ConnectionInterface $con, $charset)
    {
        $con->exec(sprintf("SET NAMES '%s'", $charset));
    }

    /**
     * This method is used to ignore case.
     *
     * @param  string $in The string to transform to upper case.
     * @return string The upper case string.
     */
    public function toUpperCase($in)
    {
        return sprintf('UPPER(%s)', $in);
    }

    /**
     * This method is used to ignore case.
     *
     * @param  string $in The string whose case to ignore.
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCase($in)
    {
        return sprintf('UPPER(%s)', $in);
    }

    /**
     * This method is used to ignore case in an ORDER BY clause.
     * Usually it is the same as ignoreCase, but some databases
     * (Interbase for example) does not use the same SQL in ORDER BY
     * and other clauses.
     *
     * @param  string $in The string whose case to ignore.
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCaseInOrderBy($in)
    {
        return $this->ignoreCase($in);
    }

    /**
     * Returns the character used to indicate the beginning and end of
     * a piece of text used in a SQL statement (generally a single
     * quote).
     *
     * @return string The text delimiter.
     */
    public function getStringDelimiter()
    {
        return '\'';
    }

    /**
     * Quotes database object identifiers (entity names, col names, sequences, etc.).
     * @param  string $text The identifier to quote.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($text)
    {
        return '"' . $text . '"';
    }

    /**
     * Quotes full qualified field names and entity names.
     *
     * book.author_id => `book`.`author_id`
     * author_id => `author_id`
     *
     * @param string $text
     * @return string
     */
    public function quote($text)
    {
        if (false !== ($pos = strrpos($text, '.'))) {
            $entity = substr($text, 0, $pos);
            $field = substr($text, $pos + 1);
        } else {
            $entity = '';
            $field = $text;
        }

        if ($entity) {
            return $this->quoteTableIdentifier($entity) . '.' . $this->quoteIdentifier($field);
        } else {
            return $this->quoteIdentifier($field);
        }
    }

    /**
     * Quotes a database entity which could have space separating it from an alias,
     * both should be identified separately. This doesn't take care of dots which
     * separate schema names from entity names. Adapters for RDBMs which support
     * schemas have to implement that in the platform-specific way.
     *
     * @param  string $entity The entity name to quote
     * @return string The quoted entity name
     **/
    public function quoteTableIdentifier($entity)
    {
        return implode(' ', array_map(array($this, 'quoteIdentifier'), explode(' ', $entity)));
    }

    /**
     * Returns the native ID method for this RDBMS.
     *
     * @return integer One of AdapterInterface:ID_METHOD_SEQUENCE, AdapterInterface::ID_METHOD_AUTOINCREMENT.
     */
    protected function getIdMethod()
    {
        return AdapterInterface::ID_METHOD_AUTOINCREMENT;
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return boolean
     */
    public function isGetIdBeforeInsert()
    {
        return AdapterInterface::ID_METHOD_SEQUENCE === $this->getIdMethod();
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return boolean
     */
    public function isGetIdAfterInsert()
    {
        return AdapterInterface::ID_METHOD_AUTOINCREMENT === $this->getIdMethod();
    }

    /**
     * Gets the generated ID (either last ID for autoincrement or next sequence ID).
     *
     * @param ConnectionInterface $con
     * @param string              $name
     *
     * @return mixed
     */
    public function getId(ConnectionInterface $con, $name = null)
    {
        return $con->lastInsertId($name);
    }

    /**
     * Formats a temporal value before binding, given a FieldMap object
     *
     * @param mixed     $value The temporal value
     * @param FieldMap $cMap
     *
     * @return string The formatted temporal value
     */
    public function formatTemporalValue($value, FieldMap $cMap)
    {
        /** @var $dt PropelDateTime */
        if ($dt = PropelDateTime::newInstance($value)) {
            switch ($cMap->getType()) {
                case PropelTypes::TIMESTAMP:
                case PropelTypes::BU_TIMESTAMP:
                    $value = $dt->format($this->getTimestampFormatter());
                    break;
                case PropelTypes::DATE:
                case PropelTypes::BU_DATE:
                    $value = $dt->format($this->getDateFormatter());
                    break;
                case PropelTypes::TIME:
                    $value = $dt->format($this->getTimeFormatter());
                    break;
            }
        }

        return $value;
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @param Criteria $criteria
     *
     * @return string
     */
    public function getGroupBy(Criteria $criteria)
    {
        $groupBy = $criteria->getGroupByFields();
        if ($groupBy) {
            return ' GROUP BY ' . implode(',', $groupBy);
        }
    }

    /**
     * Returns date formatter string for use in date() function.
     *
     * @return string
     */
    public function getDateFormatter()
    {
        return 'Y-m-d';
    }

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimeFormatter()
    {
        return 'H:i:s';
    }

    /**
     * Allows manipulation of the query string before StatementPdo is instantiated.
     *
     * @param string      $sql    The sql statement
     * @param array       $params array('field' => ..., 'entity' => ..., 'value' => ...)
     * @param Criteria    $values
     * @param DatabaseMap $dbMap
     */
    public function cleanupSQL(&$sql, array &$params, Criteria $values, DatabaseMap $dbMap)
    {
    }

    /**
     * Returns the "DELETE FROM <entity> [AS <alias>]" part of DELETE query.
     *
     * @param Criteria $criteria
     * @param string   $entityName
     *
     * @return string
     */
    public function getDeleteFromClause(Criteria $criteria, $entityName)
    {
        $sql = 'DELETE ';
        if ($queryComment = $criteria->getComment()) {
            $sql .= '/* ' . $queryComment . ' */ ';
        }

        if ($realEntityName = $criteria->getEntityForAlias($entityName)) {
            $realEntityName = $criteria->quoteTableIdentifierForEntity($realEntityName);
            $sql .= $entityName . ' FROM ' . $realEntityName . ' AS ' . $entityName;
        } else {
            $entityName = $criteria->quoteTableIdentifierForEntity($entityName);
            $sql .= 'FROM ' . $entityName;
        }

        return $sql;
    }

    /**
     * Builds the SELECT part of a SQL statement based on a Criteria
     * taking into account select fields and 'as' fields (i.e. fields aliases)
     *
     * @param Criteria $criteria
     * @param boolean  $aliasAll
     *
     * @return string
     */
    public function createSelectSqlPart(Criteria $criteria, $aliasAll = false)
    {
        $selectClause = array();

        if ($aliasAll) {
            $this->turnSelectFieldsToAliases($criteria);
            // no select fields after that, they are all aliases

        } else {
            foreach ($criteria->getSelectFields() as $fieldName) {
                $selectClause[] = $fieldName;
            }
        }

        // set the aliases
        foreach ($criteria->getAsFields() as $alias => $col) {
            $selectClause[] = $col . ' AS ' . $alias;
        }

        $selectModifiers = $criteria->getSelectModifiers();
        $queryComment = $criteria->getComment();

        // Build the SQL from the arrays we compiled
        $sql =  'SELECT '
            . ($queryComment ? '/* ' . $queryComment . ' */ ' : '')
            . ($selectModifiers ? (implode(' ', $selectModifiers) . ' ') : '')
            . implode(', ', $selectClause)
        ;

        return $sql;
    }

    /**
     * Returns all selected fields that are selected without a aggregate function.
     *
     * @param  Criteria $criteria
     * @return string[]
     */
    public function getPlainSelectedFields(Criteria $criteria)
    {
        $selected = [];
        foreach ($criteria->getSelectFields() as $fieldName) {
            if (false === strpos($fieldName, '(')) {
                $selected[] = $fieldName;
            }
        }

        foreach ($criteria->getAsFields() as $alias => $col) {
            if (false === strpos($col, '(') && !in_array($col, $selected)) {
                $selected[] = $col;
            }
        }

        return $selected;
    }

    /**
     * Ensures uniqueness of select field names by turning them all into aliases
     * This is necessary for queries on more than one entity when the entities share a field name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param  Criteria $criteria
     * @return Criteria The input, with Select fields replaced by aliases
     */
    public function turnSelectFieldsToAliases(Criteria $criteria)
    {
        $selectFields = $criteria->getSelectFields();
        // clearSelectFields also clears the aliases, so get them too
        $asFields = $criteria->getAsFields();
        $criteria->clearSelectFields();
        $fieldAliases = $asFields;
        // add the select fields back
        foreach ($selectFields as $clause) {
            // Generate a unique alias
            $baseAlias = preg_replace('/\W/', '_', $clause);
            $alias = $baseAlias;
            // If it already exists, add a unique suffix
            $i = 0;
            while (isset($fieldAliases[$alias])) {
                $i++;
                $alias = $baseAlias . '_' . $i;
            }
            // Add it as an alias
            $criteria->addAsField($alias, $clause);
            $fieldAliases[$alias] = $clause;
        }
        // Add the aliases back, don't modify them
        foreach ($asFields as $name => $clause) {
            $criteria->addAsField($name, $clause);
        }

        return $criteria;
    }

    /**
     * Binds values in a prepared statement.
     *
     * This method is designed to work with the Criteria::createSelectSql() method, which creates
     * both the SELECT SQL statement and populates a passed-in array of parameter
     * values that should be substituted.
     *
     * <code>
     * $adapter = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
     * $sql = $criteria->createSelectSql($params);
     * $stmt = $con->prepare($sql);
     * $params = array();
     * $adapter->populateStmtValues($stmt, $params, Propel::getServiceContainer()->getDatabaseMap($critera->getDbName()));
     * $stmt->execute();
     * </code>
     *
     * @param \PDOStatement $stmt
     * @param array              $params array('field' => ..., 'entity' => ..., 'value' => ...)
     * @param DatabaseMap        $dbMap
     */
    public function bindValues(\PDOStatement $stmt, array $params, DatabaseMap $dbMap)
    {
        $position = 0;
        foreach ($params as $param) {
            $position++;
            $parameter = ':p' . $position;
            $value = $param['value'];
            if (null === $value) {
                $stmt->bindValue($parameter, null, \PDO::PARAM_NULL);
                continue;
            }
            $entityName = $param['entity'];
            if (null === $entityName) {
                $type = isset($param['type']) ? $param['type'] : \PDO::PARAM_STR;
                $stmt->bindValue($parameter, $value, $type);
                continue;
            }
            $cMap = $dbMap->getEntity($entityName)->getField($param['field']);
            $this->bindValue($stmt, $parameter, $value, $cMap, $position);
        }
    }

    /**
     * Binds a value to a positioned parameter in a statement,
     * given a FieldMap object to infer the binding type.
     *
     * @param \PDOStatement $stmt      The statement to bind
     * @param string             $parameter Parameter identifier
     * @param mixed              $value     The value to bind
     * @param FieldMap          $cMap      The FieldMap of the field to bind
     * @param null|integer       $position  The position of the parameter to bind
     *
     * @return boolean
     */
    public function bindValue(\PDOStatement $stmt, $parameter, $value, FieldMap $cMap, $position = null)
    {
        if ($cMap->isTemporal()) {
            $value = $this->formatTemporalValue($value, $cMap);
        } elseif (is_resource($value) && $cMap->isLob()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            rewind($value);
        }

        return $stmt->bindValue($parameter, $value, $cMap->getPdoType());
    }

}
