---
layout: documentation
title: Delegate Behavior
---

# Delegate Behavior #

The `delegate` behavior allows a model to delegate methods to one of its relationships. This helps to isolate logic in a dedicated model, or to simulate [class table inheritance](http://martinfowler.com/eaaCatalog/classTableInheritance.html).

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `delegate` behavior to a table. In the `<parameters>` tag, specify the table that the current table delegates to as the `to` parameter:

```xml
<table name="account">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="login" type="VARCHAR" required="true" />
  <column name="password" type="VARCHAR" required="true" />
  <behavior name="delegate">
    <parameter name="to" value="profile" />
  </behavior>
</table>
<table name="profile">
  <column name="email" type="VARCHAR" />
  <column name="telephone" type="VARCHAR" />
</table>
```

Rebuild your model, insert the table creation sql again, and you're ready to go. The delegate `profile` table is now related to the `account` table using a one-to-one relationship. That means that the behavior creates a foreign primary key in the `profile` table. In fact, everything happens as if you had defined the following schema:

```xml
<table name="account">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="login" type="VARCHAR" required="true" />
  <column name="password" type="VARCHAR" required="true" />
</table>
<table name="profile">
  <column name="id" required="true" primaryKey="true" type="INTEGER" />
  <column name="email" type="VARCHAR" />
  <column name="telephone" type="VARCHAR" />
  <foreign-key foreignTable="account" onDelete="setnull" onUpdate="cascade">
    <reference local="id" foreign="id" />
  </foreign-key>
</table>
```

>**Tip**<br />If the delegate table already has a foreign key to the main table, the behavior doesn't recreate it. It allows you to have full control over the relationship between the two tables.

In addition, the ActiveRecord `Account` class now provides integrated delegation capabilities. That means that it offers to handle directly the columns of the `Profile` model, while in reality it finds or create a related `Profile` object and calls the methods on this delegate:

```php
<?php
$account = new Account();
$account->setLogin('francois');
$account->setPassword('S€cr3t');

// Fill the profile via delegation
$account->setEmail('francois@example.com');
$account->setTelephone('202-555-9355');
// same as
$profile = new Profile();
$profile->setEmail('francois@example.com');
$profile->setTelephone('202-555-9355');
$account->setProfile($profile);

// save the account and its profile
$account->save();

// retrieve delegated data directly from the main object
echo $account->getEmail(); // francois@example.com
```

Getter and setter methods for delegate columns don't exist on the main object ; the delegation is handled by the magical `__call()` method. Therefore, the delegation also works for custom methods in the delegate table.

```php
<?php
class Profile extends BaseProfile
{
  public function setFakeEmail()
  {
    $n = rand(10e16, 10e20);
    $fakeEmail = base_convert($n, 10, 36) . '@example.com';
    $this->setEmail($fakeEmail);
  }
}

$account = new Account();
$account->setFakeEmail(); // delegates to Profile::setFakeEmail()
```

## Delegating Using a Many-To-One Relationship ##

Instead of adding a one-to-one relationship, the `delegate` behavior can take advantage of an existing many-to-one relationship. For instance:

```xml
<table name="player">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="first_name" type="VARCHAR" />
  <column name="last_name" type="VARCHAR" />
</table>
<table name="basketballer">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="points" type="INTEGER" />
  <column name="field_goals" type="INTEGER" />
  <column name="three_points_field_goals" type="INTEGER" />
  <column name="player_id" type="INTEGER" />
  <foreign-key foreignTable="player">
      <reference local="player_id" foreign="id" />
    </foreign-key>
  <behavior name="delegate">
    <parameter name="to" value="player" />
  </behavior>
</table>

```

In that case, the behavior doesn't modify the foreign keys, it just proxies method called on `Basketballer` to the related `Player`, or creates one if it doesn't exist:

```php
<?php
$basketballer = new Basketballer();
$basketballer->setPoints(101);
$basketballer->setFieldGoals(47);
$basketballer->setThreePointsFieldGoals(7);
// set player identity via delegation
$basketballer->setFirstName('Michael');
$basketballer->setLastName('Giordano');
// same as
$player = new Player();
$player->setFirstName('Michael');
$player->setLastName('Giordano');
$basketballer->setPlayer($player);

// save basketballer and player
$basketballer->save();

// retrieve delegated data directly from the main object
echo $basketballer->getFirstName(); // Michael
```

And since several models can delegate to the same player object, that means that a single player can have both basketball and soccer stats!

>**Tip**<br />In this example, table delegation is used to implement [Class Table Inheritance](http://martinfowler.com/eaaCatalog/classTableInheritance.html). See how Propel implements this inheritance type, and others, in the [inheritance chapter](../documentation/09-inheritance.html).

## Delegating To Several Tables ##

Delegation allows to delegate to several tables. Just separate the name of the delegate tables by commas in the `to` parameter of the `delegate` behavior tag in your schema to delegate to several tables:

```xml
<table name="account">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="login" type="VARCHAR" required="true" />
  <column name="password" type="VARCHAR" required="true" />
  <behavior name="delegate">
    <parameter name="to" value="profile, preference" />
  </behavior>
</table>
<table name="profile">
  <column name="email" type="VARCHAR" />
  <column name="telephone" type="VARCHAR" />
</table>
<table name="preference">
  <column name="preferred_color" type="VARCHAR" />
  <column name="max_size" type="INTEGER" />
</table>
```

Now the `Account` class has two delegates, that can be addressed seamlessly:

```php
<?php
$account = new Account();
$account->setLogin('francois');
$account->setPassword('S€cr3t');

// Fill the profile via delegation
$account->setEmail('francois@example.com');
$account->setTelephone('202-555-9355');
// Fill the preference via delegation
$account->setPreferredColor('orange');
$account->setMaxSize('200');

// save the account and its profile and its preference
$account->save();
```

On the other hand, it is not possible to cascade delegation to yet another model. So even if the `profile` table delegates to another `detail` table, the methods of the `Detail` model won't be accessible to the `Profile` objects.

## Parameters ##

The `delegate` behavior takes only one parameter, the list of delegate tables:

```xml
<table name="account">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="login" type="VARCHAR" required="true" />
  <column name="password" type="VARCHAR" required="true" />
  <behavior name="delegate">
    <parameter name="to" value="profile, preference" />
  </behavior>
</table>
```

Note that the delegate tables must exist, but they don't need to share a relationship with the main table (in which case the behavior creates a one-to-one relationship).
