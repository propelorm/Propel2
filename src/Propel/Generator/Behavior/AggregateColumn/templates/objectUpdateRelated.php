
/**
 * Update the aggregate column in the related <?=$relationName?> object
 *
 * @param ConnectionInterface $con A connection object
 */
protected function updateRelated<?=$relationName?>(ConnectionInterface $con)
{
    if ($<?=$variableName?> = $this->get<?=$relationName?>()) {
        $<?=$variableName?>-><?=$updateMethodName?>($con);
    }
    if ($this->old<?=$relationName?>) {
        $this->old<?=$relationName?>-><?=$updateMethodName?>($con);
        $this->old<?=$relationName?> = null;
    }
}
