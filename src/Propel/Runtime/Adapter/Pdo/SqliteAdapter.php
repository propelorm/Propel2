<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * This is used in order to connect to a SQLite database.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqliteAdapter extends PdoAdapter implements SqlAdapterInterface
{

    /**
     * For SQLite this method has no effect, since SQLite doesn't support specifying a character
     * set (or, another way to look at it, it doesn't require a single character set per DB).
     *
     * @param ConnectionInterface $con     A PDO connection instance.
     * @param string              $charset The charset encoding.
     *
     * @throws \Propel\Runtime\Exception\PropelException If the specified charset doesn't match sqlite_libencoding()
     */
    public function setCharset(ConnectionInterface $con, $charset)
    {
    }

    public function initConnection(ConnectionInterface $con, array $settings)
    {
        $con->query('PRAGMA foreign_keys = ON');
        parent::initConnection($con, $settings);

        //add regex support
        $con->sqliteCreateFunction('regexp', function($pattern, $value) {
            mb_regex_encoding('UTF-8');
            return (false !== mb_ereg($pattern, $value)) ? 1 : 0;
        });
    }

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param string $s1 String to concatenate.
     * @param string $s2 String to append.
     *
     * @return string
     */
    public function concatString($s1, $s2)
    {
        return "($s1 || $s2)";
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param string  $s   String to extract from.
     * @param integer $pos Offset to start from.
     * @param integer $len Number of characters to extract.
     *
     * @return string
     */
    public function subString($s, $pos, $len)
    {
        return "substr($s, $pos, $len)";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param  string $s String to calculate length of.
     * @return string
     */
    public function strLength($s)
    {
        return "length($s)";
    }

    /**
     * @see AdapterInterface::quoteIdentifier()
     *
     * @param  string $text
     * @return string
     */
    public function quoteIdentifier($text)
    {
        return "[$text]";
    }

    /**
     * @see AdapterInterface::applyLimit()
     *
     * @param string  $sql
     * @param integer $offset
     * @param integer $limit
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        if ($limit >= 0) {
            $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . $offset : '');
        } elseif ($offset > 0) {
            $sql .= sprintf(' LIMIT -1 OFFSET %i', $offset);
        }
    }

    /**
     * @param  string $seed
     * @return string
     */
    public function random($seed = null)
    {
        return 'random()';
    }
}
