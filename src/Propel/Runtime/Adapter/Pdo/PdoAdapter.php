<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use PDO;
use PDOException;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Adapter\Pdo\PdoConnection;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Util\PropelDateTime;
use Propel\Runtime\Util\PropelColumnTypes;

/**
 * Base for PDO database adapters.
 */
abstract class PdoAdapter
{
    /**
     * Build database connection
     *
     * @param array    $conparams connection parameters
     *
     * @return Propel\Runtime\Adapter\Pdo\PdoConnection
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
                $value = $optiondata['value'];
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
        } catch (PDOException $e) {
            throw new AdapterException("Unable to open PDO connection", $e);
        }

        return $con;
    }

    /**
     * Prepare the parameters for a Connection
     *
     * @param array the connection parameters from the configuration
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
     * @see       setCharset()
     *
     * @param     Propel\Runtime\Connection\ConnectionInterface    $con
     * @param     array  $settings  An array of settings.
     */
    public function initConnection(ConnectionInterface $con, array $settings)
    {
        if (isset($settings['charset'])) {
            $this->setCharset($con, $settings['charset']);
        }

        if (isset($settings['queries']) && is_array($settings['queries'])) {
            foreach ($settings['queries'] as $queries) {
                foreach ((array)$queries as $query) {
                    $con->exec($query);
                }
            }
        }
    }

    /**
     * Sets the character encoding using SQL standard SET NAMES statement.
     *
     * This method is invoked from the default initConnection() method and must
     * be overridden for an RDMBS which does _not_ support this SQL standard.
     *
     * @see       initConnection()
     *
     * @param     Propel\Runtime\Connection\ConnectionInterface $con
     * @param     string  $charset  The $string charset encoding.
     */
    public function setCharset(ConnectionInterface $con, $charset)
    {
        $con->exec("SET NAMES '" . $charset . "'");
    }

    /**
     * This method is used to ignore case.
     *
     * @param     string  $in  The string to transform to upper case.
     * @return    string  The upper case string.
     */
    public function toUpperCase($in)
    {
        return 'UPPER(' . $in . ')';
    }

    /**
     * This method is used to ignore case.
     *
     * @param     string  $in The string whose case to ignore.
     * @return    string  The string in a case that can be ignored.
     */
    public function ignoreCase($in)
    {
        return 'UPPER(' . $in . ')';
    }

    /**
     * This method is used to ignore case in an ORDER BY clause.
     * Usually it is the same as ignoreCase, but some databases
     * (Interbase for example) does not use the same SQL in ORDER BY
     * and other clauses.
     *
     * @param     string  $in  The string whose case to ignore.
     * @return    string  The string in a case that can be ignored.
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
     * @return    string  The text delimeter.
     */
    public function getStringDelimiter()
    {
        return '\'';
    }

    /**
     * Quotes database objec identifiers (table names, col names, sequences, etc.).
     * @param     string  $text  The identifier to quote.
     * @return    string  The quoted identifier.
     */
    public function quoteIdentifier($text)
    {
        return '"' . $text . '"';
    }

    /**
     * Quotes a database table which could have space seperating it from an alias,
     * both should be identified seperately. This doesn't take care of dots which
     * separate schema names from table names. Adapters for RDBMs which support
     * schemas have to implement that in the platform-specific way.
     *
     * @param     string  $table  The table name to quo
     * @return    string  The quoted table name
     **/
    public function quoteIdentifierTable($table)
    {
        return implode(" ", array_map(array($this, "quoteIdentifier"), explode(" ", $table) ) );
    }

    /**
     * Returns the native ID method for this RDBMS.
     *
     * @return    integer  One of AdapterInterface:ID_METHOD_SEQUENCE, AdapterInterface::ID_METHOD_AUTOINCREMENT.
     */
    protected function getIdMethod()
    {
        return AdapterInterface::ID_METHOD_AUTOINCREMENT;
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return    boolean
     */
    public function isGetIdBeforeInsert()
    {
        return (AdapterInterface::ID_METHOD_SEQUENCE === $this->getIdMethod());
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return    boolean
     */
    public function isGetIdAfterInsert()
    {
        return (AdapterInterface::ID_METHOD_AUTOINCREMENT === $this->getIdMethod());
    }

    /**
     * Gets the generated ID (either last ID for autoincrement or next sequence ID).
     *
     * @param     Propel\Runtime\Connection\ConnectionInterface $con
     * @param     string  $name
     *
     * @return    mixed
     */
    public function getId(ConnectionInterface $con, $name = null)
    {
        return $con->lastInsertId($name);
    }

    /**
     * Formats a temporal value before binding, given a ColumnMap object
     *
     * @param     mixed      $value  The temporal value
     * @param     Propel\Runtime\Map\ColumnMap  $cMap
     *
     * @return    string  The formatted temporal value
     */
    public function formatTemporalValue($value, ColumnMap $cMap)
    {
        /** @var $dt PropelDateTime */
        if ($dt = PropelDateTime::newInstance($value)) {
            switch($cMap->getType()) {
                case PropelColumnTypes::TIMESTAMP:
                case PropelColumnTypes::BU_TIMESTAMP:
                    $value = $dt->format($this->getTimestampFormatter());
                    break;
                case PropelColumnTypes::DATE:
                case PropelColumnTypes::BU_DATE:
                    $value = $dt->format($this->getDateFormatter());
                    break;
                case PropelColumnTypes::TIME:
                    $value = $dt->format($this->getTimeFormatter());
                    break;
            }
        }

        return $value;
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return    string
     */
    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Returns date formatter string for use in date() function.
     *
     * @return    string
     */
    public function getDateFormatter()
    {
        return "Y-m-d";
    }

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return    string
     */
    public function getTimeFormatter()
    {
        return "H:i:s";
    }

    /**
     * Should Column-Names get identifiers for inserts or updates.
     * By default false is returned -> backwards compability.
     *
     * it`s a workaround...!!!
     *
     * @deprecated
     *
     * @return    boolean
     */
    public function useQuoteIdentifier()
    {
        return false;
    }

    /**
     * Allows manipulation of the query string before StatementPdo is instantiated.
     *
     * @param     string       $sql  The sql statement
     * @param     array        $params  array('column' => ..., 'table' => ..., 'value' => ...)
     * @param     Propel\Runtime\Query\Criteria     $values
     * @param     Propel\Runtime\Map\DatabaseMap  $dbMap
     */
    public function cleanupSQL(&$sql, array &$params, Criteria $values, DatabaseMap $dbMap)
    {
    }

    /**
     * Returns the "DELETE FROM <table> [AS <alias>]" part of DELETE query.
     *
     * @param     Propel\Runtime\Query\Criteria  $criteria
     * @param     string    $tableName
     *
     * @return    string
     */
    public function getDeleteFromClause(Criteria $criteria, $tableName)
    {
        $sql = 'DELETE ';
        if ($queryComment = $criteria->getComment()) {
            $sql .= '/* ' . $queryComment . ' */ ';
        }

        if ($realTableName = $criteria->getTableForAlias($tableName)) {
            if ($this->useQuoteIdentifier()) {
                $realTableName = $this->quoteIdentifierTable($realTableName);
            }
            $sql .= $tableName . ' FROM ' . $realTableName . ' AS ' . $tableName;
        } else {
            if ($this->useQuoteIdentifier()) {
                $tableName = $this->quoteIdentifierTable($tableName);
            }
            $sql .= 'FROM ' . $tableName;
        }

        return $sql;
    }

    /**
     * Builds the SELECT part of a SQL statement based on a Criteria
     * taking into account select columns and 'as' columns (i.e. columns aliases)
     * Move from BasePeer to PdoAdapter and turn from static to non static
     *
     * @param     Propel\Runtime\Query\Criteria  $criteria
     * @param     array     $fromClause
     * @param     boolean   $aliasAll
     *
     * @return    string
     */
    public function createSelectSqlPart(Criteria $criteria, &$fromClause, $aliasAll = false)
    {
        $selectClause = array();

        if ($aliasAll) {
            $this->turnSelectColumnsToAliases($criteria);
            // no select columns after that, they are all aliases
        } else {
            foreach ($criteria->getSelectColumns() as $columnName) {

                // expect every column to be of "table.column" formation
                // it could be a function:  e.g. MAX(books.price)

                $tableName = null;

                $selectClause[] = $columnName; // the full column name: e.g. MAX(books.price)

                $parenPos = strrpos($columnName, '(');
                $dotPos = strrpos($columnName, '.', ($parenPos !== false ? $parenPos : 0));

                if (false !== $dotPos) {
                    if (false === $parenPos) { // table.column
                        $tableName = substr($columnName, 0, $dotPos);
                    } else { // FUNC(table.column)
                        // functions may contain qualifiers so only take the last
                        // word as the table name.
                        // COUNT(DISTINCT books.price)
                        $tableName = substr($columnName, $parenPos + 1, $dotPos - ($parenPos + 1));
                        $lastSpace = strrpos($tableName, ' ');
                        if (false !== $lastSpace) { // COUNT(DISTINCT books.price)
                            $tableName = substr($tableName, $lastSpace + 1);
                        }
                    }
                    // is it a table alias?
                    $tableName2 = $criteria->getTableForAlias($tableName);
                    if ($tableName2 !== null) {
                        $fromClause[] = $tableName2 . ' ' . $tableName;
                    } else {
                        $fromClause[] = $tableName;
                    }
                } // if $dotPost !== false
            }
        }

        // set the aliases
        foreach ($criteria->getAsColumns() as $alias => $col) {
            $selectClause[] = $col . ' AS ' . $alias;
        }

        $selectModifiers = $criteria->getSelectModifiers();
        $queryComment = $criteria->getComment();

        // Build the SQL from the arrays we compiled
        $sql =  "SELECT "
            . ($queryComment ? '/* ' . $queryComment . ' */ ' : '')
            . ($selectModifiers ? (implode(' ', $selectModifiers) . ' ') : '')
            . implode(", ", $selectClause);

        return $sql;
    }

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     * Moved from BasePeer to PdoAdapter and turned from static to non static
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param     Propel\Runtime\Query\Criteria  $criteria
     * @return    Propel\Runtime\Query\Criteria  The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria)
    {
        $selectColumns = $criteria->getSelectColumns();
        // clearSelectColumns also clears the aliases, so get them too
        $asColumns = $criteria->getAsColumns();
        $criteria->clearSelectColumns();
        $columnAliases = $asColumns;
        // add the select columns back
        foreach ($selectColumns as $clause) {
            // Generate a unique alias
            $baseAlias = preg_replace('/\W/', '_', $clause);
            $alias = $baseAlias;
            // If it already exists, add a unique suffix
            $i = 0;
            while (isset($columnAliases[$alias])) {
                $i++;
                $alias = $baseAlias . '_' . $i;
            }
            // Add it as an alias
            $criteria->addAsColumn($alias, $clause);
            $columnAliases[$alias] = $clause;
        }
        // Add the aliases back, don't modify them
        foreach ($asColumns as $name => $clause) {
            $criteria->addAsColumn($name, $clause);
        }

        return $criteria;
    }

    /**
     * Binds values in a prepared statement.
     *
     * This method is designed to work with the BasePeer::createSelectSql() method, which creates
     * both the SELECT SQL statement and populates a passed-in array of parameter
     * values that should be substituted.
     *
     * <code>
     * $adapter = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
     * $sql = BasePeer::createSelectSql($criteria, $params);
     * $stmt = $con->prepare($sql);
     * $params = array();
     * $adapter->populateStmtValues($stmt, $params, Propel::getServiceContainer()->getDatabaseMap($critera->getDbName()));
     * $stmt->execute();
     * </code>
     *
     * @param     Propel\Runtime\Connection\StatementInterface $stmt
     * @param     array         $params  array('column' => ..., 'table' => ..., 'value' => ...)
     * @param     Propel\Runtime\Map\DatabaseMap   $dbMap
     */
    public function bindValues(StatementInterface $stmt, array $params, DatabaseMap $dbMap)
    {
        $position = 0;
        foreach ($params as $param) {
            $position++;
            $parameter = ':p' . $position;
            $value = $param['value'];
            if (null === $value) {
                $stmt->bindValue($parameter, null, PDO::PARAM_NULL);
                continue;
            }
            $tableName = $param['table'];
            if (null === $tableName) {
                $type = isset($param['type']) ? $param['type'] : PDO::PARAM_STR;
                $stmt->bindValue($parameter, $value, $type);
                continue;
            }
            $cMap = $dbMap->getTable($tableName)->getColumn($param['column']);
            $this->bindValue($stmt, $parameter, $value, $cMap, $position);
        }
    }

    /**
     * Binds a value to a positioned parameted in a statement,
     * given a ColumnMap object to infer the binding type.
     *
     * @param     Propel\Runtime\Connection\StatementInterface $stmt  The statement to bind
     * @param     string        $parameter  Parameter identifier
     * @param     mixed         $value  The value to bind
     * @param     Propel\Runtime\Map\ColumnMap     $cMap  The ColumnMap of the column to bind
     * @param     null|integer  $position  The position of the parameter to bind
     *
     * @return    boolean
     */
    public function bindValue(StatementInterface $stmt, $parameter, $value, ColumnMap $cMap, $position = null)
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
