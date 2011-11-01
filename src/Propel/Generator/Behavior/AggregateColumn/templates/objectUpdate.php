
/**
 * Updates the aggregate column <?php echo $column->getName() ?>
 *
 * @param ConnectionInterface $con A connection object
 */
public function update<?php echo $column->getPhpName() ?>(ConnectionInterface $con)
{
    $this->set<?php echo $column->getPhpName() ?>($this->compute<?php echo $column->getPhpName() ?>($con));
    $this->save($con);
}
