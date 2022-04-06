
/**
 * Delete records matching the current query without archiving them.
 *
 * @param ConnectionInterface|null $con    Connection to use.
 *
 * @return int The number of deleted rows
 */
public function deleteWithoutArchive($con = null): int
{
    $this->archiveOnDelete = false;

    return $this->delete($con);
}

/**
 * Delete all records without archiving them.
 *
 * @param ConnectionInterface|null $con    Connection to use.
 *
 * @return int The number of deleted rows
 */
public function deleteAllWithoutArchive($con = null): int
{
    $this->archiveOnDelete = false;

    return $this->deleteAll($con);
}
