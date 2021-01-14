<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\InvalidArgumentException;

/**
 * PhpFileLoader loads configuration values from a PHP file.
 *
 * The configuration values are expected to be in form of array. I.e.
 * <code>
 *     <?php
 *         return array(
 *                    'property1' => 'value1',
 *                    .......................
 *                );
 * </code>
 *
 * @author Cristiano Cinotti
 */
class PhpFileLoader extends FileLoader
{
    /**
     * Loads a PHP file.
     *
     * @param string $resource The resource
     * @param string|null $type The resource type
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException if invalid json file
     * @throws \InvalidArgumentException if configuration file not found
     *
     * @return array
     */
    public function load($resource, $type = null)
    {
        $path = $this->getPath($resource);

        //Use output buffering because in case $file contains invalid non-php content (i.e. plain text), include() function
        //write it on stdoutput
        ob_start();
        $content = include $path;
        ob_end_clean();

        if (!is_array($content)) {
            throw new InvalidArgumentException("The configuration file '$resource' has invalid content.");
        }

        $content = $this->resolveParams($content); //Resolve parameter placeholders (%name%)

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     * It supports both .php and .inc extensions.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $this->checkSupports(['php', 'inc'], $resource);
    }
}
