<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Util;

/**
 * Enumeration of Propel types.
 *
 * THIS CLASS MUST BE KEPT UP-TO-DATE WITH THE MORE EXTENSIVE GENERATOR VERSION OF THIS CLASS.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
class PropelColumnTypes
{
    const CHAR          = 'CHAR';
    const VARCHAR       = 'VARCHAR';
    const LONGVARCHAR   = 'LONGVARCHAR';
    const CLOB          = 'CLOB';
    const CLOB_EMU      = 'CLOB_EMU';
    const NUMERIC       = 'NUMERIC';
    const DECIMAL       = 'DECIMAL';
    const TINYINT       = 'TINYINT';
    const SMALLINT      = 'SMALLINT';
    const INTEGER       = 'INTEGER';
    const BIGINT        = 'BIGINT';
    const REAL          = 'REAL';
    const FLOAT         = 'FLOAT';
    const DOUBLE        = 'DOUBLE';
    const BINARY        = 'BINARY';
    const VARBINARY     = 'VARBINARY';
    const LONGVARBINARY = 'LONGVARBINARY';
    const BLOB          = 'BLOB';
    const DATE          = 'DATE';
    const TIME          = 'TIME';
    const TIMESTAMP     = 'TIMESTAMP';
    const BU_DATE       = 'BU_DATE';
    const BU_TIMESTAMP  = 'BU_TIMESTAMP';
    const BOOLEAN       = 'BOOLEAN';
    const BOOLEAN_EMU   = 'BOOLEAN_EMU';
    const OBJECT        = 'OBJECT';
    const PHP_ARRAY     = 'ARRAY';
    const ENUM          = 'ENUM';

    private static $propelToPdoMap = array(
        self::CHAR          => \PDO::PARAM_STR,
        self::VARCHAR       => \PDO::PARAM_STR,
        self::LONGVARCHAR   => \PDO::PARAM_STR,
        self::CLOB          => \PDO::PARAM_LOB,
        self::CLOB_EMU      => \PDO::PARAM_STR,
        self::NUMERIC       => \PDO::PARAM_STR,
        self::DECIMAL       => \PDO::PARAM_STR,
        self::TINYINT       => \PDO::PARAM_INT,
        self::SMALLINT      => \PDO::PARAM_INT,
        self::INTEGER       => \PDO::PARAM_INT,
        self::BIGINT        => \PDO::PARAM_STR,
        self::REAL          => \PDO::PARAM_STR,
        self::FLOAT         => \PDO::PARAM_STR,
        self::DOUBLE        => \PDO::PARAM_STR,
        self::BINARY        => \PDO::PARAM_STR,
        self::VARBINARY     => \PDO::PARAM_STR,
        self::LONGVARBINARY => \PDO::PARAM_STR,
        self::BLOB          => \PDO::PARAM_LOB,
        self::DATE          => \PDO::PARAM_STR,
        self::TIME          => \PDO::PARAM_STR,
        self::TIMESTAMP     => \PDO::PARAM_STR,
        self::BU_DATE       => \PDO::PARAM_STR,
        self::BU_TIMESTAMP  => \PDO::PARAM_STR,
        self::BOOLEAN       => \PDO::PARAM_BOOL,
        self::BOOLEAN_EMU   => \PDO::PARAM_INT,
        self::OBJECT        => \PDO::PARAM_STR,
        self::PHP_ARRAY     => \PDO::PARAM_STR,
        self::ENUM          => \PDO::PARAM_INT,
    );

    /**
     * Returns the PDO type (PDO::PARAM_* constant) value for the Propel type provided.
     * @param  string $propelType
     * @return int
     */
    public static function getPdoType($propelType)
    {
        return self::$propelToPdoMap[$propelType];
    }
}
