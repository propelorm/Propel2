
/**
 * Computes the value of the aggregate columns defined as <?=$aggregationName?> ?>
 *
 * @param ConnectionInterface $con A connection object
 *
 * @return array The result row from the aggregate query
 */
public function compute<?=$aggregationName?>(ConnectionInterface $con)
{
    $stmt = $con->prepare(<?= var_export($sql, true) ?>);
<?php foreach ($bindings as $key => $binding):?>
    $stmt->bindValue(':p<?=$key?>', $this->get<?=$binding?>());
<?php endforeach;?>
    $stmt->execute();

    return $stmt->fetch(\PDO::FETCH_NUM);
}
