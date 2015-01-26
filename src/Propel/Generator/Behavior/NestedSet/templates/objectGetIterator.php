
/**
 * Returns a pre-order iterator for this node and its children.
 *
 * @return NestedSetRecursiveIterator
 */
public function getIterator()
{
    return new NestedSetRecursiveIterator($this);
}
