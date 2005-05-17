<?php 
$firstIteration = true;
foreach ($table->getUnices() as $index ) { 
    if(!$firstIteration): ?>, <?php endif; $firstIteration = false;
?> 
    UNIQUE KEY <?php echo "`" . $index->getName() . "`" ?> (<?php
        $values = array();
        foreach ($index->getColumns() as $column) {
            $values[] = "`" . $column . "`";
        }
        echo implode(',', $values);
    ?>)<?php } 