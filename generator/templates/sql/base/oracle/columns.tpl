<?php
        foreach ($table->getColumns() as $col) {
                /*
                $type = $col->getSqlType();
                $size = $col->printSize();
                $default = $col->getDefaultSetting();

                $entry = $col->getName() . " $type $size $default ".$col->getNotNullString();
                */
                $entry = $col->getSqlString();
                
                // collapse spaces
                $entry = preg_replace('/[\s]+/', ' ', $entry);


                // ' ,' -> ','
                $entry = preg_replace('/[\s]*,[\s]*/', ',', $entry);
?> 
	<?php echo $entry ?>,<?php	} ?>