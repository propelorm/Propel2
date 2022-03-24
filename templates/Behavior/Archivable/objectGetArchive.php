
/**
 * Get an archived version of the current object.
 *
 * @param ConnectionInterface $con Optional connection object
 *
 * @return     <?php echo $archiveTablePhpName ?> An archive object, or null if the current object was never archived
 */
public function getArchive(ConnectionInterface $con = null)
{
    if ($this->isNew()) {
        return null;
    }
    $archive = <?php echo $archiveTableQueryName ?>::create()
        ->filterByPrimaryKey($this->getPrimaryKey())
        ->findOne($con);

    return $archive;
}
