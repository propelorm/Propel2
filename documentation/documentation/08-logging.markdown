---
layout: documentation
title: Logging And Debugging
---

# Logging And Debugging #

Propel provides tools to monitor and debug your model. Whether you need to check the SQL code of slow queries, or to look for error messages previously thrown, Propel is your best friend for finding and fixing problems.

## Propel Logs ##

Propel uses the logging facility configured in `runtime-conf.xml` to record errors, warnings, and debug information.

By default Propel will attempt to use the Log framework that is distributed with PEAR. If you are not familiar with it, check its [online documentation](http://www.indelible.org/php/Log/guide.html). It is also easy to configure Propel to use your own logging framework -- or none at all.

### Logger Configuration ###

The Propel log handler is configured in the `<log>` section of your project's `runtime-conf.xml` file. Here is the accepted format for this section with the default values that Propel uses:

{% highlight xml %}
<?xml version="1.0" encoding="ISO-8859-1"?>
<config>
  <log>
    <type>file</type>
    <name>./propel.log</name>
    <ident>propel</ident>
    <level>7</level> <!-- PEAR_LOG_DEBUG -->
    <conf></conf>
  </log>
  <propel>
    ...
  </propel>
</config>
{% endhighlight %}

Using these parameters, Propel creates a `file` Log handler in the background, and keeps it for later use:

{% highlight php %}
<?php
Propel::setLogger(Log::singleton($type = 'file', $name = './propel.log', $ident = 'propel', $conf = array(), $level = PEAR_LOG_DEBUG));
{% endhighlight %}

The meaning of each of the `<log>` nested elements may vary, depending on which log handler you are using. Most common accepted logger types are `file`, `console`, `syslog`, `display`, `error_log`, `firebug`, and `sqlite`. Refer to the [PEAR::Log](http://www.indelible.org/php/Log/guide.html#standard-log-handlers) documentation for more details on log handlers configuration and options.

Note that the `<level>` tag needs to correspond to the integer represented by one of the `PEAR_LOG_*` constants:

|Constant           |Value  |Description
|-------------------|-------|-------------------------
|PEAR_LOG_EMERG     |0      |System is unusable
|PEAR_LOG_ALERT     |1      |Immediate action required
|PEAR_LOG_CRIT      |2      |Critical conditions
|PEAR_LOG_ERR       |3      |Error conditions
|PEAR_LOG_WARNING   |4      |Warning conditions
|PEAR_LOG_NOTICE    |5      |Normal but significant
|PEAR_LOG_INFO      |6      |Informational
|PEAR_LOG_DEBUG     |7      |Debug-level messages

### Logging Messages ###

Use the static `Propel::log()` method to log a message using the configured log handler:

{% highlight php %}
<?php
$myObj = new MyObj();
$myObj->setName('foo');
Propel::log('uh-oh, something went wrong with ' . $myObj->getName(), Propel::LOG_ERR);
{% endhighlight %}

You can log your own messages from the generated model objects by using their `log()` method, inherited from `BaseObject`:

{% highlight php %}
<?php
$myObj = new MyObj();
$myObj->log('uh-oh, something went wrong', Propel::LOG_ERR);
{% endhighlight %}

The log messages will show up in the log handler defined in `runtime-conf.xml` (`propel.log` file by default) as follows:

{% highlight text %}
Oct 04 00:00:18 [error] uh-oh, something went wrong with foo
Oct 04 00:00:18 [error] MyObj: uh-oh, something went wrong
{% endhighlight %}

>**Tip**<br />All serious errors coming from the Propel core do not only issue a log message, they are also thrown as `PropelException`.

### Using An Alternative PEAR Log Handler ###

In many cases you may wish to integrate Propel's logging facility with the rest of your web application. In `runtime-conf.xml`, you can customize a different PEAR logger. Here are a few examples:

_Example 1:_ Using `display` handler (for output to HTML)

{% highlight xml %}
 <log>
  <type>display</type>
  <level>6</level> <!-- PEAR_LOG_INFO -->
 </log>
{% endhighlight %}

_Example 2:_ Using `syslog` handler

{% highlight xml %}
 <log>
  <type>syslog</type>
  <name>8</name> <!-- LOG_USER -->
  <ident>propel</ident>
  <level>6</level>
 </log>
{% endhighlight %}

### Using A Custom Logger ###

If you omit the `<log>` section of your `runtime-conf.xml`, then Propel will not setup _any_ logging for you. In this case, you can set a custom logging facility and pass it to Propel at runtime.

Here's an example of how you could configure your own logger and then set Propel to use it:

{% highlight php %}
<?php
require_once 'MyLogger.php';
$logger = new MyLogger();
require_once 'propel/Propel.php';
Propel::setLogger($logger);
Propel::init('/path/to/runtime-conf.php');
{% endhighlight %}

Your custom logger could be any object that implements a basic logger interface. Check the `BasicLogger` interface provided with the Propel runtime to see the methods that a logger must implement in order to be compatible with Propel. You do not actually have to implement this interface, but all the specified methods must be present in your container.

Let's see an example of a simple log container suitable for use with Propel:

{% highlight php %}
<?php
class MyLogger implements BasicLogger
{
  public function emergency($m)
  {
    $this->log($m, Propel::LOG_EMERG);
  }
  public function alert($m)
  {
    $this->log($m, Propel::LOG_ALERT);
  }
  public function crit($m)
  {
    $this->log($m, Propel::LOG_CRIT);
  }
  public function err($m)
  {
    $this->log($m, Propel::LOG_ERR);
  }
  public function warning($m)
  {
    $this->log($m, Propel::LOG_WARNING);
  }
  public function notice($m)
  {
    $this->log($m, Propel::LOG_NOTICE);
  }
  public function info($m)
  {
    $this->log($m, Propel::LOG_INFO);
  }
  public function debug($m)
  {
    $this->log($m, Propel::LOG_DEBUG);
  }

  public function log($message, $priority)
  {
    $color = $this->priorityToColor($priority);
    echo '<p style="color: ' . $color . '">$message</p>';
  }

  private function priorityToColor($priority)
  {
     switch($priority) {
       case Propel::LOG_EMERG:
       case Propel::LOG_ALERT:
       case Propel::LOG_CRIT:
       case Propel::LOG_ERR:
         return 'red';
         break;
       case Propel::LOG_WARNING:
         return 'orange';
         break;
       case Propel::LOG_NOTICE:
         return 'green';
         break;
       case Propel::LOG_INFO:
         return 'blue';
         break;
       case Propel::LOG_DEBUG:
         return 'grey';
         break;
     }
  }
}
{% endhighlight %}

>**Tip**<br />There is also a bundled `MojaviLogAdapter` class which allows you to use a Mojavi logger with Propel.

## Debugging Database Activity ##

By default, Propel uses `PropelPDO` for database connections. This class, which extends PHP's `PDO`, offers a debug mode to keep track of all the database activity, including all the executed queries.

### Enabling The Debug Mode ###

The debug mode is disabled by default, but you can enable it at runtime as follows:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$con->useDebug(true);
{% endhighlight %}

You can also disable the debug mode at runtime, by calling `PropelPDO::useDebug(false)`. Using this method, you can choose to enable the debug mode for only one particular query, or for all queries.

Alternatively, you can ask Propel to always enable the debug mode for a particular connection by using the `DebugPDO` class instead of the default `PropelPDO` class. This is accomplished in the `runtime-conf.xml` file, in the `<classname>` tag of a given datasource connection (see the [runtime configuration reference]() for more details).

{% highlight xml %}
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <!-- the classname that Propel should instantiate, must be PropelPDO subclass -->
          <classname>DebugPDO</classname>
{% endhighlight %}

>**Tip**<br />You can use your own connection class there, but make sure that it extends `PropelPDO` and not only `PDO`. Propel requires certain fixes to PDO API that are provided by `PropelPDO`.

### Counting Queries ###

In debug mode, `PropelPDO` keeps track of the number of queries that are executed. Use `PropelPDO::getQueryCount()` to retrieve this number:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$myObjs = MyObjPeer::doSelect(new Criteria(), $con);
echo $con->getQueryCount();  // 1
{% endhighlight %}

Tip: You cannot use persistent connections if you want the query count to work. Actually, the debug mode in general requires that you don't use persistent connections in order for it to correctly log bound values and count executed statements.

### Retrieving The Latest Executed Query ###

For debugging purposes, you may need the SQL code of the latest executed query. It is available at runtime in debug mode using `PropelPDO::getLastExecutedQuery()`, as follows:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$myObjs = MyObjPeer::doSelect(new Criteria(), $con);
echo $con->getLastExecutedQuery(); // 'SELECT * FROM my_obj';
{% endhighlight %}

Tip: You can also get a decent SQL representation of the criteria being used in a SELECT query by using the `Criteria->toString()` method.

Propel also keeps track of the queries executed directly on the connection object, and displays the bound values correctly.

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$stmt = $con->prepare('SELECT * FROM my_obj WHERE name = :p1');
$stmt->bindValue(':p1', 'foo');
$stmt->execute();
echo $con->getLastExecutedQuery(); // 'SELECT * FROM my_obj where name = "foo"';
{% endhighlight %}

>**Tip**<br />The debug mode is intended for development use only. Do not use it in production environment, it logs too much information for a production server, and adds a small overhead to the database queries.

## Full Query Logging ##

The combination of the debug mode and a logging facility provides a powerful debugging tool named _full query logging_. If you have properly configured a log handler, enabling the debug mode (or using `DebugPDO`) automatically logs the executed queries into Propel's default log file:

{% highlight text %}
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO publisher (`ID`,`NAME`) VALUES (NULL,'William Morrow')
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO author (`ID`,`FIRST_NAME`,`LAST_NAME`) VALUES (NULL,'J.K.','Rowling')
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO book (`ID`,`TITLE`,`ISBN`,`PRICE`,`PUBLISHER_ID`,`AUTHOR_ID`) VALUES (NULL,'Harry Potter and the Order of the Phoenix','043935806X',10.99,53,58)
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO review (`ID`,`REVIEWED_BY`,`REVIEW_DATE`,`RECOMMENDED`,`BOOK_ID`) VALUES (NULL,'Washington Post','2009-10-04',1,52)
...
Oct 04 00:00:18 propel-bookstore [debug] SELECT bookstore_employee_account.EMPLOYEE_ID, bookstore_employee_account.LOGIN FROM `bookstore_employee_account` WHERE bookstore_employee_account.EMPLOYEE_ID=25
{% endhighlight %}

By default, Propel logs all SQL queries, together with the date of the query and the name of the connection.

### Setting The Data To Log ###

The full query logging feature can be configured either in the `runtime-conf.xml` configuration file, or using the runtime configuration API.

In `runtime-conf.xml`, tweak the feature by adding a `<debugpdo>` tag under `<propel>`:

{% highlight xml %}
<?xml version="1.0"?>
<config>
  <log>
    ...
  </log>
  <propel>
    <datasources default="bookstore">
      ...
    </datasources>
    <debugpdo>
      <logging>
        <details>
          <method>
            <enabled>true</enabled>
          </method>
          <time>
            <enabled>true</enabled>
          </time>
          <mem>
            <enabled>true</enabled>
          </mem>
        </details>
      </logging>
    </debugpdo>
  </propel>
</config>
{% endhighlight %}

To accomplish the same configuration as above at runtime, change the settings in your main include file, after `Propel::init()`, as follows:

{% highlight php %}
<?php
$config = Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT);
$config->setParameter('debugpdo.logging.details.method.enabled', true);
$config->setParameter('debugpdo.logging.details.time.enabled', true);
$config->setParameter('debugpdo.logging.details.mem.enabled', true);
{% endhighlight %}

Let's see a few of the provided parameters.

### Logging More Connection Messages ###

`PropelPDO` can log queries, but also connection events (open and close), and transaction events (begin, commit and rollback). Since Propel can emulate nested transactions, you may need to know when an actual `COMMIT` or `ROLLBACK` is issued.

To extend which methods of `PropelPDO` do log messages in debug mode, customize the `'debugpdo.logging.methods'` parameter, as follows:

{% highlight php %}
<?php
$allMethods = array(
  'PropelPDO::__construct',       // logs connection opening
  'PropelPDO::__destruct',        // logs connection close
  'PropelPDO::exec',              // logs a query
  'PropelPDO::query',             // logs a query
  'PropelPDO::prepare',           // logs the preparation of a statement
  'PropelPDO::beginTransaction',  // logs a transaction begin
  'PropelPDO::commit',            // logs a transaction commit
  'PropelPDO::rollBack',          // logs a transaction rollBack (watch out for the capital 'B')
  'DebugPDOStatement::execute',   // logs a query from a prepared statement
  'DebugPDOStatement::bindValue'  // logs the value and type for each bind
);
$config = Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT);
$config->setParameter('debugpdo.logging.methods', $allMethods, false);
{% endhighlight %}

By default, only the messages coming from `PropelPDO::exec`, `PropelPDO::query`, and `DebugPDOStatement::execute` are logged.

### Logging Execution Time And Memory ###

In debug mode, Propel counts the time and memory necessary for each database query. This very valuable data can be added to the log messages on demand, by adding the following configuration:

{% highlight php %}
<?php
$config = Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT);
$config->setParameter('debugpdo.logging.details.time.enabled', true);
$config->setParameter('debugpdo.logging.details.mem.enabled', true);
{% endhighlight %}

Enabling the options shown above, you get log output along the lines of:

{% highlight text %}
Feb 23 16:41:04 Propel [debug] time: 0.000 sec | mem: 1.4 MB | SET NAMES 'utf8'
Feb 23 16:41:04 Propel [debug] time: 0.002 sec | mem: 1.6 MB | SELECT COUNT(tags.NAME) FROM tags WHERE tags.IMAGEID = 12
Feb 23 16:41:04 Propel [debug] time: 0.012 sec | mem: 2.4 MB | SELECT tags.NAME, image.FILENAME FROM tags LEFT JOIN image ON tags.IMAGEID = image.ID WHERE image.ID = 12
{% endhighlight %}

The order in which the logging details are enabled is significant, since it determines the order in which they will appear in the log file.

### Complete List Of Logging Options ###

The following settings can be customized at runtime or in the configuration file:

|Parameter                                      |Default    |Meaning
|-----------------------------------------------|-----------|---------------------------------------------------------------------------------
|`debugpdo.logging.innerglue`                   |":"        |String to use for combining the title of a detail and its value
|`debugpdo.logging.outerglue`                   |"&#124;"   |String to use for combining details together on a log line
|`debugpdo.logging.realmemoryusage`             |`false`    |Parameter to [memory_get_usage()](http://www.php.net/manual/en/function.memory-get-usage.php) and [memory_get_peak_usage()](http://www.php.net/manual/en/function.memory-get-peak-usage.php) calls
|`debugpdo.logging.methods`                     |`array`    |An array of method names (`Class::method`) to be included in method call logging
|`debugpdo.logging.details.slow.enabled`        |`false`    |Enables flagging of slow method calls
|`debugpdo.logging.details.slow.threshold`      |`0.1`      |Method calls taking more seconds than this threshold are considered slow
|`debugpdo.logging.details.time.enabled`        |`false`    |Enables logging of method execution times
|`debugpdo.logging.details.time.precision`      |`3`        |Determines the precision of the execution time logging
|`debugpdo.logging.details.time.pad`            |`10`       |How much horizontal space to reserve for the execution time on a log line
|`debugpdo.logging.details.mem.enabled`         |`false`    |Enables logging of the instantaneous PHP memory consumption
|`debugpdo.logging.details.mem.precision`       |`1`        |Determines the precision of the memory consumption logging
|`debugpdo.logging.details.mem.pad`             |`9`        |How much horizontal space to reserve for the memory consumption on a log line
|`debugpdo.logging.details.memdelta.enabled`    |`false`    |Enables logging differences in memory consumption before and after the method call
|`debugpdo.logging.details.memdelta.precision`  |`1`        |Determines the precision of the memory difference logging
|`debugpdo.logging.details.memdelta.pad`        |`10`       |How much horizontal space to reserve for the memory difference on a log line
|`debugpdo.logging.details.mempeak.enabled`     |`false`    |Enables logging the peak memory consumption thus far by the currently executing PHP script
|`debugpdo.logging.details.mempeak.precision`   |`1`        |Determines the precision of the memory peak logging
|`debugpdo.logging.details.mempeak.pad`         |`9`        |How much horizontal space to reserve for the memory peak on a log line
|`debugpdo.logging.details.querycount.enabled`  |`false`    |Enables logging of the number of queries performed by the DebugPDO instance thus far
|`debugpdo.logging.details.querycount.pad`      |`2`        |How much horizontal space to reserve for the query count on a log line
|`debugpdo.logging.details.method.enabled`      |`false`    |Enables logging of the name of the method call
|`debugpdo.logging.details.method.pad`          |`28`       |How much horizontal space to reserve for the method name on a log line

### Changing the Log Level ###

By default the connection log messages are logged at the `Propel::LOG_DEBUG` level. This can be changed by calling the `setLogLevel()` method on the connection object:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$con->setLogLevel(Propel::LOG_INFO);
{% endhighlight %}

Now all queries and bind param values will be logged at the INFO level.

### Configuring a Different Full Query Logger ###

By default the `PropelPDO` connection logs queries and binds param values using the `Propel::log()` static method. As explained above, this method uses the log storage configured by the `<log>` tag in the `runtime-conf.xml` file.

If you would like the queries to be logged using a different logger (e.g. to a different file, or with different ident, etc.), you can set a logger explicitly on the connection at runtime, using `Propel::setLogger()`:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$logger = Log::factory('syslog', LOG_LOCAL0, 'propel', array(), PEAR_LOG_INFO);
$con->setLogger($logger);
{% endhighlight %}

This will not affect the general Propel logging, but only the full query logging. That way you can log the Propel error and warnings in one file, and the SQL queries in another file.
