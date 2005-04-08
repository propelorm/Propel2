<?php foreach ($table->getUnices() as $unique) { ?>
    UNIQUE (<?php echo $unique->getColumnList() ?>),
<?php } ?>
