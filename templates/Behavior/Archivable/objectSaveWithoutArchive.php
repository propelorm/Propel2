
/**
 * Persists the object to the database without archiving it.
 *
 * @param ConnectionInterface|null $con Optional connection object
 *
 * @return $this|<?php echo $objectClassName ?> The current object (for fluent API support)
 */
public function saveWithoutArchive(?ConnectionInterface $con = null)
{
<?php if (!$isArchiveOnInsert): ?>
    if (!$this->isNew()) {
        $this->archiveOnUpdate = false;
    }
<?php elseif (!$isArchiveOnUpdate): ?>
    if ($this->isNew()) {
        $this->archiveOnInsert = false;
    }
<?php else: ?>
    if ($this->isNew()) {
        $this->archiveOnInsert = false;
    } else {
        $this->archiveOnUpdate = false;
    }
<?php endif; ?>

    return $this->save($con);
}
