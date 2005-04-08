<?php foreach ($table->getForeignKeys() as $fk) { ?>
	-- SQLite does not support foreign keys; this is just for reference
    -- FOREIGN KEY (<?php echo $fk->getLocalColumnNames()?>) REFERENCES <?php echo $fk->getForeignTableName() ?> (<?php echo $fk->getForeignColumnNames() ?>),
<?php } ?>
