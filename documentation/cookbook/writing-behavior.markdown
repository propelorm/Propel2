---
layout: documentation
title: How to Write A Behavior
---

# How to Write A Behavior #

Behaviors are a good way to reuse code across models without requiring inheritance (a.k.a. horizontal reuse). This step-by-step tutorial explains how to port model code to a behavior, focusing on a simple example.

In the tutorial "[Keeping an Aggregate Column up-to-date](http://propel.posterous.com/getting-to-know-propel-15-keeping-an-aggregat)", posted in the [Propel blog](http://propel.posterous.com/), the `TotalNbVotes` property of a `PollQuestion` object was updated each time a related `PollAnswer` object was saved, edited, or deleted. This "aggregate column" behavior was implemented by hand using hooks in the model classes. To make it truly reusable, the custom model code needs to be refactored and moved to a Behavior class.

## Boostrapping A Behavior ##

A behavior is a class that can alter the generated classes for a table of your model. It must only extend the `Behavior` class and implement special "hook" methods. Here is the class skeleton to start with for the `aggregate_column` behavior:

```php
<?php
class AggregateColumnBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'name' => null,
  );
}
```

Save this class in a file called `AggregateColumnBehavior.php`, and set the path for the class file in the project `build.properties` (just replace directory separators with dots). Remember that the `build.properties` paths are relative to the include path:

```ini
propel.behavior.aggregate_column.class = path.to.AggregateColumnBehavior
```

Test the behavior by adding it to a table of your model, for instance to a `poll_question` table:

```xml
<database name="poll" defaultIdMethod="native">
  <table name="poll_question" phpName="PollQuestion">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="body" type="VARCHAR" size="100" />
    <behavior name="aggregate_column">
      <parameter name="name" value="total_nb_votes" />
    </behavior>
  </table>
</database>
```

Rebuild your model, and check the generated `PollQuestionTableMap` class under the `map` subdirectory of your build class directory. This class carries the structure metadata for the `PollQuestion` ActiveRecord class at runtime. The class should feature a `getBehaviors()` method as follows, proving that the behavior was correctly applied:

```php
<?php
class PollQuestionTableMap extends TableMap
{
  // ...

  public function getBehaviors()
  {
    return array(
      'aggregate_column' => array('name' => 'total_nb_votes', ),
    );
  } // getBehaviors()
}
```

## Adding A Column ##

The behavior works, but it still does nothing at all. Let's make it useful by allowing it to add a column. In the `AggregateColumnBehavior` class, just implement the `modifyTable()` method with the following code:

```php
<?php
class AggregateColumnBehavior extends Behavior
{
  // ...

  public function modifyTable()
  {
    $table = $this->getTable();
    if (!$columnName = $this->getParameter('name')) {
      throw new InvalidArgumentException(sprintf(
        'You must define a \'name\' parameter for the \'aggregate_column\' behavior in the \'%s\' table',
        $table->getName()
      ));
    }
    // add the aggregate column if not present
    if(!$table->containsColumn($columnName)) {
      $table->addColumn(array(
        'name'    => $columnName,
        'type'    => 'INTEGER',
      ));
    }
  }
}
```

This method shows that a behavior class has access to the `<parameters>` defined for it in the `schema.xml` through the `getParameter()` command. Behaviors can also always access the `Table` object attached to them, by calling `getTable()`. A `Table` can check if a column exists and add a new one easily. The `Table` class is one of the numerous generator classes that serve to describe the object model at buildtime, together with `Column`, `ForeignKey`, `Index`, and a lot more classes. You can find all the buildtime model classes under the `generator/lib/model` directory.

_Tip_: Don't mix up the _runtime_ database model (`DatabaseMap`, `TableMap`, `ColumnMap`, `RelationMap`) with the _buildtime_ database model (`Database`, `Table`, `Column`, etc.). The buildtime model is very detailed, in order to ease the work of the builders that write the ActiveRecord and Query classes. On the other hand, the runtime model is optimized for speed, and carries minimal information to allow correct hydration and binding at runtime. Behaviors use the buildtime object model, because they are run at buildtime, so they have access to the most powerful model.

Now rebuild the model and the SQL, and sure enough, the new column is there. `BasePollQuestion` offers a `getTotalNbVotes()` and a `setTotalNbVotes()` method, and the table creation SQL now includes the additional `total_nb_votes` column:

```sql
DROP TABLE IF EXISTS poll_question;
CREATE TABLE poll_question
(
  id INTEGER  NOT NULL AUTO_INCREMENT,
  title VARCHAR(100),
  total_nb_votes INTEGER,
  PRIMARY KEY (id)
)Type=InnoDB;
```

_Tip_: The behavior only adds the column if it's not present (`!$table->containsColumn($columnName)`). So if a user needs to customize the column type, or any other attribute, he can include a `<column>` tag in the table with the same name as defined in the behavior, and the `modifyTable()` will then skip the column addition.

## Adding A Method To The ActiveRecord Class ##

In the previous post, a method of the ActiveRecord class was in charge of updating the `total_nb_votes` column. A behavior can easily add such methods by implementing the `objectMethods()` method:

```php
<?php
class AggregateColumnBehavior extends Behavior
{
  // ...

  public function objectMethods()
  {
    $script = _;
    $script .= $this->addUpdateAggregateColumn();
    return $script;
  }

  protected function addUpdateAggregateColumn()
  {
    $sql = sprintf('SELECT %s FROM %s WHERE %s = ?',
      $this->getParameter('expression'),
      $this->getParameter('foreign_table'),
      $this->getParameter('foreign_column')
    );
    $table = $this->getTable();
    $aggregateColumn = $table->getColumn($this->getParameter('name'));
    $columnPhpName = $aggregateColumn->getPhpName();
    $localColumn = $table->getColumn($this->getParameter('local_column'));
    return "
/**
 * Updates the aggregate column {$aggregateColumn->getName()}
 *
 * @param PropelPDO \$con A connection object
 */
public function update{$columnPhpName}(PropelPDO \$con)
{
  \$sql = '{$sql}';
  \$stmt = \$con->prepare(\$sql);
  \$stmt->execute(array(\$this->get{$localColumn->getPhpName()}()));
  \$this->set{$columnPhpName}(\$stmt->fetchColumn());
  \$this->save(\$con);
}
";
  }
}
```

The ActiveRecord class builder expects a string in return to the call to `Behavior::objectMethods()`, and appends this string to the generated code of the ActiveRecord class. Don't bother about indentation: builder classes know how to properly indent a string returned by a behavior. A good rule of thumb is to create one behavior method for each added method, to provide better readability.

Of course, the schema must be modified to supply the necessary parameters to the behavior:

```xml
<database name="poll" defaultIdMethod="native">
  <table name="poll_question" phpName="PollQuestion">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="body" type="VARCHAR" size="100" />
    <behavior name="aggregate_column">
      <parameter name="name" value="total_nb_votes" />
      <parameter name="expression" value="count(nb_votes)" />
      <parameter name="foreign_table" value="poll_answer" />
      <parameter name="foreign_column" value="question_id" />
      <parameter name="local_column" value="id" />
    </behavior>
  </table>
  <table name="poll_answer" phpName="PollAnswer">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="question_id" required="true" type="INTEGER" />
    <column name="body" type="VARCHAR" size="100" />
    <column name="nb_votes" type="INTEGER" />
    <foreign-key foreignTable="poll_question" onDelete="cascade">
      <reference local="question_id" foreign="id" />
    </foreign-key>
  </table>
</database>
```

Now if you rebuild the model, you will see the new `updateTotalNbVotes()` method in the generated `BasePollQuestion` class:

```php
<?php
class BasePollQuestion extends BaseObject
{
  // ...

  /**
   * Updates the aggregate column total_nb_votes
   *
   * @param PropelPDO $con A connection object
   */
  public function updateTotalNbVotes(PropelPDO $con)
  {
    $sql = 'SELECT count(nb_votes) FROM poll_answer WHERE question_id = ?';
    $stmt = $con->prepare($sql);
    $stmt->execute(array($this->getId()));
    $this->setTotalNbVotes($stmt->fetchColumn());
    $this->save($con);
  }
}
```

Behaviors offer similar hook methods to allow the addition of methods to the query classes (`queryMethods()`) and to the object classes (`objectMethods()`). And if you need to add attributes, just implement one of the `objectAttributes()` or `queryAttributes()` methods.

## Using a Template For Generated Code ##

The behavior's `addUpdateAggregateColumn()` method is somehow hard to read, because of the large string containing the PHP code canvas for the added method. Propel behaviors can take advantage of Propel's simple templating system to use an external file as template for the code to insert.

Let's refactor the `addUpdateAggregateColumn()` method to take advantage of this feature:

```php
<?php
class AggregateColumnBehavior extends Behavior
{
  // ...

  protected function addUpdateAggregateColumn()
  {
    $sql = sprintf('SELECT %s FROM %s WHERE %s = ?',
      $this->getParameter('expression'),
      $this->getParameter('foreign_table'),
      $this->getParameter('foreign_column')
    );
    $table = $this->getTable();
    $aggregateColumn = $table->getColumn($this->getParameter('name'));
    return $this->renderTemplate('objectUpdateAggregate', array(
      'aggregateColumn' => $aggregateColumn,
      'columnPhpName'   => $aggregateColumn->getPhpName(),
      'localColumn'     => $table->getColumn($this->getParameter('local_column')),
      'sql' => $sql,
    ));
  }
}
```

The method no longer returns a string created by hand, but a _rendered template_. Propel templates are simple PHP files executed in a sandbox - they have only access to the variables declared as second argument of the `renderTemplate()` call.

Now create a `templates/` directory in the same directory as the `AggregateColumnBehavior` class file, and add in a `objectUpdateAggregate.php` file with the following code:

```php
/**
 * Updates the aggregate column <?php echo $aggregateColumn->getName() ?>
 *
 * @param PropelPDO $con A connection object
 */
public function update<?php echo $columnPhpName ?>(PropelPDO $con)
{
  $sql = '<?php echo $sql ?>';
  $stmt = $con->prepare($sql);
  $stmt->execute(array($this->get<?php echo $localColumn->getPhpName() ?>()));
  $this->set<?php echo $columnPhpName ?>($stmt->fetchColumn());
  $this->save($con);
}
```

No need to escape dollar signs anymore: this syntax allows for a cleaner separation, and is very convenient for large behaviors.

## Adding Another Behavior From A Behavior ##

This is where it's getting tricky. In the [blog post](http://propel.posterous.com/getting-to-know-propel-15-keeping-an-aggregat) describing the column aggregation technique, the calls to the `updateTotalNbVotes()` method come from the `postSave()` and `postDelete()` hooks of the `PollAnswer` class. But the current behavior is applied to the `poll_question` table, how can it modify the code of a class based on another table?

The short answer is: it can't. To modify the classes built for the `poll_answer` table, a behavior must be registered on the `poll_answer` table. But a behavior is just like a column or a foreign key: it has an object counterpart in the buildtime database model. So the trick here is to modify the `AggregateColumnBehavior::modifyTable()` method to _add a new behavior_ to the foreign table. This second behavior will be in charge of implementing the `postSave()` and `postDelete()` hooks of the `PollAnswer` class.

```php
<?php
class AggregateColumnBehavior extends Behavior
{
  // ...

  public function modifyTable()
  {
    // ...

    // add a behavior to the foreign table to autoupdate the aggregate column
    $foreignTable =  $table->getDatabase()->getTable($this->getParameter('foreign_table'));
    if (!$foreignTable->hasBehavior('concrete_inheritance_parent')) {
      require_once 'AggregateColumnRelationBehavior.php';
      $relationBehavior = new AggregateColumnRelationBehavior();
      $relationBehavior->setName('aggregate_column_relation');
      $relationBehavior->addParameter(array(
        'name' => 'foreign_table',
        'value' => $table->getName()
      ));
      $relationBehavior->addParameter(array(
        'name' => 'foreign_column',
        'value' => $this->getParameter('name')
      ));
      $foreignTable->addBehavior($relationBehavior);
    }
  }
}
```

In practice, everything now happens as if the `poll_answer` had its own behavior:

```xml
<database name="poll" defaultIdMethod="native">
  <!-- ... -->
  <table name="poll_answer" phpName="PollAnswer">
    <!-- ... -->
    <behavior name="aggregate_column_relation">
      <parameter name="foreign_table" value="poll_question" />
      <parameter name="foreign_column" value="total_nb_votes" />
    </behavior>
  </table>
</database>
```

Adding a behavior to a `Table` instance, as well as adding a `Parameter` to a `Behavior` instance, is quite straightforward. And since the second behavior class file is required in the `modifyTable()` method, there is no need to add a path for it in the `build.properties`.

## Adding Code For Model Hooks ##

The new `AggregateColumnRelationBehavior` is yet to write. It must implement a call to `PollQuestion::updateTotalNbVotes()` in the `postSave()` and `postDelete()` hooks.

Adding code to hooks from a behavior is just like adding methods: add a method with the right hook name returning a code string, and the code will get appended at the right place. Unsurprisingly, the behavior hook methods for `postSave()` and `postDelete()` are called `postSave()` and `postDelete()`:

```php
<?php
class AggregateColumnBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'foreign_table' => null,
    'foreignColumn' => null,
  );

  public function postSave()
  {
    $table = $this->getTable();
    $foreignTable = $table->getDatabase()->getTable($this->getParameter('foreign_table'));
    $foreignColumn = $foreignTable->getColumn($this->getParameter('foreign_column'));
    $foreignColumnPhpName = $foreignColumn->getPhpName();
    return "\$this->updateRelated{$foreignColumnPhpName}(\$con)";
  }

  public function postDelete()
  {
    return $this->postSave();
  }

  public function objectMethods()
  {
    $script = _;
    $script .= $this->addUpdateRelatedAggregateColumn();
    return $script;
  }

  protected function addUpdateRelatedAggregateColumn()
  {
    $table = $this->getTable();
    $foreignTable = $table->getDatabase()->getTable($this->getParameter('foreign_table'));
    $foreignTablePhpName = foreignTable->getPhpName();
    $foreignColumn = $foreignTable->getColumn($this->getParameter('foreign_column'));
    $foreignColumnPhpName = $foreignColumn->getPhpName();
    return "
/**
 * Updates an aggregate column in the foreign {$foreignTable->getName()} table
 *
 * @param PropelPDO \$con A connection object
 */
protected function updateRelated{$foreignColumnPhpName}(PropelPDO \$con)
{
  if (\$parent{$foreignTablePhpName} = \$this->get{$foreignTablePhpName}()) {
    \$parent{$foreignTablePhpName}->update{$foreignColumnPhpName}(\$con);
  }
}
";
  }
}
```

The `postSave()` and `postDelete()` behavior hooks will not add code to the ActiveRecord `postSave()` and `postDelete()` methods - to allow users to further implement these methods - but instead it adds code directly to the `save()` and `delete()` methods, inside a transaction. Check the generated `BasePollAnswer` class for the added code in these methods:

```php
<?php
// aggregate_column_relation behavior
$this->updateRelatedTotalNbVotes($con);
```

You will also see the new `updateRelatedTotalNbVotes()` method added by `AggregateColumnBehavior::objectMethods()`:

```php
<?php
/**
 * Updates an aggregate column in the foreign poll_question table
 *
 * @param PropelPDO $con A connection object
 */
protected function updateRelatedTotalNbVotes(PropelPDO $con)
{
  if ($parentPollQuestion = $this->getPollQuestion()) {
    $parentPollQuestion->updateTotalNbVotes($con);
  }
}
```

## Specifying a Priority For Behavior Execution ##

Since behaviors can modify tables, and even add tables, you may encounter cases where two behaviors conflict with each other. The usual way to solve these conflicts is to force a particular execution order, i.e. behavior A must be executed before behavior B, no matter in what order they were specified in the schema.

Propel Behavior classes support a `$tableModificationOrder` attribute just for that purpose. By default, it is set to 50; set it to a lower number to force an early execution, or to a greater number to force a late execution. For instance, in the following example, `BehaviorA` will be executed before `BehaviorB`:

```php
<?php
class BehaviorA extends Behavior
{
  protected $tableModificationOrder = 40;
}

class BehaviorB extends Behavior
{
  protected $tableModificationOrder = 60;
}
```

## What's Left ##

These are the basics of behavior writing: implement one of the methods documented in the [behaviors chapter](../documentation/07-behaviors.html#writing-a-behavior) of the Propel guide, and return strings containing the code to be added to the ActiveRecord, Query, and TableMap classes. In addition to the behavior code, you should always write unit tests - all the behaviors bundled with Propel have full unit test coverage. And to make your behavior usable by others, documentation is highly recommended. Once again, Propel core behaviors are fully documented, to let users understand the behavior usage without having to peek into the code.

As for the `AggregateColumnBehavior`, the job is not finished. The [blog post](http://propel.posterous.com/getting-to-know-propel-15-keeping-an-aggregat) emphasized the need for hooks in the Query class, and these are not yet implemented in the above code. Besides, the  post kept quiet about one use case that left the aggregate column not up to date (when a question is detached from a poll without deleting it). Lastly, the parameters required for this behavior are currently a bit verbose, especially concerning the need to define the foreign table and the foreign key - this could be simplified thanks to the knowledge of the object model that behaviors have.

All this is left to the reader as an exercise. Fortunately, the final behavior is part of the Propel core behaviors, so the [aggregate_column documentation](../behaviors/aggregate-column) and the code are all ready to help you to further understand the power of Propel's behavior system.
