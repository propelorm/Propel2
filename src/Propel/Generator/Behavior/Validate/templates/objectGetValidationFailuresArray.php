
/**
 * Gets an associative array where keys are the properties which generated a constraint violation and the values
 * are relative error messages. I.e.
 * <code>
 *     $errors = array(
 *         'property1' => 'Error message 1',
 *         'property2' => array(
 *             'error message 2.1',
 *             'error message 2.2'
 *         );
 *     );
 * </code>
 *
 * @return     array
 * @see        validate()
 */
public function getValidationFailuresArray()
{
    $errors = array();

    foreach ($this->validationFailures as $failure) {
        $key = $failure->getPropertyPath();

        if (array_key_exists($key, $errors)) {
            if (is_array($errors[$key])) {
                $errors[$key][] = $failure->getMessage();
            } else {
                $errors[$key] = array($errors[$key], $failure->getMessage());
            }
        } else {
            $errors[$key] = $failure->getMessage();
        }
    }

    return $errors;
}
