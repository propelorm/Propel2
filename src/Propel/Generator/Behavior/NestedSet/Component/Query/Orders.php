<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Query;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Orders extends BuildComponent
{
    Use NamingTrait;

    public function process()
    {
        $this->addOrderByBranch();
        $this->addOrderByLevel();
    }

    protected function addOrderByBranch()
    {
        $entityMapClassName = $this->getEntityMapClassName();
        $body = "
if (\$reverse) {
    return \$this
        ->addDescendingOrderByField({$entityMapClassName}::LEFT_COL);
} else {
    return \$this
        ->addAscendingOrderByField({$entityMapClassName}::LEFT_COL);
}
";
        $this->addMethod('orderByBranch')
            ->setDescription("Order the result by branch, i.e. natural tree order")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter('reverse', 'bool', 'If true, reverses the order', false)
            ->setBody($body)
        ;
    }

    protected function addOrderByLevel()
    {
        $entityMapClassName = $this->getEntityMapClassName();
        $body = "
if (\$reverse) {
    return \$this
        ->addDescendingOrderByField({$entityMapClassName}::LEVEL_COL)
        ->addDescendingOrderByField({$entityMapClassName}::LEFT_COL);
} else {
    return \$this
        ->addAscendingOrderByField({$entityMapClassName}::LEVEL_COL)
        ->addAscendingOrderByField({$entityMapClassName}::LEFT_COL);
}
";
        $this->addMethod('orderByLevel')
            ->setDescription("Order the result by level, the closer to the root first")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter('reverse', 'bool', 'If true, reverses the order', false)
            ->setBody($body)
        ;
    }
}
