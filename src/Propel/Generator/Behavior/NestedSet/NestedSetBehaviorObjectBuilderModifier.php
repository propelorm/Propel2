<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author François Zaninotto
 * @author heltem <heltem@o2php.com>
 */
class NestedSetBehaviorObjectBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table    = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getColumnAttribute($name)
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    protected function getColumnPhpName($name)
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
    }

    public function objectClearReferences($builder)
    {
        return "\$this->collNestedSetChildren = null;
\$this->aNestedSetParent = null;";
    }

    public function objectMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';

        $this->addProcessNestedSetQueries($script);

        if ('LeftValue' !== $this->getColumnPhpName('left_column')) {
            $this->addGetLeft($script);
        }
        if ('RightValue' !== $this->getColumnPhpName('right_column')) {
            $this->addGetRight($script);
        }
        if ('Level' !== $this->getColumnPhpName('level_column')) {
            $this->addGetLevel($script);
        }
        if ('true' === $this->getParameter('use_scope')
            && 'ScopeValue' !== $this->getColumnPhpName('scope_column')) {
            $this->addGetScope($script);
        }

        if ('LeftValue' !== $this->getColumnPhpName('left_column')) {
            $script .= $this->addSetLeft();
        }
        if ('RightValue' !== $this->getColumnPhpName('right_column')) {
            $this->addSetRight($script);
        }
        if ('Level' !== $this->getColumnPhpName('level_column')) {
            $this->addSetLevel($script);
        }
        if ('true' === $this->getParameter('use_scope')
            && 'ScopeValue' !== $this->getColumnPhpName('scope_column')) {
            $this->addSetScope($script);
        }

        $this->addMakeRoot($script);

        $this->addIsInTree($script);
        $this->addIsRoot($script);
        $this->addIsLeaf($script);
        $this->addIsDescendantOf($script);
        $this->addIsAncestorOf($script);

        $this->addHasParent($script);
        $this->addSetParent($script);
        $this->addGetParent($script);

        $this->addHasPrevSibling($script);
        $this->addGetPrevSibling($script);

        $this->addHasNextSibling($script);
        $this->addGetNextSibling($script);

        $this->addNestedSetChildrenClear($script);
        $this->addNestedSetChildrenInit($script);
        $this->addNestedSetChildAdd($script);
        $this->addHasChildren($script);
        $this->addGetChildren($script);
        $this->addCountChildren($script);

        $this->addGetFirstChild($script);
        $this->addGetLastChild($script);
        $this->addGetSiblings($script);
        $this->addGetDescendants($script);
        $this->addCountDescendants($script);
        $this->addGetBranch($script);
        $this->addGetAncestors($script);

        $this->builder->declareClassFromBuilder($builder->getStubObjectBuilder(), 'Child');
        $this->addAddChild($script);
        $this->addInsertAsFirstChildOf($script);

        $script .= $this->addInsertAsLastChildOf();

        $this->addInsertAsPrevSiblingOf($script);
        $this->addInsertAsNextSiblingOf($script);

        $this->addMoveToFirstChildOf($script);
        $this->addMoveToLastChildOf($script);
        $this->addMoveToPrevSiblingOf($script);
        $this->addMoveToNextSiblingOf($script);
        $this->addMoveSubtreeTo($script);

        $this->addDeleteDescendants($script);

        $this->builder->declareClass(
            '\Propel\Runtime\ActiveRecord\NestedSetRecursiveIterator'
        );

        $script .= $this->addGetIterator();

        return $script;
    }


    protected function addGetIterator()
    {
        return $this->behavior->renderTemplate('objectGetIterator');
    }
}
