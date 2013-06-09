---
layout: default
title: Runtime Configuration File
---

# Runtime Configuration File #

## Example `runtime-conf.xml` File ##

Here is a the sample runtime configuration file.

```xml
<?xml version="1.0"?>
<config>
  <log>
    <ident>propel-bookstore</ident>
    <type>console</type>
    <level>7</level>
  </log>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <classname>DebugPDO</classname>
          <dsn>mysql:host=localhost;dbname=bookstore</dsn>
          <user>testuser</user>
          <password>password</password>
          <options>
            <option id="ATTR_PERSISTENT">false</option>
          </options>
          <attributes>
            <option id="ATTR_EMULATE_PREPARES">true</option>
          </attributes>
          <settings>
            <setting id="charset">utf8</setting>
            <setting id="queries">
              <query>set search_path myschema, public</query><!-- automatically set postgresql's search_path -->
              <query>INSERT INTO BAR ('hey', 'there')</query><!-- execute some other query -->
            </setting>
          </settings>
        </connection>
        <slaves>
         <connection>
          <dsn>mysql:host=slave-server1; dbname=bookstore</dsn>
         </connection>
         <connection>
          <dsn>mysql:host=slave-server2; dbname=bookstore</dsn>
         </connection>
        </slaves>
      </datasource>
    </datasources>
  </propel>
  <profiler class="\Runtime\Runtime\Util\Profiler">
    <slowTreshold>0.2</slowTreshold>
    <details>
      <time name="Time" precision="3" pad="8" />
      <mem name="Memory" precision="3" pad="8" />
    </details>
    <innerGlue>: </innerGlue>
    <outerGlue> | </outerGlue>
  </profiler>
</config>
```

## Explanation of Configuration Sections ##

Below you will find an explanation of the primary elements in the configuration.

### `<log>` ###

If the `<log>` element is present, Propel will use the specified information to instantiate a PEAR Log logger.

```xml
<config>
 <log>
  <type>file<type>
  <name>/path/to/logger.log</name>
  <ident>my-app</ident>
  <level>7</level>
 </log>
```

The nested elements correspond to the configuration options for the logger (options that would otherwise be passed to **Log::factory()** method).

|Element    |Default            |Description
|-----------|-------------------|----------------------------------------------------------------------------------------------
| type      |file               |The logger type.
| name      |./propel.log       |Name of log, meaning is dependent on type specified. (For _file_ type this is the filename).
| ident     |propel             |The identifier tag for the log.
| level     |7 (PEAR_LOG_DEBUG) |The logging level.

This log configuring API is designed to provide a simple way to get log output from Propel; however, if your application already has a logging mechanism, we recommend instead that you use your existing logger (writing a simple log adapter, if you are using an unsupported logger). See the [Logging documentation](../documentation/08-logging) for more info.

### `<datasources>` ###

This is the top-level tag for Propel datasources configuration.

```xml
<config>
 <propel>
  <datasources>
```

### `<datasource>` ###

```xml
<config>
 <propel>
  <datasources>
   <datasource id="bookstore">
```
A specific datasource being configured.

* The @id must match the `<database>` @name attribute from your `schema.xml`.

### `<adapter>` ###

The adapter to use for Propel.  Currently supported adapters:  sqlite, pgsql, mysql, oracle, mssql.  Note that it is possible that your adapter could be different from your connection driver (e.g. if using ODBC to connect to MSSQL database, you would use an ODBC PDO driver, but MSSQL Propel adapter).

```xml
<config>
 <propel>
  <datasources>
   <datasource>
     <adapter>sqlite</adapter>
```

### `<connection>` ###

The PDO database connection for the specified datasource.

Nested elements define the DSN, connection options, other PDO attributes, and finally some Propel-specific initialization settings.

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
```

#### `<classname>` ####

A custom PDO class (must be a PropelPDO subclass) that you would like to use for the PDO connection.

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <classname>DebugPDO</classname>
```

This can be used to specify the alternative **DebugPDO** class bundled with Propel, or your own subclass.  _Your class must extend PropelPDO, because Propel requires the ability to nest transactions (without having exceptions being thrown by PDO)._

#### `<dsn>` ####

The PDO DSN that Propel will use to connect to the database for this datasource.

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <dsn>mysql:host=localhost;dbname=bookstore</dsn>
```

See the PHP documentation for specific format:
 * [MySQL DSN](http://www.php.net/manual/en/ref.pdo-mysql.connection.php)
 * [PostgreSQL DSN](http://github.com/seven1m/trac_wiki_to_github)
 * [SQLite DSN](http://www.php.net/manual/en/ref.pdo-sqlite.connection.php)
 * [Oracle DSN](http://www.php.net/manual/en/ref.pdo-oci.connection.php)
 * [MSSQL DSN](http://www.php.net/manual/en/ref.pdo-dblib.connection.php)

Note that some database (e.g. PostgreSQL) specify username and password as part of the DSN while the others specify user and password separately.

#### `<user>` and `<password>` ####

Specifies credentials for databases that specify username and password separately (e.g. MySQL, Oracle).

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <dsn>mysql:host=localhost;dbname=bookstore</dsn>
     <user>test</user>
     <password>testpass</password>
```

#### `<options>` ####

Specify any options which _must_ be specified when the PDO connection is created.  For example, the ATTR_PERSISTENT option must be specified at object creation time.

See the [PDO documentation](http://www.php.net/pdo) for more details.

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <!-- ... -->
     <options>
      <option id="ATTR_PERSISTENT">false</option>
     </options>
```

#### `<attributes>` ####

`<attributes>` are similar to `<options>`; the difference is that options specified in `<attributes>` are set after the PDO object has been created.  These are set using the [PDO->setAttribute()](http://us.php.net/PDO-setAttribute) method.

In addition to the standard attributes that can be set on the PDO object, there are also the following Propel-specific attributes that change the behavior of the PropelPDO connection:

|Attribute constant                     | Valid Values (Default)    | Description |
|---------------------------------------|---------------------------|-----------------------------------------------------------------
| PropelPDO::PROPEL_ATTR_CACHE_PREPARES | true/false (false)        | Whether to have the PropelPDO connection cache the PDOStatement prepared statements.  This will improve performance if you are executing the same query multiple times by your script (within a single request / script run).

> Note that attributes in the XML can be specified with or without the PDO:: (or PropelPDO::) constant prefix.

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <!-- ... -->
     <attributes>
      <option id="ATTR_ERRMODE">PDO::ERRMODE_WARNING</option>
      <option id="ATTR_STATEMENT_CLASS">myPDOStatement</option>
      <option id="PROPEL_ATTR_CACHE_PREPARES">true</option>
     </attributes>
```

>**Tip**<br />If you are using MySQL and get the following error : "SQLSTATE[HY000]: General error: 2014 Cannot execute queries while other unbuffered queries are active", you can try adding the following attribute:

```xml
<option id="MYSQL_ATTR_USE_BUFFERED_QUERY">true</option>
```

####  `<settings>` ####

Settings are Propel-specific options used to further configure the connection -- or perform other user-defined initialization tasks.

Currently supported settings are:

 * charset
 * queries

##### charset #####

Specifies the character set to use.  Currently you must specify the charset in the way that is understood by your RDBMS.  Also note that not all database systems support specifying charset (e.g. SQLite must be compiled with specific charset support).  Specifying this option will likely result in an exception if your database doesn't support the specified charset.

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <!-- ... -->
     <settings>
      <setting id="charset">utf8</setting>
     </settings>
```

##### queries #####

Specifies any SQL statements to run when the database connection is initialized.  This can be used for any environment setup or db initialization you would like to perform. These statements will be executed with every Propel initialization (e.g. every PHP script load).

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <connection>
     <!-- ... -->
     <settings>
      <setting id="queries">
       <query>set search_path myschema, public</query><!-- automatically set postgresql's search_path -->
       <query>INSERT INTO BAR ('hey', 'there')</query>
      </setting>
     </settings>
```

### `<slaves>` ###

```xml
<config>
 <propel>
  <datasources>
   <datasource>
    <slaves>
```

The `<slaves>` tag groups lists slave `<connection>` elements which provide support for configuring slave db servers -- when using Propel in a master-slave replication environment. See the [Master-Slave documentation](../cookbook/replication.html) for more information.  The nested `<connection>` elements are configured the same way as the top-level `<connection>` element is configured.

### `<profiler>` ###

The optional `<profiler>` element may be provided to customize the profiler when using a `ProfilerConnectionWrapper` connection class. See the [Logging documentation](../documentation/08-logging) for more information on configuring the profiler.
