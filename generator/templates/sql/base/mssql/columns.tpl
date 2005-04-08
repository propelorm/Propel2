<?php 
	foreach ($table->getColumns() as $col) {
		$type = $col->getDomain()->getSqlType();
		if ($type == "INT" || $type == "TEXT") {
		    $size = "";
		} else {
			$size = $col->printSize();
		}
		
		$default = $col->getDefaultSetting();        
        $entry = $col->getName() . ' ' . $type . ' ' . $size . ' ' . $default . ' ' . $col->getNotNullString() . ' ' . $col->getAutoIncrementString();

		// collapse spaces
		$entry = preg_replace('/[\s]+/', ' ', $entry);
		
		// ' ,' -> ','
		$entry = preg_replace('/[\s]*,[\s]*/', ',', $entry);
?> 
	<?php echo $entry ?>,
<?php } ?>