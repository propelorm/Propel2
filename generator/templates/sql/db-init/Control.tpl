<?php    
    // build db names array    
    $databaseNames = array();
    foreach($dataModels as $dm) {
        foreach($dm->getDatabases() as $db) {
            $databaseNames[] = $db->getName();
        }
    }
    $databaseNames = array_unique($databaseNames);
    
    $generator->put("databaseNames", $databaseNames);
	$generator->display("sql/db-init/$targetDatabase/createdb.tpl");
?>