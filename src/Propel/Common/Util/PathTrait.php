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
     * Template paths are by convention in
     * - templates/
     * - same level as src/ folder (which is required to auto-detect root path)
     *
     * Note:
     * - Propel/Generator/ namespace prefix is removed from the path
     *
     * Examples:
     * - Behavior/BehaviorName/
     * - Builder/Om/
     * - Command/
     * - Manager/
     *
     * @param string $path
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    protected function getTemplatePath(string $path): string
    {
        $srcPos = strrpos($path, DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);
        if ($srcPos === false) {
            // BC shim for old template paths
            $path .= 'templates' . DIRECTORY_SEPARATOR;
            if (is_dir($path)) {
                trigger_error(sprintf('Deprecated template path `%s`, use `ROOT/templates/` instead.', $path), E_USER_DEPRECATED);

                return $path;
            }

            throw new RuntimeException('Cannot find root of repository. Please manually set a template path to file or use `ROOT/src/` and `ROOT/templates/` folders.');
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
        // Propel/Generator/ prefixes are just noise and filtered out
        if ($elements[0] === 'Propel') {
            array_shift($elements);
        }
        if ($elements[0] === 'Generator') {
            array_shift($elements);
        }

        $templatePath = $root . 'templates' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $elements) . DIRECTORY_SEPARATOR;

        // BC shim for old template paths
        if (!is_dir($templatePath)) {
            $path .= 'templates' . DIRECTORY_SEPARATOR;
            if (is_dir($path)) {
                trigger_error(sprintf('Deprecated template path `%s`, use `%s` instead.', $path, $templatePath), E_USER_DEPRECATED);

                return $path;
            }
        }

        return $templatePath;
    }
}
