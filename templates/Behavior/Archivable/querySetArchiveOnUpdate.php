
/**
 * Enable/disable auto-archiving on update for the next query.
 *
 * @param bool True if the query must archive updated objects, false otherwise.
 */
public function setArchiveOnUpdate(bool $archiveOnUpdate)
{
    $this->archiveOnUpdate = $archiveOnUpdate;
}
