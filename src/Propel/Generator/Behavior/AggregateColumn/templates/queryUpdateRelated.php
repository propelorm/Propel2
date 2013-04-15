
protected function updateRelated<?=$relationName?>s($con)
{
    foreach ($this-><?=$variableName?>s as $<?=$variableName?>) {
        $<?=$variableName?>-><?= $updateMethodName ?>($con);
    }
    $this-><?=$variableName?>s = array();
}
