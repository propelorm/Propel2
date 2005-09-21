<?php foreach ($tablefk->getForeignKeys() as $fk) { ?>
ALTER TABLE <?php echo $tablefk->getName()?>
    ADD CONSTRAINT <?php echo $fk->getName() ?> FOREIGN KEY (<?php echo $fk->getLocalColumnNames()?>)
    REFERENCES <?php echo $fk->getForeignTableName()?> (<?php echo $fk->getForeignColumnNames()?>)<?php
 if ($fk->hasOnDelete()) {?>
    ON DELETE <?php echo $fk->getOnDelete();
}?>;

<?php } ?>
<?php
  /*
         -- TODO
        ON UPDATE $fk.OnUpdate
  */
?>

