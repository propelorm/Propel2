<?php declare(strict_types = 1);

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Util;

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
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    /**
     * @return \org\bovigo\vfs\vfsStreamDirectory
     */
    public function getRoot(): vfsStreamDirectory
    {
        if ($this->root === null) {
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
     * @return \org\bovigo\vfs\vfsStreamFile
     */
    public function newFile(string $filename, string $content = ''): vfsStreamFile
    {
        $path = pathinfo($filename);
        $dir = $this->getDir($path['dirname']);

        return vfsStream::newFile($path['basename'])->at($dir)->setContent($content);
    }

    /**
     * Return the directory on which append a file.
     * If the directory does not exists, it'll be created. If the directory name represents
     * a structure (e.g. dir/sub_dir/sub_sub_dir) the structure is created.
     *
     * @param string $dirname
     *
     * @return \org\bovigo\vfs\vfsStreamDirectory
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
