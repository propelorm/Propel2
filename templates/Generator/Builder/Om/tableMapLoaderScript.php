<?= '<?php'?>

$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->initDatabaseMaps(<?= var_export($databaseNameToTableMapNames, true) ?>);
