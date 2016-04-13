<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component;

use Propel\Generator\Exception\BuildException;

trait SimpleTemplateTrait
{

    /**
     * Renders a template and returns it output.
     *
     * Searches for a template following this scheme:
     *   $curDir/template/{$template}.mustache
     *
     * where $curDir is the current directory the get_called_class is living and
     * $template is your given value or the underscore version of your get_called_class name.
     *
     * @param array  $context
     * @param string $template relative to current Component directory + ./template/.
     *
     * @return string
     */
    protected function renderTemplate(array $context = array(), $template = '')
    {
        $m = new \Mustache_Engine;
        $classReflection = new \ReflectionClass(get_called_class());
        $currentDir = dirname($classReflection->getFileName());

        if (!$template) {
            $template = $classReflection->getShortName();
        }

        $filePath = $currentDir . '/templates/' . $template . '.mustache';
        if (!file_exists($filePath)) {
            throw new BuildException(sprintf('Can not find template `%s`.', $filePath));
        }

        return $m->render(file_get_contents($filePath), $context);
    }

}