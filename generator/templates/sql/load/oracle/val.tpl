<?php

if (in_array($column->getColumn()->getPropelType(), array('VARCHAR', 'LONGVARCHAR', 'DATE','CHAR'))) { 
        echo "'" . $column->getValue() . "'"; 
    } else { 
        echo $column->getValue();
    } 

?>
