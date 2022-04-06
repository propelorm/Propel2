
/**
 * Remove the translation for a given locale
 *
 * @param string $locale Locale to use for the translation, e.g. 'fr_FR'
 * @param ConnectionInterface|null $con an optional connection object
 *
 * @return $this The current object (for fluent API support)
 */
public function removeTranslation(string $locale = '<?php echo $defaultLocale ?>', ?ConnectionInterface $con = null)
{
    if (!$this->isNew()) {
        <?php echo $i18nQueryName ?>::create()
            ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
            ->delete($con);
    }
    unset($this->currentTranslations[$locale]);
    foreach ($this-><?php echo $i18nCollection ?> as $key => $translation) {
        if ($translation->get<?php echo $localeColumnName ?>() == $locale) {
            unset($this-><?php echo $i18nCollection ?>[$key]);
            break;
        }
    }

    return $this;
}
