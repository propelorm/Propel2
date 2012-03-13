

/**
 * Validates the object and all objects related to this table.
 *
 * @param      object \$validator A Validator class instance
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
    
    $res = $this->doValidate($validator);
    
    if ($res === true)
    {
        $this->validationFailures = new ConstraintViolationList();
    
        return true;
        
    } 
    else
    {
        $this->validationFailures = $res;

        return false;
        
    }
}