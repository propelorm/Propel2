
    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array(<?= $fieldNamesPhpName ?>),
        self::TYPE_STUDLYPHPNAME => array(<?= $fieldNamesStudlyPhpName ?>),
        self::TYPE_COLNAME       => array(<?= $fieldNamesColname ?>),
        self::TYPE_RAW_COLNAME   => array(<?= $fieldNamesRawColname ?>),
        self::TYPE_FIELDNAME     => array(<?= $fieldNamesFieldName ?>),
        self::TYPE_NUM           => array(<?= $fieldNamesNum ?>)
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array(<?= $fieldKeysPhpName ?>),
        self::TYPE_STUDLYPHPNAME => array(<?= $fieldKeysStudlyPhpName ?>),
        self::TYPE_COLNAME       => array(<?= $fieldKeysColname ?>),
        self::TYPE_RAW_COLNAME   => array(<?= $fieldKeysRawColname ?>),
        self::TYPE_FIELDNAME     => array(<?= $fieldKeysFieldName ?>),
        self::TYPE_NUM           => array(<?= $fieldKeysNum ?>)
    );
