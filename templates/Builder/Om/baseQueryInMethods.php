
    /**
     * Use the <?= $relationDescription ?> for an IN query.
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param string|null $queryClass Allows to use a custom query class for the IN query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return <?= $queryClass ?> The inner query object of the IN statement
     */
    public function useIn<?= $relationName ?>Query($modelAlias = null, $queryClass = null, $typeOfIn = '<?= $inType ?>')
    {
        /** @var $q <?= $queryClass ?> */
        $q = $this->useInQuery('<?= $relationName ?>', $modelAlias, $queryClass, $typeOfIn);
        return $q;
    }

    /**
     * Use the <?= $relationDescription ?> for a NOT IN query.
     *
     * @see use<?= $relationName ?>InQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param string|null $queryClass Allows to use a custom query class for the NOT IN query, like ExtendedBookQuery::class
     *
     * @return <?= $queryClass ?> The inner query object of the NOT IN statement
     */
    public function useNotIn<?= $relationName ?>Query($modelAlias = null, $queryClass = null)
    {
        /** @var $q <?= $queryClass ?> */
        $q = $this->useInQuery('<?= $relationName ?>', $modelAlias, $queryClass, '<?= $notInType ?>');
        return $q;
    }
