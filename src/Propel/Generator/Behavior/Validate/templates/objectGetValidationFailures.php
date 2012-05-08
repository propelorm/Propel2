
/**
 * Gets any ConstraintViolation objects that resulted from last call to validate().
 *
 *
 * @return     object ConstraintViolationList
 * @see        validate()
 */
public function getValidationFailures()
{
    return $this->validationFailures;
}
