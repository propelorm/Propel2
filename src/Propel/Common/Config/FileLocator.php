<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config;

use Symfony\Component\Config\FileLocator as BaseFileLocator;

/**
 * Propel file locator class.
 *
 * @author Cristiano Cinotti
 */
class FileLocator extends BaseFileLocator
{
    /**
     * Constructor
     *
     * By default, the locator looks for configuration file in the current directory (where bin/propel script is running)
     * or in a 'conf' or 'config' subdirectory.
     *
     * @param null|array $configDirectories
     */
    public function __construct($configDirectories = null)
    {
        if (null === $configDirectories) {
            $configDirectories = [
                getcwd(),
                'config',
                'conf',
            ];
        }

        parent::__construct($configDirectories);
    }
}
