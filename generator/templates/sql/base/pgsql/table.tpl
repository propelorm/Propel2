
-----------------------------------------------------------------------------
-- <?php echo $table->getName() ?>
-----------------------------------------------------------------------------
<?php
echo $generator->parse("$basepath/drop.tpl");
$sequence = $generator->parse("$basepath/sequence.tpl");
if (!empty($sequence)) {
    echo $sequence;
}
?>

CREATE TABLE <?php echo $table->getName() ?> 
(
	<?php
	$cols = $generator->parse("$basepath/columns.tpl");
	$pk = trim($generator->parse("$basepath/primarykey.tpl"));
	$unique = $generator->parse("$basepath/unique.tpl");
	$index = trim($generator->parse("$basepath/index.tpl"));	
	
	if ( empty($pk) && empty($unique)) {
	    echo preg_replace('/[ ,]+[\s]*$/', '', $cols);
	} else {
		echo $cols;
	}
	
	if (empty($unique) && !empty($pk)) {
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
	if(!empty($index)) { 
		echo preg_replace('/[ ,]+[\s]*$/', '', $index); 
	} 
?>

COMMENT ON TABLE <?php echo $table->getName() ?> IS '<?php echo $platform->escapeText($table->getDescription()) ?>';

<?php
  foreach ($table->getColumns() as $col) {
    if( $col->getDescription() != '' ) {
?>
COMMENT ON COLUMN <?php echo $table->getName() ?>.<?php echo $col->getName() ?> IS '<?php echo $platform->escapeText($col->getDescription()) ?>';
<?php
    }
  }
?>

