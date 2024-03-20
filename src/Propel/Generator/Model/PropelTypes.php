<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use PDO;

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
    /**
     * @var string
     */
    public const CHAR = 'CHAR';

    /**
     * @var string
     */
    public const VARCHAR = 'VARCHAR';

    /**
     * @var string
     */
    public const LONGVARCHAR = 'LONGVARCHAR';

    /**
     * @var string
     */
    public const CLOB = 'CLOB';

    /**
     * @var string
     */
    public const CLOB_EMU = 'CLOB_EMU';

    /**
     * @var string
     */
    public const NUMERIC = 'NUMERIC';

    /**
     * @var string
     */
    public const DECIMAL = 'DECIMAL';

    /**
     * @var string
     */
    public const TINYINT = 'TINYINT';

    /**
     * @var string
     */
    public const SMALLINT = 'SMALLINT';

    /**
     * @var string
     */
    public const INTEGER = 'INTEGER';

    /**
     * @var string
     */
    public const BIGINT = 'BIGINT';

    /**
     * @var string
     */
    public const REAL = 'REAL';

    /**
     * @var string
     */
    public const FLOAT = 'FLOAT';

    /**
     * @var string
     */
    public const DOUBLE = 'DOUBLE';

    /**
     * @var string
     */
    public const BINARY = 'BINARY';

    /**
     * @var string
     */
    public const VARBINARY = 'VARBINARY';

    /**
     * @var string
     */
    public const LONGVARBINARY = 'LONGVARBINARY';

    /**
     * @var string
     */
    public const BLOB = 'BLOB';

    /**
     * @var string
     */
    public const DATE = 'DATE';

    /**
     * @var string
     */
    public const DATETIME = 'DATETIME';

    /**
     * @var string
     */
    public const TIME = 'TIME';

    /**
     * @var string
     */
    public const TIMESTAMP = 'TIMESTAMP';

    /**
     * @var string
     */
    public const BU_DATE = 'BU_DATE';

    /**
     * @var string
     */
    public const BU_TIMESTAMP = 'BU_TIMESTAMP';

    /**
     * @var string
     */
    public const BOOLEAN = 'BOOLEAN';

    /**
     * @var string
     */
    public const BOOLEAN_EMU = 'BOOLEAN_EMU';

    /**
     * @var string
     */
    public const OBJECT = 'OBJECT';

    /**
     * @var string
     */
    public const PHP_ARRAY = 'ARRAY';

    /**
     * @var string
     */
    public const ENUM = 'ENUM';

    /**
     * @var string
     */
    public const SET = 'SET';

    /**
     * @var string
     */
    public const GEOMETRY = 'GEOMETRY';

    /**
     * @var string
     */
    public const JSON = 'JSON';

    /**
     * @var string
     */
    public const CHAR_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const VARCHAR_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const LONGVARCHAR_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const CLOB_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const CLOB_EMU_NATIVE_TYPE = 'resource';

    /**
     * @var string
     */
    public const NUMERIC_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const DECIMAL_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const TINYINT_NATIVE_TYPE = 'int';

    /**
     * @var string
     */
    public const SMALLINT_NATIVE_TYPE = 'int';

    /**
     * @var string
     */
    public const INTEGER_NATIVE_TYPE = 'int';

    /**
     * @var string
     */
    public const BIGINT_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const REAL_NATIVE_TYPE = 'double';

    /**
     * @var string
     */
    public const FLOAT_NATIVE_TYPE = 'double';

    /**
     * @var string
     */
    public const DOUBLE_NATIVE_TYPE = 'double';

    /**
     * @var string
     */
    public const BINARY_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const VARBINARY_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const LONGVARBINARY_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const BLOB_NATIVE_TYPE = 'resource';

    /**
     * @var string
     */
    public const BU_DATE_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const DATE_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const DATETIME_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const TIME_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const TIMESTAMP_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const BU_TIMESTAMP_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const BOOLEAN_NATIVE_TYPE = 'boolean';

    /**
     * @var string
     */
    public const BOOLEAN_EMU_NATIVE_TYPE = 'boolean';

    /**
     * @var string
     */
    public const OBJECT_NATIVE_TYPE = '';

    /**
     * @var string
     */
    public const PHP_ARRAY_NATIVE_TYPE = 'array';

    /**
     * @var string
     */
    public const ENUM_NATIVE_TYPE = 'int';

    /**
     * @var string
     */
    public const SET_NATIVE_TYPE = 'int';

    /**
     * @var string
     */
    public const GEOMETRY_NATIVE_TYPE = 'resource';

    /**
     * @var string
     */
    public const JSON_TYPE = 'string';

    /**
     * @var string
     */
    public const UUID = 'UUID';

    /**
     * @var string
     */
    public const UUID_NATIVE_TYPE = 'string';

    /**
     * @var string
     */
    public const UUID_BINARY = 'UUID_BINARY';

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
        self::DATETIME,
        self::TIME,
        self::TIMESTAMP,
        self::BOOLEAN,
        self::BOOLEAN_EMU,
        self::OBJECT,
        self::PHP_ARRAY,
        self::ENUM,
        self::GEOMETRY,
        // These are pre-epoch dates, which we need to map to String type
        // since they cannot be properly handled using strtotime() -- or
        // even numeric timestamps on Windows.
        self::BU_DATE,
        self::BU_TIMESTAMP,
        self::SET,
        self::JSON,
        self::UUID,
        self::UUID_BINARY,
    ];

    /**
     * Mapping between Propel mapping types and PHP native types.
     *
     * @var array
     */
    private static $mappingToPHPNativeMap = [
        self::CHAR => self::CHAR_NATIVE_TYPE,
        self::VARCHAR => self::VARCHAR_NATIVE_TYPE,
        self::LONGVARCHAR => self::LONGVARCHAR_NATIVE_TYPE,
        self::CLOB => self::CLOB_NATIVE_TYPE,
        self::CLOB_EMU => self::CLOB_EMU_NATIVE_TYPE,
        self::NUMERIC => self::NUMERIC_NATIVE_TYPE,
        self::DECIMAL => self::DECIMAL_NATIVE_TYPE,
        self::TINYINT => self::TINYINT_NATIVE_TYPE,
        self::SMALLINT => self::SMALLINT_NATIVE_TYPE,
        self::INTEGER => self::INTEGER_NATIVE_TYPE,
        self::BIGINT => self::BIGINT_NATIVE_TYPE,
        self::REAL => self::REAL_NATIVE_TYPE,
        self::FLOAT => self::FLOAT_NATIVE_TYPE,
        self::DOUBLE => self::DOUBLE_NATIVE_TYPE,
        self::BINARY => self::BINARY_NATIVE_TYPE,
        self::VARBINARY => self::VARBINARY_NATIVE_TYPE,
        self::LONGVARBINARY => self::LONGVARBINARY_NATIVE_TYPE,
        self::BLOB => self::BLOB_NATIVE_TYPE,
        self::DATE => self::DATE_NATIVE_TYPE,
        self::DATETIME => self::DATETIME_NATIVE_TYPE,
        self::BU_DATE => self::BU_DATE_NATIVE_TYPE,
        self::TIME => self::TIME_NATIVE_TYPE,
        self::TIMESTAMP => self::TIMESTAMP_NATIVE_TYPE,
        self::BU_TIMESTAMP => self::BU_TIMESTAMP_NATIVE_TYPE,
        self::BOOLEAN => self::BOOLEAN_NATIVE_TYPE,
        self::BOOLEAN_EMU => self::BOOLEAN_EMU_NATIVE_TYPE,
        self::OBJECT => self::OBJECT_NATIVE_TYPE,
        self::PHP_ARRAY => self::PHP_ARRAY_NATIVE_TYPE,
        self::ENUM => self::ENUM_NATIVE_TYPE,
        self::SET => self::SET_NATIVE_TYPE,
        self::GEOMETRY => self::GEOMETRY,
        self::JSON => self::JSON_TYPE,
        self::UUID => self::UUID_NATIVE_TYPE,
        self::UUID_BINARY => self::UUID_NATIVE_TYPE,
    ];

    /**
     * Mapping between mapping types and PDO type constants (for prepared
     * statement settings).
     *
     * @var array<int>
     */
    private static $mappingTypeToPDOTypeMap = [
        self::CHAR => PDO::PARAM_STR,
        self::VARCHAR => PDO::PARAM_STR,
        self::LONGVARCHAR => PDO::PARAM_STR,
        self::CLOB => PDO::PARAM_STR,
        self::CLOB_EMU => PDO::PARAM_STR,
        self::NUMERIC => PDO::PARAM_INT,
        self::DECIMAL => PDO::PARAM_STR,
        self::TINYINT => PDO::PARAM_INT,
        self::SMALLINT => PDO::PARAM_INT,
        self::INTEGER => PDO::PARAM_INT,
        self::BIGINT => PDO::PARAM_INT,
        self::REAL => PDO::PARAM_STR,
        self::FLOAT => PDO::PARAM_STR,
        self::DOUBLE => PDO::PARAM_STR,
        self::BINARY => PDO::PARAM_STR,
        self::VARBINARY => PDO::PARAM_LOB,
        self::LONGVARBINARY => PDO::PARAM_LOB,
        self::BLOB => PDO::PARAM_LOB,
        self::DATE => PDO::PARAM_STR,
        self::DATETIME => PDO::PARAM_STR,
        self::TIME => PDO::PARAM_STR,
        self::TIMESTAMP => PDO::PARAM_STR,
        self::BOOLEAN => PDO::PARAM_BOOL,
        self::BOOLEAN_EMU => PDO::PARAM_INT,
        self::OBJECT => PDO::PARAM_LOB,
        self::PHP_ARRAY => PDO::PARAM_STR,
        self::ENUM => PDO::PARAM_INT,
        self::SET => PDO::PARAM_INT,
        self::GEOMETRY => PDO::PARAM_LOB,

        // These are pre-epoch dates, which we need to map to String type
        // since they cannot be properly handled using strtotime() -- or even
        // numeric timestamps on Windows.
        self::BU_DATE => PDO::PARAM_STR,
        self::BU_TIMESTAMP => PDO::PARAM_STR,
        self::JSON => PDO::PARAM_STR,
        self::UUID => PDO::PARAM_STR,
        self::UUID_BINARY => PDO::PARAM_LOB,
    ];

    /**
     * @var array<string>
     */
    private static $pdoTypeNames = [
        PDO::PARAM_BOOL => 'PDO::PARAM_BOOL',
        PDO::PARAM_NULL => 'PDO::PARAM_NULL',
        PDO::PARAM_INT => 'PDO::PARAM_INT',
        PDO::PARAM_STR => 'PDO::PARAM_STR',
        PDO::PARAM_LOB => 'PDO::PARAM_LOB',
    ];

    /**
     * Returns the native PHP type which corresponds to the
     * mapping type provided. Use in the base object class generation.
     *
     * @param string $mappingType
     *
     * @return string
     */
    public static function getPhpNative(string $mappingType): string
    {
        return self::$mappingToPHPNativeMap[$mappingType];
    }

    /**
     * Returns the PDO type (PDO::PARAM_* constant) value.
     *
     * @param string $type
     *
     * @return int
     */
    public static function getPDOType(string $type): int
    {
        return self::$mappingTypeToPDOTypeMap[$type];
    }

    /**
     * Returns the PDO type ('PDO::PARAM_*' constant) name.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getPdoTypeString(string $type): string
    {
        return self::$pdoTypeNames[self::$mappingTypeToPDOTypeMap[$type]];
    }

    /**
     * Returns an array of mapping types.
     *
     * @return array
     */
    public static function getPropelTypes(): array
    {
        return self::$mappingTypes;
    }

    /**
     * Returns whether the given type is a temporal type.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isTemporalType(string $type): bool
    {
        return in_array($type, [
            self::DATE,
            self::DATETIME,
            self::TIME,
            self::TIMESTAMP,
            self::BU_DATE,
            self::BU_TIMESTAMP,
        ], true);
    }

    /**
     * Returns whether the given type is a text type.
     *
     * @param string $mappingType
     *
     * @return bool
     */
    public static function isTextType(string $mappingType): bool
    {
        return in_array($mappingType, [
            self::CHAR,
            self::VARCHAR,
            self::LONGVARCHAR,
            self::CLOB,
            self::DATE,
            self::DATETIME,
            self::TIME,
            self::TIMESTAMP,
            self::BU_DATE,
            self::BU_TIMESTAMP,
            self::JSON,
        ], true);
    }

    /**
     * Returns whether the given type is a numeric type.
     *
     * @param string $mappingType
     *
     * @return bool
     */
    public static function isNumericType(string $mappingType): bool
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
        ], true);
    }

    /**
     * Returns whether this column is a boolean type.
     *
     * @param string $mappingType
     *
     * @return bool
     */
    public static function isBooleanType(string $mappingType): bool
    {
        return in_array($mappingType, [self::BOOLEAN, self::BOOLEAN_EMU], true);
    }

    /**
     * Returns whether this column is a lob/blob type.
     *
     * @param string $mappingType
     *
     * @return bool
     */
    public static function isLobType(string $mappingType): bool
    {
        return in_array($mappingType, [self::VARBINARY, self::LONGVARBINARY, self::BLOB, self::OBJECT, self::GEOMETRY], true);
    }

    /**
     * Returns whether the given type is a UUID type.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isUuidType(string $type): bool
    {
        return in_array($type, [
            self::UUID,
            self::UUID_BINARY,
        ], true);
    }

    /**
     * Returns whether a passed-in PHP type is a primitive type.
     *
     * @param string $phpType
     *
     * @return bool
     */
    public static function isPhpPrimitiveType(string $phpType): bool
    {
        return in_array($phpType, ['boolean', 'int', 'double', 'float', 'string'], true);
    }

    /**
     * Returns whether a passed-in PHP type is a primitive numeric type.
     *
     * @param string $phpType
     *
     * @return bool
     */
    public static function isPhpPrimitiveNumericType(string $phpType): bool
    {
        return in_array($phpType, ['boolean', 'int', 'double', 'float'], true);
    }

    /**
     * Returns whether a passed-in PHP type is an object.
     *
     * @param string $phpType
     *
     * @return bool
     */
    public static function isPhpObjectType(string $phpType): bool
    {
        return !self::isPhpPrimitiveType($phpType) && !in_array($phpType, ['resource', 'array'], true);
    }

    /**
     * Convenience method to indicate whether a passed-in PHP type is an array.
     *
     * @param string $phpType The PHP type to check
     *
     * @return bool
     */
    public static function isPhpArrayType(string $phpType): bool
    {
        return strtoupper($phpType) === self::PHP_ARRAY;
    }
}
