---
layout: documentation
title: Customizing build
---

# Customizing Build #

It is possible to customize the Propel build process by overriding values in your propel `build.properties` file. For maximum flexibility, you can even create your own Phing `build.xml` file.

## Customizing the build.properties ##

The easiest way to customize your Propel build is to simply specify build properties in your project's `build.properties` file.

### Understanding Phing build properties ###

_Properties_ are essentially variables. These variables can be specified on the commandline or in _properties files_.

For example, here's how a property might be specified on the commandline:

```bash
> phing -Dpropertyname=value
```

More typically, properties are stored in files and loaded by Phing. For those not familiar with Java properties files, these files look like PHP INI files; the main difference is that values in properties files can be references to other properties (a feature that will probably exist in in INI files in PHP 5.1).

>**Importantly**<br />properties, once loaded, are not overridden by properties with the same name unless explicitly told to do so. In the Propel build process, the order of precedence for property values is as follows:

1. Commandline properties
2. Project `build.properties`
3. Top-level `build.properties`
4. Top-level `default.properties`

This means, for example, that values specified in the project's `build.properties` files will override those in the top-level `build.properties` and `default.properties` files.

### Changing values ###

To get an idea of what you can modify in Propel, simply look through the `build.properties` and `default.properties` files.

_Note, however, that some of the current values exist for legacy reasons and will be cleaned up in Propel 1.1._

#### New build output directories ####

This can easily be customized on a project-by-project basis. For example, here is a `build.properties` file for the _bookstore _project that puts the generated classes in `/var/www/bookstore/classes` and puts the generated SQL in `/var/www/bookstore/db/sql`:

```ini
propel.project = bookstore
propel.database = sqlite
propel.database.url = sqlite://localhost/./test/bookstore.db
propel.targetPackage = bookstore

# directories
propel.output.dir = /var/www/bookstore
propel.php.dir = ${propel.output.dir}/classes
propel.phpconf.dir = ${propel.output.dir}/conf
propel.sql.dir = ${propel.output.dir}/db/sql
```

The _targetPackage_ property is also used in determining the path of the generated classes. In the example above, the `Book.php` class will be located at `/var/www/bookstore/classes/bookstore/Book.php`. You can change this `bookstore` subdir by altering the _targetPackage_ property:

```ini
propel.targetPackage = propelom
```

Now the class will be located at `/var/www/bookstore/classes/propelom/Book.php`

_Note that you can override the targetPackage property by specifying a package="" attribute in the `<database>` tag or even the `<table>` tag of the schema.xml._

## Creating a custom build.xml file ##

If you want to make more major changes to the way the build script works, you can setup your own Phing build script. This actually is not a very scary task, and once you've managed to create a Phing build script, you'll probably want to create build targets for other aspects of your project (e.g. running batch unit tests is now supported in Phing 2.1-CVS).

To start with, I suggest taking a look at the `build-propel.xml` script (the build.xml script is just a wrapper script). Note, however, that the `build-propel.xml` script does a lot and has a lot of complexity that is designed to make it easy to configure using properties (so, don't be scared).

Without going into too much detail about how Phing works, the important thing is that Phing build scripts XML and they are grouped into _targets_ which are kinda like functions. The actual work of the scripts is performed by _tasks_, which are PHP5 classes that extend the base Phing _Task_ class and implement its abstract methods. Propel provides some Phing tasks that work with templates to create the object model.

### Step 1: register the needed tasks ###

The Propel tasks must be registered so that Phing can find them. This is done using the `<taskdef>` tag. You can see this near the top of the `build-propel.xml` file.

For example, here is how we register the `<propel-om>` task, which is the task that creates the PHP classes for your object model:

```xml
<taskdef
    name="propel-om"
    classname="propel.phing.PropelOMTask"/>
```

Simple enough. Phing will now associate the `<propel-data-model>` tag with the _PropelOMTask_ class, which it expects to find at `propel/phing/PropelOMTask.php` (on your _include_path_). If Propel generator classes are not on your _include_path_, you can specify that path in your `<taskdef>` tag:

```xml
<taskdef
    name="propel-om"
    classname="propel.phing.PropelOMTask"
    classpath="/path/to/propel-generator/classes"/>
```

Or, for maximum re-usability, you can create a `<path>` object, and then reference it (this is the way `build-propel.xml` does it):

```xml
  <path id="propelclasses">
      <pathelement dir="/path/to/propel-generator/classes"/>
  </path>

  <taskdef
    name="propel-om"
    classname="propel.phing.PropelOMTask"
    classpathRef="propelclasses"/>
```

### Step 2: invoking the new task ###

Now that the `<propel-om>` task has been registered with Phing, it can be invoked in your build file.

```xml
<propel-om
      outputDirectory="/var/www/bookstore/classes"
      targetDatabase="mysql"
      targetPackage="bookstore"
      templatePath="/path/to/propel-generator/templates"
      targetPlatform="php5">
    <schemafileset dir="/var/www/bookstore/db/model" includes="*schema.xml"/>
</propel-om>
```

In the example above, it's worth pointing out that the `<propel-om>` task can actually transform multiple `schema.xml` files, which is why there is a `<schemafileset>` sub-element. Phing _filesets_ are beyond the scope of this HOWTO, but hopefully the above example is obvious enough.

### Step 3: putting it together into a build.xml file ###

Now that we've seen the essential elements of our custom build file, it's time to look at how to assemble them into a working whole:

```xml
<?xml version="1.0">
<project name="propel" default="om">

 <!-- set properties we use later -->
 <property name="propelgen.home" value="/path/to/propel-generator"/>
 <property name="out.dir" value="/var/www/bookstore"/>

 <!-- register task -->
  <path id="propelclasses">
      <pathelement dir="${propelgen.home}/classes"/>
  </path>

  <taskdef
    name="propel-om"
    classname="propel.phing.PropelOMTask"
    classpathRef="propelclasses"/>


 <!-- this [default] target performs the work -->
 <target name="om" description="build propel om">
  <propel-om
    outputDirectory="${out.dir}/classes"
    targetDatabase="mysql"
    targetPackage="bookstore"
    templatePath="${propelgen.home}/templates"
    targetPlatform="php5">
      <schemafileset dir="${out.dir}/db/model" includes="*schema.xml"/>
  </propel-om>
 </target>

</project>
```

If that build script was named `build.xml` then it could be executed by simply running _phing_ in the directory where it is located:

```bash
> phing om
```

Actually, specifying the _om_ target is not necessary since it is the default.

Refer to the `build-propel.xml` file for examples of how to use the other Propel Phing tasks -- e.g. `<propel-sql>` for generating the DDL SQL, `<propel-sql-exec>` for inserting the SQL, etc.
