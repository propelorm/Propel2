DROP TABLE <?php echo $table->getName() ?> CASCADE;
<?php if ($table->getIdMethod() == "native") { ?> 
DROP SEQUENCE <?php echo $table->getSequenceName() ?>;
<?php } ?> 
