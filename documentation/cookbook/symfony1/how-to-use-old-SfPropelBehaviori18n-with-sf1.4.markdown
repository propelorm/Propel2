---
layout: documentation
title: How To Use Old SfPropelBehaviori18n (Aka symfony_i18n) With Symfony 1.4
---

# How To Use Old SfPropelBehaviori18n (Aka symfony_i18n) With Symfony 1.4 #

>**Warning**<br /> If you're currently starting a new project or just willing to update your `symfony` project, you should consider using the `Propel` i18n behavior integration with `symfony 1.4`.
 
All you have to do is to write your `schema.xml` with the old SfPropelBehaviorI18n style `<table is18n="true">` with a `culture` column, instead of the i18n `<behavior>` tag.
 
First [init a `symfony` project with `Propel` as default ORM](init-a-Symfony-project-with-Propel-git-way) and let's start with this `schema.xml`:
  
```xml
<?xml version="1.0" encoding="UTF-8"?>
<database defaultIdMethod="native" name="propel">
  <table name="author">
    <column name="id" type="INTEGER" primaryKey="true" required="true"/>
    <column name="name" type="VARCHAR" size="256"/>
  </table>
  <table name="book" isI18N="true" i18nTable="book_i18n">
    <column name="id" type="INTEGER" primaryKey="true" required="true"/>
    <column name="author_id" type="INTEGER" required="true"/>
    <column name="ISBN" type="VARCHAR" size="13"/>
    <foreign-key n foreignTable="author">
      <reference local="author_id" foreign="id"/>
    </foreign-key>
  </table>
  <table name="book_i18n">
    <column name="id" type="INTEGER" primaryKey="true" required="true"/>
    <column name="title" type="VARCHAR" size="45"/>
    <column name="description" type="VARCHAR" size="45"/>
    <column name="culture" type="varchar" size="7" required="true" primaryKey="true" isCulture="true" />
    <foreign-key foreignTable="book">
      <reference local="id" foreign="id"/>
    </foreign-key>
  </table>
</database>
```

And those fixtures:

```yaml
Author:
  bach:
    id: 1
    name: Richard Bach

Book:
  livingston:
    id: 1
    author_id: bach
    ISBN: 0-380-01286-3     
  illusions:
    id: 2
    author_id: bach
    ISBN: 0-440-20488-7

BookI18n:
  livingston_fr:
    id: livingston
    culture: fr
    title: Jonathan Livingston le goéland
  livingston_en: 
    id: livingston
    culture: en
    title: Jonathan Livingston Seagull
  illusions_fr:
    id: illusions
    culture: fr
    title: Le Messie récalcitrant
  illusions_en: 
    id: illusions
    culture: en
    title: Jonathan Livingston Seagull
```

Let's build this schema:

```bash
php symfony propel:build --all --and-load --no-confirmation
```

## Simple Use Of embedI18n()

Create a book module:

```bash
php symfony propel:generate-module main book Book
```

Add i18N to book form `lib/form/BookForm.class.php`:  

```php
<?php
class BookForm extends BaseBookForm
{
  public function configure()
  {
    $this->embedI18n(array('fr','en'));
  }
}
```

Let's print the form with the i18n embedded forms in `apps/main/modules/book/templates/_form.php`:

```php
<?php use_stylesheets_for_form($form) ?>
<?php use_javascripts_for_form($form) ?>

<form action="<?php echo url_for('book/'.($form->getObject()->isNew() ? 'create' : 'update').(!$form->getObject()->isNew() ? '?id='.$form->getObject()->getId() : '')) ?>" method="post" <?php $form->isMultipart() and print 'enctype="multipart/form-data" ' ?>>
<?php if (!$form->getObject()->isNew()): ?>
<input type="hidden" name="sf_method" value="put" />
<?php endif; ?>
  <table>
    <tfoot>
      <tr>
        <td colspan="2">
          <?php echo $form->renderHiddenFields(false) ?>
          &nbsp;<a href="<?php echo url_for('book/index') ?>">Back to list</a>
          <?php if (!$form->getObject()->isNew()): ?>
            &nbsp;<?php echo link_to('Delete', 'book/delete?id='.$form->getObject()->getId(), array('method' => 'delete', 'confirm' => 'Are you sure?')) ?>
          <?php endif; ?>
          <input type="submit" value="Save" />
        </td>
      </tr>
    </tfoot>
    <tbody>
      <?php echo $form->renderGlobalErrors() ?>
      <?php echo $form ?>
    </tbody>
  </table>
</form>
```

## Use embedI18n() In An Embedded Form

Create an author module:

```bash
php symfony propel:generate-module main author Author
```

Embed book form in author `lib/form/AuthorForm.class.php`:

```php
<?php
class AuthorForm extends BaseAuthorForm
{
  public function configure()
  {
    $this->embedRelation('Book');
  }
}
```

Finally let's print the form with all his embedded forms in `apps/main/modules/templates/_form.php`:

```php
<?php use_stylesheets_for_form($form) ?>
<?php use_javascripts_for_form($form) ?>

<form action="<?php echo url_for('author/'.($form->getObject()->isNew() ? 'create' : 'update').(!$form->getObject()->isNew() ? '?id='.$form->getObject()->getId() : '')) ?>" method="post" <?php $form->isMultipart() and print 'enctype="multipart/form-data" ' ?>>
<?php if (!$form->getObject()->isNew()): ?>
<input type="hidden" name="sf_method" value="put" />
<?php endif; ?>
  <table>
    <tfoot>
      <tr>
        <td colspan="2">
          <?php echo $form->renderHiddenFields(false) ?>
          &nbsp;<a href="<?php echo url_for('author/index') ?>">Back to list</a>
          <?php if (!$form->getObject()->isNew()): ?>
            &nbsp;<?php echo link_to('Delete', 'author/delete?id='.$form->getObject()->getId(), array('method' => 'delete', 'confirm' => 'Are you sure?')) ?>
          <?php endif; ?>
          <input type="submit" value="Save" />
        </td>
      </tr>
    </tfoot>
    <tbody>
      <?php echo $form->renderGlobalErrors() ?>
      <?php echo $form ?>
    </tbody>
  </table>
</form>
```

As a bonus you can use special joinWithI18n() query even if it's not native (thanks to javer).

>**Warning**<br />Remember you should consider using the `Propel` i18n behavior integration with `symfony 1.4`.    

 
 


