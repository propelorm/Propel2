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

    if (empty($pk) && empty($fk) && empty($unique) && empty($index)) {
        echo preg_replace('/[,]+[\s]*$/', '', $cols);
    } else {
        echo $cols;
    }

    if (empty($fk) && empty($unique) && empty($index) && !empty($pk)) {
        echo preg_replace('/[,]+[\s]*$/', '', $pk);
    } else {
        echo $pk;
    }

    if (empty($unique) && empty($index) && !empty($fk)) {
        echo preg_replace('/[,]+[\s]*$/', '', $fk);
    } else {
        echo $fk;
    }

    if (empty($index) && !empty($unique)) {
        echo preg_replace('/[,]+[\s]*$/', '', $unique);
    } else {
        echo $unique;
    }

    if (!empty($index)) {
        echo preg_replace('/[,]+[\s]*$/', '', $index);
    }
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
