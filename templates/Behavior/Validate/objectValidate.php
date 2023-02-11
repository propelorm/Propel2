
/**
 * Validates the object and all objects related to this table.
 *
 * @see        getValidationFailures()
 * @param ValidatorInterface|null $validator A Validator class instance
 * @return bool Whether all objects pass validation.
 */
public function validate(ValidatorInterface $validator = null)
{
    if (null === $validator) {
        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new StaticMethodLoader()),
            new ConstraintValidatorFactory()
        );
    }

    $failureMap = new ConstraintViolationList();

    if (!$this->alreadyInValidation) {
        $this->alreadyInValidation = true;
        $retval = null;

<?php if ($hasForeignKeys) : ?>
        // We call the validate method on the following object(s) if they
        // were passed to this object by their corresponding set
        // method.  This object relates to these object(s) by a
        // foreign key reference.

<?php foreach($aVarNames as $aVarName) : ?>
        // If validate() method exists, the validate-behavior is configured for related object
        if (is_object($this-><?php echo $aVarName; ?>) and method_exists($this-><?php echo $aVarName; ?>, 'validate')) {
            if (!$this-><?php echo $aVarName; ?>->validate($validator)) {
                $failureMap->addAll($this-><?php echo $aVarName; ?>->getValidationFailures());
            }
        }
<?php endforeach; ?>
<?php endif; ?>

        $retval = $validator->validate($this);
        if (count($retval) > 0) {
            $failureMap->addAll($retval);
        }

<?php foreach($collVarNames as $collVarName) : ?>
        if (null !== $this-><?php echo $collVarName; ?>) {
            foreach ($this-><?php echo $collVarName; ?> as $referrerFK) {
                if (method_exists($referrerFK, 'validate')) {
                    if (!$referrerFK->validate($validator)) {
                        $failureMap->addAll($referrerFK->getValidationFailures());
                    }
                }
            }
        }
<?php endforeach; ?>

        $this->alreadyInValidation = false;
    }

    $this->validationFailures = $failureMap;

    return (bool) (!(count($this->validationFailures) > 0));

}
