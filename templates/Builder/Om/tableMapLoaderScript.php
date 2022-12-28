<?= '<?php'?>

$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->initDatabaseMapFromDumps(<?= var_export($databaseNameToTableMapDumps, true) ?>);
