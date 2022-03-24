<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Util;

use RuntimeException;

trait PathTrait
{
    /**
     * @var string|null
     */
    protected ?string $path = null;

    /**
     * Template paths are by convention in
     * - templates/
     * - besides src/ folder (which is required to detect root path)
     *
     * @param string $path
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    protected function getTemplatePath(string $path): string
    {
        if ($this->path) {
            return $this->path;
        }

        $srcPos = strpos($path, DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);
        if ($srcPos === false) {
            throw new RuntimeException('Cannot find root of repository. Please manually set a template path to file.');
        }

        $root = substr($path, 0, $srcPos) . DIRECTORY_SEPARATOR;

        $pathElements = explode(DIRECTORY_SEPARATOR, $path);
        $pathElements = array_reverse($pathElements);

        $elements = [];
        foreach ($pathElements as $element) {
            if ($element === 'src') {
                break;
            }

            $elements[] = $element;
        }

        $elements = array_reverse($elements);

        if ($elements[0] === 'Propel') {
            array_shift($elements);
        }

        $this->path = $root . 'templates' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $elements) . DIRECTORY_SEPARATOR;

        return $this->path;
    }
}
