<?php
    /**
     * phpcs:ignoreFile
     *
     * Expected Variables:
     *
     * @var array<array{'column_index'?: array<int>, 'relation'?: array<int>}> $outputGroups
     */
?>
/**
 * Output group definitions.
 *
 * @var array<string, array{'column_index': array<int>, 'relation': array<int>}>
 */
protected static $outputGroups = [
<?php foreach ($outputGroups as $outputGroupName => $values) :?>
    '<?= $outputGroupName ?>' => [
        'column_index' => [<?= implode(', ', $values['column_index'] ?? []) ?>],
        'relation' => [
<?php foreach ($values['relation'] ?? [] as $relationName) : ?>
            '<?= $relationName ?>' => 1,
<?php endforeach; ?>
        ],
    ],
<?php endforeach; ?>
];
