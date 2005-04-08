<?php foreach ($tablefk->getForeignKeys() as $fk) { ?>

BEGIN
ALTER TABLE <?php echo $tablefk->getName() ?> 
    ADD CONSTRAINT <?php echo $fk->getName() ?> FOREIGN KEY (<?php echo $fk->getLocalColumnNames() ?>)
    REFERENCES <?php echo $fk->getForeignTableName() ?> (<?php echo $fk->getForeignColumnNames() ?>)
<?php if ($fk->hasOnUpdate()) { ?>
    ON UPDATE <?php echo $fk->getOnUpdate() ?> 
<?php } ?>
<?php if ($fk->hasOnDelete()) { ?>
    ON DELETE <?php echo $fk->getOnDelete() ?> 
<?php } ?>
END    
;

<?php } ?>

