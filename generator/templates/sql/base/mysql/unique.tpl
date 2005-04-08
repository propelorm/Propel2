<?php foreach ($table->getUnices() as $index ) { ?>
    UNIQUE KEY <?php echo "`" . $index->getName() . "`" ?> (<?php
        $values = array();
        foreach ($index->getColumns() as $column) {
            $values[] = "`" . $column . "`";
        }
        echo implode(',', $values);
    ?>),
<?php } ?>