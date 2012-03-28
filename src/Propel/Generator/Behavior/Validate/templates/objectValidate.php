

/**
 * Validates the object and all objects related to this table.
 *
 * @param      object $validator A Validator class instance
 * @return     boolean Whether all objects pass validation.
 * @see        doValidate()
 * @see        getValidationFailures()
 */
public function validate(Validator $validator = null)
{
    if (is_null($validator))
    {
        $validator = new Validator(new ClassMetadataFactory(new StaticMethodLoader()), new ConstraintValidatorFactory());
    }
    
    $failureMap = new ConstraintViolationList();
    
    if (!$this->alreadyInValidation) 
    {
        $this->alreadyInValidation = true;
        $retval = null;
        
<?php if ($hasForeignKeys) : ?>
        // We call the validate method on the following object(s) if they
        // were passed to this object by their coresponding set
        // method.  This object relates to these object(s) by a
        // foreign key reference.
        
<?php foreach($aVarNames as $aVarName) : ?>
        //If validate() method exists, the validate-behavior is configured for related object
        if (method_exists($this-><?php echo $aVarName; ?>, 'validate'))
        {
            if (!$this-><?php echo $aVarName; ?>->validate($validator))
            {
                $failureMap->addAll($this-><?php echo $aVarName; ?>->getValidationFailures());
            }
        }
<?php endforeach; ?>
<?php endif; ?>

        $retval = $validator->validate($this);
        if (count($retval) > 0)
        {
            $failureMap->addAll($retval);
        }

<?php foreach($collVarNames as $collVarName) : ?>
        if (!is_null($this-><?php echo $collVarName; ?>))
        {
            foreach ($this-><?php echo $collVarName; ?> as $referrerFK)
            {
                if (method_exists($referrerFK, 'validate'))
                {
                    if (!$referrerFK->validate($validator))
                    {
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

