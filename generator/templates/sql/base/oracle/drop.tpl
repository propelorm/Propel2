DROP TABLE <?php echo $table->getName() ?> CASCADE CONSTRAINTS;
<?php if ($table->getIdMethod() == "native") { ?>
DROP SEQUENCE <?php echo $table->getSequenceName() ?>;
<?php } ?>