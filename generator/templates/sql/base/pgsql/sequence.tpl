<?php if ($table->getIdMethod() == "native") { ?> 
CREATE SEQUENCE <?php echo $table->getSequenceName() ?>;
<?php } ?> 
