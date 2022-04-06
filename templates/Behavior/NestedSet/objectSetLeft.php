
/**
 * Proxy setter method for the left value of the nested set model.
 * It provides a generic way to set the value, whatever the actual column name is.
 *
 * @param int $v The nested set left value
 * @return $this|<?= $objectClassName ?> The current object (for fluent API support)
 */
public function setLeftValue($v)
{
    return $this->set<?= $leftColumn ?>($v);
}
