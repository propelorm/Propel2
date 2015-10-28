/**
 * Copy the data of the current object into a $archiveTablePhpName archive object.
 * The archived object is then saved.
 * If the current object has already been archived, the archived object
 * is updated and not duplicated.
 *
 * @param ConnectionInterface $con Optional connection object
 *
 * @throws PropelException If the object is new
 *
 * @return     <?php echo $archiveTablePhpName ?> The archive object based on this object
 */
public function archive(ConnectionInterface $con = null)
{
    if ($this->isNew()) {
        throw new PropelException('New objects cannot be archived. You must save the current object before calling archive().');
    }
    $archive = $this->getArchive(<?php if (!$hasArchiveClass): ?>$con<?php endif; ?>);
    if (!$archive) {
        $archive = new <?php echo $archiveTablePhpName ?>();
        $archive->setPrimaryKey($this->getPrimaryKey());
    }
    $this->copyInto($archive, $deepCopy = false, $makeNew = false);
<?php if ($archivedAtColumn): ?>
    $archive->set<?php echo $archivedAtColumn->getPhpName() ?>(time());
<?php endif; ?>
    $archive->save(<?php if (!$hasArchiveClass): ?>$con<?php endif; ?>);

    return $archive;
}
