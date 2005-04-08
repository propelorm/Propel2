<?php 

	foreach ($table->getColumns() as $col) {
		$entry = $col->getSqlString();

		// collapse spaces
		$entry = preg_replace('/[\s]+/', ' ', $entry);
		
		// ' ,' -> ','
		$entry = preg_replace('/[\s]*,[\s]*/', ',', $entry);
?> 
	<?php echo $entry ?>,
<?php } ?>