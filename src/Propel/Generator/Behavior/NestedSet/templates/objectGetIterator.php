
/**
 * Returns a pre-order iterator for this node and its children.
 *
 * @return RecursiveIterator
 */
public function getIterator()
{
    return new NestedSetRecursiveIterator($this);
}
