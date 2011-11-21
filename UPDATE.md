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

## Builders renamed

The classes used by Propel internally to build the object model were renamed. This affects your project if you extended one of these classes.

    Old name                         New name
    OMBuilder.php                    AbstractOMBuilder.php
    ObjectBuilder.php                AbstractObjectBuilder.php
    PeerBuilder.php                  AbstractPeerBuilder.php
    PHP5ExtensionObjectBuilder.php   ExtensionObjectBuilder.php
    PHP5ExtensionPeerBuilder.php     ExtensionPeerBuilder.php
    PHP5InterfaceBuilder.php         InterfaceBuilder.php
    PHP5MultiExtendObjectBuilder.php MultiExtendObjectBuilder.php
    PHP5ObjectBuilder.php            ObjectBuilder.php
    PHP5PeerBuilder.php              PeerBuilder.php
    PHP5TableMapBuilder.php          TableMapBuilder.php
