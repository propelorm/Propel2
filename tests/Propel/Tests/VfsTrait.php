<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;

/**
 * Useful methods to manipulate virtual filesystem, via vfsStream library
 *
 * @author Cristiano Cinotti
 */
trait VfsTrait
{
    /** @var vfsStreamDirectory */
    private $root;

    /**
     * @return vfsStreamDirectory
     */
    public function getRoot(): vfsStreamDirectory
    {
        if (null === $this->root) {
            $this->root = vfsStream::setup();
        }

        return $this->root;
    }

    /**
     * Add a new file to the filesystem.
     * If the path of the file contains a directory structure, or a directory not present in
     * the virtual file system, it'll be created.
     *
     * @param string $filename
     * @param string $content
     *
     * @return vfsStreamFile
     */
    public function newFile(string $filename, string $content = ''): vfsStreamFile
    {
        $path = pathinfo($filename);
        $dir = $this->getDir($path['dirname']);

        return vfsStream::newFile($filename)->at($dir)->setContent($content);
    }

    /**
     * Return the directory on which append a file.
     * If the directory does not exists, it'll be created. If the directory name represents
     * a structure (e.g. dir/sub_dir/sub_sub_dir) the structure is created.
     *
     * @param string $dirname
     *
     * @return vfsStreamDirectory
     */
    private function getDir(string $dirname): vfsStreamDirectory
    {
        if ('.' === $dirname) {
            return $this->getRoot();
        }

        $dirs = explode('/', $dirname);
        $parent = $this->getRoot();
        foreach ($dirs as $dir) {
            $current = $parent->getChild($dir);
            if (null === $current) {
                $current = vfsStream::newDirectory($dir)->at($parent);
            }
            $parent = $current;
        }

        return $parent;
    }
}
