

/**
 * This function performs the validation work for complex object models.
 *
 * In addition to checking the current object, all related objects will
 * also be validated.  If all pass then <code>true</code> is returned; otherwise
 * an aggreagated array of ValidationFailed objects will be returned.
 *
 * @param      object $validator A Validator class instance
 * @return     mixed <code>true</code> if all validations pass; <code>ConstraintViolationList</code> object otherwise.
 */
protected function doValidate(Validator $validator)
{
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
        if (!$this-><?php echo $aVarName; ?>->validate($validator))
        {
            $failureMap->addAll($this-><?php echo $aVarName; ?>->getValidationFailures());
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
                if (!$referrerFK->validate($validator))
                {
                    $failureMap->addAll($referrerFK->getValidationFailures());
                }
            }
        }
        <?php endforeach; ?>

        $this->alreadyInValidation = false;
    }

    return ((count($failureMap) > 0) ? $failureMap : true);
  
}