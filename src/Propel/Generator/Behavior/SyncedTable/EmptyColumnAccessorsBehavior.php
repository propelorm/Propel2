<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\SyncedTable;

use Propel\Generator\Behavior\Util\InsertCodeBehavior;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;

/**
 * Adds empty getter and setter for the given columns.
 *
 * Used in SyncedTableBehavior to keep generated model classes of the synced
 * table compatible with the source table if ignore_columns is used.
 */
class EmptyColumnAccessorsBehavior extends InsertCodeBehavior
{
    /**
     * Add this behavior to a table.
     *
     * @param \Propel\Generator\Model\Behavior $insertingBehavior
     * @param \Propel\Generator\Model\Table $table
     * @param array $columnNames
     *
     * @return self
     */
    public static function addEmptyAccessors(Behavior $insertingBehavior, Table $table, array $columnNames): self
    {
        $behavior = new self();
        $codeForHooks = $behavior->buildCodeForHooks($columnNames);
        $behavior->setup($insertingBehavior, $table, $codeForHooks);

        return $behavior;
    }

    /**
     * @param array $columnNames
     *
     * @return array
     */
    protected function buildCodeForHooks(array $columnNames): array
    {
        $accessorNames = $this->buildAccessorNames($columnNames);

        return [
            'objectAttributes' => $this->buildObjectAttributes($accessorNames),
            'objectCall' => $this->buildObjectCall(),
        ];
    }

    /**
     * @param array $columnNames
     *
     * @return array<string>
     */
    protected function buildAccessorNames(array $columnNames): array
    {
        $accessors = [];
        foreach ($columnNames as $columnName) {
            $phpName = (new Column($columnName))->getPhpName();
            array_push($accessors, 'get' . $phpName, 'set' . $phpName);
        }

        return $accessors;
    }

    /**
     * @param array $accessorNames
     *
     * @return string
     */
    public function buildObjectAttributes(array $accessorNames): string
    {
        if (!$accessorNames) {
            return '';
        }
        $nameToArrayKeyFun = fn (string $name) => "    '$name' => 1,";
        $namesAsArrayKeys = implode("\n", array_map($nameToArrayKeyFun, $accessorNames));

        return <<<EOT
/**
 * Non-existing columns with mock getters and setters.
 * 
 * Calls to these accessors will be handled in __call().
 */
protected const MOCKED_ACCESSORS = [
$namesAsArrayKeys
];
EOT;
    }

    /**
     * @return string
     */
    public function buildObjectCall(): string
    {
        return <<<EOT
    if (!empty(static::MOCKED_ACCESSORS[\$name])){
        try {
            return \$this->__parentCall(\$name, \$params);
        } catch(BadMethodCallException \$e){
            return null;
        }
    }

EOT;
    }
}
