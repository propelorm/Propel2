
/**
 * Revert the the current object to the state it had when it was last archived.
 * The object must be saved afterwards if the changes must persist.
 *
 * @param ConnectionInterface $con Optional connection object
 *
 * @throws PropelException If the object has no corresponding archive.
 *
 * @return <?php echo $objectClassName ?> The current object (for fluent API support)
 */
public function restoreFromArchive(ConnectionInterface $con = null)
{
    if (!$archive = $this->getArchive($con)) {
        throw new PropelException('The current object has never been archived and cannot be restored');
    }
    $this->populateFromArchive($archive);

    return $this;
}
