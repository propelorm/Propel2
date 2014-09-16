
/**
 * Returns the current translation
 *
 * @param     ConnectionInterface $con an optional connection object
 *
 * @return <?= $i18nTablePhpName ?>
 */
public function getCurrentTranslation(ConnectionInterface $con = null)
{
    return $this->getTranslation($this->get<?= $localeColumnName ?>(), $con);
}
