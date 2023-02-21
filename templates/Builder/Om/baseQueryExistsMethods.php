    
    /**
     * Use the <?= $relationDescription ?> for an EXISTS query.
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param string|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return <?= $queryClass ?> The inner query object of the EXISTS statement
     */
    public function use<?= $relationName ?>ExistsQuery($modelAlias = null, $queryClass = null, $typeOfExists = '<?= $existsType ?>')
    {
        /** @var $q <?= $queryClass ?> */
        $q = $this->useExistsQuery('<?= $relationName ?>', $modelAlias, $queryClass, $typeOfExists);
        return $q;
    }

    /**
     * Use the <?= $relationDescription ?> for a NOT EXISTS query.
     *
     * @see use<?= $relationName ?>ExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param string|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return <?= $queryClass ?> The inner query object of the NOT EXISTS statement
     */
    public function use<?= $relationName ?>NotExistsQuery($modelAlias = null, $queryClass = null)
    {
        /** @var $q <?= $queryClass ?> */
        $q = $this->useExistsQuery('<?= $relationName ?>', $modelAlias, $queryClass, '<?= $notExistsType ?>');
        return $q;
    }
