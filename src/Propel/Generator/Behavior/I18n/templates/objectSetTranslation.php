
/**
 * Sets the translation for a given locale
 *
 * @param     <?php echo $i18nEntityPhpName ?> $translation The translation object
 * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
 *
 * @return    $this|<?php echo $objectClassName ?> The current object (for fluent API support)
 */
public function setTranslation($translation, $locale = '<?php echo $defaultLocale ?>')
{
    $translation->set<?php echo $localeFieldName ?>($locale);
    $this->add<?php echo $i18nEntityPhpName ?>($translation);
    $this->currentTranslations[$locale] = $translation;

    return $this;
}
