<?php

namespace Propel\Generator\Model;

class NamingTool
{
    public static function toCamelCase($string)
    {
        return implode('', array_map('ucfirst', explode('_', $string)));
    }

    public static function toUnderscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }
}