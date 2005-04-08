<?php
if (in_array($column->getColumn()->getPropelType(), array('VARCHAR', 'LONGVARCHAR', 'DATE', 'DATETIME','CHAR'))) { 
    print "'" . mysql_escape_string($column->getValue()) . "'";
} else {
    print $column->getValue();
}
?>