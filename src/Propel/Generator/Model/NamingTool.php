<?php

namespace Propel\Generator\Model;

class NamingTool
{
    /**
     * Convert a string from underscore to camel case.
     * E.g. my_own_variable => myOwnVariable
     *
     * @param string $string The string to convert
     * @static
     *
     * @return string
     */
    public static function toCamelCase($string)
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $string))));
    }

    /**
     * Convert a string from camel case to underscore.
     * E.g. myOwnVariable => my_own_variable.
     *
     * Numbers are considered as part of its previous piece:
     * E.g. myTest3Variable => my_test3_variable
     *
     * @param string $string The string to convert
     * @static
     *
     * @return string
     */
    public static function toUnderscore($string)
    {
        return strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Convert a string from underscore to camel case, with upper-case first letter.
     * This function is useful while writing getter and setter method names.
     * E.g. my_own_variable => MyOwnVariable
     *
     * @param string $string
     * @static
     *
     * @return string
     */
    public static function toUpperCamelCase($string)
    {
        return implode('', array_map('ucfirst', explode('_', $string)));
    }

    /**
     * App\Model\User -> User
     *
     * @param string $fullClassName
     * @return string
     */
    public static function shortClassName($fullClassName)
    {
        return basename(str_replace('\\', '/', $fullClassName));
    }

    /**
     * App\Model\User.titleName -> titleName
     *
     * @param string $identifier
     * @return string
     */
    public static function fieldName($identifier)
    {
        return basename(str_replace('.', '/', $identifier));
    }

    /**
     * Returns a short unique-enough id for debugging purposes.
     *
     * @param object|string $entity
     * @return string
     */
    public static function shortEntityId($entity)
    {
        $id = is_string($entity) ? $entity : spl_object_hash($entity);
        return substr(md5($id), 0, 9);
    }
}