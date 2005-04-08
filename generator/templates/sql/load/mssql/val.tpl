<?php

if (in_array($column->getColumn()->getPropelType(), array('VARCHAR', 'LONGVARCHAR', 'DATE','CHAR'))) { 
        echo "'" . str_replace("'", "''", $column->getValue()). "'"; 
    } else { 
        echo $column->getValue();
    } 

?>
