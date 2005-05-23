<?php
	$tableName = $table->getName();
	$length = strlen($tableName);
	if ($length > 27) {
		$length = 27;
	}
?>
<?php if ( is_array($table->getPrimaryKey()) && count($table->getPrimaryKey()) ) { ?>
ALTER TABLE <?php echo $table->getName() ?>

    ADD CONSTRAINT <?php echo substr($tableName,0,$length)?>_PK
PRIMARY KEY (<?php
	$delim = "";
	foreach ($table->getPrimaryKey() as $col) {
		echo $delim . $col->getName();
		$delim = ",";
	}
?>);
<?php } ?>