<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class SetNegativeScopeMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$this->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(['max' => 0])
    ->update([{$this->getEntityMapClassName()}::SCOPE_COL => \$scope]);
";
        $this->addMethod('setNegativeScope')
            ->setDescription('Updates all scope values for items that has negative left (<=0) values.')
            ->addSimpleParameter('scope', 'mixed')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }
}
