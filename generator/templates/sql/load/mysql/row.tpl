INSERT INTO <?php echo "`" . $row->getTable()->getName() . "`" ?> (<?php
$comma="";
foreach($row->getColumnValues() as $col) {
    print $comma;
    print "`" . $col->getColumn()->getName() . "`";
    $comma = ',';
}?>) VALUES (<?php
$comma="";
foreach($row->getColumnValues() as $col) {
    print $comma;
    $generator->put("column", $col);
    $generator->display("sql/load/mysql/val.tpl");
    $comma=',';
}?>);
