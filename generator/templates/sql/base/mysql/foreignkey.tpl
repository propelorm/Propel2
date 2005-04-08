<?php $counter = 0;
      foreach ($table->getForeignKeys() as $fk) {
        $counter++;
        $fnames = array();
        foreach ($fk->getForeignColumns() as $column) {
            $fnames[] = "`" . $column . "`";
        }

        $lnames = array();
        foreach ($fk->getLocalColumns() as $column) {
            $lnames[] = "`" . $column . "`";
        }

        $constraintName = "`" . $table->getName() . "_ibfk_{$counter}`";
?>
    CONSTRAINT <?php echo $constraintName ?> FOREIGN KEY (<?php echo implode(',', $lnames); ?>) REFERENCES <?php echo "`" . $fk->getForeignTableName() . "`" ?> (<?php echo implode(',', $fnames); ?>),
<?php } ?>
