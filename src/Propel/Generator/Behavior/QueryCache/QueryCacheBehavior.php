<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Speeds up queries on a model by caching the query
 *
 * @author François Zaninotto
 */
class QueryCacheBehavior extends Behavior
{
    use ComponentTrait;

    // default parameters value
    protected $parameters = array(
        'backend'     => 'apc',
        'lifetime'    => 3600,
    );

    public function queryBuilderModification(QueryBuilder $builder)
    {
        $builder->getDefinition()->addUseStatement('Propel\Runtime\Configuration');

        $this->applyComponent('Attributes', $builder);
        $this->applyComponent('QueryKeyManipulation', $builder);
        $this->applyComponent('CacheManipulation', $builder);
        $this->applyComponent('DoSelectMethod', $builder);
        $this->applyComponent('DoCountMethod', $builder);
    }
}
