---
layout: documentation
title: Adding Additional SQL Files
---

# Adding Additional SQL Files #

In many cases you may wish to have the _insert-sql_ task perform additional SQL operations (e.g. add views, stored procedures, triggers, sample data, etc.).  Rather than have to run additional SQL statements yourself every time you re-build your object model, you can have the Propel generator do this for you.

## 1. Create the SQL DDL files ##

Create any additional SQL files that you want executed against the database (after the base _schema.sql_ file is applied).

For example, if we wanted to add a default value to a column that was unsupported in the schema (e.g. where value is a SQL function):

```sql
-- (for postgres)
ALTER TABLE my_table ALTER COLUMN my_column SET DEFAULT CURRENT_TIMESTAMP;
```

Now we save that as _'my_column-default.sql_' in the same directory as the generated _'schema.sql_' file (usually in projectdir/build/sql/).

## 2. Tell Propel Generator about the new file ##

In that same directory (where your _'schema.sql_' is located), there is a _'sqldb.map_' file which contains a mapping of SQL DDL files to the database that they should be executed against.  After running the propel generator, you will probably have a single entry in that file that looks like:

```
schema.sql=your-db-name
```

We want to simply add the new file we created to this file (future builds will preserve anything you add to this file).  When we're done, the file will look like this:

```
schema.sql=your-db-name
my_column-default.sql=your-db-name
```

Now when you execute the _insert-sql_ Propel generator target, the _'my_column-default.sql_' file will be executed against the _your-db-name_ database.
