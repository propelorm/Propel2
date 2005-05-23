<?php if ($table->getIdMethod() == "native") { ?>
CREATE SEQUENCE <?php echo $table->getSequenceName()?> INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
<?php } ?>