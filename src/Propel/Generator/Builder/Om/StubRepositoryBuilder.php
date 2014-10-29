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
 * Generates the empty PHP5 stub object class for user object model (OM).
 *
 * This class produces the empty stub class that can be customized with application
 * business logic, custom behavior, etc.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class StubRepositoryBuilder extends AbstractBuilder
{
    /**
     * @return string
     */
    public function getFullClassName($fullClassName = '', $classPrefix = '')
    {
        if (true === $this->getEntity()->getRepository()) {
            $fullClassName = parent::getFullClassName('');

            return $fullClassName . 'Repository';
        } else {
            if ($this->getEntity()->getRepository()) {
                return $this->getEntity()->getRepository();
            } else {
                return parent::getFullClassName('Base', 'Base') . 'Repository';
            }
        }
    }

    public function buildClass()
    {
        if (!$this->getEntity()->getRepository()) {
            //entity has a custom repository class, so we don't generate a stub
            return false;
        }

        $parentClass = $this->getRepositoryBuilder()->getFullClassName();
        $this->getDefinition()->setParentClassName($parentClass);
    }
}
