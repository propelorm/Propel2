---
layout: documentation
title: Validate Behavior
---

# Validate Behavior #

The `validate` behavior provides validating capabilities to ActiveRecord objects.
Using this behavior, you can perform validation of an ActiveRecord and its related objects, checking if properties meet certain conditions.

This behavior is based on [Symfony2 Validator Component](http://symfony.com/doc/current/book/validation.html).
We recommend to read Symfony2 Validator Component documentation, in particular [Validator Constraints](http://symfony.com/doc/current/reference/constraints.html) chapter, before to start using this behavior.

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `validate` behavior to a table.
Then add validation rules via `<parameter>` tag.
```xml
<table name="author" description="Author Table">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
  <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
  <column name="last_name" required="true" type="VARCHAR" size="128" description="Last Name" />
  <column name="email" type="VARCHAR" size="128" description="E-Mail Address" />

  <behavior name="validate">
    <parameter name="rule1" value="{column: first_name, validator: NotNull}" />
    <parameter name="rule2" value="{column: first_name, validator: Length, options: {max: 128}}" />
    <parameter name="rule3" value="{column: last_name, validator: NotNull}" />
    <parameter name="rule4" value="{column: last_name, validator: Length, options: {max: 128}}" />
    <parameter name="rule5" value="{column: email, validator: Email}" />
  </behavior>
</table>
```

Let's now see the properties of `<parameter>` tag:
*   The `name` of each parameter is arbitrary.
*   The `value` of the parameters is an array in YAML format, in which we need to specify 3 values:
     `column`: the column to validate
     `validator`: the name of [Validator Constraint](http://symfony.com/doc/current/reference/constraints.html)
     `options`: (optional)an array of optional values to pass to the validator constraint class, according to its reference documentation



Rebuild your model and you're ready to go. The ActiveRecord object now exposes two public methods:
* `validate()`: this method performs validation on the ActiveRecord object itself and on all related objects. If the validation is successful it returns true, otherwise false.
* `getValidationFailures()`: this method returns a [ConstraintViolationList](http://api.symfony.com/2.0/Symfony/Component/Validator/ConstraintViolationList.html) object. If validate() is false, it returns a list of [ConstraintViolation](http://api.symfony.com/2.0/Symfony/Component/Validator/ConstraintViolation.html) objects, if validate() is true, it returns an empty `ConstraintViolationList` object.


Now you are ready to perform validations:

```php
<?php

$author = new Author();
$author->setLastName('Wilde');
$author->setFirstName('Oscar');
$author->setEmail('oscar.wilde@gmail.com');

if (!$author->validate()) {
    foreach ($author->getValidationFailures() as $failure) {
        echo "Property ".$failure->getPropertyPath().": ".$failure->getMessage()."\n";
    }
}
else {
   echo "Everything's all right!";
}

```



## Related objects validation ##


When we use ActiveRecord `validate()` method, we perform validation on the object itself and on all related objects. It's a great possibility but we need to know how this method works, to avoid unpleasant surprises.


The `validate()` method follows these steps:

1.   search the 1-n related objects by foreign key
2.   if validate behavior is configured on it, call its `validate()` method
3.   performs validation on itself
4.   search the n-1 related objects
5.   if validate behavior is configured on them, call their `validate()` method



Let's see it in action, with an example.

Consider the following model:

```xml
<database name="bookstore">
    <table name="book">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" required="true" />
        <column name="isbn" type="VARCHAR" size="24" />
        <column name="price" required="false" type="FLOAT" />
        <column name="publisher_id" required="false" type="INTEGER" />
        <column name="author_id" required="false" type="INTEGER" />
        <foreign-key foreignTable="validate_publisher" onDelete="setnull">
            <reference local="publisher_id" foreign="id" />
        </foreign-key>
        <foreign-key foreignTable="validate_author" onDelete="setnull" onUpdate="cascade">
            <reference local="author_id" foreign="id" />
        </foreign-key>
        <behavior name="validate">
            <parameter name="rule1" value="{column: title, validator: NotNull}" />
        </behavior>
    </table>

    <table name="publisher">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="name" required="true" type="VARCHAR" size="128" />
        <column name="website" type="VARCHAR" />
        <behavior name="validate">
            <parameter name="rule1" value="{column: name, validator: NotNull}" />
            <parameter name="rule2" value="{column: website, validator: Url}" />
        </behavior>
    </table>

    <table name="author">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="first_name" required="true" type="VARCHAR" size="128" />
        <column name="last_name" required="true" type="VARCHAR" size="128" />
        <column name="email" type="VARCHAR" size="128" />
        <behavior name="validate">
            <parameter name="rule1" value="{column: first_name, validator: NotNull}" />
            <parameter name="rule2" value="{column: first_name, validator: Length, options: {max: 128}}" />
            <parameter name="rule3" value="{column: last_name, validator: NotNull}" />
            <parameter name="rule4" value="{column: last_name, validator: Length, options: {max: 128}}" />
            <parameter name="rule5" value="{column: email, validator: Email}" />
        </behavior>
    </table>

    <table name="reader">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="first_name" required="true" type="VARCHAR" size="128" />
        <column name="last_name" required="true" type="VARCHAR" size="128" />
        <column name="email" type="VARCHAR" size="128" />
        <behavior name="validate">
            <parameter name="rule1" value="{column: first_name, validator: NotNull}" />
            <parameter name="rule2" value="{column: first_name, validator: Length, options: {min: 4}}" />
            <parameter name="rule3" value="{column: last_name, validator: NotNull}" />
            <parameter name="rule4" value="{column: last_name, validator: Length, options: {max: 128}}" />
            <parameter name="rule5" value="{column: email, validator: Email}" />
        </behavior>
    </table>

    <table name="reader_book" isCrossRef="true">
         <column name="reader_id" type="INTEGER" primaryKey="true"/>
         <column name="book_id" type="INTEGER" primaryKey="true"/>
         <foreign-key foreignTable="validate_reader">
              <reference local="reader_id" foreign="id"/>
         </foreign-key>
         <foreign-key foreignTable="validate_book">
              <reference local="book_id" foreign="id"/>
         </foreign-key>
     </table>

</database>
```

And consider to perform a validation on a book object:

```php
<?php

$book = new Book();

// some operations by which we add to the book object some related objects:
// we add a publisher object, an author object and some reader objects

$book->validate();
```


The steps of validation are the following:

1.    search the author and publisher objects, related to our book
2.    author and publisher objects have the validate behavior tag in its schema definition, so `$author->validate()` and `$publisher->validate()` are called
3.    perform validation on *book* object itself
4.    search all reader objects associated to this book object, by using `reader_book` table
5.    the reader_book table has *no* validate behavior tag so no other validation will be performed


In this case, no reader object will be validated because the cross reference table has no validate behavior, even if reader table has the validate behavior properly configured. No error message will be raised, because the behavior gives you the possibility to configure validations on a table but not on related ones. It's your choice.

In previous example, if you want to perform validations also on reader objects, you need to configure the behavior also on reader_book table:

```xml
<!-- previous schema -->

<table name="validate_reader_book" isCrossRef="true">
         <column name="reader_id" type="INTEGER" primaryKey="true"/>
         <column name="book_id" type="INTEGER" primaryKey="true"/>
         <foreign-key foreignTable="validate_reader">
              <reference local="reader_id" foreign="id"/>
         </foreign-key>
         <foreign-key foreignTable="validate_book">
              <reference local="book_id" foreign="id"/>
         </foreign-key>
         <behavior name="validate">
            <parameter name="rule1" value="{column: reader_id, validator: NotNull}" />
            <parameter name="rule2" value="{column: book_id, validator: NotNull}" />
            <parameter name="rule3" value="{column: reader_id, validator: Type, options: {type: integer}}" />
            <parameter name="rule4" value="{column: book_id, validator: Type, options: {type: integer}}" />
        </behavior>
     </table>
```

And now the validation flow will be the following:

1.    search the author and publisher objects
2.    author and publisher objects have the validate behavior tag in its schema definition, so `$author->validate()` and `$publisher->validate()` are called
3.    perform validation on $book itself
4.    search all readers associated to this book object, by using reader_book table
5.    reader_book table now has the behavior, so `$reader_book->validate()` is called
6.    inside the `$reader_book->validate()` all related reader objects will be searched and validated

>**Tip**<br />If you configure the behavior on all related objects, every object will be ALWAYS validated, no matter if you call `validate()` method of the one or the other.



## Parameter tag: name ##

Inside the `<parameter>` tag, you define the `name` property.
This property can be a value of your choice, but this name should be *unique*. If we define more rules with the same name, only the last one will be considered.

In the following example, only the third and the fourth rules will be considered: the first two rules are overwritten by the third one.

```xml
<!-- your schema -->

   <column name="reader_id" type="INTEGER" primaryKey="true"/>
   <column name="book_id" type="INTEGER" primaryKey="true"/>
   <behavior name="validate">
       <parameter name="rule1" value="{column: reader_id, validator: NotNull}" />
       <parameter name="rule1" value="{column: book_id, validator: NotNull}" />
       <parameter name="rule1" value="{column: reader_id, validator: Type, options: {type: integer}}" />
       <parameter name="rule2" value="{column: book_id, validator: Type, options: {type: integer}}" />
    </behavior>

<!-- end of your schema -->
```


## Parameter tag: value ##

As we mentioned earlier, the `value` property contains a string, representing an array in YAML format. We've chosen this format because, in YAML array definition, there is no special xml character, so we have no need to escape anything and no need to change standard Propel xsd and xsl files.
`options` key, inside the value array, is an array too, and it can contain other arrays (i.e. see [Choice constraint](http://symfony.com/doc/current/reference/constraints/Choice.html), in wich the `choices` option is an array, too) and with YAML there's no problem.

Only in one case we suggest to be careful.
As each respectable validation library, also Symfony Validator Component allows validations against regular expressions, by using the constraint [Regex](http://symfony.com/doc/current/reference/constraints/Regex.html).
As you can see in Regex constraint documentation, `options` parameter contains a `pattern` key, defining the pattern for validation.

But usually, a regular expression pattern contains a lot of special and escape characters so, in YAML definition, we need to include the pattern string in a couple of double-quote (").

In the following example, we add a constraint to validate ISBN. It's very complicated to check if an ISBN is valid, but a first check could be to disallow every character that's not a digit or minus, using the pattern  `/[^\d-]+/`:

```xml
<!-- ATTENTION PLEASE: THIS EXAMPLE DOES NOT WORK -->

<!-- your schema -->
  <behavior name="validate">
      .......
      <parameter name="rule1" value="{column: isbn, validator: Regex, options: {pattern: "/[^\d-]+/", match: false, message: Please enter a valid ISBN}}" />
  </behavior>

<!-- end of your schema -->
```

But inside an xml string the double-quote characters should be escaped, so replace them with `&quot;`:


```xml
<!-- THIS EXAMPLE WORKS FINE -->

<!-- your schema -->
  <behavior name="validate">
      .......
      <parameter name="rule1" value="{column: isbn, validator: Regex, options: {pattern: &quot;/[^\d-]+/&quot;, match: false, message: Please enter a valid ISBN }}" />
  </behavior>

<!-- end of your schema -->
```


## Automatic validation ##

You can automatic validate an ActiveRecord, before saving it into your database, thanks to `preSave()` hook (see [behaviors documentation](/documentation/07-behaviors.html)).
For example, let's suppose we wish to add automatic validation capability to our `Book` class. Open `Book.php`, in your model path, and add the following code:

```php
<?php
// Code of your Book class.
// Remember use statement to set properly ConnectionInterface namespace

public function preSave(ConnectionInterface $con = null)
{
    return $this->validate();
}
```

If validation failed, `preSave()` returns false and the saving operation stops. No error is raised but the `save()` method of your ActiveRecord returns the integer `0`, because no object was affected. So, we can check the returned value of `save()` method to see what was happened and to get any error messages:

```php
<?php
// your app code

$author = AuthorQuery::create()->findPk(1);
$publisher = PublisherQuery::create()->findPk(1);

$book = new Book();
$book->setAuthor($author);
$book->setPublisher($publisher);
$book->setTitle('The country house');
$book->setPrice(10,00);

$ret = $book->save();

// if $ret <= 0 means no affected rows, that is validation failed or no object to persist
if ($ret <= 0) {
    $failures = $book->getValidationFailures();

    // count($failures) > 0 means that we have ConstraintViolation objects and validation failed
    if (count($failures) > 0) {
        foreach ($failures as $failure) {
            echo $failure->getPropertyPath()." validation failed: ".$failure->getMessage();
        }
    }
}
```

## Supported constraints ##

The behavior supports all Symfony Validator Constraints (see [http://symfony.com/doc/current/reference/constraints.html]), except `UniqueEntity` which is not compatible with Propel.
Propel has its own unique validator: `Unique` constraint.
This constraint checks if a certain value is already stored in the database. You can use it in the same way:

```xml
<!-- your schema -->
  <behavior name="validate">
      <parameter name="rule1" value="{column: column_name, validator: Unique}" />
  </behavior>
```

And if you want to specify an error message:

```xml
<!-- your schema -->
  <behavior name="validate">
      <parameter name="rule1" value="{column: column_name, validator: Unique, options: {message: Your message here}}" />
  </behavior>
```

>**Tip**<br />`Date`, `Time` and `DateTime` constraints are useful if you store a date-time value inside a string. If you use a php `DateTime` object, if a value isn't valid, the `DateTime` object itself raises an exception, before performing any validations.


## Custom validation constraints ##

Propel and Symfony 2 Validator component come with many bundled constraints and that gives the possibility to perform almost all validations you could need.
But sometimes, you could think that a custom validation constraint is a better choice for you.

Adding a custom validation constraint to your project is very easy and it can be considered a two-step process:

1.    Write your custom constraint: please refer to [this document](http://symfony.com/doc/current/cookbook/validation/custom_constraint.html) and see the example below.
2.    Set up Propel to work with it: simply adjust your autoload class or function, to correctly map `Propel\Runtime\Validator\Constraints` namespace to the directory in which your constraint scripts reside.

>**Tip**<br /> Propel expects to find custom constraints under `Propel\Runtime\Validator\Constraints` namespace.


For example, let's suppose we want to write a custom constraint, called *PropelDomain*, that checks if an url belongs to *propelorm.org* domain.
Let's also suppose to put our files in a subdir of our project root, called `/myConstraints`, and to manage the dependencies of our project via [Composer](http://getcomposer.org).

Under `/myConstraints` dir, let's create the subdir `Propel/Runtime/Validator/Constraints`, in which we'll put the two following scripts:

`PropelDomain.php`
```php
<?php
// /myConstraints/Propel/Runtime/Validator/Constraints/PropelDomain.php

namespace Propel\Runtime\Validator\Constraints;

use Symfony\Component\Validator\Constraint;


class PropelDomain extends Constraint
{
    public $message = 'This url does not belong to propelorm.org domain';
    public $column = '';
}
```

`PropelDomainValidator.php`
```php
<?php
 // /myConstraints/Propel/Runtime/Validator/Constraints/PropelDomainValidator.php

 namespace Propel\Runtime\Validator\Constraints;

 use Symfony\Component\Validator\Constraint;
 use Symfony\Component\Validator\ConstraintValidator;

 class PropelDomainValidator extends ConstraintValidator
 {
     public function isValid($value, Constraint $constraint)
     {
         if ('propelorm.org' === strstr($value, 'propelorm.org')) {
             return false;
         } else {
             $this->setMessage($constraint->message);

             return true;
         }
     }
 }
```

Now, open `composer.json` file, in your project root and add the namespace `Propel\Runtime\Validator\Constraints` to the autoload directive:

```json
"autoload": {
        "psr-0": {
            "Propel\\Runtime\\Validator\\Constraints": "myConstraints/"
        }
    }
```

Done! Now you can use your custom validator constraint in your `schema.xml` file, as usual:

```xml

<!-- your schema -->
  <behavior name="validate">
      <parameter name="rule1" value="{column: website, validator: PropelDomain, options: {message: Your custom message}}" />
  </behavior>

<!-- end of your schema -->
```

**Note**: if you think your custom constraint could be generic enough to be useful for the community, please submit it to Propel team,
to include it in Propel bundled constraints (see [http://dotheweb.posterous.com/open-source-is-a-gift](http://dotheweb.posterous.com/open-source-is-a-gift)).

## Inside Symfony2 ##

The behavior adds to ActiveRecord objects the static `loadValidatorMetadata()` method, which contains all validation rules. So, inside your Symfony projects, you can perform "usual" Symfony validations:

```php
<?php

// Symfony 2

use Symfony\Component\HttpFoundation\Response;
use YourVendor\YourBundle\Model\Author;
// ...

public function indexAction()
{
    $author = new Author();
    // ... do something to the $author object

    $validator = $this->get('validator');
    $errors = $validator->validate($author);

    if (count($errors) > 0) {
        return new Response(print_r($errors, true));
    } else {
        return new Response('The author is valid! Yes!');
    }
}
```

But if you wish to automatically validate also related objects, you can use the ActiveRecord `validate()` method, passing to it an instance of registered validator object:

```php
<?php

// Symfony 2

use Symfony\Component\HttpFoundation\Response;
use YouVendor\YourBundle\Model\Author;
// ...

public function indexAction()
{
    $author = new Author();
    // ... do something to the $author object

    $validator = $this->get('validator');
    if (!$author->validate($validator)) {
        $errors = $author->getValidationFailures();

        return new Response(print_r($errors, true));

    }
    else {
        return new Response('The author is valid! Yes!');
    }
}
```


## Inside Silex ##

Using the behavior inside a Silex project, is about the same as we've seen for Symfony:

```php
<?php

// Silex

// ...

$app->post('/authors/new', function () use ($app) {
    $post = new Author();
    $author->setLastName($app['request']->get('lastname'));
    $author->setFirstName($app['request']->get('firstname'));
    $author->setEmail($app['request']->get('email'));

    $violations = $app['validator']->validate($author);

    return $violations;

}
```

and if you wish to automatically validate also related objects:

```php
<?php

// Silex

// ...

$app->post('/authors/new', function () use ($app) {
    $post = new Author();
    $author->setLastName($app['request']->get('lastname'));
    $author->setFirstName($app['request']->get('firstname'));
    $author->setEmail($app['request']->get('email'));

    $author->validate($app['validator']))
    $violations = $author->getValidationFailures();

    return $violations;

}
```

## Properties and methods added to ActiveRecord ##

The behavior adds the following properties to your ActiveRecord:

*   `alreadyInValidation`:  this *protected* property is a flag to prevent endless validation loop, if this object is referenced by another object on which we're performing a validation.
*   `validationFailures`:   this *protected* property contains the ConstraintViolationList object.


The behavior adds the following methods to your ActiveRecord:

*   `validate`:  this *public* method validates the object and all objects related to it.
*   `getValidationFailures`:  this *public* method gets the ConstraintViolationList object, that contains all ConstraintViolation objects resulted from last call to `validate()` method.
*   `loadValidatorMetadata`:  this *public static* method contains all the Constraint objects.
