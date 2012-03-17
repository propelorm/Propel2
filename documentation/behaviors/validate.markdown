---
layout: documentation
title: Validate Behavior
---

# Validate Behavior #

The `validate` behavior provides validating capabilities to any ActiveRecord object. Using this behavior, you can perform validation of an ActiveRecord and its related objects.

This behavior is based on [Symfony2 Validator Component](http://symfony.com/doc/current/book/validation.html).

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `validate` behavior to a table.
Then add validation rules via `<parameter>` tag.
{% highlight xml %}
<table name="author" description="Author Table">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
  <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
  <column name="last_name" required="true" type="VARCHAR" size="128" description="Last Name" />
  <column name="email" type="VARCHAR" size="128" description="E-Mail Address" />
  
  <behavior name="validate">
    <parameter name="rule1" value="{column: first_name, validator: NotNull}" />
    <parameter name="rule2" value="{column: first_name, validator: MaxLength, options: {limit: 128}}" />
    <parameter name="rule3" value="{column: last_name, validator: NotNull}" />
    <parameter name="rule4" value="{column: last_name, validator: MaxLength, options: {limit: 128}}" />
    <parameter name="rule5" value="{column: email, validator: Email}" />
  </behavior>
</table>
{% endhighlight %}

The `name` of each parameter is arbitrary. 
The `value` of the parameters is an array in YAML format, in wich we need to specify 3 values: 
* `column`: the column to validate
* `validator`: the name of [Validator Constraint](http://symfony.com/doc/current/reference/constraints.html)
* `options`: an array of optional values to pass to the validator constraint class, according to its reference documentation


Rebuild your model and you're ready to go. The ActiveRecord object now has three methods:
* `loadValidatorMetadata`: this static method contains validation rules
* `validate()`: this method performs validation on the ActiveRecord object itself and all related objects. If the validation is successful it returns true, otherwise false.
* `getValidationFailures()`: if validate() is false, this method returns a [`ConstraintViolationList`](http://api.symfony.com/2.0/Symfony/Component/Validator/ConstraintViolationList.html) object.


Standard validation:

{% highlight php %}
<?php

$author = new Author();
$author->setLastName('Wilde');
$author->setFirstName('Oscar');
$author->setEmail('oscar.wilde@gmail.com');

if (!$author->validate())
{
    foreach ($author->getValidationFailures() as $failure)
    {
        echo "Property ".$failure->getPropertyPath().": ".$failure->getMessage()."\n";
    }
}
else
{
   echo "Everything's all right!";
}

{% endhighlight %}

The behavior adds to ActiveRecord object the static loadValidatorMetadata() method. So, inside your Symfony or Silex projects, you can perform "usual" validation:

{% highlight php %}
<?php

//Symfony 2

use Symfony\Component\HttpFoundation\Response;
use YouVendor\YourBundle\Model\Author;
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
{% endhighlight %}
{% highlight php %}
<?php

//Silex

// ...

$app->post('/authors/new', function () use ($app) {
    $post = new Author();
    $author->setLastName($app['request']->get('lastname'));
    $author->setFirstName($app['request']->get('firstname'));
    $author->setEmail($app['request']->get('email'));
    
    $violations = $app['validator']->validate($author);
    return $violations;
{% endhighlight %}

But if you wish to validate also related objects, you can pass the registered validator object instance:

{% highlight php %}
<?php

//Symfony 2

use Symfony\Component\HttpFoundation\Response;
use YouVendor\YourBundle\Model\Author;
// ...

public function indexAction()
{
    $author = new Author();
    // ... do something to the $author object

    $validator = $this->get('validator');
    if (!$author->validate($validator))
    {
        $errors = $author->getValidationFailures();
        
        return new Response(print_r($errors, true));
    
    } 
    else 
    {
        return new Response('The author is valid! Yes!');
    }
}
{% endhighlight %}
