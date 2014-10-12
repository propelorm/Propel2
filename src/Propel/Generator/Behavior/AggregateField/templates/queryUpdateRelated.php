
protected function updateRelated<?=$relationName.$aggregateName?>s($con)
{
    foreach ($this-><?=$variableName?>s as $<?=$variableName?>) {
        $<?=$variableName?>-><?= $updateMethodName ?>($con);
    }
    $this-><?=$variableName?>s = array();
}
