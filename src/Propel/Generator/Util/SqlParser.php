<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Util;

use \Propel\Runtime\Connection\ConnectionInterface;

/**
 * Service class for parsing a large SQL string into an array of SQL statements
 *
 * @author François Zaninotto
 */
class SqlParser
{
    protected $delimiter = ';';
    protected $delimiterLength = 1;

    protected $sql = '';
    protected $len = 0;
    protected $pos = 0;

    /**
     * Sets the inner SQL string for this object.
     * Also resets the parsing cursor (see getNextStatement)
     *
     * @param string $sql The SQL string to parse
     */
    public function setSQL($sql)
    {
        if (strncmp($sql, "\xef\xbb\xbf", 3) === 0) {
            $sql = substr($sql, 3);
        }
        $this->sql = $sql;
        $this->pos = 0;
        $this->len = strlen($sql);
    }

    /**
     * Gets the inner SQL string for this object.
     *
     * @return string The SQL string to parse
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * Execute a list of DDL statements based on a string
     * Does not use transactions since they are not supported in DDL statements
     *
     * @param string              $input      The SQL statements
     * @param ConnectionInterface $connection a connection object
     *
     * @return integer the number of executed statements
     */
    public static function executeString($input, ConnectionInterface $connection)
    {
        return self::executeStatements(self::parseString($input), $connection);
    }

    /**
     * Execute a list of DDL statements based on the path to the SQL file
     * Does not use transactions since they are not supported in DDL statements
     *
     * @param string              $file       the path to the SQL file
     * @param ConnectionInterface $connection a connection object
     *
     * @return integer the number of executed statements
     */
    public static function executeFile($file, ConnectionInterface $connection)
    {
        return self::executeStatements(self::parseFile($file), $connection);
    }

    /**
     * Execute a list of DDL statements based on an array
     * Does not use transactions since they are not supported in DDL statements
     *
     * @param array               $statements a list of SQL statements
     * @param ConnectionInterface $connection a connection object
     *
     * @return integer the number of executed statements
     */
    protected static function executeStatements($statements, ConnectionInterface $connection)
    {
        $executed = 0;

        foreach ($statements as $statement) {
            $stmt = $connection->prepare($statement);
            if ($stmt instanceof \PDOStatement) {
                // only execute if has no error
                $stmt->execute();
                $executed++;
            }
        }

        return $executed;
    }

    /**
     * Explodes a SQL string into an array of SQL statements.
     * @example
     * <code>
     * echo SqlParser::parseString("-- Table foo
     * DROP TABLE foo;
     * CREATE TABLE foo (
     *   id int(11) NOT NULL AUTO_INCREMENT,
     *   title varchar(255) NOT NULL,
     *   PRIMARY KEY (id),
     * ) ENGINE=InnoDB;");
     * // results in
     * // array(
     * //   "DROP TABLE foo;",
     * //   "CREATE TABLE foo (
     * //      id int(11) NOT NULL AUTO_INCREMENT,
     * //      title varchar(255) NOT NULL,
     * //      PRIMARY KEY (id),
     * //   ) ENGINE=InnoDB;"
     * // )
     * </code>
     * @param string $input The SQL code to parse
     *
     * @return array A list of SQL statement strings
     */
    public static function parseString($input)
    {
        $parser = new self();
        $parser->setSQL($input);
        $parser->convertLineFeedsToUnixStyle();
        $parser->stripSQLCommentLines();

        return $parser->explodeIntoStatements();
    }

    /**
     * Explodes a SQL file into an array of SQL statements.
     * @example
     * <code>
     * echo SqlParser::parseFile('/var/tmp/foo.sql');
     * // results in
     * // array(
     * //   "DROP TABLE foo;",
     * //   "CREATE TABLE foo (
     * //      id int(11) NOT NULL AUTO_INCREMENT,
     * //      title varchar(255) NOT NULL,
     * //      PRIMARY KEY (id),
     * //   ) ENGINE=InnoDB;"
     * // )
     * </code>
     * @param string $file The absolute path to the file to parse
     *
     * @return array A list of SQL statement strings
     */
    public static function parseFile($file)
    {
        if (!file_exists($file)) {
            return [];
        }

        return self::parseString(file_get_contents($file));
    }

    public function convertLineFeedsToUnixStyle()
    {
        $this->setSQL(str_replace(["\r\n", "\r"], "\n", $this->sql));
    }

    public function stripSQLCommentLines()
    {
        $this->setSQL(preg_replace([
            '#^\s*(//|--|\#).*(\n|$)#m',    // //, --, or # style comments
            '#^\s*/\*.*?\*/#s'              // c-style comments
        ], '', $this->sql));
    }

    /**
     * Explodes the inner SQL string into statements based on the SQL statement delimiter (;)
     *
     * @return array A list of SQL statement strings
     */
    public function explodeIntoStatements()
    {
        $this->delimiter = ';';
        $this->delimiterLength = 1;
        $this->pos = 0;
        $sqlStatements = [];
        while ($sqlStatement = $this->getNextStatement()) {
            $sqlStatements[] = $sqlStatement;
        }

        return $sqlStatements;
    }

    private static function parseDelimiter($s, $pos, $length) {
        $end = $pos + $length;
        if ($end <= $pos) {
            return '';
        }
        $quote = $s[$pos];
        if ($quote === "'" || $quote === '"') {
            $pos++;
        } else {
            $quote = " \t\n\r\x0B";
        }
        $decoded = '';
        while (true) {
            $runLength = strcspn($s, '\\' . $quote, $pos, $end - $pos);
            if (0 < $runLength) {
                $decoded .= substr($s, $pos, $runLength);
                $pos += $runLength;
            }
            if ($end <= $pos + 1 || $s[$pos] !== '\\') {
                break;
            }
            $decoded .= $s[++$pos];
        }
        return $decoded;
    }

    /**
     * Gets the next SQL statement in the inner SQL string,
     * and advances the cursor to the end of this statement.
     *
     * @return string A SQL statement
     */
    public function getNextStatement() {
        while (true) {
            while ($this->pos < $this->len) {
                $chord = $this->sql[$this->pos];
                switch ($chord) { // 20 09 0a 0d 0b
                    case " ":
                    case "\t":
                    case "\n":
                    case "\r":
                    case "\x0B":
                        $this->pos++;
                        break;
                    default:
                        break 2;
                }
            }
            $i = $this->pos + 9;
            if ($i < $this->len && substr_compare($this->sql, "delimiter", $this->pos, 9, true) === 0) {
                $skip = strspn($this->sql, " \t\x0B", $i);
                if (0 < $skip) {
                    $i += $skip;
                    $remainingLen = strcspn($this->sql, "\r\n", $i);
                    $this->pos = $i + $remainingLen + 1;
                    if ($remainingLen > 0) {
                        $delimiter = self::parseDelimiter($this->sql, $i, $remainingLen);
                        if ($delimiter !== '' && false === strpos($delimiter, '\\')) {
                            $this->delimiter = $delimiter;
                            $this->delimiterLength = strlen($delimiter);
                            continue;
                        }
                    }
                }
            }
            break;
        }
        $parsedString = '';
        $stringQuotes = '';
        while ($this->pos < $this->len) {
            $chord = $this->sql[$this->pos++];
            if ($chord === '\\') {
                $parsedString .= $chord;
                if ($this->len <= $this->pos) {
                    break;
                }
                $parsedString .= $this->sql[$this->pos++];
                continue;
            }
            if ($stringQuotes) {
                if ($chord === $stringQuotes) {
                    $stringQuotes = '';
                }
            } else {
                if ($chord === $this->delimiter[0] && ($this->delimiterLength === 1 || substr_compare($this->sql, substr($this->delimiter, 1), $this->pos, $this->delimiterLength - 1) === 0)) {
                    $this->pos += $this->delimiterLength - 1;
                    break;
                }
                if ($chord === '"' || $chord === "'") {
                    $stringQuotes = $chord;
                }
            }
            $parsedString .= $chord;
        }
        return trim($parsedString);
    }
}
