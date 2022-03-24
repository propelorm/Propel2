
/**
 * Finds the related <?=$foreignTable->getPhpName()?> objects and keep them for later
 *
 * @param ConnectionInterface $con A connection object
 */
protected function findRelated<?=$relationName.$aggregateName?>s($con)
{
    $criteria = clone $this;
    if ($this->useAliasInSQL) {
        $alias = $this->getModelAlias();
        $criteria->removeAlias($alias);
    } else {
        $alias = '';
    }
    $this-><?=$variableName?>s = <?=$foreignQueryName?>::create()
        ->join<?=$refRelationName?>($alias)
        ->mergeWith($criteria)
        ->find($con);
}
