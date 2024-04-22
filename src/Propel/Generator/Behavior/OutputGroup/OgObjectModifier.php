<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\OutputGroup;

use Propel\Generator\Builder\Om\ObjectBuilder;

class OgObjectModifier
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
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
    public function objectMethods(ObjectBuilder $builder): string
    {
        return $this->behavior->renderLocalTemplate('baseObjectToOutputGroup', [
            'tableMapClassName' => $builder->getTableMapClassName(),
            'objectClassName' => $builder->getUnqualifiedClassName(),
            'temporalColumnIndexesByFormatter' => $this->getTemporalColumnIndexesByFormatter($builder),
            'relationFormatterData' => $this->buildRelationFormatterData($builder),
        ]);
    }

    /**
     * Build a map with date format strings (i.e. 'Y-m-d') as keys and the
     * indexes of associated column indexes as values.
     *
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return array<array<int>>
     */
    protected function getTemporalColumnIndexesByFormatter(ObjectBuilder $builder): array
    {
        $temporalColumnIndexesByFormatter = [];
        foreach ($builder->getTable()->getColumns() as $num => $col) {
            if (!$col->isTemporalType()) {
                continue;
            }
            $formatter = $builder->getTemporalFormatter($col);
            $temporalColumnIndexesByFormatter[$formatter][] = $num;
        }

        return $temporalColumnIndexesByFormatter;
    }

    /**
     * Get relation data as used in output group template.
     *
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return array<array{
     *  'localVariableName': string,
     *  'relationName': string,
     *  'targetKeyLookupStatement': string,
     *  'isCollection': bool,
     *  'relationId': string
     * }>
     */
    protected function buildRelationFormatterData(ObjectBuilder $builder): array
    {
        $result = [];

        $fks = $builder->getTable()->getForeignKeys();
        foreach ($fks as $fk) {
            $lookup = $builder->addToArrayKeyLookUp($fk->getPhpName(), $fk->getForeignTable(), false);
            $result[] = [
                'localVariableName' => $builder->getFKVarName($fk),
                'relationName' => $builder->getFKPhpNameAffix($fk),
                'targetKeyLookupStatement' => $lookup,
                'isCollection' => false,
                'relationId' => $fk->getName(),
            ];
        }

        $refs = $builder->getTable()->getReferrers();
        foreach ($refs as $ref) {
            $isLocal = $ref->isLocalPrimaryKey();
            $localVariableName = ($isLocal) ? $builder->getPKRefFKVarName($ref) : $builder->getRefFKCollVarName($ref);
            $lookup = $builder->addToArrayKeyLookUp($ref->getRefPhpName(), $ref->getTable(), !$isLocal);
            /** @var string $relationName */
            $relationName = $builder->getRefFKPhpNameAffix($ref);
            $result[] = [
                'localVariableName' => $localVariableName,
                'relationName' => $relationName,
                'targetKeyLookupStatement' => $lookup,
                'isCollection' => !$isLocal,
                'relationId' => $ref->getName(),
            ];
        }

        return $result;
    }

    /**
     * @see \Propel\Generator\Model\Behavior::objectFilter()
     *
     * @param string $script
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return void
     */
    public function objectFilter(string &$script, ObjectBuilder $objectBuilder)
    {
        $script = $this->addInterfaceDeclaration($script);
    }

    /**
     * @param string $script
     *
     * @return string
     */
    protected function addInterfaceDeclaration(string $script): string
    {
        $interface = ObjectWithOutputGroupInterface::class;
        $pattern = '/^((abstract )?class \w+\s+(extends \w+\s+)?implements\s+[\w\s,]+\w)/m';

        return preg_replace_callback($pattern, fn (array $match) => "{$match[0]}, \\$interface", $script, 1);
    }
}
