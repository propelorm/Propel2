    /**
     * Method to invalidate the instance pool of all tables related to <?= $tableName ?>
     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
    <?php foreach ($relatedClassNames as $relatedClassName) : ?>
    <?= $relatedClassName ?>::clearInstancePool();
    <?php endforeach; ?>
}
