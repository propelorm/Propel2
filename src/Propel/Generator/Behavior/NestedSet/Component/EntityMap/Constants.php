<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Constants extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entityName = $this->getEntity()->getFullClassName();

        $this->getDefinition()
            ->setConstant(PhpConstant::create(
                'LEFT_COL', $entityName . '.' . $this->getBehavior()->getFieldForParameter('left_field')->getName())
                ->setDescription('Left field for the set'))
            ->setConstant(PhpConstant::create(
                'RIGHT_COL', $entityName . '.' . $this->getBehavior()->getFieldForParameter('right_field')->getName())
                ->setDescription('Right field for the set'))
            ->setConstant(PhpConstant::create(
                'LEVEL_COL', $entityName . '.' . $this->getBehavior()->getFieldForParameter('level_field')->getName())
                ->setDescription('Level field for the set'))
        ;

        if ($this->getBehavior()->useScope()) {
            $this->getDefinition()
                ->setConstant(PhpConstant::create(
                    'SCOPE_COL', $entityName . '.' . $this->getBehavior()->getFieldForParameter('scope_field')->getName())
                    ->setDescription('Scope field for the set'))
            ;
        }
    }
}
