<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

/**
 * Generates the empty PHP5 stub class for object query
 *
 * This class produces the empty stub class that can be customized with application
 * business logic, custom behavior, etc.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class StubQueryBuilder extends AbstractBuilder
{

    /**
     * @return string
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        return parent::getFullClassName('') . 'Query';
    }

    public function buildClass()
    {
        $parentClass = $this->getQueryBuilder()->getFullClassName();
        $shortClass = $this->getDefinition()->declareUse($parentClass);
        $this->getDefinition()->setParentClassName($shortClass);
    }
}
