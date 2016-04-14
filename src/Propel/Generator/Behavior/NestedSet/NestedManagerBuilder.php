<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Entity;

/**
 * Generate the nested set entity manager.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class NestedManagerBuilder extends AbstractBuilder
{
    public function __construct(Entity $entity)
    {
        $this->overwrite = true;

        parent::__construct($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        return parent::getFullClassName($injectNamespace, $classPrefix) . 'NestedManager';
    }

    public function buildClass()
    {
        $behavior = $this->getEntity()->getBehavior('nested_set');
        $this->getDefinition()->addInterface('\Propel\Runtime\EntityManager\NestedManagerInterface');
        $this->applyComponent('NestedManager\Counters', $this, $behavior);
        $this->applyComponent('NestedManager\DeleteDescendantsMethod', $this, $behavior);
        $this->applyComponent('NestedManager\Getters', $this, $behavior);
        $this->applyComponent('NestedManager\Hassers', $this, $behavior);
        $this->applyComponent('NestedManager\Inserts', $this, $behavior);
        $this->applyComponent('NestedManager\Issers', $this, $behavior);
        $this->applyComponent('NestedManager\MakeRootMethod', $this, $behavior);
        $this->applyComponent('NestedManager\Movers', $this, $behavior);
        $this->applyComponent('NestedManager\UseStatements', $this, $behavior);
    }
}
