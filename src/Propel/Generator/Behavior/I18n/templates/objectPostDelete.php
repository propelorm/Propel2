
// emulate delete cascade
<?php echo $i18nQueryName ?>::create()
    ->filterBy<?php echo $objectClassName ?>($this)
    ->delete($con);
