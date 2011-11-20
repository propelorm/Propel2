# List of Backwards Incompatible Changes

## `Propel\Runtime\Propel` methods renamed.

Some static methods from the `Propel` class have been renamed. Therefore, you must replace the following occurrences in your code:

    Replace...                                              With...
    Propel::CONNECTION_WRITE                                ServiceContainerInterface::CONNECTION_WRITE
    Propel::CONNECTION_READ                                 ServiceContainerInterface::CONNECTION_READ
    Propel::getDB($name)                                    Propel::getAdapter($name)
    Propel::getConnection($name, Propel::CONNECTION_READ)   Propel::getReadConnection($name)
    Propel::getConnection($name, Propel::CONNECTION_WRITE)  Propel::getWriteConnection($name)
    Propel::getDefaultDB()                                  Propel::getDefaultDatasource()

The generated model is automatically updated once you rebuild your model.

>**Tip**: Internally, `Propel::getAdapter()` proxies to `Propel::getServiceContainer()->getAdapter()`. The `Propel` class was refactored to keep only one static class and to be more extensible. It remains the easy entry point to all the necessary services provided by Propel.
