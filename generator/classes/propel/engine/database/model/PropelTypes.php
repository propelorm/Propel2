<?php
/*
 *  $Id: PropelTypes.php,v 1.4 2005/03/26 04:56:55 joe_cai Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */
 
require_once 'creole/CreoleTypes.php';

/**
 * A class that maps PropelTypes to CreoleTypes and to native PHP types.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @version $Revision: 1.4 $
 * @package propel.engine.database.model
 */
class PropelTypes {

    const CHAR = "CHAR";
    const VARCHAR = "VARCHAR";
    const LONGVARCHAR = "LONGVARCHAR";
    const CLOB = "CLOB";
    const NUMERIC = "NUMERIC";
    const DECIMAL = "DECIMAL";
    const TINYINT = "TINYINT";
    const SMALLINT = "SMALLINT";
    const INTEGER = "INTEGER";
    const BIGINT = "BIGINT";
    const REAL = "REAL";
    const FLOAT = "FLOAT";
    const DOUBLE = "DOUBLE";
    const BINARY = "BINARY";
    const VARBINARY = "VARBINARY";
    const LONGVARBINARY = "LONGVARBINARY";
    const BLOB = "BLOB";
    const DATE = "DATE";
    const TIME = "TIME";
    const TIMESTAMP = "TIMESTAMP";
	
	const BU_DATE = "BU_DATE";
	const BU_TIMESTAMP = "BU_TIMESTAMP";
	
    const BOOLEAN = "BOOLEAN";
    
    private static $TEXT_TYPES = null;
    
    private static $LOB_TYPES = null;
    
    const CHAR_NATIVE_TYPE = "string";
    const VARCHAR_NATIVE_TYPE = "string";
    const LONGVARCHAR_NATIVE_TYPE = "string";
    const CLOB_NATIVE_TYPE = "string"; // Clob
    const NUMERIC_NATIVE_TYPE = "double";
    const DECIMAL_NATIVE_TYPE = "double";
    const BOOLEAN_NATIVE_TYPE = "boolean";
    const TINYINT_NATIVE_TYPE = "int";
    const SMALLINT_NATIVE_TYPE = "int";
    const INTEGER_NATIVE_TYPE = "int";
    const BIGINT_NATIVE_TYPE = "int";
    const REAL_NATIVE_TYPE = "double";
    const FLOAT_NATIVE_TYPE = "double";
    const DOUBLE_NATIVE_TYPE = "double";
    const BINARY_NATIVE_TYPE = "string";
    const VARBINARY_NATIVE_TYPE = "string";
    const LONGVARBINARY_NATIVE_TYPE = "string";
    const BLOB_NATIVE_TYPE = "string";
	const BU_DATE_NATIVE_TYPE = "string";
    const DATE_NATIVE_TYPE = "int";
    const TIME_NATIVE_TYPE = "int";
    const TIMESTAMP_NATIVE_TYPE = "int";
	const BU_TIMESTAMP_NATIVE_TYPE = "string";
	
    private static $propelToPHPNativeMap = null;
    private static $propelTypeToCreoleTypeMap = null;
    private static $creoleToPropelTypeMap = null;
    
    private static $isInitialized = false;

    /**
     * Initializes the SQL to PHP map so that it
     * can be used by client code.
     */
    public static function initialize()
    {
        if (self::$isInitialized === false) {
        
            self::$TEXT_TYPES = array (
                        self::CHAR, self::VARCHAR, self::LONGVARCHAR, self::CLOB, self::DATE, self::TIME, self::TIMESTAMP, self::BU_DATE, self::BU_TIMESTAMP
                    );
        
            self::$LOB_TYPES = array (
                        self::VARBINARY, self::LONGVARBINARY, self::CLOB, self::BLOB
                    );
            
            /*
             * Create Creole -> native PHP type mappings.
             */
             
            self::$propelToPHPNativeMap = array();

            self::$propelToPHPNativeMap[self::CHAR] = self::CHAR_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::VARCHAR] = self::VARCHAR_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::LONGVARCHAR] = self::LONGVARCHAR_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::CLOB] = self::CLOB_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::NUMERIC] = self::NUMERIC_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::DECIMAL] = self::DECIMAL_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::TINYINT] = self::TINYINT_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::SMALLINT] = self::SMALLINT_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::INTEGER] = self::INTEGER_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::BIGINT] = self::BIGINT_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::REAL] = self::REAL_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::FLOAT] = self::FLOAT_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::DOUBLE] = self::DOUBLE_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::BINARY] = self::BINARY_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::VARBINARY] = self::VARBINARY_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::LONGVARBINARY] = self::LONGVARBINARY_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::BLOB] = self::BLOB_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::DATE] = self::DATE_NATIVE_TYPE;
			self::$propelToPHPNativeMap[self::BU_DATE] = self::BU_DATE_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::TIME] = self::TIME_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::TIMESTAMP] = self::TIMESTAMP_NATIVE_TYPE;
			self::$propelToPHPNativeMap[self::BU_TIMESTAMP] = self::BU_TIMESTAMP_NATIVE_TYPE;
            self::$propelToPHPNativeMap[self::BOOLEAN] = self::BOOLEAN_NATIVE_TYPE;

            /*
             * Create Propel -> Creole _name_ mappings (not CreoleType:: mappings).
             * (this is now pretty useless since we've designed them to be the same!)
             */
            self::$propelTypeToCreoleTypeMap = array();
            self::$propelTypeToCreoleTypeMap[self::CHAR] = self::CHAR;
            self::$propelTypeToCreoleTypeMap[self::VARCHAR] = self::VARCHAR;
            self::$propelTypeToCreoleTypeMap[self::LONGVARCHAR] = self::LONGVARCHAR;
            self::$propelTypeToCreoleTypeMap[self::CLOB] = self::CLOB;
            self::$propelTypeToCreoleTypeMap[self::NUMERIC] = self::NUMERIC;
            self::$propelTypeToCreoleTypeMap[self::DECIMAL] = self::DECIMAL;
            self::$propelTypeToCreoleTypeMap[self::TINYINT] = self::TINYINT;
            self::$propelTypeToCreoleTypeMap[self::SMALLINT] = self::SMALLINT;
            self::$propelTypeToCreoleTypeMap[self::INTEGER] = self::INTEGER;
            self::$propelTypeToCreoleTypeMap[self::BIGINT] = self::BIGINT;
            self::$propelTypeToCreoleTypeMap[self::REAL] = self::REAL;
            self::$propelTypeToCreoleTypeMap[self::FLOAT] = self::FLOAT;
            self::$propelTypeToCreoleTypeMap[self::DOUBLE] = self::DOUBLE;
            self::$propelTypeToCreoleTypeMap[self::BINARY] = self::BINARY;
            self::$propelTypeToCreoleTypeMap[self::VARBINARY] = self::VARBINARY;
            self::$propelTypeToCreoleTypeMap[self::LONGVARBINARY] = self::LONGVARBINARY;
            self::$propelTypeToCreoleTypeMap[self::BLOB] = self::BLOB;
            self::$propelTypeToCreoleTypeMap[self::DATE] = self::DATE;
            self::$propelTypeToCreoleTypeMap[self::TIME] = self::TIME;
            self::$propelTypeToCreoleTypeMap[self::TIMESTAMP] = self::TIMESTAMP;
            self::$propelTypeToCreoleTypeMap[self::BOOLEAN] = self::BOOLEAN;
			
			// These are pre-epoch dates, which we need to map to String type
			// since they cannot be properly handled using strtotime() -- or even numeric
			// timestamps on Windows.
			self::$propelTypeToCreoleTypeMap[self::BU_DATE] = self::VARCHAR;
			self::$propelTypeToCreoleTypeMap[self::BU_TIMESTAMP] = self::VARCHAR;
			

            /*
             * Create Creole type code to Propel type map.
             */
            self::$creoleToPropelTypeMap = array();

            self::$creoleToPropelTypeMap[CreoleTypes::CHAR] = self::CHAR;
            self::$creoleToPropelTypeMap[CreoleTypes::VARCHAR] = self::VARCHAR;
            self::$creoleToPropelTypeMap[CreoleTypes::LONGVARCHAR] = self::LONGVARCHAR;
            self::$creoleToPropelTypeMap[CreoleTypes::CLOB] = self::CLOB;
            self::$creoleToPropelTypeMap[CreoleTypes::NUMERIC] = self::NUMERIC;
            self::$creoleToPropelTypeMap[CreoleTypes::DECIMAL] = self::DECIMAL;
            self::$creoleToPropelTypeMap[CreoleTypes::TINYINT] = self::TINYINT;
            self::$creoleToPropelTypeMap[CreoleTypes::SMALLINT] = self::SMALLINT;
            self::$creoleToPropelTypeMap[CreoleTypes::INTEGER] = self::INTEGER;
            self::$creoleToPropelTypeMap[CreoleTypes::BIGINT] = self::BIGINT;
            self::$creoleToPropelTypeMap[CreoleTypes::REAL] = self::REAL;
            self::$creoleToPropelTypeMap[CreoleTypes::FLOAT] = self::FLOAT;
            self::$creoleToPropelTypeMap[CreoleTypes::DOUBLE] = self::DOUBLE;
            self::$creoleToPropelTypeMap[CreoleTypes::BINARY] = self::BINARY;
            self::$creoleToPropelTypeMap[CreoleTypes::VARBINARY] = self::VARBINARY;
            self::$creoleToPropelTypeMap[CreoleTypes::LONGVARBINARY] = self::LONGVARBINARY;
            self::$creoleToPropelTypeMap[CreoleTypes::BLOB] = self::BLOB;
            self::$creoleToPropelTypeMap[CreoleTypes::DATE] = self::DATE;
            self::$creoleToPropelTypeMap[CreoleTypes::TIME] = self::TIME;
            self::$creoleToPropelTypeMap[CreoleTypes::TIMESTAMP] = self::TIMESTAMP;
            self::$creoleToPropelTypeMap[CreoleTypes::BOOLEAN] = self::BOOLEAN;
            self::$creoleToPropelTypeMap[CreoleTypes::YEAR] = self::INTEGER;
            
            self::$isInitialized = true;
        }
    }

    /**
     * Report whether this object has been initialized.
     *
     * @return true if this object has been initialized
     */
    public static function isInitialized()
    {
        return self::$isInitialized;
    }

    /**
     * Return native PHP type which corresponds to the
     * Creole type provided. Use in the base object class generation.
     *
     * @param $propelType The Propel type name.
     * @return string Name of the native PHP type
     */
    public static function getPhpNative($propelType)
    {
        return self::$propelToPHPNativeMap[$propelType];
    }            
    
    /**
     * Returns the correct Creole type _name_ for propel added types
     *
     * @param $type the propel added type.
     * @return string Name of the the correct Creole type (e.g. "VARCHAR").
     */
    public static function getCreoleType($type)
    {
        return  self::$propelTypeToCreoleTypeMap[$type];
    }

    /**
     * Returns Propel type constant corresponding to Creole type code.
     * Used but Propel Creole task.
     *
     * @param int $sqlType The Creole SQL type constant.
     * @return string The Propel type to use or NULL if none found.
     */
    public static function getPropelType($sqlType)
    {
        if (isset(self::$creoleToPropelTypeMap[$sqlType])) {
            return self::$creoleToPropelTypeMap[$sqlType];
        }
    }
    
    /**
     * Get array of Propel types.
     * 
     * @return array string[]
     */
    public static function getPropelTypes()
    {
        return array_keys(self::$propelTypeToCreoleTypeMap);
    }
    
    /**
     * Returns true if values for the type need to be quoted.
     *
     * @param string $type The Propel type to check.
     * @return true if values for the type need to be quoted.
     */
    public static function isTextType($type)
    {
        // Make sure the we are initialized.
        if (self::$isInitialized === false) {
            self::initialize();
        }
        return in_array($type, self::$TEXT_TYPES);
    }
    
    /**
     * Returns true if type is a LOB type (i.e. would be handled by Blob/Clob class).
     * @param string $type Propel type to check.
     * @return boolean
     */
    public static function isLobType($type)
    {
        return in_array($type, self::$LOB_TYPES);
    }
}

// static
PropelTypes::initialize();
