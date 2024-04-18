<?php
/**
 * phpcs:ignoreFile
 * 
 * Expected Variables:
 *
 * @var class-string $objectCollectionClass
 */
?>
/**
 * Get column indexes for the given output group name.
 *
 * @param string|array<string> $outputGroupName Name of the output group as specified in schema.xml. Can be array of output group names.
 *
 * @return array<string, array{'column_index': array<int>, 'relation': array<int>|null}>
 */
public static function getOutputGroupData($outputGroupName): array
{
    if (is_string($outputGroupName) ){
        return self::$outputGroups[$outputGroupName] ?? [
            'column_index' => self::$fieldKeys[self::TYPE_NUM],
            'relation' => null,
        ];
    }

    $columnIndex = [];
    $relation = [];
    foreach ($outputGroupName as $groupName) {
        if (!isset(self::$outputGroups[$groupName])) {
            continue;
        }
        $groupData = self::$outputGroups[$groupName];
        array_push($columnIndex, ...$groupData['column_index']);
        $relation = array_merge($relation, $groupData['relation']);
    }
    $columnIndex = array_unique($columnIndex, SORT_NUMERIC);
    sort($columnIndex);

    return ($columnIndex || $relation) ? [
        'column_index' => $columnIndex,
        'relation' => $relation,
    ]: [
        'column_index' => self::$fieldKeys[self::TYPE_NUM],
        'relation' => null,
    ];
}

/**
 * Get the Collection ClassName to this table.
 *
 * @return string
 */
public function getCollectionClassName(): string
{
    $parentCollectionClassName = parent::getCollectionClassName();

    return ($parentCollectionClassName === \Propel\Runtime\Collection\ObjectCollection::class)
        ? '<?= $objectCollectionClass ?>'
        : $parentCollectionClassName;
}
