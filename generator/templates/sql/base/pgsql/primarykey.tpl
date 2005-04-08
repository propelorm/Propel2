<?php if ($table->hasPrimaryKey()) { ?> 
    PRIMARY KEY (<?php echo $table->printPrimaryKey() ?>), 
<?php } ?> 
