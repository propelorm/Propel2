
/**
 * Enable/disable auto-archiving on delete for the next query.
 *
 * @param bool True if the query must archive deleted objects, false otherwise.
 */
public function setArchiveOnDelete($archiveOnDelete)
{
    $this->archiveOnDelete = $archiveOnDelete;
}
