
/**
 * Flag to prevent endless validation loop, if this object is referenced
 * by another object which falls in this transaction.
 * @var        boolean
 */
protected $alreadyInValidation = false;

/**
 * ConstraintViolationList object
 *
 * @see     http://api.symfony.com/2.0/Symfony/Component/Validator/ConstraintViolationList.html
 * @var     ConstraintViolationList
 */
protected $validationFailures;
