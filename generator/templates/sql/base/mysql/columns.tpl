<?php
		$firstIteration = true;
    foreach ($table->getColumns() as $col) {
        if(!$firstIteration): ?>, 
<?php endif; $firstIteration = false;
        //$entry = $col->getSqlString();
        //using the following code instead of the above line
        //for escaping column names:

        $entry = "";
        $entry .= "`" . $col->getName() . "` ";
        $entry .= $col->getDomain()->getSqlType();
        if ($col->getPlatform()->hasSize($col->getDomain()->getSqlType())) {
            $entry .= $col->getDomain()->printSize();
        }
        $entry .= " ";
        $entry .= $col->getDefaultSetting() . " ";
        $entry .= $col->getNotNullString() . " ";
        $entry .= $col->getAutoIncrementString();

        // collapse spaces
        $entry = preg_replace('/[\s]+/', ' ', $entry);

        // ' ,' -> ','
        $entry = preg_replace('/[\s]*,[\s]*/', ',', $entry);
?>
    <?php echo $entry ?><?php if ($col->getDescription()) { ?> COMMENT '<?php echo $platform->escapeText($col->getDescription()) ?>'<?php } ?>
<?php } 