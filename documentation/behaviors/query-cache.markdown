---
layout: documentation
title: Query Cache Behavior
---

# Query Cache Behavior #

The `query_cache` behavior gives a speed boost to Propel queries by caching the transformation of a PHP Query object into reusable SQL code.

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `query_cache` behavior to a table:
```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="query_cache" />
</table>
```

After you rebuild your model, all the queries on this object can now be cached. To trigger the query cache on a particular query, just give it a query key using the `setQueryKey()` method. The key is a unique identifier that you can choose, later used for cache lookups:

```php
<?php
$title = 'War And Peace';
$books = BookQuery::create()
  ->setQueryKey('search book by title')
  ->filterByTitle($title)
  ->findOne();
```

The first time Propel executes the termination method, it computes the SQL translation of the Query object and stores it into a cache backend (APC by default). Next time you run the same query, it executes faster, even with different parameters:

```php
<?php
$title = 'Anna Karenina';
$books = BookQuery::create()
  ->setQueryKey('search book by title')
  ->filterByTitle($title)
  ->findOne();
```

>**Tip**<br />The more complex the query, the greater the boost you get from the query cache behavior.

## Parameters ##

You can change the cache backend and the cache lifetime (in seconds) by setting the `backend` and `lifetime` parameters:

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="query_cache">
    <parameter name="backend" value="custom" />
    <parameter name="lifetime" value="600" />
  </behavior>
</table>
```

To implement a custom cache backend, just override the generated `cacheContains()`, `cacheFetch()` and `cacheStore()` methods in the Query object. For instance, to implement query cache using Zend_Cache and memcached, try the following:

```php
<?php
class BookQuery extends BaseBookQuery
{
  public function cacheContains($key)
  {
    return $this->getCacheBackend()->test($key);
  }

  public function cacheFetch($key)
  {
    return $this->getCacheBackend()->load($key);
  }

  public function cacheStore($key, $value)
  {
    return $this->getCacheBackend()->save($key, $value);
  }

  protected function getCacheBackend()
  {
    if (self::$cacheBackend ### null) {
      $frontendOptions = array(
         'lifetime' => 7200,
         'automatic_serialization' => true
      );
      $backendOptions = array(
        'servers' => array(
          array(
            'host' => 'localhost',
            'port' => 11211,
            'persistent' => true
          )
        )
      );
      self::$cacheBackend = Zend_Cache::factory('Core', 'Memcached', $frontendOptions, $backendOptions);
    }

    return self::$cacheBackend;
  }
}
```
