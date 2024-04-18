<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\OutputGroup;

use Propel\Generator\Builder\Om\TableMapBuilder;
use Propel\Generator\Model\Table;

class OgTableMapModifier
{
    /**
     * @var \Propel\Generator\Behavior\OutputGroup\OutputGroupBehavior
     */
    protected $behavior;

    /**
     * @param \Propel\Generator\Behavior\OutputGroup\OutputGroupBehavior $behavior
     */
    public function __construct(OutputGroupBehavior $behavior)
    {
        $this->behavior = $behavior;
    }

    /**
     * @param \Propel\Generator\Builder\Om\TableMapBuilder $builder
     *
     * @return string
     */
    public function staticAttributes(TableMapBuilder $builder): string
    {
        return $this->behavior->renderLocalTemplate('tableMapOutputGroupsAttributes', [
            'outputGroups' => $this->buildOutputGroups($builder),
        ]);
    }

    /**
     * @param \Propel\Generator\Builder\Om\TableMapBuilder $builder
     *
     * @return string
     */
    public function staticMethods(TableMapBuilder $builder): string
    {
        return $this->behavior->renderLocalTemplate('tableMapOutputGroupsMethods', [
            'objectCollectionClass' => $this->behavior->getObjectCollectionClass(),
        ]);
    }

    /**
     * @param \Propel\Generator\Builder\Om\TableMapBuilder $builder
     *
     * @return array<array{'column_index'?: array<int>, 'relation'?: array<int>}> $outputGroups
     */
    protected function buildOutputGroups(TableMapBuilder $builder): array
    {
        $outputGroups = [];
        $this->collectColumnIndexesByOutputGroup($builder->getTable(), $outputGroups);
        $this->collectForeignKeysByOutputGroup($builder, $outputGroups);
        ksort($outputGroups);

        return $outputGroups;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     * @param array<array{'column_index'?: array<int>, 'relation'?: array<int>}> $outputGroups
     *
     * @return void
     */
    protected function collectColumnIndexesByOutputGroup(Table $table, array &$outputGroups)
    {
        foreach ($table->getColumns() as $columnIndex => $column) {
            $groupNames = $this->behavior->getColumnOutputGroupNames($column);
            foreach ($groupNames as $groupName) {
                $outputGroups[$groupName]['column_index'][] = $columnIndex;
            }
        }
    }

    /**
     * @param \Propel\Generator\Builder\Om\TableMapBuilder $builder
     * @param array<array{'column_index'?: array<int>, 'relation'?: array<int>}> $outputGroups
     *
     * @return void
     */
    protected function collectForeignKeysByOutputGroup(TableMapBuilder $builder, array &$outputGroups)
    {
        $table = $builder->getTable();

        foreach ($table->getForeignKeys() as $fk) {
            $groupNames = $this->behavior->getForeignKeyLocalOutputGroupNames($fk);
            $fkName = $builder->getFKPhpNameAffix($fk);
            foreach ($groupNames as $groupName) {
                $outputGroups[$groupName]['relation'][] = $fkName;
            }
        }
        foreach ($table->getReferrers() as $ref) {
            $groupNames = $this->behavior->getForeignKeyRefOutputGroupNames($ref);
            $refName = $builder->getRefFKPhpNameAffix($ref);
            foreach ($groupNames as $groupName) {
                $outputGroups[$groupName]['relation'][] = $refName;
            }
        }
    }
}
