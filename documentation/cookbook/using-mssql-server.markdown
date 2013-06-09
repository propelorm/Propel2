---
layout: documentation
title: Using Propel With MSSQL Server
---

# Using Propel With MSSQL Server #

Sybase ASE and MSSQL server 2005 and above are both supported in Propel 1.5.x. There are several different options available for PDO drivers in both Windows and Linux.

## Windows ##

Windows has 4 different driver implementations that could be used. In order of support: `pdo_sqlsrv`, `pdo_sybase`, `pdo_mssql`, and `pdo_odbc`.

`pdo_dblib` can be built against either FreeTDS (`pdo_sybase`) or MS SQL Server (`pdo_mssql`) dblib implementations. The driver is not a complete PDO driver implementation and lacks support for transactions or driver attributes.

### pdo_sqlsrv ###

This is a driver [released in August 2010 by Microsoft](http://blogs.msdn.com/b/sqlphp/archive/2010/08/04/microsoft-drivers-for-php-for-sql-server-2-0-released.aspx) for interfacing with MS SQL Server. This is a very complete PDO driver implementation and will provide the best results when using Propel on the Windows platform. It does not return blobs as a resource right now but this feature will hopefully be added in a future release. There is also a bug with setting blob values to null that Propel has a workaround for.

Sample dsn's for pdo_sqlsrv:

```xml
<dsn>sqlsrv:server=localhost\SQLEXPRESS;Database=propel</dsn>
<dsn>sqlsrv:server=localhost\SQLEXPRESS,1433;Database=propel</dsn>
<dsn>sqlsrv:server=localhost,1433;Database=propel</dsn>
```

Sample runtime-conf.xml for pdo_sqlsrv:

```xml
<datasource id="bookstore">
  <adapter>sqlsrv</adapter>
  <connection>
    <classname>DebugPDO</classname>
    <dsn>sqlsrv:server=localhost,1433;Database=propel</dsn>
    <user>username</user>
    <password>password</password>
  </connection>
</datasource>
```

Sample build.properties for pdo_sqlsrv:

```ini
propel.database = sqlsrv
propel.database.url = sqlsrv:server=127.0.0.1,1433;Database=propel
```

### pdo_sybase ###

When built against FreeTDS dblib it will be called `pdo_sybase`. This requires properly setting up the FreeTDS `freetds.conf` and `locales.conf`. There is a workaround for the lack of transactions support in the `pdo_dblib` driver by using `MssqlDebugPDO` or `MssqlPropelPDO` classes.

c:\freetds.conf

```ini
[global]
  client charset = UTF-8
  tds version = 8.0
  text size = 20971520
```

c:\locales.conf

```ini
[default]
  date format = %Y-%m-%d %H:%M:%S.%z
```

Sample dsn's for pdo_sybase:

```xml
<dsn>sybase:host=localhost\SQLEXPRESS;dbname=propel</dsn>
<dsn>sybase:host=localhost\SQLEXPRESS:1433;dbname=propel</dsn>
<dsn>sybase:host=localhost:1433;dbname=propel</dsn>
```

Sample `runtime-conf.xml` for pdo_sybase:

```xml
<datasource id="bookstore">
  <adapter>mssql</adapter>
  <connection>
    <classname>MssqlDebugPDO</classname>
    <dsn>sybase:host=localhost:1433;dbname=propel</dsn>
    <user>username</user>
    <password>password</password>
  </connection>
</datasource>
```

Sample `build.properties` for `pdo_sybase`:

```ini
propel.database = mssql
propel.database.url = sybase:host=localhost:1433;dbname=propel
```

### pdo_mssql ###

When built against MS SQL Server dblib the driver will be called `pdo_mssql`. It is not recommended to use the `pdo_mssql` driver because it strips blobs of single quotes when retrieving from the database and will not return blobs or clobs longer that 8192 characters. The dsn differs from `pdo_sybase` in that it uses a comma between the server and port number instead of a colon and mssql instead of sybase for the driver name.

Sample dsn's for `pdo_mssql`:

```xml
<dsn>mssql:host=localhost\SQLEXPRESS;dbname=propel</dsn>
<dsn>mssql:host=localhost\SQLEXPRESS,1433;dbname=propel</dsn>
<dsn>mssql:host=localhost,1433;dbname=propel</dsn>
```

### pdo_odbc ###

Currently `pdo_odbc` cannot be used to access MSSQL with propel because of a [long standing bug](http://connect.microsoft.com/SQLServer/feedback/details/521409/odbc-client-mssql-does-not-work-with-bound-parameters-in-subquery) with the MS SQL Server ODBC Client. Last update on 8/3/2010 was that it would be resolved in a future release of the SQL Server Native Access Client. This bug is related to two php bugs ([Bug #44643](http://bugs.php.net/bug.php?id=44643) and [Bug #36561](http://bugs.php.net/bug.php?id=36561))

## Linux ##

Linux has 2 driver implementations that could be used: `pdo_dblib`, and `pdo_obdc`.

### pdo_dblib ###

`pdo_dblib` is built against the FreeTDS dblib implementation. The driver is not a complete PDO driver implementation and lacks support for transactions or driver attributes. This requires properly setting up the FreeTDS `freetds.conf` and `locales.conf`. There is a workaround for the lack of transactions support in the `pdo_dblib` driver by using `MssqlDebugPDO` or `MssqlPropelPDO` classes.

Redhat: `/etc/freetds.conf`
Ubuntu: `/etc/freetds/freetds.conf`

```ini
[global]
  client charset = UTF-8
  tds version = 8.0
  text size = 20971520
```

Redhat: `/etc/locales.conf`
Ubuntu: `/etc/freetds/locales.conf`

```ini
[default]
  date format = %Y-%m-%d %H:%M:%S.%z
```

Sample dsn's for `pdo_dblib`:

```xml
<dsn>dblib:host=localhost\SQLEXPRESS;dbname=propel</dsn>
<dsn>dblib:host=localhost\SQLEXPRESS:1433;dbname=propel</dsn>
<dsn>dblib:host=localhost:1433;dbname=propel</dsn>
```

Sample `runtime-conf.xml` for `pdo_dblib`:

```xml
<datasource id="bookstore">
  <adapter>mssql</adapter>
  <connection>
    <classname>MssqlDebugPDO</classname>
    <dsn>dblib:host=localhost:1433;dbname=propel</dsn>
    <user>username</user>
    <password>password</password>
  </connection>
</datasource>
```

Sample `build.properties` for `pdo_dblib`:

```ini
propel.database = mssql
propel.database.url = dblib:host=localhost:1433;dbname=propel
```

### pdo_odbc ###

`pdo_odbc` using UnixODBC and FreeTDS. This should be supported in propel but with ubuntu 10.04 and php 5.2.x any statement binding causes apache to segfault so I have not been able to test it further. If anyone has any additional experience with this please post information to the propel development group. If you would like to experiment there are some instructions you can follow [here](http://kitserve.org.uk/content/accessing-microsoft-sql-server-php-ubuntu-using-pdo-odbc-and-freetds) for getting it setup on ubuntu.
