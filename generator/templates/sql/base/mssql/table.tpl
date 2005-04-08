
/* ---------------------------------------------------------------------- */
/* <?php echo $table->getName()  ?>                                                    */
/* ---------------------------------------------------------------------- */

<?php echo $generator->parse("$basepath/drop.tpl"); ?>
CREATE TABLE <?php echo $table->getName()?>
(
<?php

	$cols = $generator->parse("$basepath/columns.tpl");
	$pk = $generator->parse("$basepath/primarykey.tpl");
	
	$unique = $generator->parse("$basepath/unique.tpl");
	
	if (empty($pk) && empty($unique)) {
		echo preg_replace('/[ ,]+[\s]*$/', '', $cols);
	} else {
		echo $cols;
	}
	
	if (!empty($pk) && empty($unique)) {
		echo preg_replace('/[ ,]+[\s]*$/', '', $pk);
	} else {
		echo $pk;
	}
	
	if (!empty($unique)) {
		echo preg_replace('/[ ,]+[\s]*$/', '', $unique);
	}
?>
);	
<?php
	$index = $generator->parse("$basepath/index.tpl");	
	if (!empty($index)) {
	    echo $index;
	}
?>