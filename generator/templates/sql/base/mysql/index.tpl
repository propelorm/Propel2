<?php $firstIteration = true; foreach ($table->getIndices() as $index ) {
        $vendor = $index->getVendorSpecificInfo();
        if(!$firstIteration): ?>,<?php endif; $firstIteration = false; ?> 
    <?php echo ($vendor && $vendor['Index_type'] == 'FULLTEXT') ? 'FULLTEXT  ' : '' ?>KEY <?php echo "`" . $index->getName() . "`" ?> (<?php
        $values = array();
        foreach ($index->getColumns() as $column) {
            $values[] = "`" . $column . "`";
        }
        echo implode(',', $values);
    ?>)<?php } 