
/**
 * Updates the aggregate columns defined as <?= $aggregationName ?>
 *
 * @param ConnectionInterface $con A connection object
 */
public function update<?= $aggregationName ?>(ConnectionInterface $con)
{
    if ($this->alreadyInSave) {
        // Defer recomputation until parent doSave() finishes persisting all children.
        $this->needUpdate<?= $aggregationName ?> = true;

        return;
    }

    $aggregatedValues = $this->compute<?= $aggregationName ?>($con);
    if (is_array($aggregatedValues)) {
<?php foreach ($columnPhpNames as $index => $columnName): ?>
        $this->set<?= $columnName ?>($aggregatedValues[<?= $index ?>]);
<?php endforeach; ?>
    }
    $this->save($con);
}
