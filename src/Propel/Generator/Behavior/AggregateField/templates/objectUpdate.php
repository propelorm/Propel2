
/**
 * Updates the aggregate column <?=$column->getName()?>
 *
 * @param ConnectionInterface $con A connection object
 */
public function update<?=$column->getName()?>(ConnectionInterface $con)
{
    $this->set<?=$column->getName()?>($this->compute<?=$column->getName()?>($con));
    $this->save($con);
}
