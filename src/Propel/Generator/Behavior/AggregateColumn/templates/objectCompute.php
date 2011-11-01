
/**
 * Computes the value of the aggregate column <?php echo $column->getName() ?>
 *
 * @param ConnectionInterface $con A connection object
 *
 * @return mixed The scalar result from the aggregate query
 */
public function compute<?php echo $column->getPhpName() ?>(ConnectionInterface $con)
{
    $stmt = $con->prepare('<?php echo $sql ?>');
<?php foreach ($bindings as $key => $binding): ?>
  $stmt->bindValue(':p<?php echo $key ?>', $this->get<?php echo $binding ?>());
<?php endforeach; ?>
    $stmt->execute();

    return $stmt->fetchColumn();
}
