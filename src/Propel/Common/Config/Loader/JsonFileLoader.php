<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\JsonParseException;

/**
 * JsonFileLoader loads configuration parameters from json file.
 *
 * @author Cristiano Cinotti
 */
class JsonFileLoader extends FileLoader
{
    /**
     * Loads an Json file.
     *
     * @param string $resource The resource
     * @param string|null $type The resource type
     *
     * @throws \Propel\Common\Config\Exception\JsonParseException if invalid json file
     *
     * @return array
     */
    public function load($resource, $type = null): array
    {
        $json = file_get_contents($this->getPath($resource));

        $content = [];

        if ($json && $json !== '') {
            $content = json_decode($json, true);
            $error = json_last_error();

            if ($error !== JSON_ERROR_NONE) {
                throw new JsonParseException($error);
            }
        }

        return $this->resolveParams($content); //Resolve parameter placeholders (%name%)
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return static::checkSupports('json', $resource);
    }
}
