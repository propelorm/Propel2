<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Query;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class OrderByRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$order = strtoupper(\$order);
    switch (\$order) {
        case Criteria::ASC:
            return \$this->addAscendingOrderByField(\$this->getAliasedColName({$this->getEntityMapClassName()}::RANK_COL));
            break;
        case Criteria::DESC:
            return \$this->addDescendingOrderByField(\$this->getAliasedColName({$this->getEntityMapClassName()}::RANK_COL));
            break;
        default:
            throw new \\Propel\\Runtime\\Exception\\PropelException('{$this->getQueryClassName()}::orderBy() only accepts \"asc\" or \"desc\" as argument');
    }
";

        $this->addMethod('orderByRank')
            ->addSimpleDescParameter('order', 'string', 'either Criteria::ASC (default) or Criteria::DESC', PhpConstant::create('Criteria::ASC'))
            ->setDescription(" Order the query based on the rank in the list.\nUsing the default \$order, returns the item with the lowest rank first")
            ->setTypeDescription("The current query, for fluid interface")
            ->setType('$this|' . $this->getQueryClassName())
            ->setBody($body)
        ;
    }
}
