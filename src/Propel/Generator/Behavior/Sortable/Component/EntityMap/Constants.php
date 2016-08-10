<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Jérémie Augustin
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Constants extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $entityName = $this->getEntity()->getFullClassName();

        $col = [];

        if ($behavior->useScope()) {

            if ($behavior->hasMultipleScopes()) {
                foreach ($behavior->getScopes() as $scope) {
                    $col[] = "$entityName.".strtoupper($scope);
                }
                $col = json_encode($col);
                $col = "'$col'";
            } else {
                $colName = $behavior->getFieldForParameter('scope_field')->getName();
                $col = "$entityName.$colName";
            }
        }

        $definition = $this->getDefinition();
        $definition->setConstant(PhpConstant::create(
                'RANK_COL', $entityName . '.' . $behavior->getFieldForParameter('rank_field')->getName())
                ->setDescription('Rank field'));

        if ($behavior->useScope()) {
            if ($behavior->hasMultipleScopes()) {
                $definition->setConstant(PhpConstant::create(
                    'MULTI_SCOPE_COL', true)
                    ->setType('bool')
                    ->setDescription('If defined, the `SCOPE_COL` contains a json_encoded array with all fields'));
            }
            $definition->setConstant(PhpConstant::create('SCOPE_COL', $col)
                ->setDescription('Scope field for the set'));
        }
    }
}
