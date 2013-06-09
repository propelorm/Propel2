---
layout: documentation
title: Build Properties Reference
---

# Build Properties Reference #

Here is a list of properties that can be set to affect how Propel builds database files.  For a complete list, see the `default.properties` file that is bundled with your version of Propel generator (this will be in PEAR's data directory if you are using a PEAR-installed version of Propel).

First, some conventions:

* Text surrounded by a  `/` is text that you would provide and is not defined in the language. (i.e. a table name is a good example of this.)
* Items where you have an alternative choice have a `|` character between them (i.e. true|false)
* Alternative choices may be delimited by `{` and `}` to indicate that this is the default option, if not overridden elsewhere.

## Where to Specify Properties ##

### In the Project `build.properties` File ###

The most natural place to specify properties for a file are in the project's `build.properties` file. This file is expected to be found in the project directory.

### In a global `build.properties` file ###

You can also create a global `build.properties` file in the same directory as Propel's `default.properties` file. For users who have installed Propel using PEAR, this will be in PEAR data directory structure.

### On the Command Line ###

You can also specify properties on the command line when you invoke Propel. The command line accepts a camelCase version of the property name. So for instance, to set the value of the `propel.some.other.property` property using the command line, type:

    > propel-gen /path/to/project -Dpropel.someOtherProperty#value

>**Tip**<br />There is no space between the -D and the property name.

## Property List ##

### General Build Settings ###

```ini
# The name of your project.
# This affects names of generated files, etc.
propel.project = /Your-Project-Name/

# The package to use for the generated classes.
# This affects the value of the @package phpdoc tag, and it also affects
# the directory that the classes are placed in. By default this will be
# the same as the project. Note that the target package (and thus the target
# directory for generated classes) can be overridden in each `<database>` and
# `<table>` element in the XML schema.
propel.targetPackage = {propel.project}|string

# Whether to join schemas using the same database name into a single schema.
# This allows splitting schemas in packages, and referencing tables in another
# schema (but in the same database) in a foreign key. Beware that database
# behaviors will also be joined when this parameter is set to true.
propel.packageObjectModel = true|{false}

# If you use namespaces in your schemas, this setting tells Propel to use the
# namespace attribute for the package. Consequently, the namespace attribute
# will also stipulate the subdirectory in which model classes get generated.
propel.namespace.autoPackage = true|{false}

# If your XML schema specifies SQL schemas for each table, you can copy the
# value of the `schema` attribute to other attributes.
# To copy the schema attribute to the package attribute, set this to true
propel.schema.autoPackage = true|{false}
# To copy the schema attribute to the namespace attribute, set this to true
propel.schema.autoNamespace = true|{false}
# To use the schema attribute as a prefix to all model phpNames, set this to true
propel.schema.autoPrefix = true|{false}

# Whether to validate the XML schema using the XSD file.
# The default XSD file is located under `generator/resources/xsd/database.xsd`
# and you can use a custom XSD file by changing the `propel.schema.xsd.file`
# property.
propel.schema.validate = {true}|false

# Whether to transform the XML schema using the XSL file.
# This was used in previous Propel versions to clean up the schema, but tended
# to hide problems in the schema. It is disabled by default since Propel 1.5.
# The default XSL file is located under `generator/resources/xsd/database.xsl`
# and you can use a custom XSL file by changing the `propel.schema.xsl.file`
# property.
propel.schema.transform = true|{false}
```

### Database Settings ###

```ini
# The Propel platform that will be used to determine how to build
# the SQL DDL, the PHP classes, etc.
propel.database = pgsql|mysql|sqlite|mssql|oracle

# The database PDO connection settings at buildtime.
# This setting is required for the sql, reverse, and datasql tasks.
# Note that some drivers (e.g. mysql, oracle) require that you specify the
# username and password separately from the DSN, which is why they are
# available as options.
# Example PDO connection strings:
#   mysql:host=localhost;port=3307;dbname=testdb
#   sqlite:/opt/databases/mydb.sq3
#   sqlite::memory:
#   pgsql:host=localhost;port=5432;dbname=testdb;user=bruce;password=mypass
#   oci:dbname=//localhost:1521/mydb
propel.database.url = {empty}|string
propel.database.user = {empty}|string
propel.database.password = {empty}|string

# The database PDO connection settings at builtime for reverse engineer
# or data dump. The default is to use the database connection defined by the
# `propel.database.url` property.
propel.database.buildUrl = {propel.database.url}/string

# The database PDO connection settings at builtime for creating a database.
# The default is to use the database connection defined by the
# `propel.database.url` property.
# Propel is unable to create databases for some vendors because they do not
# provide a SQL method for creation; therefore, it is usually recommended that
# you actually create your database by hand.
propel.database.createUrl = {propel.database.url}/string

# Optional schema name, for RDBMS supporting them.
# Propel will use this schema is provided.
propel.database.schema = {empty}|string

# The encoding to use for the database.
# This can affect things such as transforming charsets when exporting to XML, etc.
propel.database.encoding = {empty}|string

# Add a prefix to all the table names in the database.
# This does not affect the tables phpName.
# This setting can be overridden on a per-database basis in the schema.
propel.tablePrefix = {empty}|string
```

>**Tip**<br />If you need more than one database connection at buildtime, the INI format is not enough. Therefore, you can add a `buildtime-conf.xml` file in the same directory as the `build.properties` file, and Propel will use the connections defined in this file instead of the ones defined by `propel.database.XXX` settings. The buildtime configuration file uses the same format as the `runtime-conf.xml` (see the runtime documentation reference for more details about this format).

### Reverse-Engineering Settings ###

```ini
# Whether to specify PHP names that are the same as the column names.
propel.samePhpName = true|{false}

# Whether to add the vendor info. This is currently only used for MySQL, but
# does provide additional information (such as full-text indexes) which can
# affect the generation of the DDL from the schema.
propel.addVendorInfo = true|{false}
```

### Customizing Generated Object Model ###

```ini
# Whether to add generic getter/setter methods.
# Generic accessors are `getByName()`, `getByPosition(), ` and `toArray()`.
propel.addGenericAccessors = {true}|false
# Generic mutators are `setByName()`, `setByPosition()`, and `fromArray()`.
propel.addGenericMutators = {true}|false

# Whether to add a timestamp to the phpdoc header of generated OM classes.
# If you use a versioning system, don't set this to true, or the classes
# will be committed too often with just a date change.
propel.addTimeStamp = true|{false}

# Whether to add `require` statements on the generated stub classes.
# Propel uses autoloading for OM classes, and doesn't insert require statements
# by default. If you don't want to use autoloading, set this to true.
propel.addIncludes = true|{false}

# Whether to support pre- and post- hooks on `save()` and `delete()` methods.
# Set to false if you never use these hooks for a small speed boost.
propel.addHooks = {true}|false

# The prefix to use for the base (super) classes that are generated.
propel.basePrefix = {Base}|string

# Some sort of "namespacing": All Propel classes with get the Prefix
# "My_ORM_Prefix_" just like "My_ORM_Prefix_BookTableMap".
propel.classPrefix = {empty}|string

# Identifier quoting may result in undesired behavior (especially in Postgres),
# it can be disabled in DDL by setting this property to true in your build.properties file.
propel.disableIdentifierQuoting = true|{false}

# Whether the generated `doSelectJoin*()` methods use LEFT JOIN or INNER JOIN
# (see ticket:491 and ticket:588 to understand more about why this might be
# important).
propel.useLeftJoinsInDoJoinMethods = {true}|false
```

### MySQL-specific Settings ###

```ini
# Default table type.
# You can override this setting if you wish to default to another engine for
# all tables (for instance InnoDB, or HEAP). This setting can also be
# overridden on a per-table basis using the `<vendor>` element in the schema
# (see Schema AddingVendorInfo).
propel.mysql.tableType = {MyISAM}|string

# Keyword used to specify the table engine in the CREATE SQL statement.
# Defaults to 'ENGINE', users of MYSQL < 5 should use 'TYPE' instead.
propel.mysql.tableEngineKeyword = {ENGINE}|TYPE
```

### Date/Time Settings ###

```ini
# Enable full use of the DateTime class.
# Setting this to true means that getter methods for date/time/timestamp
# columns will return a DateTime object when the default format is empty.
propel.useDateTimeClass = {true}|false

# Specify a custom DateTime subclass that you wish to have Propel use
# for temporal values.
propel.dateTimeClass = {DateTime}|string

# These are the default formats that will be used when fetching values from
# temporal columns in Propel. You can always specify these when calling the
# methods directly, but for methods like getByName() it is nice to change
# the defaults.
# To have these methods return DateTime objects instead, you should set these
# to empty values
propel.defaultTimeStampFormat = {Y-m-d H:i:s}|string
propel.defaultTimeFormat = { %X }|string
propel.defaultDateFormat = { %x }|string
```

### Directories and Filenames ###

```ini
# Directory where the project files (`build.properties`, `schema.xml`,
# `runtime-conf.xml`, etc.) are located.
# If you use the `propel-gen` script, this value will get overridden by
# the path from which the script is called.
propel.project.dir = {current_path}|string

# The directory where Propel expects to find the XML configuration files.
propel.conf.dir # ${propel.project.dir}
# The XML configuration file names
propel.runtime.conf.file = runtime-conf.xml
propel.buildtime.conf.file = buildtime-conf.xml

# The directory where Propel expects to find your `schema.xml` file.
propel.schema.dir = ${propel.project.dir}
# The schema base name
propel.default.schema.basename = schema

# The directory where Propel should output classes, sql, config, etc.
propel.output.dir = ${propel.project.dir}/build

# The directory where Propel should output generated object model classes.
propel.php.dir = ${propel.output.dir}/classes

# The directory where Propel should output the compiled runtime configuration.
propel.phpconf.dir = ${propel.output.dir}/conf
# The name of the compiled configuration and classmap files
propel.runtime.phpconf.file = ${propel.project}-conf.php
propel.runtime.phpconf-classmap.file = ${propel.project}-classmap.php

# The directory where Propel should output the generated DDL (or data insert statements, etc.)
propel.sql.dir = ${propel.output.dir}/sql
```


### Overriding Builder Classes ###

```ini
# Object Model builders
propel.builder.object.class = builder.om.ObjectBuilder
propel.builder.objectstub.class = builder.om.ExtensionObjectBuilder

propel.builder.objectmultiextend.class = builder.om.MultiExtendObjectBuilder

propel.builder.tablemap.class = builder.om.TableMapBuilder
propel.builder.query.class = builder.om.QueryBuilder
propel.builder.querystub.class = builder.om.ExtensionQueryBuilder
propel.builder.queryinheritance.class = builder.om.QueryInheritanceBuilder
propel.builder.queryinheritancestub.class = builder.om.ExtensionQueryInheritanceBuilder

propel.builder.interface.class = builder.om.InterfaceBuilder

# SQL builders
propel.builder.datasql.class = builder.sql.${propel.database}.${propel.database}DataSQLBuilder

# Platform classes
propel.platform.class = platform.${propel.database}Platform

# Pluralizer class (used to generate plural forms)
propel.builder.pluralizer.class = builder.util.DefaultEnglishPluralizer
# Use StandardEnglishPluralizer instead of DefaultEnglishPluralizer for better pluralization
# (Handles uncountable and irregular nouns)
```

As you can see, you can specify your own builder and platform classes if you want to extend & override behavior in the default classes

### Overriding / Adding Behaviors ###

```ini
# Define the path to the class to be used for the `timestampable` behavior.
# This behavior is bundled with Propel, but if you want to override it, you can
# specify a different path.
propel.behavior.timestampable.class = propel.engine.behavior.TimestampableBehavior
# Other behaviors use similar settings

# If you want to add more behaviors, write their path following the same model:
propel.behavior.my_behavior.class = my.custom.path.to.MyBehaviorClass

# Behaviors are enabled on a per-table basis in the `schema.xml`. However, you
# can add behaviors for all your schemas, provided that you define them in the
# `propel.behavior.default` setting:
propel.behavior.default = archivable,my_behavior
```
