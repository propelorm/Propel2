<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Model\Table;

/**
 * Tools to support class & package inclusion and referencing.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ClassTools
{

    /**
     * Gets just classname, given a dot-path to class.
     * @param  string $qualifiedName
     * @return string
     */
    static public function classname($qualifiedName)
    {
        if (false === $pos = strrpos($qualifiedName, '.')) {
            return $qualifiedName;  // there is no '.' in the qualifed name
        }

        return substr($qualifiedName, $pos + 1); // start just after '.'
    }

    /**
     * Gets the path to be used in include()/require() statement.
     *
     * Supports multiple function signatures:
     *
     * (1) getFilePath($dotPathClass);
     * (2) getFilePath($dotPathPrefix, $className);
     * (3) getFilePath($dotPathPrefix, $className, $extension);
     *
     * @param  string $path      dot-path to class or to package prefix.
     * @param  string $classname class name
     * @param  string $extension The extension to use on the file.
     * @return string The constructed file path.
     */
    static public function getFilePath($path, $classname = null, $extension = '.php')
    {
        $path = strtr(ltrim($path, '.'), '.', '/');

        return self::createFilePath($path, $classname, $extension);
    }

    /**
     * This method remplaces the `getFilePath()` method in OMBuilder as we consider `$path` as
     * a real path instead of a dot-notation value. `$path` is generated by  the `getPackagePath()`
     * method.
     *
     * @param  string $path      path to class or to package prefix.
     * @param  string $classname class name
     * @param  string $extension The extension to use on the file.
     * @return string The constructed file path.
     */
    static public function createFilePath($path, $classname = null, $extension = '.php')
    {
        if (null === $classname) {
            return $path . $extension;
        }

        if (!empty($path)) {
            $path .= '/';
        }

        return $path . $classname . $extension;
    }

    /**
     * Gets the basePeer path if specified for table/db.
     * If not, will return 'propel.util.BasePeer'
     * @return string
     */
    static public function getBasePeer(Table $table)
    {
        if (null === $class = $table->getBasePeer()) {
            $class = 'propel.util.BasePeer';
        }

        return $class;
    }

    /**
     * Gets the baseClass path if specified for table/db.
     * If not, will return 'propel.om.BaseObject'
     * @return string
     */
    static public function getBaseClass(Table $table)
    {
        if (null === $class = $table->getBaseClass()) {
            $class = 'propel.om.BaseObject';
        }

        return $class;
    }

    /**
     * Gets the interface path if specified for table.
     * If not, will return 'propel.om.Persistent'.
     * @return string
     */
    static public function getInterface(Table $table)
    {
        $interface = $table->getInterface();
        if (null === $interface && !$table->isReadOnly()) {
            $interface = 'propel.om.Persistent';
        }

        return $interface;
    }

    /**
     * Gets a list of PHP reserved words.
     *
     * @return array string[]
     */
    static public function getPhpReservedWords()
    {
        return array(
            'and', 'or', 'xor', 'exception', '__FILE__', '__LINE__',
            'array', 'as', 'break', 'case', 'class', 'const', 'continue',
            'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
            'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
            'eval', 'exit', 'extends', 'for', 'foreach', 'function', 'global',
            'if', 'include', 'include_once', 'isset', 'list', 'new', 'print', 'require',
            'require_once', 'return', 'static', 'switch', 'unset', 'use', 'var', 'while',
            '__FUNCTION__', '__CLASS__', '__METHOD__', '__DIR__', '__NAMESPACE__', 'final', 'php_user_filter', 'interface',
            'implements', 'extends', 'public', 'protected', 'private', 'abstract', 'clone', 'try', 'catch',
            'throw', 'this', 'namespace'
        );
    }
}
