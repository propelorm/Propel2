<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Parser;

use Propel\Runtime\Exception\FileNotFoundException;

/**
 * Base class for all parsers. A parser converts data from and to an associative array.
 *
 * @author Francois Zaninotto (Propel)
 * @author Jonathan H. Wage <jwage@mac.com> (Doctrine_Parser)
 */
abstract class AbstractParser
{
    /**
     * Converts data from an associative array to the parser format.
     *
     * Override in the parser driver.
     *
     * @param  array $array Source data to convert
     * @return mixed Converted data, depending on the parser format
     */
    abstract public function fromArray($array);

    /**
     * Converts data from the parser format to an associative array.
     *
     * Override in the parser driver.
     *
     * @param  mixed $data Source data to convert, depending on the parser format
     * @return array Converted data
     */
    abstract public function toArray($data);

    public function listFromArray($data)
    {
        return $this->fromArray($data);
    }

    public function listToArray($data)
    {
        return $this->toArray($data);
    }

    /**
     * Loads data from a file. Executes PHP code blocks in the file.
     *
     * @param string $path Path to the file to load
     *
     * @return string The file content processed by PHP
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist or is unreadable', $path));
        }

        ob_start();
        include $path;
        $contents = ob_get_clean();

        return $contents;
    }

    /**
     * Dumps data to a file, or to STDOUT if no filename is given
     *
     * @param string $data The file content
     * @param string $path Path of the file to create
     */
    public function dump($data, $path = null)
    {
        if (null !== $path) {
            return file_put_contents($path, $data);
        }

        echo $data;
    }

    /**
     * Factory for getting an instance of a subclass of AbstractParser
     *
     * @param string $type Parser type, amon 'XML', 'YAML', 'JSON', and 'CSV'
     *
     * @return AbstractParser A PropelParser subclass instance
     */
    public static function getParser($type = 'XML')
    {
        $class = sprintf('\Propel\Runtime\Parser\%sParser', ucfirst(strtolower($type)));

        if (!class_exists($class)) {
            throw new FileNotFoundException(sprintf('Unknown parser class "%s"', $class));
        }

        return new $class;
    }
}
