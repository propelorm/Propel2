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
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = '<?php echo $classPath ?>';

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
<?php foreach ($columns as $col) : ?>

    /**
     * the column name for the <?php echo $col->getName() ?> field
     */
    const <?php echo $col->getConstantName() ?> = '<?php echo $tableName ?>.<?php echo $col->getName() ?>';
<?php endforeach; ?>

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = '<?php echo $stringFormat ?>';
