
/**
 * Updates the aggregate field <?=$field->getName()?>
 *
 * @param ConnectionInterface $con A connection object
 */
public function update<?=$field->getName()?>(ConnectionInterface $con)
{
    $this->set<?=$field->getName()?>($this->compute<?=$field->getName()?>($con));
    $this->save($con);
}
