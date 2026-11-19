
/**
 * Updates the aggregate column <?=$column->getName()?>
 *
 * @param ConnectionInterface $con A connection object
 */
public function update<?=$column->getPhpName()?>(ConnectionInterface $con)
{
    if ($this->alreadyInSave) {
        // Defer recomputation until parent doSave() finishes persisting all children.
        $this->needUpdate<?=$column->getPhpName()?> = true;

        return;
    }

    $this->set<?=$column->getPhpName()?>($this->compute<?=$column->getPhpName()?>($con));
    $this->save($con);
}
