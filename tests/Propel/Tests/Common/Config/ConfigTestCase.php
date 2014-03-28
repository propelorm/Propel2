<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

use Propel\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for configuration classes tests
 *
 * @author Cristiano Cinotti
 */
class ConfigTestCase extends TestCase
{
    /**
     * Symfony\Component\Filesystem\Filesystem instance
     */
    private $fileSystem = null;

    public function getFilesystem()
    {
        if (null === $this->fileSystem) {
            $this->fileSystem = new Filesystem();
        }

        return $this->fileSystem;
    }

    /**
     * Create a temporary config file inside the system temporary directory
     *
     * @param string $filename File Name
     * @param string $content  File content
     */
    public function dumpTempFile($filename, $content)
    {
        $this->getFilesystem()->dumpFile(sys_get_temp_dir() . '/' . $filename, $content);
    }
}