<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @param array $array Source data to convert
     * @param string $rootKey The parser might use this for naming the root key of the parser format
     *
     * @return mixed Converted data, depending on the parser format
     */
    abstract public function fromArray(array $array, string $rootKey = 'data');

    /**
     * Converts data from the parser format to an associative array.
     *
     * Override in the parser driver.
     *
     * @param string $data Source data to convert, depending on the parser format
     * @param string $rootKey The parser might use this name for converting from parser format
     *
     * @return array Converted data
     */
    abstract public function toArray(string $data, string $rootKey = 'data'): array;

    /**
     * @param array $array
     * @param string|null $rootKey
     *
     * @return string
     */
    public function listFromArray(array $array, ?string $rootKey = 'data'): string
    {
        return $this->fromArray($array, (string)$rootKey);
    }

    /**
     * @param string $data
     * @param string $rootKey
     *
     * @return array
     */
    public function listToArray(string $data, string $rootKey = 'data'): array
    {
        return $this->toArray($data, $rootKey);
    }

    /**
     * Loads data from a file. Executes PHP code blocks in the file.
     *
     * @param string $path Path to the file to load
     *
     * @throws \Propel\Runtime\Exception\FileNotFoundException
     *
     * @return string The file content processed by PHP
     */
    public function load(string $path): string
    {
        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist or is unreadable', $path));
        }

        ob_start();
        include $path;

        return (string)ob_get_clean();
    }

    /**
     * Dumps data to a file, or to STDOUT if no filename is given
     *
     * @param string $data The file content
     * @param string|null $path Path of the file to create
     *
     * @return int|null|void
     */
    public function dump(string $data, ?string $path = null)
    {
        if ($path !== null) {
            return (int)file_put_contents($path, $data);
        }

        echo $data;
    }

    /**
     * Factory for getting an instance of a subclass of AbstractParser
     *
     * @param string $type Parser type, amon 'XML', 'YAML', 'JSON', and 'CSV'
     *
     * @throws \Propel\Runtime\Exception\FileNotFoundException
     *
     * @return self A PropelParser subclass instance
     */
    public static function getParser(string $type = 'XML'): self
    {
        /** @phpstan-var class-string<\Propel\Runtime\Parser\AbstractParser> $class */
        $class = sprintf('\Propel\Runtime\Parser\%sParser', ucfirst(strtolower($type)));

        if (!class_exists($class)) {
            throw new FileNotFoundException(sprintf('Unknown parser class "%s"', $class));
        }

        return new $class();
    }
}
