<?php foreach ($table->getUnices() as $unique ) { ?> 
    CONSTRAINT <?php echo $unique->getName() ?> UNIQUE (<?php echo $unique->getColumnList() ?>),
<?php } ?>