---
layout: documentation
title: Working With Existing Databases
---

# Working With Existing Databases #

The following topics are targeted for developers who already have a working database solution in place, but would like to use Propel to work with the data. For this case, Propel provides a number of command-line utilities helping with migrations of data and data structures.

## Working with Database Structures ##

Propel uses an abstract XML schema file to represent databases (the [schema](../reference/schema)). Propel builds the SQL specific to a database based on this schema. Propel also provides a way to reverse-engineer the generic schema file based on database metadata.

### Creating an XML Schema from a DB Structure ###

To generate a schema file, create a new directory for your project & specify the connection information in your `build.properties` file for that project. For example, to create a new project, `legacyapp`, follow these steps:

 1. Create the `legacyapp` project directory anywhere on your filesystem:

```bash
> mkdir legacyapp
> cd legacyapp
```

 2. Create a `build.properties` file in `legacyapp/` directory with the DB connection parameters for your existing database, e.g.:

```ini
propel.project = legacyapp

# The Propel driver to use for generating SQL, etc.
propel.database = mysql

# This must be a PDO DSN
propel.database.url = mysql:dbname=legacyapp
propel.database.user = root
# propel.database.password #
```

 3. Run the `reverse` task to generate the `schema.xml`:

```bash
> propel-gen reverse
```

 4. Pay attention to any errors/warnings issued by Phing during the task execution and then examine the generated `schema.xml` file to make any corrections needed.

 5. _'You're done! _' Now you have a `schema.xml` file in the `legacyapp/` project directory. You can now run the default Propel build to generate all the classes.

The generated `schema.xml` file should be used as a guide, not a final answer. There are some datatypes that Propel may not be familiar with; also some datatypes are simply not supported by Propel (e.g. arrays in PostgreSQL). Unfamiliar datatypes will be reported as warnings and substituted with a default VARCHAR datatype.

>**Tip**<br />The reverse engineering classes may not be able to provide the same level of detail for all databases. In particular, metadata information for SQLite is often very basic since SQLite is a typeless database.

### Migrating Structure to a New RDBMS ###

Because Propel has both the ability to create XML schema files based on existing database structures and to create RDBMS-specific DDL SQL from the XML schema file, you can use Propel to convert one database into another.

To do this you would simply:

 1. Follow the steps above to create the `schema.xml` file from existing db.
 2. Then you would change the target database type and specify connection URL for new database in the project's `build.properties` file:

```ini
propel.database = pgsql
propel.database.url = pgsql://unix+localhost/newlegacyapp
```

 3. And then run the `sql` task to generate the new DDL:

```bash
> propel-gen sql
```

 4. And (optionally) the `insert-sql` task to create the new database:

```bash
> propel-gen insert-sql
```

## Working with Database Data ##

Propel also provides several tasks to facilitate data import/export. The most important of these are `datadump` and `datasql`. The first dumps data to XML and the second converts the XML data dump to a ready-to-insert SQL file.

>**Tip**<br />Both of these tasks require that you already have generated the `schema.xml` for your database.

### Dumping Data to XML ###

Once you have created (or reverse-engineered) your `schema.xml` file, you can run the `datadump` task to dump data from the database into a `data.xml` file.

```bash
> propel-gen datadump
```

The task transfers database records to XML using a simple format, where each row is an element, and each column is an attribute. So for instance, the XML representation of a row in a `publisher` table:

|publisher_id   |name
|---------------|--------------
|1              |William Morrow

... is rendered in the `data.xml` as follows:

```xml
<dataset name="all">
 ...
  <Publisher PublisherId="1" Name="William Morrow"/>
 ...
</dataset>
```

### Creating SQL from XML ###

To create the SQL files from the XML, run the `datasql` task:

```bash
> propel-gen datasql
```

The generated SQL is placed in the `build/sql/` directory and will be inserted when you run the `insert-sql` task.
