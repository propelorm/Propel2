---
layout: documentation
title: How to Create A Schema Based On A DBDesigner Model
---

# How to Create A Schema Based On A DBDesigner Model #

If you use [DBDesigner 4](http://www.fabforce.net/dbdesigner4/) to design your model, Propel can take the XML file used by DBDesigner as storage, and convert it to a Propel schema.

## Basic Usage ##

The process is very straightforward. Just copy a DBDesigner model file under the `dbd/` subdirectory of your project folder, and call the `dbd2propel` task:

```
> cd /path/to/myproject
> mkdir dbd
> cp /path/to/dbdesigner/models/* dbd/
> propel-gen dbd2propel
```

Propel looks for all `dbd/*.xml` files, converts them to the Propel XML schema format, and saves the new schemas in the project folder. For instance, if you have a DBDesigner4 model named `model.xml` under the `dbd/` directory, Propel will create a `model.schema.xml` file out of it.

Once the Propel XML schema is created, you can build the project, as described in the [Building A Project chapter](../documentation/02-buildtime).

## Customizing The Task ##

You can customize the task by overriding any of the three following settings in your `build.properties`:

```ini
# -------------------------------------------------------------------
#
#  D B D E S I G N E R   2   P R O P E L   S E T T I N G S
#
# -------------------------------------------------------------------

# Directory where the task looks for DBDesigner model files
propel.dbd2propel.dir = ${propel.project.dir}/dbd
# Pattern for DBDesigner file names
propel.dbd2propel.includes = *.xml
# XSLT used to transform DBDesigner files to Propel schemas
propel.dbd2propel.xsl.file = ${propel.home}/resources/xsl/dbd2propel.xsl
```
