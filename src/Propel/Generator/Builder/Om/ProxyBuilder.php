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
 * Generates the proxy class.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ProxyBuilder extends AbstractBuilder
{
    /**
     * @return string
     */
    public function getFullClassName($fullClassName = 'Base', $classPrefix = '')
    {
        return parent::getFullClassName('Base', '').'Proxy';
    }

    public function buildClass()
    {
        $parentClass = $this->getObjectBuilder()->getFullClassName();
        $this->getDefinition()->setParentClassName('\\' . $parentClass);
        $this->getDefinition()->addInterface('\\Propel\\Runtime\\EntityProxyInterface');
        $this->applyComponent('Proxy\\MagicMethods');
    }
}