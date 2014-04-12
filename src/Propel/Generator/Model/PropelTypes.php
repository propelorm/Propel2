<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * A class that maps PropelTypes to PHP native types and PDO types.
 *
 * Support for Creole types have been removed as this DBAL library is no longer
 * supported by the Propel project.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class PropelTypes
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

    const CHAR_NATIVE_TYPE          = 'string';
    const VARCHAR_NATIVE_TYPE       = 'string';
    const LONGVARCHAR_NATIVE_TYPE   = 'string';
    const CLOB_NATIVE_TYPE          = 'string';
    const CLOB_EMU_NATIVE_TYPE      = 'resource';
    const NUMERIC_NATIVE_TYPE       = 'string';
    const DECIMAL_NATIVE_TYPE       = 'string';
    const TINYINT_NATIVE_TYPE       = 'int';
    const SMALLINT_NATIVE_TYPE      = 'int';
    const INTEGER_NATIVE_TYPE       = 'int';
    const BIGINT_NATIVE_TYPE        = 'string';
    const REAL_NATIVE_TYPE          = 'double';
    const FLOAT_NATIVE_TYPE         = 'double';
    const DOUBLE_NATIVE_TYPE        = 'double';
    const BINARY_NATIVE_TYPE        = 'string';
    const VARBINARY_NATIVE_TYPE     = 'string';
    const LONGVARBINARY_NATIVE_TYPE = 'string';
    const BLOB_NATIVE_TYPE          = 'resource';
    const BU_DATE_NATIVE_TYPE       = 'string';
    const DATE_NATIVE_TYPE          = 'string';
    const TIME_NATIVE_TYPE          = 'string';
    const TIMESTAMP_NATIVE_TYPE     = 'string';
    const BU_TIMESTAMP_NATIVE_TYPE  = 'string';
    const BOOLEAN_NATIVE_TYPE       = 'boolean';
    const BOOLEAN_EMU_NATIVE_TYPE   = 'boolean';
    const OBJECT_NATIVE_TYPE        = '';
    const PHP_ARRAY_NATIVE_TYPE     = 'array';
    const ENUM_NATIVE_TYPE          = 'int';

    /**
     * Propel mapping types.
     *
     * @var array
     */
    private static $mappingTypes = [
        self::CHAR,
        self::VARCHAR,
        self::LONGVARCHAR,
        self::CLOB,
        self::NUMERIC,
        self::DECIMAL,
        self::TINYINT,
        self::SMALLINT,
        self::INTEGER,
        self::BIGINT,
        self::REAL,
        self::FLOAT,
        self::DOUBLE,
        self::BINARY,
        self::VARBINARY,
        self::LONGVARBINARY,
        self::BLOB,
        self::DATE,
        self::TIME,
        self::TIMESTAMP,
        self::BOOLEAN,
        self::BOOLEAN_EMU,
        self::OBJECT,
        self::PHP_ARRAY,
        self::ENUM,
        // These are pre-epoch dates, which we need to map to String type
        // since they cannot be properly handled using strtotime() -- or
        // even numeric timestamps on Windows.
        self::BU_DATE,
        self::BU_TIMESTAMP,
    ];

    /**
     * Mapping between Propel mapping types and PHP native types.
     *
     * @var array
     */
    private static $mappingToPHPNativeMap = [
        self::CHAR          => self::CHAR_NATIVE_TYPE,
        self::VARCHAR       => self::VARCHAR_NATIVE_TYPE,
        self::LONGVARCHAR   => self::LONGVARCHAR_NATIVE_TYPE,
        self::CLOB          => self::CLOB_NATIVE_TYPE,
        self::CLOB_EMU      => self::CLOB_EMU_NATIVE_TYPE,
        self::NUMERIC       => self::NUMERIC_NATIVE_TYPE,
        self::DECIMAL       => self::DECIMAL_NATIVE_TYPE,
        self::TINYINT       => self::TINYINT_NATIVE_TYPE,
        self::SMALLINT      => self::SMALLINT_NATIVE_TYPE,
        self::INTEGER       => self::INTEGER_NATIVE_TYPE,
        self::BIGINT        => self::BIGINT_NATIVE_TYPE,
        self::REAL          => self::REAL_NATIVE_TYPE,
        self::FLOAT         => self::FLOAT_NATIVE_TYPE,
        self::DOUBLE        => self::DOUBLE_NATIVE_TYPE,
        self::BINARY        => self::BINARY_NATIVE_TYPE,
        self::VARBINARY     => self::VARBINARY_NATIVE_TYPE,
        self::LONGVARBINARY => self::LONGVARBINARY_NATIVE_TYPE,
        self::BLOB          => self::BLOB_NATIVE_TYPE,
        self::DATE          => self::DATE_NATIVE_TYPE,
        self::BU_DATE       => self::BU_DATE_NATIVE_TYPE,
        self::TIME          => self::TIME_NATIVE_TYPE,
        self::TIMESTAMP     => self::TIMESTAMP_NATIVE_TYPE,
        self::BU_TIMESTAMP  => self::BU_TIMESTAMP_NATIVE_TYPE,
        self::BOOLEAN       => self::BOOLEAN_NATIVE_TYPE,
        self::BOOLEAN_EMU   => self::BOOLEAN_EMU_NATIVE_TYPE,
        self::OBJECT        => self::OBJECT_NATIVE_TYPE,
        self::PHP_ARRAY     => self::PHP_ARRAY_NATIVE_TYPE,
        self::ENUM          => self::ENUM_NATIVE_TYPE,
    ];

    /**
     * Mapping between mapping types and PDO type constants (for prepared
     * statement settings).
     *
     * @var array
     */
    private static $mappingTypeToPDOTypeMap = [
        self::CHAR          => \PDO::PARAM_STR,
        self::VARCHAR       => \PDO::PARAM_STR,
        self::LONGVARCHAR   => \PDO::PARAM_STR,
        self::CLOB          => \PDO::PARAM_STR,
        self::CLOB_EMU      => \PDO::PARAM_STR,
        self::NUMERIC       => \PDO::PARAM_INT,
        self::DECIMAL       => \PDO::PARAM_STR,
        self::TINYINT       => \PDO::PARAM_INT,
        self::SMALLINT      => \PDO::PARAM_INT,
        self::INTEGER       => \PDO::PARAM_INT,
        self::BIGINT        => \PDO::PARAM_INT,
        self::REAL          => \PDO::PARAM_STR,
        self::FLOAT         => \PDO::PARAM_STR,
        self::DOUBLE        => \PDO::PARAM_STR,
        self::BINARY        => \PDO::PARAM_STR,
        self::VARBINARY     => \PDO::PARAM_LOB,
        self::LONGVARBINARY => \PDO::PARAM_LOB,
        self::BLOB          => \PDO::PARAM_LOB,
        self::DATE          => \PDO::PARAM_STR,
        self::TIME          => \PDO::PARAM_STR,
        self::TIMESTAMP     => \PDO::PARAM_STR,
        self::BU_DATE       => \PDO::PARAM_STR,
        self::BU_TIMESTAMP  => \PDO::PARAM_STR,
        self::BOOLEAN       => \PDO::PARAM_BOOL,
        self::BOOLEAN_EMU   => \PDO::PARAM_INT,
        self::OBJECT        => \PDO::PARAM_LOB,
        self::PHP_ARRAY     => \PDO::PARAM_STR,
        self::ENUM          => \PDO::PARAM_INT,

        // These are pre-epoch dates, which we need to map to String type
        // since they cannot be properly handled using strtotime() -- or even
        // numeric timestamps on Windows.
        self::BU_DATE       => \PDO::PARAM_STR,
        self::BU_TIMESTAMP  => \PDO::PARAM_STR,
    ];

    private static $pdoTypeNames = [
        \PDO::PARAM_BOOL => 'PDO::PARAM_BOOL',
        \PDO::PARAM_NULL => 'PDO::PARAM_NULL',
        \PDO::PARAM_INT  => 'PDO::PARAM_INT',
        \PDO::PARAM_STR  => 'PDO::PARAM_STR',
        \PDO::PARAM_LOB  => 'PDO::PARAM_LOB',
    ];

    /**
     * Returns the native PHP type which corresponds to the
     * mapping type provided. Use in the base object class generation.
     *
     * @param  string $mappingType
     * @return string
     */
    public static function getPhpNative($mappingType)
    {
        return self::$mappingToPHPNativeMap[$mappingType];
    }

    /**
     * Returns the PDO type (PDO::PARAM_* constant) value.
     *
     * @return integer
     */
    public static function getPDOType($type)
    {
        return self::$mappingTypeToPDOTypeMap[$type];
    }

    /**
     * Returns the PDO type ('PDO::PARAM_*' constant) name.
     *
     * @return string
     */
    public static function getPdoTypeString($type)
    {
        return self::$pdoTypeNames[self::$mappingTypeToPDOTypeMap[$type]];
    }

    /**
     * Returns an array of mapping types.
     *
     * @return array
     */
    public static function getPropelTypes()
    {
        return self::$mappingTypes;
    }

    /**
     * Returns whether or not the given type is a temporal type.
     *
     * @param  string  $type
     * @return boolean
     */
    public static function isTemporalType($type)
    {
        return in_array($type, [
            self::DATE,
            self::TIME,
            self::TIMESTAMP,
            self::BU_DATE,
            self::BU_TIMESTAMP,
        ]);
    }

    /**
     * Returns whether or not the given type is a text type.
     *
     * @param  string  $mappingType
     * @return boolean
     */
    public static function isTextType($mappingType)
    {
        return in_array($mappingType, [
            self::CHAR,
            self::VARCHAR,
            self::LONGVARCHAR,
            self::CLOB,
            self::DATE,
            self::TIME,
            self::TIMESTAMP,
            self::BU_DATE,
            self::BU_TIMESTAMP,
        ]);
    }

    /**
     * Returns whether or not the given type is a numeric type.
     *
     * @param  string  $mappingType
     * @return boolean
     */
    public static function isNumericType($mappingType)
    {
        return in_array($mappingType, [
            self::SMALLINT,
            self::TINYINT,
            self::INTEGER,
            self::BIGINT,
            self::FLOAT,
            self::DOUBLE,
            self::NUMERIC,
            self::DECIMAL,
            self::REAL,
        ]);
    }

    /**
     * Returns whether or not this column is a boolean type.
     *
     * @param  string  $mappingType
     * @return boolean
     */
    public static function isBooleanType($mappingType)
    {
        return in_array($mappingType, [ self::BOOLEAN, self::BOOLEAN_EMU ]);
    }

    /**
     * Returns whether or not this column is a lob/blob type.
     *
     * @param  string  $mappingType
     * @return boolean
     */
    public static function isLobType($mappingType)
    {
        return in_array($mappingType, [ self::VARBINARY, self::LONGVARBINARY, self::BLOB, self::OBJECT ]);
    }

    /**
     * Returns whether or not a passed-in PHP type is a primitive type.
     *
     * @param  string  $phpType
     * @return boolean
     */
    public static function isPhpPrimitiveType($phpType)
    {
        return in_array($phpType, [ 'boolean', 'int', 'double', 'float', 'string' ]);
    }

    /**
     * Returns whether or not a passed-in PHP type is a primitive numeric type.
     *
     * @param  string  $phpType
     * @return boolean
     */
    public static function isPhpPrimitiveNumericType($phpType)
    {
        return in_array($phpType, [ 'boolean', 'int', 'double', 'float' ]);
    }

    /**
     * Returns whether or not a passed-in PHP type is an object.
     *
     * @param  string  $phpType
     * @return boolean
     */
    public static function isPhpObjectType($phpType)
    {
        return !self::isPhpPrimitiveType($phpType) && !in_array($phpType, [ 'resource', 'array' ]);
    }

    /**
     * Convenience method to indicate whether a passed-in PHP type is an array.
     *
     * @param  string  $phpType The PHP type to check
     * @return boolean
     */
    public static function isPhpArrayType($phpType)
    {
        return strtoupper($phpType) === self::PHP_ARRAY;
    }
}
