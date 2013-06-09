---
layout: documentation
title: Migrations
---

# Migrations #

During the life of a project, the Model seldom stays the same. New tables arise, and existing tables often need modifications (a new column, a new index, another foreign key...). Updating the database structure accordingly, while preserving existing data, is a common concern. Propel provides a set of tools to allow the _migration_ of database structure and data with ease.

>**Tip**<br />Propel only supports migrations in MySQL for now.

## Migration Workflow ##

The workflow of Propel migrations is very simple:

1. Edit the XML schema to modify the model
2. Call the `diff` task to create a migration class containing the SQL statements altering the database structure
3. Review the migration class Propel just generated, and add data migration code if necessary
4. Execute the migration using the `migrate` task.

Here is a concrete example. On a new bookstore project, a developer creates an XML schema with a single `book` table:

```xml
<database name="bookstore" defaultIdMethod="native">
  <table name="book" description="Book Table">
    <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />    <column name="title" type="VARCHAR" required="true" primaryString="true" />
    <column name="isbn" required="true" type="VARCHAR" size="24" phpName="ISBN" />
  </table>
</database>
```

The developer then calls the `diff` task to ask Propel to compare the database structure and the XML schema:

```
> propel-gen diff

[propel-sql-diff] Reading databases structure...
[propel-sql-diff] Database is empty
[propel-sql-diff] Loading XML schema files...
[propel-sql-diff] 1 tables found in 1 schema file.
[propel-sql-diff] Comparing models...
[propel-sql-diff] Structure of database was modified: 1 added table
[propel-sql-diff] "PropelMigration_1286483354.php" file successfully created in /path/to/project/build/migrations
[propel-sql-diff]   Please review the generated SQL statements, and add data migration code if necessary.
[propel-sql-diff]   Once the migration class is valid, call the "migrate" task to execute it.
```

It is recommended to review the generated migration class to check the generated SQL code. It contains two methods, `getUpSQL()` and `getDownSQL()`, allowing to migrate the database structure to match the updated schema, and back:

```php
<?php
/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version 1286483354.
 * Generated on 2010-10-07 22:29:14 by francois
 */
class PropelMigration_1286483354
{

	public function getUpSQL()
	{
		return array('bookstore' => '
CREATE TABLE `book`
(
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(255) NOT NULL,
	`isbn` VARCHAR(24) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT=\'Book Table\';
',
);
	}

	public function getDownSQL()
	{
		return array('bookstore' => '
DROP TABLE IF EXISTS `book`;
',
);
	}
}
```

>**Tip**<br />On a project using version control, it is important to commit the migration classes to the code repository. That way, other developers checking out the project will just have to run the same migrations to get a database in a similar state.

Now, to actually create the `book` table in the database, the developer has to call the `migrate` task:

```
> propel-gen migrate

[propel-migration] Executing migration PropelMigration_1286483354 up
[propel-migration] 1 of 1 SQL statements executed successfully on datasource "bookstore"
[propel-migration] Migration complete. No further migration to execute.
```

The `book` table is now created in the database. It can be populated with data.

After a few days, the developer wants to add a new `author` table, with a foreign key in the `book` table. The schema is modified as follows:

```xml
<database name="bookstore" defaultIdMethod="native">
  <table name="book" description="Book Table">
    <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />
    <column name="title" type="VARCHAR" required="true" primaryString="true" />
    <column name="isbn" required="true" type="VARCHAR" size="24" phpName="ISBN" />
    <column name="author_id" type="INTEGER" />
    <foreign-key foreignTable="author" onDelete="setnull" onUpdate="cascade">
      <reference local="author_id" foreign="id" />
    </foreign-key>
  </table>
  <table name="author">
    <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />
    <column name="first_name" type="VARCHAR" />
    <column name="last_name" type="VARCHAR" />
  </table>
</database>
```

In order to update the database structure accordingly, the process is the same:

```
> propel-gen diff

[propel-sql-diff] Reading databases structure...
[propel-sql-diff] 1 tables imported from databases.
[propel-sql-diff] Loading XML schema files...
[propel-sql-diff] 2 tables found in 1 schema file.
[propel-sql-diff] Comparing models...
[propel-sql-diff] Structure of database was modified: 1 added table, 1 modified table
[propel-sql-diff] "PropelMigration_1286484196.php" file successfully created in /path/to/project/build/migrations
[propel-sql-diff]   Please review the generated SQL statements, and add data migration code if necessary.
[propel-sql-diff]   Once the migration class is valid, call the "migrate" task to execute it.

> propel-gen migrate

[propel-migration] Executing migration PropelMigration_1286484196 up
[propel-migration] 4 of 4 SQL statements executed successfully on datasource "bookstore"
[propel-migration] Migration complete. No further migration to execute.
```

Propel has executed the `PropelMigration_1286484196::getUpSQL()` code, which alters the `book` structure _without removing data_:

```sql
ALTER TABLE `book` ADD
(
	`author_id` INTEGER
);

CREATE INDEX `book_FI_1` ON `book` (`author_id`);

ALTER TABLE `book` ADD CONSTRAINT `book_FK_1`
	FOREIGN KEY (`author_id`)
	REFERENCES `author` (`id`)
	ON UPDATE CASCADE
	ON DELETE SET NULL;

CREATE TABLE `author`
(
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`first_name` VARCHAR(255),
	`last_name` VARCHAR(255),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;
```

>**Tip**<br />`diff` and `migrate` often come one after the other, so you may want to execute them both in one call. That's possible, provided that the first argument of the `propel-gen` script is the path to the current project:

```
> propel-gen . diff migrate
```

## Migration Tasks ##

The two basic migration tasks are `diff` and `migrate` - you already know them. `diff` creates a migration class, and `migrate` executes the migrations. But there are three more migration tasks that you will find very useful.

### Migration Up or Down, One At A Time ###

In the previous example, two migrations were executed. But the developer now wants to revert the last one. The `down` task provides exactly this feature: it reverts only one migration.

```
> propel-gen down

[propel-migration-down] Executing migration PropelMigration_1286484196 down
[propel-migration-down] 4 of 4 SQL statements executed successfully on datasource "bookstore"
[propel-migration-down] Reverse migration complete. 1 more migrations available for reverse.
```

Notice that the `PropelMigration_1286484196` was executed _down_, not _up_ like the previous time. You can call this command several times to continue reverting the database structure, up to its original state:

```
> propel-gen down

[propel-migration-down] Executing migration PropelMigration_1286483354 down
[propel-migration-down] 1 of 1 SQL statements executed successfully on datasource "bookstore"
[propel-migration-down] Reverse migration complete. No more migration available for reverse
```

As you may have guessed, the `up` task does exactly the opposite: it executes the next migration up:

```
> propel-gen up

[propel-migration-up] Executing migration PropelMigration_1286483354 up
[propel-migration-up] 1 of 1 SQL statements executed successfully on datasource "bookstore"
[propel-migration-up] Migration complete. 1 migrations left to execute.
```

>**Tip**<br />The difference between the `up` and `migrate` tasks is that `up` executes only one migration, while `migrate` executes all the migrations that were not yet executed.

### Migration Status ###

If you followed the latest example, you may notice that the schema and the database should now be desynchronized. By calling `down` twice, and `up` just once, there is one migration left to execute. This kind of situation sometimes happen in the life of a project: you don't really know which migrations were already executed, and which ones need to be executed now.

For these situations, Propel provides the `status` task. It simply lists the migrations not yet executed, to help you understand where you are in the migration process.

```
> propel-gen status

[propel-migration-status] Checking Database Versions...
[propel-migration-status] Listing Migration files...
[propel-migration-status] 1 migration needs to be executed:
[propel-migration-status]    PropelMigration_1286484196
[propel-migration-status] Call the "migrate" task to execute it
```

>**Tip**<br />Like all other Propel tasks, `status` offers a "verbose" mode, where the CLI output shows a lot more details. Add `-verbose` at the end of the call to enable it - but remember to add the path to the project as first argument:

```
> propel-gen . status -verbose
[propel-migration-status] Checking Database Versions...
[propel-migration-status] Connecting to database "bookstore" using DSN "mysql:dbname=bookstore"
[propel-migration-status] Latest migration was executed on 2010-10-07 22:29:14 (timestamp 1286483354)
[propel-migration-status] Listing Migration files...
[propel-migration-status] 2 valid migration classes found in "/Users/francois/propel/1.6/test/fixtures/migration/build/migrations"
[propel-migration-status] 1 migration needs to be executed:
[propel-migration-status]  > PropelMigration_1286483354 (executed)
[propel-migration-status]    PropelMigration_1286484196
[propel-migration-status] Call the "migrate" task to execute it
```

`up`, `down`, and `status` will help you to find your way in migration files, especially when they become numerous or when you need to revert more than one.

>**Tip**<br />There is no need to keep old migration files if you are sure that you won't ever need to revert to an old state. If a new developer needs to setup the project from scratch, the `sql` and `insert-sql` tasks will initialize the database structure to the current XML schema.

## How Do Migrations Work? ##

The Propel `diff` task creates migration class names (like `PropelMigration_1286483354`) using the timestamp of the date they were created. Not only does it make the classes automatically sorted by date in a standard directory listing, it also avoids collision between two developers working on two structure changes at the same time.

Propel creates a special table in the database, where it keeps the date of the latest executed migration. That way, by comparing the available migrations and the date of the latest ones, Propel can determine the next migration to execute.

```
mysql> select * from propel_migration;
+------------+
| version    |
+------------+
| 1286483354 |
+------------+
1 row in set (0.00 sec)
```

So don't be surprised if your database show a `propel_migration` table that you never added to your schema - this is the Propel migration table. Propel doesn't use this table at runtime, and it never contains more than one line, so it should not bother you.

## Migration Configuration ##

The migration tasks support customization through a few settings from `build.properties`:

```ini
# Name of the table Propel creates to keep the latest migration date
propel.migration.table = propel_migration
# Whether the comparison between the XML schema and the database structure
# cares for differences in case (e.g. 'my_table' and 'MY_TABLE')
propel.migration.caseInsensitive = true
# The directory where migration classes are generated and looked for
propel.migration.dir = ${propel.output.dir}/migrations
```

>**Tip**<br />The `diff` task supports an additional parameter, called `propel.migration.editor`, which specifies a text editor to be automatically launched at the end of the task to review the generated migration. Unfortunately, only editors launched in another window are accepted due to a Phing limitation. Mac users will find it useful, though:

```
> propel-gen . diff -Dpropel.migration.editor=mate
```

## Migrating Data ##

Propel generates the SQL code to alter the database structure, but your project may require more. For instance, in the newly added `author` table, the developer may want to add a few records.

That's why Propel automatically executes the `preUp()` and `postUp()` migration before and after the structure migration. If you want to add data migration, that's the place to put the related code.

Each of these methods receive a `PropelMigrationManager` instance, which is a good way to get PDO connection instances based on the buildtime configuration.

Here is an example implementation of data migration:

```php
<?php
class PropelMigration_1286483354
{
	// do nothing before structure change
	public function preUp($manager)
	{
	}

	// structure change (generated by Propel)
	public function getUpSQL()
	{
		return array('bookstore' => '
ALTER TABLE `book` ADD
(
	`author_id` INTEGER
);
//...
');
	}

	public function postUp($manager)
	{
		// post-migration code
		$sql = "INSERT INTO author (first_name,last_name) values('Leo','Tolstoi')";
		$pdo = $manager->getPdoConnection('bookstore');
		$stmt = $pdo->prepare($sql);
		$stmt->execute();
	}
}
```

>**Tip**<br />If you return `false` in the `preUp()` method, the migration is aborted.

You can also use Propel ActiveRecord and Query objects, but you'll then need to bootstrap the `Propel` class and the runtime autoloading in the migration class. This is because the Propel CLI does not know where the runtime classes are.

```php
<?php

// bootstrap the Propel runtime
require_once '/path/to/runtime/lib/Propel.php';
set_include_path('/path/to/build/classes' . PATH_SEPARATOR . get_include_path());
Propel::init('/path/to/build/conf/bookstore-conf.php');

class PropelMigration_1286483354
{

	public function postUp($manager)
	{
		// add the post-migration code here
		$pdo = $manager->getPdoConnection('bookstore');
		$author = new Author();
		$author->setFirstName('Leo');
		$author->setLastname('Tolstoi');
		$author->save($pdo);
	}

	public function getUpSQL()
	{
		// ...
	}
}
```

Of course, you can add code to the `preDown()` and `postDown()` methods to execute a data migration when reverting migrations.
