---
layout: documentation
title: Validators
---

# Validators #

Validators help you to validate an input before persisting it to the database. In Propel, validators are rules describing what type of data a column accepts. Validators are referenced in the `schema.xml` file, using `<validator>` tags.

Validators are applied at the PHP level, they are not created as constraints on the database itself. That means that if you also use another language to work with the database, the validator rules will not be enforced.
You can also apply multiple rule entries per validator entry in the schema.xml file.

## Overview ##

In the following example, the `username` column is defined to have a minimum length of 4 characters:

{% highlight xml %}
<table name="user">
  <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
  <column name="username" type="VARCHAR" size="34" required="true" />
  <validator column="username">
    <rule name="minLength"  value="4"  message="Username must be at least ${value} characters !" />
  </validator>
</table>
{% endhighlight %}

Every column rule is represented by a `<rule>` tag. A `<validator>` is a set of `<rule>` tags bound to a column.

At runtime, you can validate an instance of the model by calling the `validate()` method:

{% highlight php %}
<?php
$user = new User();
$user->setUsername("foo"); // only 3 in length, which is too short...
if ($objUser->validate()) {
  // no validation errors, so the data can be persisted
  $user->save();
} else {
  // Something went wrong.
  // Use the validationFailures to check what
  foreach ($objUser->getValidationFailures() as $failure) {
    echo $failure->getMessage() . "\n";
  }
}
{% endhighlight %}

`validate()` returns a boolean. If the validation failed, you can access the array  `ValidationFailed` objects by way of the `getValidationFailures()` method. Each `ValidationFailed` instance gives access to the column, the messagen and the validator that caused the failure.

## Core Validators ##

Propel bundles a set of validatorts that should help you deal with the most common cases.

### MatchValidator ###

The `MatchValidator` is used to run a regular expression of choice against the column. Note that this is a `preg`, not `ereg` (check [the preg_match documentation](http://www.php.net/preg_match) for more information about regexps).

{% highlight xml %}
<validator column="username">
  <!-- allow strings that match the email adress pattern -->
  <rule
    name="match"
    value="/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9])+(\.[a-zA-Z0-9_-]+)+$/"
    message="Please enter a valid email address." />
</validator>
{% endhighlight %}

### NotMatchValidator ###

Opposite of `MatchValidator`, this validator returns false if the regex returns true

{% highlight xml %}
<column name="ISBN" type="VARCHAR" size="20" required="true" />
<validator column="ISBN">
  <!-- disallow everything that's not a digit or minus -->
  <rule
    name="notMatch"
    value="/[^\d-]+/"
    message="Please enter a valid ISBN" />
</validator>
{% endhighlight %}

### MaxLengthValidator ###

When you want to limit the size of the string to be inserted in a column, use the `MaxLengthValidator`. Internally, it uses `strlen()` to get the length of the string. For instance, some database completely ignore the lentgh of `LONGVARCHAR` columns; you can enforce it using a validator:

{% highlight xml %}
<column name="comment" type="LONGVARCHAR" required="true" />
<validator column="comment">
  <rule
    name="maxLength"
    value="1024"
    message="Comments can be no larger than ${value} in size" />
</validator>
{% endhighlight %}

>**Tip**<br />If you have specified the `size` attribute in the `<column>` tag, you don't have to specify the `value` attribute in the validator rule again, as this is done automatically.

### MinLengthValidator ###

{% highlight xml %}
<column name="username" type="VARCHAR" size="34" required="true" />
<validator column="username">
  <rule
    name="minLength"
    value="4"
    message="Username must be at least ${value} characters !" />
</validator>
{% endhighlight %}

### MaxValueValidator ###

To limit the value of an integer column, use the `MaxValueValidator`. Note that this validator uses a non-strict comparison ('less than or equal'):

{% highlight xml %}
<column name="security_level" type="INTEGER" required="true" />
<validator column="security_level">
  <rule
    name="maxValue"
    value="1000"
    message="Maximum security level is ${value} !" />
</validator>
{% endhighlight %}

### MinValueValidator ###

{% highlight xml %}
<column name="cost" type="INTEGER" required="true" />
<validator column="cost">
  <rule
    name="minValue"
    value="0"
    message="Products can cost us negative $ can they?" />
</validator>
{% endhighlight %}

>**Tip**<br />You can run multiple validators against a single column.

{% highlight xml %}
<column name="security_level" type="INTEGER" required="true" default="10" />
<validator column="security_level" translate="none">
  <rule
    name="minValue"
    value="0"
    message="Invalid security level, range: 0-10" />
  <rule
    name="maxValue"
    value="10"
    message="Invalid security level, range: 0-10" />
</validator>
{% endhighlight %}

### RequiredValidator ###

This validator checks the same rule as a `required=true` on the column at the database level. This, however, will give you a clean error to work with.

{% highlight xml %}
<column name="username" type="VARCHAR" size="25" required="true" />
<validator column="username">
  <rule
    name="required"
    message="Username is required." />
</validator>
{% endhighlight %}

### UniqueValidator ###

To check whether the value already exists in the table, use the `UniqueValidator`:

{% highlight xml %}
<column name="username" type="VARCHAR" size="25" required="true" />
<validator column="username">
  <rule
    name="unique"
    message="Username already exists !" />
</validator>
{% endhighlight %}

### ValidValuesValidator ###

This rule restricts the valid values to a list delimited by a pipe ('|').

{% highlight xml %}
<column name="address_type" type="VARCHAR" required="true" default="delivery" />
<validator column="address_type">
  <rule
    name="validValues"
    value="account|delivery"
    message="Please select a valid address type." />
</validator>
{% endhighlight %}

### TypeValidator ###

Restrict values to a certain PHP type using the `TypeValidator`:

{% highlight xml %}
<column name="username" type="VARCHAR" size="25" required="true" />
<validator column="username">
  <rule
    name="type"
    value="string"
    message="Username must be a string" />
</validator>
{% endhighlight %}

## Adding A Custom Validator ##

You can easily add a custom validator. A validator is a class extending `BasicValidator` providing a public `isValid()` method. For instance:

{% highlight php %}
<?php
require_once 'propel/validator/BasicValidator.php';

/**
 * A simple validator for email fields.
 *
 * @package propel.validator
 */
class EmailValidator implements BasicValidator
{
  public function isValid(ValidatorMap $map, $str)
  {
    return preg_match('/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i', $str) !== 0;
  }
}
{% endhighlight %}

The `ValidatorMap` instance passed as parameter gives you access to the rules attribute as defined in the `<rule>` tag. So `$map->getValue()` returns the `value` attribute.

>**Tip**<br />Make sure that `isValid()` returns a boolean, so really true or false. Propel is very strict about this. Returning a mixed value just won't do.

To enable the new validator on a column, add a corresponding `<rule>` in your schema and use 'class' as the rule `name`.

{% highlight xml %}
<validator column="<column_name>">
  <rule name="class" class="my.dir.EmailValidator" message="Invalid e-mail address!" />
</validator>
{% endhighlight %}

The `class` attribute of the `<rule>` tag should contain a path to the validator class accessible from the include_path, where the directory separator is replaced by a dot.
