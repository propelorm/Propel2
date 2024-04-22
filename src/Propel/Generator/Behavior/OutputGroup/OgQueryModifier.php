<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\OutputGroup;

use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Column;

class OgQueryModifier
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
     * @param string $script
     * @param \Propel\Generator\Builder\Om\QueryBuilder $queryBuilder
     *
     * @return void
     */
    public function queryFilter(string &$script, QueryBuilder $queryBuilder)
    {
        $script = $this->addMethodSignaturesToDocBlock($script);
    }

    /**
     * @param string $script
     *
     * @return string
     */
    protected function addMethodSignaturesToDocBlock(string $script): string
    {
        $table = $this->behavior->getTable();
        $nonUniqueColumns = array_filter(
            $table->getColumns(),
            fn (Column $column) => !$table->isUnique([$column]),
        );

        $methodDeclarations = $this->behavior->renderLocalTemplate('queryToOutputGroup', [
            'collectionClass' => $this->behavior->getObjectCollectionClass(),
            'nonUniqueColumns' => $nonUniqueColumns,

        ]);

        $pattern = "/(\h\*\/\sabstract class)/"; // end of doc block "*/\nabstract class"

        return preg_replace_callback($pattern, fn (array $match) => $methodDeclarations . $match[0], $script, 1);
    }
}
