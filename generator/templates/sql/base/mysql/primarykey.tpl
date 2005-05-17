<?php if ($table->hasPrimaryKey()) {
            $values = array();
            foreach ($table->getPrimaryKey() as $column) {
                $values[] = "`" . $column->getName() . "`";
            }
?> 
    PRIMARY KEY(<?php echo implode(',', $values) ?>)<?php } 