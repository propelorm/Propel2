---
layout: documentation
title: Logging And Debugging
---

# Logging And Debugging #

Propel provides tools to monitor and debug your model. Whether you need to check the SQL code of slow queries, or to look for error messages previously thrown, Propel is your best friend for finding and fixing problems.

## Propel Logs ##

Propel uses [Monolog](https://github.com/Seldaek/monolog) to log errors, warnings, and debug information.

You can set the Propel loggers by configuration (in `runtime-conf.xml`), or directly in the service container.

### Setting a Logger Manually ###

By default, Propel uses a single logger called 'defaultLogger'. To enable logging, just set this logger using Propel's `ServiceContainer`:

```php
<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$logger = new Logger('defaultLogger');
$logger->pushHandler(new StreamHandler('php://stderr'));
Propel::getServiceContainer()->setLogger('defaultLogger', $logger);
```

Propel can use specialized loggers for each connection. For instance, you may want to log the queries for a MySQL database in a file, and the errors of the Propel runtime in another file. To do so, just set another logger using the datasource name, as follows:

```php
<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$defaultLogger = new Logger('defaultLogger');
$defaultLogger->pushHandler(new StreamHandler('/var/log/propel.log', Logger::WARNING));
Propel::getServiceContainer()->setLogger('defaultLogger', $defaultLogger);
$queryLogger = new Logger('bookstore');
$queryLogger->pushHandler(new StreamHandler('/var/log/propel_bookstore.log'));
Propel::getServiceContainer()->setLogger('bookstore', $queryLogger);
```

>**Tip**<br/>If you don't configure a logger for a particular connection, Propel falls back to the default logger.

### Logger Configuration ###

Alternatively, you can configure the logger to use via `runtime-conf.xml`, under the `<log>` section. Configuration only allows one handler per logger, and only from a subset of handler types, but this is enough for most use cases.

Here is the way to define the same loggers as in the previous snippet using configuration:

```xml
<?xml version="1.0" encoding="ISO-8859-1"?>
<config>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/propel.log</path>
      <level>300</level>
    </logger>
    <logger name="bookstore">
      <type>stream</type>
      <path>/var/log/propel_bookstore.log</path>
    </logger>
  </log>
  <propel>
    ...
  </propel>
</config>
```

The meaning of each of the `<log>` nested elements may vary, depending on which log handler you are using. Accepted handler types in configuration are `stream`, `rotating_file`, and `syslog`. Refer to the [Monolog](https://github.com/Seldaek/monolog) documentation for more details on log handlers configuration and options.

### Logging Messages ###

The service container offers a `getLogger()` method which, when called without parameter, returns the 'defaultLogger'. You can use then use the logger to add messages in the usual Monolog way.

```php
<?php
$logger = Propel::getServiceContainer()->getLogger();
$logger->addInfo('This is a message');
```

Alternatively, use the static `Propel::log()` method, passing a log level as parameter:

```php
<?php
$myObj = new MyObj();
$myObj->setName('foo');
Propel::log('uh-oh, something went wrong with ' . $myObj->getName(), Logger::ERROR);
```

You can also log your own messages from the generated model objects by using their `log()` method, inherited from `BaseObject`:

```php
<?php
$myObj = new MyObj();
$myObj->log('uh-oh, something went wrong', Logger::ERROR);
```

The log messages will show up in the log handler defined in `runtime-conf.xml` (`propel.log` file by default) as follows:

```
[2011-12-12 00:29:31] defaultLogger.ERROR: uh-oh, something went wrong with foo
[2011-12-12 00:29:31] defaultLogger.ERROR: MyObj: uh-oh, something went wrong
```

>**Tip**<br />All serious errors coming from the Propel core do not only issue a log message, they are also thrown as `PropelException`.

## Debugging Database Activity ##

By default, Propel uses the `Propel\Runtime\Connection\ConnectionWrapper` class for database connections. This class, which wraps around PHP's `PDO`, offers a debug mode to keep track of all the database activity, including all the executed queries.

### Enabling The Debug Mode ###

The debug mode is disabled by default, but you can enable it at runtime as follows:

```php
<?php
$con = Propel::getConnection(MyObjTableMap::DATABASE_NAME);
$con->useDebug(true);
```

You can also disable the debug mode at runtime, by calling `PropelPDO::useDebug(false)`. Using this method, you can choose to enable the debug mode for only one particular query, or for all queries.

Alternatively, you can ask Propel to always enable the debug mode for a particular connection by using the `Propel\Runtime\Connection\DebugPDO` class instead of the default `ConnectionWrapper` class. This is accomplished in the `runtime-conf.xml` file, in the `<classname>` tag of a given datasource connection (see the [runtime configuration reference]() for more details).

```xml
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <!-- the classname that Propel should instantiate, must be PropelPDO subclass -->
          <classname>Propel\Runtime\Connection\DebugPDO</classname>
```

>**Tip**<br />You can use your own connection class there, but make sure that it implements `Propel\Runtime\Connection\ConnectionInterface`.

### Counting Queries ###

In debug mode, the connection class keeps track of the number of queries that are executed. Use `ConnectionWrapper::getQueryCount()` to retrieve this number:

```php
<?php
$con = Propel::getConnection(MyObjTableMap::DATABASE_NAME);
$myObjs = MyObjQuery::create()->doSelect(new Criteria(), $con);
echo $con->getQueryCount();  // 1
```

>**Tip**<br />You cannot use persistent connections if you want the query count to work. Actually, the debug mode in general requires that you don't use persistent connections in order for it to correctly log bound values and count executed statements.

### Retrieving The Latest Executed Query ###

For debugging purposes, you may need the SQL code of the latest executed query. It is available at runtime in debug mode using `ConnectionWrapper::getLastExecutedQuery()`, as follows:

```php
<?php
$con = Propel::getConnection(MyObjTableMap::DATABASE_NAME);
$myObjs = MyObjTableMap::create()->doSelect(new Criteria(), $con);
echo $con->getLastExecutedQuery(); // 'SELECT * FROM my_obj';
```

>**Tip**<br/>You can also get a decent SQL representation of the criteria being used in a SELECT query by using the `Criteria->toString()` method.

Propel also keeps track of the queries executed directly on the connection object, and displays the bound values correctly.

```php
<?php
$con = Propel::getConnection(MyObjTableMap::DATABASE_NAME);
$stmt = $con->prepare('SELECT * FROM my_obj WHERE name = :p1');
$stmt->bindValue(':p1', 'foo');
$stmt->execute();
echo $con->getLastExecutedQuery(); // 'SELECT * FROM my_obj where name = "foo"';
```

>**Tip**<br />The debug mode is intended for development use only. Do not use it in production environment, it logs too much information for a production server, and adds a small overhead to the database queries.

## Full Query Logging ##

If you use both the debug mode and a logger, then Propel logs automatically all executed queries in the provided log handler:

```
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO publisher (`ID`,`NAME`) VALUES (NULL,'William Morrow')
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO author (`ID`,`FIRST_NAME`,`LAST_NAME`) VALUES (NULL,'J.K.','Rowling')
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO book (`ID`,`TITLE`,`ISBN`,`PRICE`,`PUBLISHER_ID`,`AUTHOR_ID`) VALUES (NULL,'Harry Potter and the Order of the Phoenix','043935806X',10.99,53,58)
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO review (`ID`,`REVIEWED_BY`,`REVIEW_DATE`,`RECOMMENDED`,`BOOK_ID`) VALUES (NULL,'Washington Post','2009-10-04',1,52)
...
Oct 04 00:00:18 propel-bookstore [debug] SELECT bookstore_employee_account.EMPLOYEE_ID, bookstore_employee_account.LOGIN FROM `bookstore_employee_account` WHERE bookstore_employee_account.EMPLOYEE_ID=25
```

By default, Propel logs all SQL queries, together with the date of the query and the name of the connection.

### Using a Custom Logger per Connection ###

To log SQL queries for a connection, Propel first looks for a logger named after the connection itself, and falls back to the default logger if no custom logger is defined for the connection.

Using the following config, Propel will log SQL queries from the `bookstore` datasource into a `propel_bookstore.log` file, and the SQL queries for all other datasources into a `propel.log` file.

```xml
<?xml version="1.0" encoding="ISO-8859-1"?>
<config>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/propel.log</path>
      <level>300</level>
    </logger>
    <logger name="bookstore">
      <type>stream</type>
      <path>/var/log/propel_bookstore.log</path>
    </logger>
  </log>
  <propel>
    ...
  </propel>
</config>
```

This allows you to define a different logger per connection, for instance to have different log files for each database, or to log only the queries from a MySQL database to a file while the ones from an Oracle database go into Syslog.

### Logging More Events ###

By default, the full query logger logs only executed SQL queries. But the `ConnectionWrapper` class can write into the log when most of its methods are called. To enable more methods, just set the list of logging methods to use by calling `ConnectionWrapper::setLogMethods()`, as follows:

```php
<?php
$con = Propel::getConnection(MyObjTableMap::DATABASE_NAME);
$con->setLogMethods(array(
  'exec',
  'query',
  'execute', // these first three are the default
  'beginTransaction',
  'commit',
  'rollBack',
  'bindValue'
));
```

Note that this list takes into account the methods from both `ConnectionWrapper` and `StatementWrapper`.

### Adding Profiler Information ###

In addition to the executed queries, you can ask Propel to log the execution time for each query, the memory consumption, and more. To enable profiling, change the connection class name to `Propel\Runtime\Connection\ProfilerConnectionWrapper` in the `runtime-conf.xml`, as follows:

```xml
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <classname>Propel\Runtime\Connection\ProfilerConnectionWrapper</classname>
```

The logged queries now contain profiling information for each query:

```
Feb 23 16:41:04 Propel [debug] time: 0.000 sec | mem: 1.4 MB | SET NAMES 'utf8'
Feb 23 16:41:04 Propel [debug] time: 0.002 sec | mem: 1.6 MB | SELECT COUNT(tags.NAME) FROM tags WHERE tags.IMAGEID = 12
Feb 23 16:41:04 Propel [debug] time: 0.012 sec | mem: 2.4 MB | SELECT tags.NAME, image.FILENAME FROM tags LEFT JOIN image ON tags.IMAGEID = image.ID WHERE image.ID = 12
```

### Tweaking the Profiling Information Using Configuration ###

You can tweak the type and formatting of the profiler information prefix using the `<profiler>` tag in the `runtime-conf.xml` file:

```xml
<?xml version="1.0"?>
<config>
  <profiler class="\Runtime\Runtime\Util\Profiler">
    <slowTreshold>0.1</slowTreshold>
    <details>
      <time name="Time" precision="3" pad="8" />
      <mem name="Memory" precision="3" pad="8" />
    </details>
    <innerGlue>: </innerGlue>
    <outerGlue> | </outerGlue>
  </profiler>
</config>
```

The `slowTreshold` parameter specifies when the profiler considers a query slow. By default, its value is of 0.1s, or 100ms.

>**Tip**<br/>You can choose to only log slow queries when using the `ProfilerConnectionWrapper` connection class. Just add a `isSlowOnly` attribute to the connection in `runtime-conf.xml`, as follows:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <classname>Propel\Runtime\Connection\ProfilerConnectionWrapper</classname>
          <attributes>
            <option id="isSlowOnly">true</option>
          </attributes>
```

The supported `details` include `<time>` (the time used by the RDBMS to execute the SQL request), `<mem>` (the memory used so far by the PHP script), `<memDelta>` (the memory used specifically for this query), and `<memPeak>` (the peak memory used by the PHP script). For each detail, you can modify the formatting by setting any of the `name`, `precision`, and `pad` attributes.

Each detail is separated from its name using the `innerGlue` string, and the details are separated from each other using the `outerGlue` string. Modify the corresponding attributes at will.

### Tweaking the Profiling Information At Runtime ###

All the settings described above act on a `Propel\Runtime\Util\Profiler` instance. Instead of using configuration, you can modify the profiler service settings at runtime using the `StandardServiceContainer` instance:

```php
<?php
$serviceContainer = Propel::getServiceContainer();
$serviceContainer->setProfilerClass('\Runtime\Runtime\Util\Profiler');
$serviceContainer->setProfilerConfiguration(array(
   'slowTreshold' => 0.1,
   'details' => array(
       'time' => array(
           'name' => 'Time',
           'precision' => '3',
           'pad' => '8',
        ),
        'mem' => array(
            'name' => 'Memory',
            'precision' => '3',
            'pad' => '8',
        )
   ),
   'outerGlue' => ': ',
   'innerGlue' => ' | '
));
```
