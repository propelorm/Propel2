---
layout: documentation
title: The Build Time
---

# The Build Time #

The initial step in every Propel project is the "build". During build time, a developer describes the structure of the datamodel in a XML file called the "schema". From this schema, Propel generates PHP classes, called "model classes", made of object-oriented PHP code optimized for a given RDMBS. The model classes are the primary interface to find and manipulate data in the database in Propel.

The XML schema can also be used to generate SQL code to setup your database. Alternatively, you can generate the schema from an existing database (see the [Existing-Database reverse engineering chapter](../cookbook/working-with-existing-databases) for more details), or from a DBDesigner 4 model (see the [DBDesigner2Propel chapter](../cookbook/dbdesigner)).

During build time, a developer also defines the connection settings for communicating with the database.

To illustrate Propel's build abilities, this chapter uses the data structure of a bookstore as an example. It is made of three tables: a `book` table, with a foreign key to two other tables, `author` and `publisher`.

## Describing Your Database as XML Schema ##

Propel generates PHP classes based on a _relational_ description of your data model. This "schema" uses XML to describe tables, columns and relationships. The schema syntax closely follows the actual structure of the database.

Create a `bookstore` directory. This will be the root of the bookstore project.

### Database Connection Name ###

Create a file called `schema.xml` in the new `bookstore/` directory.

The root tag of the XML schema is the `<database>` tag:

{% highlight xml %}
<?xml version="1.0" encoding="UTF-8"?>
<database name="bookstore" defaultIdMethod="native">
  <!-- table definitions go here -->
</database>
{% endhighlight %}

The `name` attribute defines the name of the connection that Propel uses for the tables in this schema. It is not necessarily the name of the actual database. In fact, Propel uses a second file to link a connection name with real connection settings (like database name, user and password). This `runtime-conf.xml` file will be explained later in this chapter.

The `defaultIdMethod` attribute indicates that the tables in this schema use the database's "native" auto-increment/sequence features to handle id columns that are set to auto-increment.

>**Tip**<br />You can define several schemas for a single project. Just make sure that each of the schema filenames end with `schema.xml`.

### Tables And Columns ###

Within the `<database>` tag, Propel expects one `<table>` tag for each table:

{% highlight xml %}
<?xml version="1.0" encoding="UTF-8"?>
<database name="bookstore" defaultIdMethod="native">
  <table name="book" phpName="Book">
    <!-- column and foreign key definitions go here -->
  </table>
  <table name="author" phpName="Author">
    <!-- column and foreign key definitions go here -->
  </table>
  <table name="publisher" phpName="Publisher">
    <!-- column and foreign key definitions go here -->
  </table>
</database>
{% endhighlight %}

This time, the `name` attributes are the real table names. The `phpName` is the name that Propel will use for the generated PHP class. By default, Propel uses a CamelCase version of the table name as its phpName - that means that you could omit the `phpName` attribute in the example above.

Within each set of `<table>` tags, define the columns that belong to that table:

{% highlight xml %}
<?xml version="1.0" encoding="UTF-8"?>
<database name="bookstore" defaultIdMethod="native">
  <table name="book" phpName="Book">
    <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
    <column name="title" type="varchar" size="255" required="true" />
    <column name="isbn" type="varchar" size="24" required="true" phpName="ISBN"/>
    <column name="publisher_id" type="integer" required="true"/>
    <column name="author_id" type="integer" required="true"/>
  </table>
  <table name="author" phpName="Author">
    <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
    <column name="first_name" type="varchar" size="128" required="true"/>
    <column name="last_name" type="varchar" size="128" required="true"/>
  </table>
  <table name="publisher" phpName="Publisher">
   <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
   <column name="name" type="varchar" size="128" required="true" />
  </table>
</database>
{% endhighlight %}

Each column has a `name` (the one used by the database), and an optional `phpName` attribute. Once again, the Propel default behavior is to use a CamelCase version of the `name` as `phpName` when not specified.

Each column also requires a `type`. The XML schema is database agnostic, so the column types and attributes are probably not exactly the same as the one you use in your own database. But Propel knows how to map the schema types with SQL types for many database vendors. Existing Propel column types are `boolean`, `tinyint`, `smallint`, `integer`, `bigint`, `double`, `float`, `real`, `decimal`, `char`, `varchar`, `longvarchar`, `date`, `time`, `timestamp`, `blob`, `clob`, `object`, and `array`. Some column types use a `size` (like `varchar` and `int`), some have unlimited size (`longvarchar`, `clob`, `blob`). Check the [schema reference]() for more details on each column type.

As for the other column attributes, `required`, `primaryKey`, and `autoIncrement`, they mean exactly what their names imply.

>**Tip**<br />Propel supports namespaces (for PHP > 5.3). If you specify a `namespace` attribute in a `<table>` element, the generated PHP classes for this table will use this namespace.

### Foreign Keys ###

A table can have several `<foreign-key>` tags, describing foreign keys to foreign tables. Each `<foreign-key>` tag consists of one or more mappings between a local column and a foreign column.

{% highlight xml %}
<?xml version="1.0" encoding="UTF-8"?>
<database name="bookstore" defaultIdMethod="native">
  <table name="book" phpName="Book">
    <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
    <column name="title" type="varchar" size="255" required="true" />
    <column name="isbn" type="varchar" size="24" required="true" phpName="ISBN"/>
    <column name="publisher_id" type="integer" required="true"/>
    <column name="author_id" type="integer" required="true"/>
    <foreign-key foreignTable="publisher" phpName="Publisher" refPhpName="Book">
      <reference local="publisher_id" foreign="id"/>
    </foreign-key>
    <foreign-key foreignTable="author">
      <reference local="author_id" foreign="id"/>
    </foreign-key>
  </table>
  <table name="author" phpName="Author">
    <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
    <column name="first_name" type="varchar" size="128" required="true"/>
    <column name="last_name" type="varchar" size="128" required="true"/>
  </table>
  <table name="publisher" phpName="Publisher">
   <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
   <column name="name" type="varchar" size="128" required="true" />
  </table>
</database>
{% endhighlight %}

A foreign key represents a relationship. Just like a table or a column, a relationship has a `phpName`. By default, Propel uses the `phpName` of the foreign table as the `phpName` of the relation. The `refPhpName` defines the name of the relation as seen from the foreign table.

There are many more attributes and elements available to describe a datamodel. Propel's documentation provides a complete [Schema of the schema syntax](../reference/schema), together with a [DTD](https://github.com/propelorm/Propel/blob/master/generator/resources/dtd/database.dtd) and a [XSD](https://github.com/propelorm/Propel/blob/master/generator/resources/xsd/database.xsd) schema for its validation.

## Building The Model ##

### Setting Up Build Configuration ###

The build process is highly customizable. Whether you need the generated classes to inherit one of your classes rather than Propel's base classes, or to enable/disable some methods in the generated classes, pretty much every customization is possible. Of course, Propel provides sensible defaults, so that you actually need to define only two settings for the build process to start: the RDBMS you are going to use, and a name for your project.

Propel expects the build configuration to be stored in a file called `build.properties`, and stored at the same level as the `schema.xml`. Here is an example for a MySQL database:

{% highlight ini %}
# Database driver
propel.database = mysql

# Project name
propel.project = bookstore
{% endhighlight %}

Use your own database vendor driver, chosen among pgsql, mysql, sqlite, mssql, and oracle.

You can learn more about the available build settings and their possible values in the  [build configuration reference](../reference/buildtime-configuration).

### Using the `propel-gen` Script To Build The Model ###

The Propel generator uses the `propel-gen` script, as seen in the previous chapter. This executable expects a command name as its argument.

Open a terminal and browse to the `bookstore/` directory, where you saved the two previous files (`schema.xml`, and `build.properties`). Then use the `propel-gen` script to call the "Object Model generator" command using its shortcut - "om":

{% highlight bash %}
cd /path/to/bookstore
propel-gen om
{% endhighlight %}

You should normally see a some colored lines appear in the terminal, logging all the class generation, and ending with "BUILD FINISHED". If not, look for red lines in the log and follow the directions in the error messages.

### Generated Object Model ###

The "om" command added a new directory in the `bookstore/` project, called `build/`. The generated model classes are located under the `classes/bookstore/` subdirectory:

{% highlight bash %}
> cd /path/to/bookstore
> cd build/classes/bookstore/
> ls
    om/
    map/
    Author.php
    AuthorPeer.php
    AuthorQuery.php
    Book.php
    BookPeer.php
    BookQuery.php
    Publisher.php
    PublisherPeer.php
    PublisherQuery.php
{% endhighlight %}

For every table in the database, Propel creates 3 PHP classes:

* a _model_ class (e.g. `Book`), which represents a row in the database;
* a _peer_ class (e.g. `BookPeer`), offering static constants and methods mostly for compatibility with previous Propel versions;
* a _query_ class (e.g. `BookQuery`), used to operate on a table to retrieve and update rows

Propel uses the `phpName` attribute of each table as the base for the PHP class names.

All these classes are empty, but they inherit from `Base` classes that you will find under the `om/` directory:

{% highlight php %}
<?php

/**
 * Skeleton subclass for representing a row from the 'book' table.
 */
class Book extends BaseBook
{
}
{% endhighlight %}

These empty classes are called _stub_ classes. This is where you will add your own model code. These classes are generated only once by Propel ; on the other hand, the _base_ classes they extend are overwritten every time you call the `om` command, and that happens a lot in the course of a project, because the schema evolves with your needs.

In addition to these classes, Propel generates one `TableMap` class for each table under the `map/` directory. You will probably never use the map classes directly, but Propel needs them to get metadata information about the table structure at runtime.

>**Tip**<br />Never add any code of your own to the classes generated by Propel in the `om/` and `map/` directories; this code would be lost next time you call the `propel-gen` script.

Basically, all that means is that despite the fact that Propel generates _seven_ classes for each table, you should only care about two of them: the model class and the query class.

## Building The Database ##

To save you the burden of defining your model twice, Propel can initialize a database based on the schema, by creating the tables and foreign keys.

### Building The SQL File ###

Once again, use the `propel-gen` script to generate the SQL files necessary to create the tables, this time with the "sql" command:

{% highlight bash %}
cd /path/to/bookstore
propel-gen sql
{% endhighlight %}

The generated SQL definition can be found in the `build/sql/schema.sql` file. The code is optimized for the database driver defined in the `build.properties`.

### Using The SQL File ###

Create the database and setup the access permissions using your favorite database client. For instance, to create the `my_db_name` database with MySQL, type:

{% highlight bash %}
mysqladmin -u root -p create my_db_name
{% endhighlight %}

Now you can use the generated code directly:

{% highlight bash %}
mysql -u root -p my_db_name < build/sql/schema.sql
{% endhighlight %}

>**Tip**<br />The `schema.sql` file will DROP any existing table before creating them, which will effectively erase your database.

Depending on which RDBMS you are using, it may be normal to see some errors (e.g. "unable to DROP...") when you first run this command. This is because some databases have no way of checking to see whether a database object exists before attempting to DROP it (MySQL is a notable exception). It is safe to disregard these errors, and you can always run the script a second time to make sure that the errors are no longer present.

### Inserting SQL With `propel-gen` ###

As an alternative to using the generated sql code directly, you can ask Propel to insert it directly into your database. Start by defining the database connection settings in the `build.properties`, as follows:

{% highlight ini %}
# Connection parameters
propel.database.url = mysql:host=localhost;dbname=my_db_name
propel.database.user = my_db_user
propel.database.password = my_db_password

# Other examples:
# propel.database.url = sqlite:/path/to/bookstore.db
# propel.database.url = pgsql:host=localhost dbname=my_db_name user=my_db_user password=my_db_password
{% endhighlight %}

The `propel.database.url` setting should be a PDO DSN (see the [PDO documentation](http://www.php.net/pdo) for more information about vendor-specific DSN). The `user` and `password` are only necessary for the `mysql` and `oracle` drivers.

Then use the `propel-gen` script with the "insert-sql" command to connect to the database and inject the generated SQL code:

{% highlight bash %}
cd /path/to/bookstore
propel-gen insert-sql
{% endhighlight %}

## Runtime Connection Settings ##

The database and PHP classes are now ready to be used. But they don't know yet how to communicate with each other at runtime. You must tell Propel which database connection settings should be used to finish the setup.

Propel stores the runtime settings in a service container, available from everywhere using `\Propel\Runtime\Propel::getServiceContainer()`. The service container uses lazy-loading to initiate connections only when necessary. 

Here is a sample setup file:

{% highlight php %}
<?php
// setup the autoloading
require_once '/path/to/propel/vendor/autoload.php';
use Propel\Runtime\Propel;
use Propel\Runtime\Connection\ConnectionManagerSingle;
$serviceContainer = Propel::getServiceContainer();
$serviceContainer->setAdapterClass('bookstore', 'mysql');
$manager = new ConnectionManagerSingle();
$manager->setConfiguration(array (
  'dsn'      => 'mysql:host=localhost;dbname=my_db_name',
  'user'     => 'my_db_user',
  'password' => 's3cr3t',
));
$serviceContainer->setConnectionManager('bookstore', $manager);
{% endhighlight %}

Notice how the "bookstore" name passed to `setAdapterClass()` and `setConnectionManager()` matches the connection name defined in the `<database>` tag of the `schema.xml`. This is how Propel maps a database description to a connection.

It's a good practice to add a logger to the service container, so that Propel can log warnings and errors. You can do so by adding the following code to the setup script:

{% highlight php %}
<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$defaultLogger = new Logger('defaultLogger');
$defaultLogger->pushHandler(new StreamHandler('/var/log/propel.log', Logger::WARNING));
$serviceContainer->setLogger('defaultLogger', $defaultLogger);
{% endhighlight %}

**Tip**: You may wish to write the setup code in a standalone script that is included at the beginning of your PHP scripts.

Now you are ready to start using your model classes!

### Alternative: Writing The XML Runtime Configuration ###

Alternatively, you can write the runtime configuration settings in an XML file and convert this file to PHP.

Create a file called `runtime-conf.xml` at the root of the `bookstore` project, using the following content:

{% highlight xml %}
<?xml version="1.0" encoding="UTF-8"?>
<config>
  <datasources default="bookstore">
    <datasource id="bookstore">
      <adapter>mysql</adapter> <!-- sqlite, mysql, myssql, oracle, or pgsql -->
      <connection>
        <dsn>mysql:host=localhost;dbname=my_db_name</dsn>
        <user>my_db_user</user>
        <password>s3cr3t</password>
      </connection>
    </datasource>
  </datasources>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/propel.log</path>
      <level>300</level>
    </logger>
  </log>
</config>
{% endhighlight %}

Notice how the `id` attribute of the `<datasource>` tag matches the connection name defined in the `<database>` tag of the `schema.xml`. This is how Propel maps a database description to a connection.

Replace the `<adapter>` and the `<connection>` settings with the ones of your database.

See the [runtime configuration reference](../reference/runtime-configuration) for a more detailed explanation of this file.

### Building the Runtime Configuration ###

For performance reasons, Propel prefers to use a PHP version of the connection settings rather than the XML file you just defined. So you must use the `propel-gen` script one last time to build the PHP version of the `runtime-conf.xml` configuration:

{% highlight bash %}
cd /path/to/bookstore
propel-gen config:convert-xml
{% endhighlight %}

The resulting file can be found under `build/conf/bookstore-conf.php`, where "bookstore" is the name of the project you defined in `build.properties`.

This simplifies the setup of Propel to the following script:

{% highlight php %}
<?php
// setup the autoloading
require_once '/path/to/propel/vendor/autoload.php';

// setup Propel
require_once 'build/conf/bookstore-conf.php';
{% endhighlight %}