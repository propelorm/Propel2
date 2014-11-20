<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
     * @param mixed  $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws \InvalidArgumentException                            if configuration file not found
     * @throws \Propel\Common\Config\Exception\JsonParseException   if invalid json file
     * @throws \Propel\Common\Config\Exception\InputOutputException if configuration file is not readable
     */
    public function load($file, $type = null)
    {
        $json = file_get_contents($this->getPath($file));

        $content = array();

        if ('' !== $json) {
            $content = json_decode($json, true);
            $error = json_last_error();

            if (JSON_ERROR_NONE !== $error) {
                throw new JsonParseException($error);
            }
        }

        $content = $this->resolveParams($content); //Resolve parameter placeholders (%name%)

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $this->checkSupports('json', $resource);
    }
}
