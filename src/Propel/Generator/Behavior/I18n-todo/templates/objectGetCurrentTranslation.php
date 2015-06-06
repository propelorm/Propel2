
/**
 * Returns the current translation
 *
 * @param     ConnectionInterface $con an optional connection object
 *
 * @return <?= $i18nEntityPhpName ?>
 */
public function getCurrentTranslation(ConnectionInterface $con = null)
{
    return $this->getTranslation($this->get<?= $localeFieldName ?>(), $con);
}
