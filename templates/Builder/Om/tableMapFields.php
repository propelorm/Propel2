
    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = [
        self::TYPE_PHPNAME       => [<?= $fieldNamesPhpName ?>],
        self::TYPE_CAMELNAME     => [<?= $fieldNamesCamelCaseName ?>],
        self::TYPE_COLNAME       => [<?= $fieldNamesColname ?>],
        self::TYPE_FIELDNAME     => [<?= $fieldNamesFieldName ?>],
        self::TYPE_NUM           => [<?= $fieldNamesNum ?>]
    ];

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = [
        self::TYPE_PHPNAME       => [<?= $fieldKeysPhpName ?>],
        self::TYPE_CAMELNAME     => [<?= $fieldKeysCamelCaseName ?>],
        self::TYPE_COLNAME       => [<?= $fieldKeysColname ?>],
        self::TYPE_FIELDNAME     => [<?= $fieldKeysFieldName ?>],
        self::TYPE_NUM           => [<?= $fieldKeysNum ?>]
    ];
