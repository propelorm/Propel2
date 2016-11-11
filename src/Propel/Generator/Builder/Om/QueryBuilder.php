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
 * Generates a PHP5 base Query class for user object model (OM).
 *
 * This class produces the base query class (e.g. BaseBookQuery) which contains
 * all the custom-built query methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class QueryBuilder extends AbstractBuilder
{
    /**
     * @return string
     */
    public function getFullClassName($fullClassName = 'Base', $classPrefix = 'Base')
    {
        return parent::getFullClassName('Base', 'Base') . 'Query';
    }

    public function buildClass()
    {
        $this->definition->setParentClassName('\Propel\Runtime\ActiveQuery\ModelCriteria');

        $this->applyComponent('Query\\Constructor');
        $this->applyComponent('Query\\JoinMethods');
        $this->applyComponent('Query\\FilterByFieldMethods');
        $this->applyComponent('Query\\FilterByCrossRelationMethods');
        $this->applyComponent('Query\\FilterByRelationMethods');
        $this->applyComponent('Query\\FilterByPrimaryKeyMethod');
        $this->applyComponent('Query\\FilterByPrimaryKeysMethod');
        $this->applyComponent('Query\\PruneMethod');
        $this->applyComponent('Query\\UseQueryMethods');
    }
}