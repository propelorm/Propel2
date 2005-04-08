
----------------------------------------------------------------------
-- <?php echo $tablefk->getName() ?> 
----------------------------------------------------------------------

<?php
// for pgsql table = tablefk, because
// foreignkey.tpl can be loaded by table.tpl also
//$generator->put("table", $tablefk); 
$fk = $generator->parse("$basepath/foreignkey.tpl");
if ($fk != "") {
    echo $fk;
}
?>
