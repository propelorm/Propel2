    /**
    * The (dot-path) name of this class
    */
    const CLASS_NAME = '<?php echo $className ?>';

    /**
    * The default database name for this class
    */
    const DATABASE_NAME = '<?php echo $dbName ?>';

    /**
    * The table name for this class
    */
    const TABLE_NAME = '<?php echo $tableName ?>';

    /**
    * The related Propel class for this table
    */
    const OM_CLASS = '<?php echo $tablePhpName ?>';

    /**
    * A class that can be returned by this peer
    */
    const CLASS_DEFAULT = '<?php echo $classPath ?>';

    /**
    * The related TableMap class for this table
    */
    const PEER_CLASS = '<?php echo $peerClassName ?>';

    /**
    * The total number of columns
    */
    const NUM_COLUMNS = <?php echo $nbColumns ?>;

    /**
    * The number of lazy-loaded columns
    */
    const NUM_LAZY_LOAD_COLUMNS = <?php echo $nbLazyLoadColumns ?>;

    /**
    * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
    */
    const NUM_HYDRATE_COLUMNS = <?php echo $nbHydrateColumns ?>;
