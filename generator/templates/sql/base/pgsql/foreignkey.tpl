<?php

//
// The following will only work for non-circular references
// if you have a dependancy chain, you will need to use
// ADD CONSTRAINT syntax (with INITIALLY DEFERRED)
// which is sticky and version dependant
//
foreach ($tablefk->getForeignKeys() as $fk) { ?> 
ALTER TABLE <?php echo $tablefk->getName() ?> 
    ADD CONSTRAINT <?php echo $fk->getName() ?> FOREIGN KEY (<?php echo $fk->getLocalColumnNames() ?>)
    REFERENCES <?php echo $fk->getForeignTableName() ?> (<?php echo $fk->getForeignColumnNames() ?>)
<?php if ($fk->hasOnUpdate()) { ?> 
    ON UPDATE <?php echo $fk->getOnUpdate() ?> 
<?php } ?>
<?php if ($fk->hasOnDelete()) { ?> 
    ON DELETE <?php echo $fk->getOnDelete() ?> 
<?php } ?> 
;
<?php } ?> 
