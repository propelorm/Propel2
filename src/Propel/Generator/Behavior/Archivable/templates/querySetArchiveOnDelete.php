
/**
 * Enable/disable auto-archiving on delete for the next query.
 *
 * @param boolean True if the query must archive deleted objects, false otherwise.
 */
public function setArchiveOnDelete($archiveOnDelete)
{
    $this->archiveOnDelete = $archiveOnDelete;
}
