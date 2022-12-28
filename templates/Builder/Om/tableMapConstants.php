    /**
     * The (dot-path) name of this class
     */
    public const CLASS_NAME = '<?php echo $className ?>';

    /**
     * The default database name for this class
     */
    public const DATABASE_NAME = '<?php echo $dbName ?>';

    /**
     * The table name for this class
     */
    public const TABLE_NAME = '<?php echo $tableName ?>';

    /**
     * The PHP name of this class (PascalCase)
     */
    public const TABLE_PHP_NAME = '<?php echo $tablePhpName ?>';

    /**
     * The related Propel class for this table
     */
    public const OM_CLASS = '<?php echo $omClassName ?>';

    /**
     * A class that can be returned by this tableMap
     */
    public const CLASS_DEFAULT = '<?php echo $classPath ?>';

    /**
     * The total number of columns
     */
    public const NUM_COLUMNS = <?php echo $nbColumns ?>;

    /**
     * The number of lazy-loaded columns
     */
    public const NUM_LAZY_LOAD_COLUMNS = <?php echo $nbLazyLoadColumns ?>;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    public const NUM_HYDRATE_COLUMNS = <?php echo $nbHydrateColumns ?>;
<?php foreach ($columns as $col) : ?>

    /**
     * the column name for the <?php echo $col->getName() ?> field
     */
    public const <?php echo $col->getConstantName() ?> = '<?php echo $tableName ?>.<?php echo $col->getName() ?>';
<?php endforeach; ?>

    /**
     * The default string format for model objects of the related table
     */
    public const DEFAULT_STRING_FORMAT = '<?php echo $stringFormat ?>';
