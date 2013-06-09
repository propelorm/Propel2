---
layout: documentation
title: Mastering Symfony2 Forms With Propel
---

# Mastering Symfony2 Forms With Propel #

In this chapter, you'll learn how to master Symfony2 forms with Propel.

>**Code along with the example**<br />If you want to follow along with the example in this chapter, create a `LibraryBundle` bundle by using this command: `php app/console generate:bundle --namespace=Acme/LibraryBundle`.

Assuming you manage `Book` and `Author` objects, you'll define the following `schema.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<database name="default" namespace="Acme\LibraryBundle\Model" defaultIdMethod="native">
    <table name="book">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="title" type="varchar" primaryString="1" size="100" />
        <column name="isbn" type="varchar" size="20" />
        <column name="author_id" type="integer" />
        <foreign-key foreignTable="author">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>
    <table name="author">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="first_name" type="varchar" size="100" />
        <column name="last_name" type="varchar" size="100" />
    </table>
</database>
```

In Symfony2, you deal with `Type` so let's create a `BookType` to manage
our books. For the moment, just ignore the relation with `Author` objects.

```php
<?php
// src/Acme/LibraryBundle/Form/Type/BookType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BookType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('title');
        $builder->add('isbn');
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\Book',
        );
    }

    public function getName()
    {
        return 'book';
    }
}
```

>**Setting the `data_class`**<br />Every form needs to know the name of the class that holds the underlying data (e.g. `Acme\LibraryBundle\Model\Book`). Usually, this is just guessed based off of the object passed to the second argument to createForm().

Basically, you will use this class in an action of one of your controllers.
Assuming you have a `BookController` controller in your `LibraryBundle`, you will
write the following code to create new books:

```php
<?php
// src/Acme/LibraryBundle/Controller/BookController.php

namespace Acme\LibraryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Acme\LibraryBundle\Model\Book;
use Acme\LibraryBundle\Form\Type\BookType;

class BookController extends Controller
{
    public function newAction()
    {
        $book = new Book();
        $form = $this->createForm(new BookType(), $book);

        return $this->render('AcmeLibraryBundle:Book:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
```

>**Warning**<br />To quickly explain how forms are rendered, the controller above extends the `Controller` class which provides the `render()` method used to return a `Response` but this is not considered as a best practice. It's better to create a controller as a service.

To render the form, you'll need to create a Twig template like below:

```jinja
{# src/Acme/LibraryBundle/Resources/views/Book/new.html.twig #}

<form action="{{ "{{ path('book_new')" }} }}" method="post" {{ "{{ form_enctype(form)" }} }}>
    {{ "{{ form_widget(form)" }} }}

    <input type="submit" />
</form>
```

You'll get this result:

![](./images/basic_form.png)

As such, the topic of persisting the `Book` object to the database is entirely
unrelated to the topic of forms. But, if you've created a `Book` class with Propel,
then persisting it after a form submission can be done when the form is valid:

```php
<?php
// src/Acme/LibraryBundle/Controller/BookController.php

// ...

    public function newAction()
    {
        $book = new Book();
        $form = $this->createForm(new BookType(), $book);

        $request = $this->getRequest();

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $book->save();

                return $this->redirect($this->generateUrl('book_success'));
            }
        }

        return $this->render('AcmeLibraryBundle:Book:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
```

If, for some reason, you don't have access to your original `$book` object,
you can fetch it from the form:

```php
<?php

$book = $form->getData();
```

As you can see, this is really easy to manage basic forms with both Symfony2
and Propel. But, in real life, this kind of forms is not enough and you'll probably
manage objects with relations, this is the next part of this chapter.


## One-To-Many relations ##

A `Book` has an `Author`, this is a **One-To-Many** relation. Let's modifying your
`BookType` to handle this relation:

```php
<?php
// src/Acme/LibraryBundle/Form/Type/BookType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BookType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('title');
        $builder->add('isbn');
        // Author relation
        $builder->add('author', new AuthorType());
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\Book',
        );
    }

    public function getName()
    {
        return 'book';
    }
}
```

You now have to write an `AuthorType` to reflect the new requirements:

```php
<?php
// src/Acme/LibraryBundle/Form/Type/AuthorType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class AuthorType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('first_name');
        $builder->add('last_name');
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\Author',
        );
    }

    public function getName()
    {
        return 'author';
    }
}
```

If you refresh your page, you'll now get the following result:

![](./images/one_to_many_form.png)

When the user submits the form, the submitted data for the `Author` fields are used to construct an
instance of `Author`, which is then set on the author field of the `Book` instance.
The `Author` instance is accessible naturally via $book->getAuthor().

But you could have the following use case: to add books to an author. The main type will be the `AuthorType` as below:

```php
<?php
// src/Acme/LibraryBundle/Form/Type/AuthorType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class AuthorType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('first_name');
        $builder->add('last_name');
        $builder->add('books', 'collection', array(
            'type'          => new \Acme\LibraryBundle\Form\Type\BookType(),
            'allow_add'     => true,
            'allow_delete'  => true,
            'by_reference'  => false,
        ));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\Author',
        );
    }

    public function getName()
    {
        return 'author';
    }
}
```

You'll also need to refactor your `BookType`:

```php
<?php
// src/Acme/LibraryBundle/Form/Type/BookType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BookType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('title');
        $builder->add('isbn');
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\Book',
        );
    }

    public function getName()
    {
        return 'book';
    }
}
```

When you'll create a new `Author` object, you'll be able to add a set of new `Books` objects and they will be
linked to this author without any effort thanks to Propel and specific methods to handle collections on related objects.

![](./images/one_to_many_form_with_collection.png)


## Many-To-Many relations ##

Now, imagine you want to add your books to some lists for book clubs. A `BookClubList` can have many
`Book` objects and a `Book` can be in many lists (`BookClubList`). This is a **Many-To-Many** relation.

Add the following definition to your `schema.xml ` and rebuild your model classes:

```xml
<table name="book_club_list" description="Reading list for a book club.">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Unique ID for a school reading list." />
    <column name="group_leader" required="true" type="VARCHAR" size="100" description="The name of the teacher in charge of summer reading." />
    <column name="theme" required="false" type="VARCHAR" size="50" description="The theme, if applicable, for the reading list." />
    <column name="created_at" required="false" type="TIMESTAMP" />
</table>
<table name="book_x_list" phpName="BookListRel" isCrossRef="true"
    description="Cross-reference table for many-to-many relationship between book rows and book_club_list rows.">
    <column name="book_id" primaryKey="true" type="INTEGER" description="Fkey to book.id" />
    <column name="book_club_list_id" primaryKey="true" type="INTEGER" description="Fkey to book_club_list.id" />
    <foreign-key foreignTable="book" onDelete="cascade">
        <reference local="book_id" foreign="id" />
    </foreign-key>
    <foreign-key foreignTable="book_club_list" onDelete="cascade">
        <reference local="book_club_list_id" foreign="id" />
    </foreign-key>
</table>
```

You now have `BookClubList` and `BookListRel` objects. Let's create a `BookClubListType`:

```php
<?php
// src/Acme/LibraryBundle/Form/Type/BookClubListType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BookClubListType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('group_leader');
        $builder->add('theme');
        // Book collection
        $builder->add('books', 'collection', array(
            'type'          => new \Acme\LibraryBundle\Form\Type\BookType(),
            'allow_add'     => true,
            'allow_delete'  => true,
            'by_reference'  => false
        ));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\BookClubList',
        );
    }

    public function getName()
    {
        return 'book_club_list';
    }
}
```

You've added a `CollectionType` for the `Book` list and you've configured it
with your `BookType`. In this example, you allow to add and/or delete books.

>**Warning**<br />The parameter `by_reference` has to be defined and set to `false`. This is required to tell the Form Component to call the setter method (`setBooks()` in this example).

Thanks to the smart collection setter provided by Propel, there is nothing more to configure.
Use the `BookClubListType` as you previously did with the `BookType`. Note the Symfony2 Form Component
doesn't handle the add/remove abilities in the view. You have to write some JavaScript for that.

![](./images/many_to_many_form.png)

### The ModelType ###

In the previous example, you always create new objects.

If you want to select existing authors when you create new books, you'll have to use a `model` type:

```php
<?php
// src/Acme/LibraryBundle/Form/Type/BookType.php

namespace Acme\LibraryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BookType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('title');
        $builder->add('isbn');

        //$builder->add('author', new AuthorType());
        $builder->add('author', 'model', array(
            'class' => 'Acme\LibraryBundle\Model\Author',
        ));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Acme\LibraryBundle\Model\Book',
        );
    }

    public function getName()
    {
        return 'book';
    }
}
```

You'll obtain the following result:

![](./images/many_to_many_form_with_existing_objects.png)

>**Information**<br />The `ModelType` is part of the [`PropelBundle`](http://github.com/propelorm/PropelBundle.git).


## Summary ##

The Symfony2 Form Component doesn't have anymore secrets for you and to use it with Propel is really
easy.
