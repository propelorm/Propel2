-- -----------------------------------------------------------------------
-- <?php echo $table->getName() ?>
-- -----------------------------------------------------------------------
<?php echo $generator->parse("$basepath/drop.tpl") ?>

CREATE TABLE <?php echo $table->getName() ?>
(
<?php 
		
	$cols = $generator->parse("$basepath/columns.tpl");
	$fk = $generator->parse("$basepath/foreignkey.tpl");
	$unique = $generator->parse("$basepath/unique.tpl");
	$index = $generator->parse("$basepath/index.tpl");	
	
	if (empty($unique)) {		
		echo preg_replace('/[,]+\s*$/', '', $cols);
	} else {
		echo $cols;
        echo preg_replace('/[,]+\s*$/', '', $unique);
	}		
	
?> 
);

<?php
	if (!empty($index)) {
		echo $index;
	}
	
	if (!empty($fk)) {
		echo $fk;
	}
?>
