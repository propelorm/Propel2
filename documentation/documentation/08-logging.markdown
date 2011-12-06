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

Using these parameters, Propel creates a `file` Log handler in the background, and keeps it for later use. You can do the same at runtime using the `Propel::setLogger()` method:

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

By default, Propel uses the `Propel\Runtime\Connection\ConnectionWrapper` class for database connections. This class, which wraps around PHP's `PDO`, offers a debug mode to keep track of all the database activity, including all the executed queries.

### Enabling The Debug Mode ###

The debug mode is disabled by default, but you can enable it at runtime as follows:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$con->useDebug(true);
{% endhighlight %}

You can also disable the debug mode at runtime, by calling `PropelPDO::useDebug(false)`. Using this method, you can choose to enable the debug mode for only one particular query, or for all queries.

Alternatively, you can ask Propel to always enable the debug mode for a particular connection by using the `Propel\Runtime\Connection\DebugPDO` class instead of the default `ConnectionWrapper` class. This is accomplished in the `runtime-conf.xml` file, in the `<classname>` tag of a given datasource connection (see the [runtime configuration reference]() for more details).

{% highlight xml %}
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <!-- the classname that Propel should instantiate, must be PropelPDO subclass -->
          <classname>Propel\Runtime\Connection\DebugPDO</classname>
{% endhighlight %}

>**Tip**<br />You can use your own connection class there, but make sure that it implements `Propel\Runtime\Connection\ConnectionInterface`. Propel requires certain fixes to PDO API that are provided by this interface.

### Counting Queries ###

In debug mode, the connection class keeps track of the number of queries that are executed. Use `ConnectionWrapper::getQueryCount()` to retrieve this number:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$myObjs = MyObjPeer::doSelect(new Criteria(), $con);
echo $con->getQueryCount();  // 1
{% endhighlight %}

>**Tip**<br />You cannot use persistent connections if you want the query count to work. Actually, the debug mode in general requires that you don't use persistent connections in order for it to correctly log bound values and count executed statements.

### Retrieving The Latest Executed Query ###

For debugging purposes, you may need the SQL code of the latest executed query. It is available at runtime in debug mode using `ConnectionWrapper::getLastExecutedQuery()`, as follows:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$myObjs = MyObjPeer::doSelect(new Criteria(), $con);
echo $con->getLastExecutedQuery(); // 'SELECT * FROM my_obj';
{% endhighlight %}

>**Tip**<br/>You can also get a decent SQL representation of the criteria being used in a SELECT query by using the `Criteria->toString()` method.

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

If you use both the debug mode and a logger, then Propel logs automatically all executed queries in the provided log handler (or the `propel.log` file if no custom handler is defined):

{% highlight text %}
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO publisher (`ID`,`NAME`) VALUES (NULL,'William Morrow')
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO author (`ID`,`FIRST_NAME`,`LAST_NAME`) VALUES (NULL,'J.K.','Rowling')
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO book (`ID`,`TITLE`,`ISBN`,`PRICE`,`PUBLISHER_ID`,`AUTHOR_ID`) VALUES (NULL,'Harry Potter and the Order of the Phoenix','043935806X',10.99,53,58)
Oct 04 00:00:18 propel-bookstore [debug] INSERT INTO review (`ID`,`REVIEWED_BY`,`REVIEW_DATE`,`RECOMMENDED`,`BOOK_ID`) VALUES (NULL,'Washington Post','2009-10-04',1,52)
...
Oct 04 00:00:18 propel-bookstore [debug] SELECT bookstore_employee_account.EMPLOYEE_ID, bookstore_employee_account.LOGIN FROM `bookstore_employee_account` WHERE bookstore_employee_account.EMPLOYEE_ID=25
{% endhighlight %}

By default, Propel logs all SQL queries, together with the date of the query and the name of the connection.

### Adding Profiler Information ###

In addition to the executed queries, you can ask Propel to log the execution time for each query, the memory consumption, and more. To enable profiling, change the connection class name to `Propel\Runtime\Connection\ProfilerConnectionWrapper` in the `runtime-conf.xml`, as follows:

{% highlight xml %}
<?xml version="1.0"?>
<config>
  <propel>
    <datasources default="bookstore">
      <datasource id="bookstore">
        <adapter>sqlite</adapter>
        <connection>
          <classname>Propel\Runtime\Connection\ProfilerConnectionWrapper</classname>
{% endhighlight %}

The logged queries now contain profiling information for each query:

{% highlight text %}
Feb 23 16:41:04 Propel [debug] time: 0.000 sec | mem: 1.4 MB | SET NAMES 'utf8'
Feb 23 16:41:04 Propel [debug] time: 0.002 sec | mem: 1.6 MB | SELECT COUNT(tags.NAME) FROM tags WHERE tags.IMAGEID = 12
Feb 23 16:41:04 Propel [debug] time: 0.012 sec | mem: 2.4 MB | SELECT tags.NAME, image.FILENAME FROM tags LEFT JOIN image ON tags.IMAGEID = image.ID WHERE image.ID = 12
{% endhighlight %}

### Tweaking the Profiling Information Using Configuration ###

You can tweak the type and formatting of the profiler information prefix using the `<profiler>` tag in the `runtime-conf.xml` file:

{% highlight xml %}
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
{% endhighlight %}

The `slowTreshold` parameter specifies when the profiler considers a query slow. By default, its value is of 0.1s, or 100ms.

>**Tip**<br/>You can choose to only log slow queries when using the `ProfilerConnectionWrapper` connection class. Just add a `isSlowOnly` attribute to the connection in `runtime-conf.xml`, as follows:

{% highlight xml %}
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
{% endhighlight %}

The supported `details` include `<time>` (the time used by the RDBMS to execute the SQL request), `<mem>` (the memory used so far by the PHP script), `<memDelta>` (the memory used specifically for this query), and `<memPeak>` (the peak memory used by the PHP script). For each detail, you can modify the formatting by setting any of the `name`, `precision`, and `pad` attributes.

Each detail is separated from its name using the `innerGlue` string, and the details are separated from each other using the `outerGlue` string. Modify the corresponding attributes at will.

### Tweaking the Profiling Information At Runtime ###

All the settings described above act on a `Propel\Runtime\Util\Profiler` instance. Instead of using configuration, you can modify the profiler service settings at runtime using the `StandardServiceContainer` instance:

{% highlight php %}
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
{% endhighlight %}

### Changing the Log Level ###

By default the connection log messages are logged at the `Propel::LOG_DEBUG` level. This can be changed by calling the `setLogLevel()` method on the connection object:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$con->setLogLevel(Propel::LOG_INFO);
{% endhighlight %}

Now all queries and bind param values will be logged at the INFO level.

### Configuring a Different Full Query Logger ###

By default the `ConnectionWrapper` connection logs queries and binds param values using the `Propel::log()` static method. As explained above, this method uses the log storage configured by the `<log>` tag in the `runtime-conf.xml` file.

If you would like the queries to be logged using a different logger (e.g. to a different file, or with different ident, etc.), you can set a logger explicitly on the connection at runtime, using `Propel::setLogger()`:

{% highlight php %}
<?php
$con = Propel::getConnection(MyObjPeer::DATABASE_NAME);
$logger = Log::factory('syslog', LOG_LOCAL0, 'propel', array(), PEAR_LOG_INFO);
$con->setLogger($logger);
{% endhighlight %}

This will not affect the general Propel logging, but only the full query logging. That way you can log the Propel error and warnings in one file, and the SQL queries in another file.
