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
class ShiftLevelMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope          = $this->getBehavior()->useScope();
        $entityMapClassName = $this->getEntityMapClassName();

        $body = "
\$levelCriteria = new Criteria($entityMapClassName::DATABASE_NAME);
\$levelCriteria->add($entityMapClassName::LEVEL_COL, array('raw' => '{$this->getBehavior()->getFieldForParameter('level_field')->getColumnName()} + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

\$this->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(['min' => \$first])
    ->filterBy{$this->getBehavior()->getFieldForParameter('right_field')->getMethodName()}(['max' => \$last])
    ";

        if ($useScope) {
            $body .= "->inTree(\$scope)
    ";
        }

        $body .= "->update(\$levelCriteria);";

        $method = $this->addMethod('shiftLevel')
            ->setDescription("Adds \$delta to level for nodes having left value >= \$first and right value <= \$last.
'\$delta' can also be negative.")
            ->addSimpleDescParameter('delta',  'int', 'Value to be shifted by, can be negative')
            ->addSimpleDescParameter('first', 'int', 'First node to be shifted')
            ->addSimpleDescParameter('last', 'int', 'Last node to be shifted')
            ->setBody($body);
        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which root node to return');
        }
        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }
}
