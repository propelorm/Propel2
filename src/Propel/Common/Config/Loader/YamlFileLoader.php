<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\InputOutputException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class YamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param string $resource The resource
     * @param string|null $type The resource type
     *
     * @throws \Propel\Common\Config\Exception\InputOutputException if configuration file is not readable
     * @throws \Symfony\Component\Yaml\Exception\ParseException if something goes wrong in parsing file
     *
     * @return array
     */
    public function load($resource, $type = null): array
    {
        $path = $this->locator->locate($resource);

        if (!is_readable($path)) {
            throw new InputOutputException(sprintf("You don't have permissions to access configuration file `%s`.", $resource));
        }

        $data = file_get_contents($path);
        if (!$data) {
            throw new InputOutputException(sprintf('Unable to read configuration file `%s`.', $resource));
        }

        $content = Yaml::parse($data);

        // config file is empty
        if ($content === null) {
            $content = [];
        }

        if (!is_array($content)) {
            throw new ParseException('Unable to parse the configuration file: wrong yaml content.');
        }

        return $this->resolveParams($content); //Resolve parameter placeholders (%name%)
    }

    /**
     * Returns true if this class supports the given resource.
     * Both 'yml' and 'yaml' extensions are accepted.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return static::checkSupports(['yaml', 'yml'], $resource);
    }
}
