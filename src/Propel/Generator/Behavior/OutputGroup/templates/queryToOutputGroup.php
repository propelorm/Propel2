<?php
    /**
     * phpcs:ignoreFile
     *
     * Expected variables:
     * 
     * @var string $collectionClass
     * @var array<\Propel\Generator\Model\Column> $nonUniqueColumns
     * 
     */
?>
 *
 * @method <?= $collectionClass ?> find(?ConnectionInterface $con = null)
 * @method <?= $collectionClass ?> findPks(array $keys, ?ConnectionInterface $con = null)
 * @method <?= $collectionClass ?> findBy(string $column, $value, ?ConnectionInterface $con = null)
 * @method <?= $collectionClass ?> findByArray($conditions, ?ConnectionInterface $con = null)
 * 
<?php foreach ($nonUniqueColumns as $column): ?>
 * @method <?= $collectionClass ?> findBy<?= $column->getPhpName() ?>(<?= $column->getPhpType() ?>|array<<?= $column->getPhpType() ?>> $<?= $column->getName() ?>)
<?php endforeach ?>
 *
