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

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class FixLevelsMethod extends BuildComponent
{
    public function process()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
\$objects = \$this->createQuery()
    ";

        if ($useScope) {
            $body .= "->inTree(\$scope)
    ";
        }

        $body .= "->orderBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}()
    ->find(\$con);

\$level = null;

foreach (\$objects as \$obj) {

    // compute level
    // Algorithm shamelessly stolen from sfPropelActAsNestedSetBehaviorPlugin
    // Probably authored by Tristan Rivoallan
    if (\$level === null) {
        \$level = 0;
        \$i = 0;
        \$prev = array(\$obj->getRightValue());
    } else {
        while (\$obj->getRightValue() > \$prev[\$i]) {
            \$i--;
        }
        \$level = ++\$i;
        \$prev[\$i] = \$obj->getRightValue();
    }

    // update level in node if necessary
    if (\$obj->getLevel() !== \$level) {
        \$obj->setLevel(\$level);
        \$this->persist(\$obj);
    }
}

//Commit updates
\$this->getConfiguration()->getSession()->commit();
";
        $method = $this->addMethod('fixLevels')
            ->setDescription('Update the tree to allow insertion of a leaf at the specified position')
            ->setBody($body)
        ;
        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope field value');
        }
        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }
}
