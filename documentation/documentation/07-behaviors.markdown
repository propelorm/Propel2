---
layout: documentation
title: Behaviors
---


# Behaviors #

Behaviors are a great way to package model extensions for reusability. They are powerful, versatile, fast, and help you organize your code in a better way.

## Pre and Post Hooks For `save()` And `delete()` Methods ##

The `save()` and `delete()` methods of your generated objects are easy to override. In fact, Propel looks for one of the following methods in your objects and executes them when needed:

```php
<?php
// save() hooks
preInsert()            // code executed before insertion of a new object
postInsert()           // code executed after insertion of a new object
preUpdate()            // code executed before update of an existing object
postUpdate()           // code executed after update of an existing object
preSave()              // code executed before saving an object (new or existing)
postSave()             // code executed after saving an object (new or existing)
// delete() hooks
preDelete()            // code executed before deleting an object
postDelete()           // code executed after deleting an object
```

For example, you may want to keep track of the creation date of every row in the `book` table. In order to achieve this behavior, you can add a `created_at` column to the table in `schema.xml`:

```xml
<table name="book">
  ...
  <column name="created_at" type="timestamp" />
</table>
```

Then, you can force the update of the `created_at` column before every insertion as follows:

```php
<?php
class Book extends BaseBook
{
  public function preInsert(ConnectionInterface $con = null)
  {
    $this->setCreatedAt(time());
    return true;
  }
}
```

Whenever you call `save()` on a new object, Propel now executes the `preInsert()` method on this objects and therefore update the `created_at` column:

```php
<?php
$b = new Book();
$b->setTitle('War And Peace');
$b->save();
echo $b->getCreatedAt(); // 2009-10-02 18:14:23
```

_Warning_: If you implement `preInsert()`, `preUpdate()`, `preSave()` or `preDelete()`, these methods **must return a boolean value**. Any return value other than `true` stops the action (save or delete). This is a neat way to bypass persistence on some cases, but can also create unexpected problems if you forget to return `true`.

>**Tip**<br />Since this feature adds a small overhead to write operations, you can disable it completely in your build properties by setting `propel.addHooks` to `false`.

```ini
# -------------------
#  TEMPLATE VARIABLES
# -------------------
propel.addHooks = false
```

## Introducing Behaviors ##

When several of your custom model classes end up with similar methods added, it is time to refactor the common code.

For example, you may want to add the same ability you gave to `Book` to all the other objects in your model. Let's call this the "Timestampable behavior", because then all of your rows have a timestamp marking their creation. In order to achieve this behavior, you have to repeat the same operations on every table. First, add a `created_at` column to the other tables:

```xml
<table name="book">
  ...
  <column name="created_at" type="timestamp" />
</table>
<table name="author">
  ...
  <column name="created_at" type="timestamp" />
</table>
```

Then, add a `preInsert()` hook to the object stub classes:

```php
<?php
class Book extends BaseBook
{
  public function preInsert()
  {
    $this->setCreatedAt(time());
  }
}

class Author extends BaseAuthor
{
  public function preInsert()
  {
    $this->setCreatedAt(time());
  }
}
```

Even if the code of this example is very simple, the repetition of code is already too much. Just imagine a more complex behavior, and you will understand that using the copy-and-paste technique soon leads to a maintenance nightmare.

Propel offers three ways to achieve the refactoring of the common behavior. The first one is to use a custom builder during the build process. This can work if all of your models share one single behavior. The second way is to use table inheritance. The inherited methods then offer limited capabilities. And the third way is to use Propel behaviors. This is the right way to refactor common model logic.

Behaviors are special objects that use events called during the build process to enhance the generated model classes. Behaviors can add attributes and methods to both the tableMap and model classes, they can modify the course of some of the generated methods, and they can even modify the structure of a database by adding columns or tables.

For instance, Propel bundles a behavior called `timestampable`, which does exactly the same thing as described above. But instead of adding columns and methods by hand, all you have to do is to declare it in a `<behavior>` tag in your `schema.xml`, as follows:

```xml
<table name="book">
  ...
  <behavior name="timestampable" />
</table>
<table name="author">
  ...
  <behavior name="timestampable" />
</table>
```

Then rebuild your model, and there you go: two columns, `created_at` and `updated_at`, were automatically added to both the `book` and `author` tables. Besides, the generated `BaseBook` and `BaseAuthor` classes already contain the code necessary to auto-set the current time on creation and on insertion.

## Bundled Behaviors ##

Propel currently bundles several behaviors. Check the behavior documentation for details on usage:

* [aggregate_column](../behaviors/aggregate-column)
* [alternative_coding_standards](../behaviors/alternative-coding-standards)
* [archivable](../behaviors/archivable) (Replace the deprecated `soft-delete` behavior)
* [auto_add_pk](../behaviors/auto-add-pk)
* [delegate](../behaviors/delegate)
* [timestampable](../behaviors/timestampable)
* [sluggable](../behaviors/sluggable)
* [sortable](../behaviors/sortable)
* [nested_set](../behaviors/nested-set)
* [versionable](../behaviors/versionable)
* [i18n](../behaviors/i18n)
* [query_cache](../behaviors/query-cache)
* And [concrete_inheritance](./09-inheritance), documented in the Inheritance Chapter even if it's a behavior

You can also look at [user contributed behaviors](../cookbook/user-contributed-behaviors.html).

Behaviors bundled with Propel require no further installation and work out of the box.

## Customizing Behaviors ##

Behaviors often offer some parameters to tweak their effect. For instance, the `timestampable` behavior allows you to customize the names of the columns added to store the creation date and the update date. The behavior customization occurs in the `schema.xml`, inside `<parameter>` tags nested in the `<behavior>` tag. So let's set the behavior to use `created_on` instead of `created_at` for the creation date column name (and same for the update date column):

```xml
<table name="book">
  ...
  <behavior name="timestampable">
    <parameter name="create_column" value="created_on" />
    <parameter name="update_column" value="updated_on" />
  </behavior>
</table>
```

If the columns already exist in your schema, a behavior is smart enough not to add them one more time.

```xml
<table name="book">
  ...
  <column name="created_on" type="timestamp" />
  <column name="updated_on" type="timestamp" />
  <behavior name="timestampable">
    <parameter name="create_column" value="created_on" />
    <parameter name="update_column" value="updated_on" />
  </behavior>
</table>
```

## Using Third-Party Behaviors ##

As a Propel behavior can be packaged into a single class, behaviors are quite easy to reuse and distribute across several projects. All you need to do is to copy the behavior file into your project, and declare it in `build.properties`, as follows:

```ini
# ----------------------------------
#  B E H A V I O R   S E T T I N G S
# ----------------------------------

propel.behavior.timestampable.class = propel.engine.behavior.timestampable.TimestampableBehavior
# Add your custom behavior paths here
propel.behavior.formidable.class = path.to.FormidableBehavior
```

Propel will then find the `FormidableBehavior` class whenever you use the `formidable` behavior in your schema:

```xml
<table name="author">
  ...
  <behavior name="timestampable" />
  <behavior name="formidable" />
</table>
```

>**Tip**<br />If you use autoloading during the build process, and if the behavior classes benefit from the autoloading, then you don't even need to declare the path to the behavior class.

## Applying a Behavior To All Tables ##

You can add a `<behavior>` tag directly under the `<database>` tag. That way, the behavior will be applied to all the tables of the database.

```xml
<database name="propel">
  <behavior name="timestampable" />
  <table name="book">
    ...
  </table>
  <table name="author">
    ...
  </table>
</database>
```

In this example, both the `book` and `author` table benefit from the `timestampable` behavior, and therefore automatically update their `created_at` and `updated_at` columns upon saving.

Going one step further, you can even apply a behavior to all the databases of your project, provided the behavior doesn't need parameters - or can use default parameters. To add a behavior to all databases, simply declare it in the project's `build.properties` under the `propel.behavior.default` key, as follows:

```ini
propel.behavior.default = archivable, timestampable
```

## Writing a Behavior ##

Check [the behaviors bundled with Propel](https://github.com/propelorm/Propel2/tree/master/src/Propel/Generator/Behavior) to see how to implement your own behavior: they are the best starting point to understanding the power of behaviors and builders.

### Modifying the Data Model ###

Behaviors can modify their table, and even add another table, by implementing the `modifyTable` method. In this method, use `$this->getTable()` to retrieve the table buildtime model and manipulate it.

For instance, to add a new column named 'foo' in the current table, add the following method to a behavior:

```php
<?php
class MyBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'column_name' => 'foo',
  );

  public function modifyTable()
  {
    $table = $this->getTable();
    $columnName = $this->getParameter('column_name');
    // add the column if not present
    if(!$this->getTable()->containsColumn($columnName)) {
      $column = $this->getTable()->addColumn(array(
        'name'    => $columnName,
        'type'    => 'INTEGER',
      ));
    }
  }
}
```

### Modifying the ActiveRecord Classes ###

Behaviors can add code to the generated model object by implementing one of the following methods:

```php
objectAttributes()     // add attributes to the object
objectMethods()        // add methods to the object
preInsert()            // add code to be executed before insertion of a new object
postInsert()           // add code to be executed after  insertion of a new object
preUpdate()            // add code to be executed before update of an existing object
postUpdate()           // add code to be executed after  update of an existing object
preSave()              // add code to be executed before saving an object (new or existing)
postSave()             // add code to be executed after  saving an object (new or existing)
preDelete()            // add code to be executed before deleting an object
postDelete()           // add code to be executed after  deleting an object
objectCall()           // add code to be executed inside the object's __call()
objectFilter(&$script) // do whatever you want with the generated code, passed as reference
```

### Modifying the Query Classes ###

Behaviors can also add code to the generated query objects by implementing one of the following methods:

```php
queryAttributes()     // add attributes to the query class
queryMethods()        // add methods to the query class
preSelectQuery()      // add code to be executed before selection of a existing objects
preUpdateQuery()      // add code to be executed before update of a existing objects
postUpdateQuery()     // add code to be executed after  update of a existing objects
preDeleteQuery()      // add code to be executed before deletion of a existing objects
postDeleteQuery()     // add code to be executed after  deletion of a existing objects
queryFilter(&$script) // do whatever you want with the generated code, passed as reference
```

### Modifying the TableMap Classes ###

Behaviors can also add code to the generated TableMap objects by implementing one of the following methods:

```php
staticAttributes()   // add static attributes to the TableMap class
staticMethods()      // add static methods to the TableMap class
tableMapFilter(&$script) // do whatever you want with the generated code, passed as reference
```

### Adding New Classes ###

Behaviors can add entirely new classes based on the data model. To build a new class, a behavior must provide an array of builder class names (in return to `getAdditionalBuilders()`) and the builder classes themselves.

For instance, to add an empty child class for the ActiveRecord class, create the following behavior:

```php
<?php
require_once 'AddChildBehaviorBuilder.php';

class AddChildBehavior extends Behavior
{
	protected $additionalBuilders = array('AddChildBehaviorBuilder');
}
```

Next, write a builder extending the `OMBuilder` class, and implement the `getUnprefixedClassName()`, `addClassOpen()`, and `addClassBody()` methods:

```php
<?php
class AddChildBehaviorBuilder extends OMBuilder
{

	public function getUnprefixedClassname()
	{
		return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'Child';
	}

	protected function addClassOpen(&$script)
	{
		$table = $this->getTable();
		$tableName = $table->getName();
		$script .= "
/**
 * Test class for Additional builder enabled on the '$tableName' table.
 *
 */
class " . $this->getClassname() . " extends " . $this->getStubObjectBuilder() . "
{
";
	}

	protected function addClassBody(&$script)
	{
		$script .= "  // no code";
	}

	protected function addClassClose(&$script)
	{
		$script .= "
}";
	}
}
```

By default, classes added by a behavior are generated each time the model is rebuilt. To limit the generation to the first time (for instance for stub classes), add a public `$overwrite` attribute to the builder and set it to `false`.

You can set the additional class to be generated in a subfolder by implementing the `getPackage()` method.

### Replacing or Removing Existing Methods ###

Behaviors can modify existing methods even if no hook is called in the builders, thanks to a service class called `PropelPHPParser`. This class can remove a method, replace a method by another one, or add a new method before or after an existing one.

The `PropelPHPParser` constructor takes a string as input, so it is best used inside "filter" hooks in behaviors. For instance, to replace the `findPk()` method in the generated query class by a custom one, use the following syntax:

```php
<?php
class FastPkFindBehavior extends Behavior
{
	public function queryFilter(&$script)
	{
	  $newFindPkMethod = "
public function findPk(\$key, \$con = null)
{
  \$query = 'SELECT * from `%s` WHERE id = ?';
  if (null === \$con) {
    \$con = Propel::getReadConnection(%sTableMap::DATABASE_NAME);
  }
  \$stmt = \$con->prepare(\$query);
  \$stmt->bindValue(1, \$key);
  \$res = \$stmt->execute();
  // hydrate ActiveRecord objects with the result
  \$formatter = new PropelObjectFormatter();
  \$formatter->setClass('%s');
  return \$formatter->formatOne(\$res);
}
";
    $table = $this->getTable();
    $newFindPkMethod = sprintf($newFindPkMethod, $table->getName(), $table->getPhpName(), $table->getPhpName());
    $parser = new PropelPHPParser($script, true);
    $parser->replaceMethod('findPk', $newFindPkMethod);
    $script = $parser->getCode();
  }
}
```

The `PropelPHPParser` class provides the following utility methods:

```php
removeMethod($methodName)
replaceMethod($methodName, $newCode)
addMethodAfter($methodName, $newCode)
addMethodBefore($methodName, $newCode)
```
