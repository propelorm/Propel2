# -----------------------------------------------------------------------
# <?php echo $table->getName() ?> 
# -----------------------------------------------------------------------
<?php echo $generator->parse("$basepath/drop.tpl") ?>

CREATE TABLE <?php echo "`" . $table->getName() . "`" ?>
(
<?php
    $cols = $generator->parse("$basepath/columns.tpl");
    $pk = $generator->parse("$basepath/primarykey.tpl");
    $fk = $generator->parse("$basepath/foreignkey.tpl");
    $unique = $generator->parse("$basepath/unique.tpl");
    $index = $generator->parse("$basepath/index.tpl");

		$output = array();
		if(!empty($cols)) {
		  $output[] = $cols;
		}
		if(!empty($pk)) {
		  $output[] = $pk;
		}
		if(!empty($unique)) {
		  $output[] = $unique;
		}
		if(!empty($index)) {
		  $output[] = $index;
		}
		if(!empty($fk)) {
		  $output[] = $fk;
		}

		echo implode(", ", $output);

?>
)<?php if (!isset($mysqlTableType)) {
            $vendorSpecific = $table->getVendorSpecificInfo();
            if(isset($vendorSpecific['Type']))
                $mysqlTableType = $vendorSpecific['Type'];
            else
                $mysqlTableType = 'MyISAM';
       }
?>

Type=<?php echo $mysqlTableType ?>
<?php if($table->getDescription()) { ?> COMMENT='<?php echo $platform->escapeText($table->getDescription()) ?>'<?php } ?>;
