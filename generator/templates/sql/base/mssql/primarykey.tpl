<?php if ($table->hasPrimaryKey()) { ?>
    CONSTRAINT <?php echo $table->getName() ?>_PK PRIMARY KEY(<?php echo $table->printPrimaryKey() ?>),
<?php } ?>