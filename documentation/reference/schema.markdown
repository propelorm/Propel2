---
layout: documentation
title: "Database Schema"
---

# Database Schema #

The schema for `schema.xml` contains a small number of elements with required and optional attributes. The Propel generator contains a DTD that can be used to validate your `schema.xml` document. Also, when you build your SQL and OM, the Propel generator will automatically validate your `schema.xml` file using a highly-detailed XSD.

## At-a-Glance ##

The hierarchical tree relationship for the elements is:

```xml
<database>
   <table>
     <column>
       <inheritance />
     </column>
     <foreign-key>
       <reference />
     </foreign-key>
     <index>
       <index-column />
     </index>
     <unique>
       <unique-column />
     </unique>
     <id-method-parameter/>
   </table>
   <external-schema />
</database>
```

You can find example schemas in the test fixtures that the Propel development team uses for unit testing. For instance, the bookstore schema describes the model of a Bookstore application.

>**Tip**<br />If you use an IDE supporting autocompletion in XML documents, you can take advantage of the XSD describing the `schema.xml` syntax to suggest elements and attributes as you type. To enable it, add a `xmlns:xsi` and a `xsi:noNamespaceSchemaLocation` attribute to the leading `<database>` tag:

```xml
<database name="my_connection_name" defaultIdMethod="native"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:noNamespaceSchemaLocation="http://xsd.propelorm.org/1.6/database.xsd" >
```

## Detailed Reference ##

This page provides an alternate rendering of the Appendix B - Schema Reference from the user's guide.
It spells out in specific detail, just where each attribute or element belongs.

First, some conventions:

*  Text surrounded by a `/` is text that you would provide and is not defined in the language. (i.e. a table name is a good example of this.)
*  Optional items are surrounded by `[` and `]` characters.
*  Items where you have an alternative choice have a `|` character between them (i.e. true|false)
*  Alternative choices may be delimited by  `{` and `}` to indicate that this is the default option, if not overridden elsewhere.
*  **...** means repeat the previous item.

### database element ###

Starting with the `<database>` element. The _attributes_ and _elements_ available are:

```xml
<database
  name="/DatabaseName/"
  defaultIdMethod="native|none"
  [package="/ProjectName/"]
  [schema="/SQLSchema/"]
  [namespace="/ClassNamespace/"]
  [baseClass="/baseClassName/"]
  [defaultPhpNamingMethod="nochange|{underscore}|phpname|clean"
  [heavyIndexing="true|false"]
  [tablePrefix="/tablePrefix/"]
>
  <table>
  <external-schema>
  ...
</database>
```

Only the `name` and the `defaultIdMethod` attributes are required.

A Database element may include an `<external-schema>` element, or multiple `<table>` elements.

#### Database Attributes ####

* `defaultIdMethod` sets the default id method to use for auto-increment columns.
* `package` specifies the "package" for the generated classes. Classes are created in subdirectories according to the `package` value.
* `schema` specifies the default SQL schema containing the tables. Ignored on RDBMS not supporting database schemas.
* `namespace` specifies the default namespace that generated model classes will use (PHP 5.3 only). This attribute can be completed or overridden at the table level.
* `baseClass` allows you to specify a default base class that all generated Propel objects should extend (in place of `propel.om.BaseObject`).
* `defaultPhpNamingMethod` the default naming method to use for tables of this database. Defaults to `underscore`, which transforms table names into CamelCase phpNames.
* `heavyIndexing` adds indexes for each component of the primary key (when using composite primary keys).
* `tablePrefix` adds a prefix to all the SQL table names.

### table element ###

The `<table>` element is the most complicated of the usable elements. Its definition looks like this:

```xml
<table
  name = "/TableName/"
  [idMethod = "native|{none}"]
  [phpName = "/PhpObjectName/"]
  [package="/PhpObjectPackage/"]
  [schema="/SQLSchema/"]
  [namespace = "/PhpObjectNamespace/"]
  [skipSql = "true|false"]
  [abstract = "true|false"]
  [phpNamingMethod = "nochange|{underscore}|phpname|clean"]
  [baseClass = "/baseClassName/"]
  [description="/A text description of the table/"]
  [heavyIndexing = "true|false"]
  [readOnly = "true|false"]
  [treeMode = "NestedSet|MaterializedPath"]
  [reloadOnInsert = "true|false"]
  [reloadOnUpdate = "true|false"]
  [allowPkInsert = "true|false"]
>

  <column>
  ...
  <foreign-key>
  ...
  <index>
  ...
  <unique>
  ...
  <id-method-parameter>
  ...
</table>
```

According to the schema, `name` is the only required attribute.  Also, the `idMethod`, `package`, `schema`, `namespace`, `phpNamingMethod`, `baseClass`, and `heavyIndexing` attributes all default to what is specified by the `<database>` element.

#### Table Attributes ####

* `idMethod` sets the id method to use for auto-increment columns.
* `phpName` specifies object model class name. By default, Propel uses a CamelCase version of the table name as phpName.
* `package` specifies the "package" (or subdirectory) in which model classes get generated.
* `schema` specifies the default SQL schema containing the table. Ignored on RDBMS not supporting database schemas.
* `namespace` specifies the namespace that the generated model classes will use (PHP 5.3 only). If the table namespace starts with a `\\`, it overrides the namespace defined in the `<database>` tag; otherwise, the actual table namespace is the concatenation of the database namespace and the table namespace.
* `skipSql` instructs Propel not to generate DDL SQL for the specified table. This can be used together with `readOnly` for supporting VIEWS in Propel.
* `abstract` Whether the generated _stub_ class will be abstract (e.g. if you're using inheritance)
* `phpNamingMethod` the naming method to use. Defaults to `underscore`, which transforms the table name into a CamelCase phpName.
* `baseClass` allows you to specify a class that the generated Propel objects should extend (in place of `propel.om.BaseObject`).
* `heavyIndexing` adds indexes for each component of the primary key (when using composite primary keys).
* `readOnly` suppresses the mutator/setter methods, save() and delete() methods.
* `treeMode` is used to indicate that this table is part of a node tree. Currently the only supported values are `NestedSet` (see the [NestedSet behavior section](../behaviors/nested-set.html)) and `MaterializedPath` (deprecated).
* `reloadOnInsert` is used to indicate that the object should be reloaded from the database when an INSERT is performed.  This is useful if you have triggers (or other server-side functionality like column default expressions) that alters the database row on INSERT.
* `reloadOnUpdate` is used to indicate that the object should be reloaded from the database when an UPDATE is performed.  This is useful if you have triggers (or other server-side functionality like column default expressions) that alters the database row on UPDATE.
* `allowPkInsert` can be used if you want to define the primary key of a new object being inserted. By default if idMethod is "native", Propel would throw an exception. However, in some cases this feature is useful, for example if you do some replication of data in an master-master environment. It defaults to false.

### column element ###

```xml
<column
  name = "/ColumnName/"
  [phpName = "/PHPColumnName/"]
  [tableMapName = "/TABLEMAPNAME/"]
  [primaryKey = "true|{false}"]
  [required = "true|{false}"]
  [type = "BOOLEAN|TINYINT|SMALLINT|INTEGER|BIGINT|DOUBLE|FLOAT|REAL|DECIMAL|CHAR|{VARCHAR}|LONGVARCHAR|DATE|TIME|TIMESTAMP|BLOB|CLOB|OBJECT|ARRAY"]
  [phpType = "boolean|int|integer|double|float|string|/BuiltInClassName/|/UserDefinedClassName/"]
  [sqlType = "/NativeDatabaseColumnType/"
  [size = "/NumericLengthOfColumn/"]
  [scale = "/DigitsAfterDecimalPlace/"]
  [defaultValue = "/AnyDefaultValueMatchingType/"]
  [defaultExpr = "/AnyDefaultExpressionMatchingType/"]
  [valueSet = "/CommaSeparatedValues/"]
  [autoIncrement = "true|{false}"]
  [lazyLoad = "true|{false}"]
  [description = "/Column Description/"]
  [primaryString = "true|{false}"]
  [phpNamingMethod = "nochange|underscore|phpname"]
  [inheritance = "single|{false}"]
  >
    [<inheritance key="/KeyName/" class="/ClassName/" [extends="/BaseClassName/"] />]
</column>
```

#### Column Attributes ####

* `type` The database-agnostic column type. Propel maps native SQL types to these types depending on the RDBMS. Using Propel types guarantees that a column definition is portable.
* `sqlType` The SQL type to be used in CREATE and ALTER statements (overriding the mapping between Propel types and RMDBS type)
* `defaultValue` The default value that the object will have for this column in the PHP instance after creating a "new Object". This value is always interpreted as a string.
* `defaultExpr` The default value for this column as expressed in SQL. This value is used solely for the "sql" target which builds your database from the schema.xml file. The defaultExpr is the SQL expression used as the "default" for the column.
* `valueSet` The list of enumerated values accepted on an ENUM column. The list contains 255 values at most, separated by commas.
* `lazyLoad` A lazy-loaded column is not fetched from the database by model queries. Only the generated getter method for such a column issues a query to the database. Useful for large column types (such as CLOB and BLOB).
* `primaryString` A column defined as primary string serves as default value for a `__toString()` method in the generated Propel object.

>**Tip**<br />For performance reasons, it is often a good idea to set BLOB and CLOB columns as lazyLoaded. A resultset containing one of more very large columns takes time to transit between the database and the PHP server, so you want to make sure this only happen when you actually need it.

### foreign-key element ###

To link a column to another table use the following syntax:

```xml
<foreign-key
  foreignTable = "/TheOtherTableName/"
  [foreignSchema = "/TheOtherTableSQLSchema/"]
  [name = "/Name for this foreign key/"]
  [phpName = "/Name for the foreign object in methods generated in this class/"]
  [refPhpName = "/Name for this object in methods generated in the foreign class/"]
  [onDelete = "cascade|setnull|restrict|none"]
  [onUpdate = "cascade|setnull|restrict|none"]
  [skipSql = "true|false"]
  [defaultJoin= "Criteria::INNER_JOIN|Criteria::LEFT_JOIN"]
>
  <reference local="/LocalColumnName/" foreign="/ForeignColumnName/" />
</foreign-key>
```

#### Foreign Key Attributes ####

* `skipSql` Instructs Propel not to generate DDL SQL for the specified foreign key. This can be used to support relationships in the model without an actual foreign key.
* `defaultJoin` This affects the default join type used in the generated `joinXXX()` methods in the model query class. Propel uses an INNER JOIN for foreign keys attached to a required column, and a LEFT JOIN for foreign keys attached to a non-required column, but you can override this in the foreign key element.

### index element ###

To create an index on one or more columns, use the following syntax:

```xml
<index [name="/IndexName/"]>
  <index-column name="/ColumnName/" [size="/LengthOfIndexColumn/"] />
  ...
</index>
```

In some cases your RDBMS may require you to specify an index size.

### unique element ###

To create a unique index on one or more columns, use the following syntax:

```xml
<unique [name="/IndexName/"]>
  <unique-column name="/ColumnName/" [size="/LengthOfIndexColumn/"] />
  ...
</unique>
```

In some cases your RDBMS may require you to specify an index size for unique indexes.

### id-method-parameter element ###

If you are using a database that uses sequences for auto-increment columns (e.g. PostgreSQL or Oracle), you can customize the name of the sequence using  the `<id-method-parameter>` tag:

```xml
<id-method-parameter value="my_custom_sequence_name"/>
```

### external-schema element ###

The `<external-schema>` element includes another schema file from the filesystem into the current schema. The format is:

```xml
<external-schema
  filename="/a path to a file/"
  referenceOnly="{true}|false"
/>
```

The `filename` can be relative or absolute. Beware that the external schema must contain a `<database>` with the same name as the current element. By default, tables from external schemas are ignored by the `sql` task - that means that Propel won't try to _insert_ the external tables. If you want Propel to take the tables from an external schema into account in SQL, set the `referenceOnly` attribute to `false`.

## Column Types ##

Here are the Propel column types with some example mappings to native database and PHP types. There are also several ways to customize the mapping between these types.

### Text Types ###

|Propel Type|Desc                               |Example Default DB Type (MySQL)|Default PHP Native Type
|-----------|-----------------------------------|-------------------------------|-----------------------
|CHAR       |Fixed-length character data        |CHAR                           |string
|VARCHAR    |Variable-length character data     |VARCHAR                        |string
|LONGVARCHAR|Long variable-length character data|TEXT                           |string
|CLOB       |Character LOB (locator object)     |LONGTEXT                       |string

`LONGVARCHAR` and `CLOB` need no declared size, and allow for very large strings (up to 2^16 and 2^32 characters in MySQL for instance).

### Numeric Types ###

|Propel Type|Desc                   |Example Default DB Type (MySQL)|Default PHP Native Type
|-----------|-----------------------|-------------------------------|---------------------------
|NUMERIC    |Numeric data           |DECIMAL                        |string (PHP int is limited)
|DECIMAL    |Decimal data           |DECIMAL                        |string (PHP int is limited)
|TINYINT    |Tiny integer           |TINYINT                        |int
|SMALLINT   |Small integer          |SMALLINT                       |int
|INTEGER    |Integer                |INTEGER                        |int
|BIGINT     |Large integer          |BIGINT                         |string (PHP int is limited)
|REAL       |Real number            |REAL                           |double
|FLOAT      |Floating point number  |FLOAT                          |double
|DOUBLE     |Floating point number  |DOUBLE                         |double

>**Tip**<br />`BIGINT` maps to a PHP string, and therefore allows for 64 bit integers even on 32 bit systems.

### Binary Types ###

|Propel Type    |Desc                               |Example Default DB Type (MySQL)|Default PHP Native Type
|---------------|-----------------------------------|-------------------------------|-----------------------
|BINARY         |Fixed-length binary data           |BLOB                           |double
|VARBINARY      |Variable-length binary data        |MEDIUMBLOB                     |double
|LONGVARBINARY  |Long variable-length binary data   |LONGBLOB                       |double
|BLOB           |Binary LOB (locator object)        |LONGBLOB                       |stream or string

>**Tip**<br />`BLOB` columns map to PHP as streams, and allows the storage of large binary objects (like images).

### Temporal (Date/Time) Types ###

|Propel Type|Desc                                   |Example Default DB Type (MySQL)|Default PHP Native Type
|-----------|---------------------------------------|-------------------------------|-----------------------
|DATE       |Date (e.g. YYYY-MM-DD)                 |DATE                           |DateTime object
|TIME       |Time (e.g. HH:MM:SS)                   |TIME                           |DateTime object
|TIMESTAMP  |Date + time (e.g. YYYY-MM-DD HH:MM:SS) |TIMESTAMP                      |DateTime object

### Other Types ###

* `BOOLEAN` columns map to a boolean in PHP. Depending on the native support for this type, they are stored in SQL as `BOOLEAN` or `TINYINT`.
* `ENUM` columns accept values among a list of predefined ones. Set the value set using the `valueSet` attribute, separated by commas.
* `OBJECT` columns map to PHP objects and are stored as strings.
* `ARRAY` columns map to PHP arrays and are stored as strings.

### Legacy Temporal Types ###

The following Propel 1.2 types are still supported, but are no longer needed with Propel 1.3.

|Propel Type    |Desc                                                   |Example Default DB Type (MySQL)|Default PHP Native Type
|---------------|-------------------------------------------------------|-------------------------------|-----------------------
|BU_DATE        |Pre-/post-epoch date (e.g. 1201-03-02)                 |DATE                           |DateTime object
|BU_TIMESTAMP   |Pre-/post-epoch Date + time (e.g. 1201-03-02 12:33:00) |TIMESTAMP                      |DateTime object

## Customizing Mappings ##

### Specify Column Attributes ###

You can change the way that Propel maps its own types to native SQL types or to PHP types by overriding the values for a specific column.

For example:

(Overriding PHP type)

```xml
<column name="population_served" type="INTEGER" phpType="string"/>
```

(Overriding SQL type)

```xml
<column name="ip_address" type="VARCHAR" sqlType="inet"/>
```

### Adding Vendor Info ###

Propel supports database-specific elements in the schema (currently only for MySQL). This "vendor" parameters affect the generated SQL. To add vendor data, add a `<vendor>` tag with a `type` attribute specifying the target database vendor. In the `<vendor>` tag, add `<parameter>` tags with a `name` and a `value` attribute. For instance:

```xml
<table name="book">
  <vendor type="mysql">
    <parameter name="Engine" value="InnoDB"/>
    <parameter name="Charset" value="utf8"/>
  </vendor>
</table>
```

This will change the generated SQL table creation to look like:

```sql
CREATE TABLE book
  ()
  ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8;
```

#### MySQL Vendor Info ####

Propel supports the following vendor parameters for MySQL:

```
Name             | Example values
-----------------|---------------
// in <table> element
Engine           | MYISAM (default), InnoDB, BDB, MEMORY, ISAM, MERGE, MRG_MYISAM, etc.
AutoIncrement    | 1234, N, etc
AvgRowLength     |
Charset          | utf8, latin1, etc.
Checksum         | 0, 1
Collate          | utf8_unicode_ci, latin1_german1_ci, etc.
Connection       | mysql://fed_user@remote_host:9306/federated/test_table (for FEDERATED storage engine)
DataDirectory    | /var/db/foo (for MyISAM storage engine)
DelayKeyWrite    | 0, 1
IndexDirectory   | /var/db/foo (for MyISAM storage engine)
InsertMethod     | FIRST, LAST (for MERGE storage Engine)
KeyBlockSize     | 0 (default), 1024, etc
MaxRows          | 1000, 4294967295, etc
MinRows          | 1000 (for MEMORY storage engine)
PackKeys         | 0, 1, DEFAULT
RowFormat        | FIXED, DYNAMIC, COMPRESSED, COMPACT, REDUNDANT
Union            | (t1,t2)  (for MERGE storage Engine)
// in <column> element
Charset          | utf8, latin1, etc.
Collate          | utf8_unicode_ci, latin1_german1_ci, etc.
// in <index> element
Index_type       | FULLTEXT
```

#### Oracle Vendor Info ####

Propel supports the following vendor parameters for Oracle:

```
Name             | Example values
-----------------|---------------
// in <table> element
PCTFree          | 20
InitTrans        | 4
MinExtents       | 1
MaxExtents       | 99
PCTIncrease      | 0
Tablespace       | L_128K
PKPCTFree        | 20
PKInitTrans      | 4
PKMinExtents     | 1
PKMaxExtents     | 99
PKPCTIncrease    | 0
PKTablespace     | IL_128K
// in <index> element
PCTFree          | 20
InitTrans        | 4
MinExtents       | 1
MaxExtents       | 99
PCTIncrease      | 0
Tablespace       | L_128K
```

### Using Custom Platform ###

For overriding the mapping between Propel types and native SQL types, you can create your own Platform class and override the mapping.

For example:

```php
<?php
require_once 'propel/engine/platform/MysqlPlatform.php';

class CustomMysqlPlatform extends MysqlPlatform
{
  /**
   * Initializes custom domain mapping.
   */
  protected function initialize()
  {
    parent::initialize();
    $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, "DECIMAL"));
    $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "TEXT"));
    $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BLOB"));
    $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "MEDIUMBLOB"));
    $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "LONGBLOB"));
    $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "LONGBLOB"));
    $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "LONGTEXT"));
  }
}
```

You must then specify that mapping in the `build.properties` for your project:

    propel.platform.class = propel.engine.platform.${propel.database}Platform

