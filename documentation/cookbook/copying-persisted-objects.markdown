---
layout: documentation
title: Copying Persisted Objects
---

# Copying Persisted Objects #

Propel provides the `copy()` method to perform copies of mapped row in the database.  Note that Propel does _not_ override the `__clone()` method; this allows you to create local duplicates of objects that map to the same persisted database row (should you need to do this).

The `copy()` method by default performs shallow copies, meaning that any foreign key references will remain the same.

```php
<?php

$a = new Author();
$a->setFirstName("Aldous");
$a->setLastName("Huxley");

$p = new Publisher();
$p->setName("Harper");

$b = new Book();
$b->setTitle("Brave New World");
$b->setPublisher($p);
$b->setAuthor($a);

$b->save(); // so that auto-increment IDs are created

$bcopy = $b->copy();
var_export($bcopy->getId() == $b->getId()); // FALSE
var_export($bcopy->getAuthorId() == $b->getAuthorId()); // TRUE
var_export($bcopy->getAuthor() == $b->getAuthor()); // TRUE
```

## Deep Copies ##

By calling `copy()` with a `TRUE` parameter, Propel will create a deep copy of the object; this means that any related objects will also be copied.

To continue with example from above:

```php
<?php

$bdeep = $b->copy(true);
var_export($bcopy->getId() == $b->getId()); // FALSE
var_export($bcopy->getAuthorId() == $b->getAuthorId()); // FALSE
var_export($bcopy->getAuthor() == $b->getAuthor()); // FALSE
```
