# List of Backwards Incompatible Changes

## `Propel\Runtime\Propel` replaced by the `Propel\Runtime\Configuration` singleton.

The static methods from the `Propel` class have been moved to a singleton. Therefore, you must replace the following occurrences in your code:

    Replace...                                With...
    use Propel\Runtime\Propel                               use Propel\Runtime\Configuration
    Propel::getDatabase($name)                              Configuration::getInstance()->getDatabase($name)
    Propel::getDB($name)                                    Configuration::getInstance()->getAdapter($name)
    Propel::getConnection($name, Propel::CONNECTION_READ)   Configuration::getInstance()->getReadConnection($name)
    Propel::getConnection($name, Propel::CONNECTION_WRITE)  Configuration::getInstance()->getWriteConnection($name)
    Propel::getDefaultDB()                                  Configuration::getInstance()->getDefaultDatasource()

The generated model is automatically updated once you rebuild your model.