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
class ShiftLRValuesMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope          = $this->getBehavior()->useScope();
        $entityMapClassName = $this->getEntityMapClassName();

        $body = "
\$leftCriteria = new Criteria($entityMapClassName::DATABASE_NAME);
\$leftCriteria->add($entityMapClassName::LEFT_COL, array('raw' => '{$this->getBehavior()->getFieldForParameter('left_field')->getColumnName()} + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

//Shift left field value
\$this->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(['min' => \$first, 'max' => \$last])
    ";

        if ($useScope) {
            $body .= "->inTree(\$scope)
    ";
        }

        $body .= "->update(\$leftCriteria);

\$rightCriteria = new Criteria($entityMapClassName::DATABASE_NAME);
\$rightCriteria->add($entityMapClassName::RIGHT_COL, array('raw' => '{$this->getBehavior()->getFieldForParameter('right_field')->getColumnName()} + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

// Shift right field values
\$this->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('right_field')->getMethodName()}(['min' => \$first, 'max' => \$last])
    ";

        if ($useScope) {
            $body .= "->inTree(\$scope)
    ";
        }

        $body .= "->update(\$rightCriteria);";

        $method = $this->addMethod('shiftRLValues')
            ->setDescription("Adds \$delta to all L and R values that are >= \$first and <= \$last.
   '\$delta' can also be negative.")
            ->addSimpleDescParameter('delta',  'int', 'Value to be shifted by, can be negative')
            ->addSimpleDescParameter('first', 'int', 'First node to be shifted')
            ->addSimpleDescParameter('last', 'int', 'Last node to be shifted (optional)', null)
            ->setBody($body)
        ;
        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which root node to return', null);
        }
    }
}
