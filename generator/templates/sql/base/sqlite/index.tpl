<?php foreach ($table->getIndices() as $index ) { ?> 
    CREATE INDEX <?php echo $index->getName()?> ON <?php echo $table->getName() ?> (<?php echo $index->getColumnList() ?>);
<?php } ?>