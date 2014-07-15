<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\InputOutputException;
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
     * @param mixed  $file The resource
     * @param string $type The resource type
     *
     * @throws \InvalidArgumentException                               if configuration file not found
     * @throws Propel\Common\Config\Exception\InputOutputException     if configuration file is not readable
     * @throws Propel\Common\Config\Exception\InvalidArgumentException if invalid xml file
     * @throws Propel\Common\Config\Exception\XmlParseException        if something went wrong while parsing xml file
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access configuration file $file.");
        }

        $content = XmlToArrayConverter::convert($path);
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
        $info = pathinfo($resource);
        $extension = $info['extension'];

        if ('dist' === $extension) {
            $extension = pathinfo($info['filename'], PATHINFO_EXTENSION);
        }

        return is_string($resource) && ('xml' === $extension);
    }
}
