<?php

if (in_array($column->getColumn()->getPropelType(), array('VARCHAR', 'LONGVARCHAR', 'DATE','CHAR', 'TIMESTAMP'))) { 
        echo "'" . pg_escape_string($column->getValue()) . "'"; 
    } elseif ($column->getColumn()->getPropelType() == 'BOOLEAN') { 
        echo ($column->getValue() == 1 || $column->getValue() == 't' ? "'t'" : "'f'");
    } else {
        echo $column->getValue();
    } 

?>
