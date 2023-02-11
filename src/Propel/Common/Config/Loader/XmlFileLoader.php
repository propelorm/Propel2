<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\XmlToArrayConverter;

/**
 * XmlFileLoader loads configuration parameters from xml file.
 *
 * @author Cristiano Cinotti
 */
class XmlFileLoader extends FileLoader
{
    /**
     * Loads an Xml file.
     *
     * @param string $resource The resource
     * @param string|null $type The resource type
     *
     * @return array
     */
    public function load($resource, $type = null): array
    {
        $content = XmlToArrayConverter::convert($this->getPath($resource));

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
        return static::checkSupports('xml', $resource);
    }
}
