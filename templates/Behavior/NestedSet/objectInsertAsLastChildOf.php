
/**
 * Inserts the current node as last child of given $parent node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param <?= $objectClassName ?> $parent Propel object for parent node
 * @return $this|<?= $objectClassName ?> The current Propel object
 */
public function insertAsLastChildOf(<?= $objectClassName ?> $parent)
{
    if ($this->isInTree()) {
        throw new PropelException(
            'A <?= $objectClassName ?> object must not already be in the tree to be inserted. Use the moveToLastChildOf() instead.'
        );
    }

    $left = $parent->getRightValue();
    // Update node properties
    $this->setLeftValue($left);
    $this->setRightValue($left + 1);
    $this->setLevel($parent->getLevel() + 1);

<?php if ($useScope) : ?>
    $scope = $parent->getScopeValue();
    $this->setScopeValue($scope);

<?php endif; ?>
    // update the children collection of the parent
    $parent->addNestedSetChild($this);

    // Keep the tree modification query for the save() transaction
    $this->nestedSetQueries []= [
        'callable'  => ['<?= $queryClassName ?>', 'makeRoomForLeaf'],
        'arguments' => [$left<?= $useScope ? ', $scope' : '' ?>, $this->isNew() ? null : $this],
    ];

    return $this;
}
