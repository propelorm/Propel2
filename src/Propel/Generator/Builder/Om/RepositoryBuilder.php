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
 * Generates a PHP5 base Object class for user object model (OM).
 *
 * This class produces the base object class (e.g. BaseMyTable) which contains
 * all the custom-built accessor and setter methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RepositoryBuilder extends AbstractBuilder
{

    /**
     * @return string
     */
    public function getFullClassName($injectNamespace = 'Base', $classPrefix = '')
    {
//        if ($this->getEntity()->getRepository()) {
//            //we have a stub class, so this will be the normal base
//            return parent::getFullClassName('Base', 'Base') . 'Repository';
//        } else {
            return parent::getFullClassName('Base', 'Base') . 'Repository';
//        }
    }

    public function buildClass()
    {
        $this->getDefinition()->setParentClassName('\\Propel\\Runtime\\Repository\\Repository');
        if ($this->getEntity()->getRepository()) {
            //we have a stub class, so this will be the normal base which
            //can not be instantiated
            $this->getDefinition()->setAbstract(true);
        }

        $this->getDefinition()->declareUses(
            '\Propel\Runtime\Exception\PropelException'
        );

//        $this->applyComponent('Repository\\IsNewMethod');
        $this->applyComponent('Repository\\SaveMethod');
        $this->applyComponent('Repository\\RemoveMethod');
        $this->applyComponent('Repository\\FindMethod');

        $this->applyComponent('Repository\\CreateObjectMethod');
        $this->applyComponent('Repository\\CreateQueryMethod');

        $this->applyComponent('Repository\\HookMethods');

        $platformBuilder = $this->getPlatform()->getRepositoryBuilder($this->getEntity());
        $platformBuilder->setDefinition($this->getDefinition());
        $platformBuilder->setGeneratorConfig($this->getGeneratorConfig());
        $platformBuilder->buildClass();
    }
}