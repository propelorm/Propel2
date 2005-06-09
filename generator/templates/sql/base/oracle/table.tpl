/* -----------------------------------------------------------------------
   <?php echo $table->getName() ?> 
   ----------------------------------------------------------------------- */
<?php echo $generator->parse("$basepath/drop.tpl") ?>
CREATE TABLE <?php echo $table->getName() ?> 
(<?php

	$cols    = $generator->parse("$basepath/columns.tpl");
	$unique  = $generator->parse("$basepath/unique.tpl");

        if (empty($unique)) {
                echo preg_replace('/[ ,]+[\s]*$/', '', $cols);
        } else {
                echo $cols;
        }

        if (!empty($unique)) {
                echo "\n" . preg_replace('/[ ,]+[\s]*$/', '', $unique);
        }

?> 
);
<?php echo $generator->parse("$basepath/primarykey.tpl")?>
<?php echo $generator->parse("$basepath/index.tpl")?>
<?php echo $generator->parse("$basepath/sequence.tpl")?>

