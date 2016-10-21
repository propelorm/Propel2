<!--
    Awesome, your propel set up is nearly done! You just have to describe how you want your database to look like.

    You can let propel set up your <?php echo $rdbms ?> database by running `vendor/bin/propel database:create && vendor/bin/propel database:insert-sql`.
    This will create your database including all the entities.
-->

<!--
    The root tag of the XML schema is the <database> tag.

    The `name` attribute defines the name of the connection that Propel uses for the entities in this schema. It is not
    necessarily the name of the actual database. In fact, Propel uses some configuration properties to link a connection
    name with real connection settings (like database name, user and password).

    The `defaultIdMethod` attribute indicates that the entities in this schema use the database's "native"
    auto-increment/sequence features to handle id fields that are set to auto-increment.

   [TIP]: You can define several schemas for a single project. Just make sure that each of the schema
          filenames end with schema.xml.
-->
<database name="default" defaultIdMethod="native"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          namespace="<?php echo $namespace?>"
        >
    <!-- Within the <database> tag, Propel expects one <entity> tag for each entity -->


    <!--
        Each entity element should have a `name` attribute. It will be used for naming the sql entity.

        The `phpName` is the name that Propel will use for the generated PHP class. By default, Propel uses a
        CamelCase version of the entity name as its phpName - that means that you could omit the `phpName` attribute
        on our `book` entity.
    -->
    <entity name="Book">
        <!--
            Each field has a `name` (the one used by the database), and an optional `phpName` attribute. Once again,
            the Propel default behavior is to use a CamelCase version of the name as `phpName` when not specified.

            Each field also requires a `type`. The XML schema is database agnostic, so the field types and attributes
            are probably not exactly the same as the one you use in your own database. But Propel knows how to map the
            schema types with SQL types for many database vendors. Existing Propel field types are:
            `boolean`, `tinyint`, `smallint`, `integer`, `bigint`, `double`, `float`, `real`, `decimal`, `char`,
            `varchar`, `longvarchar`, `date`, `time`, `timestamp`, `blob`, `clob`, `object`, and `array`.

            Some field types use a size (like `varchar` and `int`), some have unlimited size (`longvarchar`, `clob`,
            `blob`).

            Check the (schema reference)[http://propelorm.org/reference/schema.html] for more details
            on each field type.

            As for the other field attributes, `required`, `primaryKey`, and `autoIncrement`, they mean exactly
            what their names imply.
        -->
        <field name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <field name="title" type="varchar" size="255" required="true"/>
        <field name="isbn" type="varchar" size="24" required="true"/>
        <field name="publisher_id" type="integer" required="true"/>
        <field name="author_id" type="integer" required="true"/>

        <!--
            A <rerlation> represents a relationship. Just like a entity or a field, a relationship has a `phpName`.

            The `refPhpName` defines the name of the relation as seen from the foreign entity.
        -->
        <relation target="publisher">
            <reference local="publisher_id" foreign="id"/>
        </relation>
        <relation foreignEntity="author">
            <reference local="author_id" foreign="id"/>
        </relation>
    </entity>

    <entity name="author">
        <field name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <field name="first_name" type="varchar" size="128" required="true"/>
        <field name="last_name" type="varchar" size="128" required="true"/>
    </entity>

    <entity name="publisher">
        <field name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <field name="name" type="varchar" size="128" required="true"/>
    </entity>

    <!--
        When you're done with editing, open a terminal and run
            `$ cd <?php echo $schemaDir ?>`
            `$ vendor/bin/propel build`
        to generate the model classes.

        You should now be able to perform basic crud operations with your models. To learn how to use these models
        please look into our documentation: http://propelorm.org/documentation/03-basic-crud.html
    -->
</database>
