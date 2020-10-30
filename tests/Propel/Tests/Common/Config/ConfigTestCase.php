<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var \Symfony\Component\Filesystem\Filesystem|null
     */
    private $fileSystem;

    /**
     * @return \Symfony\Component\Filesystem\Filesystem
     */
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
     * @param string $content File content
     *
     * @return void
     */
    public function dumpTempFile($filename, $content)
    {
        $this->getFilesystem()->dumpFile(sys_get_temp_dir() . '/' . $filename, $content);
    }
}
