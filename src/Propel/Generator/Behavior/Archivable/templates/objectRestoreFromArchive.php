
/**
 * Revert the the current object to the state it had when it was last archived.
 * The object must be saved afterwards if the changes must persist.
 *
 * @param ConnectionInterface $con Optional connection object
 *
 * @throws PropelException If the object has no corresponding archive.
 *
 * @return $this The current object (for fluent API support)
 */
public function restoreFromArchive(ConnectionInterface $con = null)
{
    $archive = $this->getArchive($con);
    if (!$archive) {
        throw new PropelException('The current object has never been archived and cannot be restored');
    }
    $this->populateFromArchive($archive);

    return $this;
}
