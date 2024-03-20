
/**
 * Delete records matching the current query without archiving them.
 *
 * @param array $values Associative array of keys and values to replace
 * @param ConnectionInterface|null $con an optional connection object
 * @param bool $forceIndividualSaves If false (default), the resulting call is a Criteria::doUpdate(), ortherwise it is a series of save() calls on all the found objects
 *
 * @return int The number of deleted rows
 */
public function updateWithoutArchive(array $values, $con = null, bool $forceIndividualSaves = false): int
{
    $this->archiveOnUpdate = false;

    return $this->update($values, $con, $forceIndividualSaves);
}
