<?xml version="1.0" encoding="ISO-8859-1"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
	<!-- XML Schema for the Propel schema file
		  This is just the first draft derived from the existing DTD
		  and some additional restrictions have been included

		  Comments are a quite rare, I guess most things are pretty
		  readable. An additional xml schema: custom_datatypes.xsd is
		  also included. For now this file is unused, but that will
		  change; don't worry.

		  Ron -->

	<xs:include schemaLocation="custom_datatypes.xsd"/>

	<xs:element name="database" type="database"/>
	<xs:element name="vendor" type="vendor"/>

	<xs:simpleType name="file">
		<xs:restriction base="xs:string">
			<!-- Match any relative or absolute path and file containing letters, numbers and _ -->
			<xs:pattern value="((\.{1,2}|[\w_]*)/)*([\w_]*\.?)+"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="default_datatypes">
		<xs:restriction base="xs:string">
			<xs:enumeration value="BIT"/>
			<xs:enumeration value="TINYINT"/>
			<xs:enumeration value="SMALLINT"/>
			<xs:enumeration value="INTEGER"/>
			<xs:enumeration value="BIGINT"/>
			<xs:enumeration value="FLOAT"/>
			<xs:enumeration value="REAL"/>
			<xs:enumeration value="NUMERIC"/>
			<xs:enumeration value="DECIMAL"/>
			<xs:enumeration value="CHAR"/>
			<xs:enumeration value="VARCHAR"/>
			<xs:enumeration value="LONGVARCHAR"/>
			<xs:enumeration value="DATE"/>
			<xs:enumeration value="TIME"/>
			<xs:enumeration value="TIMESTAMP"/>
			<xs:enumeration value="BINARY"/>
			<xs:enumeration value="VARBINARY"/>
			<xs:enumeration value="LONGVARBINARY"/>
			<xs:enumeration value="NULL"/>
			<xs:enumeration value="OTHER"/>
			<xs:enumeration value="PHP_OBJECT"/>
			<xs:enumeration value="DISTINCT"/>
			<xs:enumeration value="STRUCT"/>
			<xs:enumeration value="ARRAY"/>
			<xs:enumeration value="BLOB"/>
			<xs:enumeration value="CLOB"/>
			<xs:enumeration value="REF"/>
			<xs:enumeration value="BOOLEANINT"/>
			<xs:enumeration value="BOOLEANCHAR"/>
			<xs:enumeration value="DOUBLE"/>
			<xs:enumeration value="BOOLEAN"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="datatype">
		<xs:union memberTypes="default_datatypes custom_datatypes"/>
	</xs:simpleType>

	<xs:simpleType name="dbidmethod">
		<xs:restriction base="xs:string">
			<xs:enumeration value="native"/>
			<xs:enumeration value="none"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="tbidmethod">
		<xs:restriction base="xs:string">
			<xs:enumeration value="autoincrement"/>
			<xs:enumeration value="sequence"/>
			<xs:enumeration value="null"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="idmethod">
		<xs:union memberTypes="dbidmethod tbidmethod"/>
	</xs:simpleType>

	<xs:simpleType name="phpnamingmethod">
		<xs:restriction base="xs:string">
			<xs:enumeration value="nochange"/>
			<xs:enumeration value="underscore"/>
			<xs:enumeration value="phpname"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="delete">
		<xs:restriction base="xs:string">
			<xs:enumeration value="cascade"/>
			<xs:enumeration value="set null"/>
			<xs:enumeration value="setnull"/>
			<xs:enumeration value="restrict"/>
			<xs:enumeration value="none"/>
			<xs:enumeration value=""/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="update">
		<xs:restriction base="xs:string">
			<xs:enumeration value="cascade"/>
			<xs:enumeration value="setnull"/>
			<xs:enumeration value="set null"/>
			<xs:enumeration value="restrict"/>
			<xs:enumeration value="none"/>
			<xs:enumeration value=""/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="rulename">
		<xs:restriction base="xs:string">
			<xs:enumeration value="mask"/>
			<xs:enumeration value="maxLength"/>
			<xs:enumeration value="maxValue"/>
			<xs:enumeration value="minLength"/>
			<xs:enumeration value="minValue"/>
			<xs:enumeration value="required"/>
			<xs:enumeration value="unique"/>
			<xs:enumeration value="validValues"/>
			<xs:enumeration value="notMatch"/>
			<xs:enumeration value="match"/>
			<xs:enumeration value="class"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="inh_option">
		<xs:restriction base="xs:string">
			<xs:enumeration value="single"/>
			<xs:enumeration value="false"/>
		</xs:restriction>
	</xs:simpleType>
	
	<xs:simpleType name="sql_type">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w\s\[\]\(\),\.']+"/>
		</xs:restriction>
	</xs:simpleType>
	
	<xs:simpleType name="php_type">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w_]+"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:simpleType name="treemode">
		<xs:restriction base="xs:string">
			<xs:enumeration value="AdjacencyList"/>
			<xs:enumeration value="MaterializedPath"/>
			<xs:enumeration value="NestedSet"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Visibility for column accessor and mutator methods -->
	<xs:simpleType name="visibility">
		<xs:restriction base="xs:string">
			<xs:enumeration value="public"/>
			<xs:enumeration value="protected"/>
			<xs:enumeration value="private"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Restrict column name to letters (upper- and lowercase), numbers and the _ -->
	<xs:simpleType name="column_name">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w_]+"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Restrict php name to letters (upper- and lowercase), numbers and the _ -->
	<xs:simpleType name="php_name">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w_]+"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Restrict php class name to letters (upper- and lowercase), numbers and the _. Dot seperated -->
	<xs:simpleType name="php_class">
		<xs:restriction base="xs:string">
			<xs:pattern value="([\w_]+.?)+"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Restrict table name to letters (upper- and lowercase), numbers and the _ -->
	<xs:simpleType name="table_name">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w_]+"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Restrict index name to letters (upper- and lowercase), numbers and the _ -->
	<xs:simpleType name="index_name">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w_]+"/>
		</xs:restriction>
	</xs:simpleType>

	<!-- Restrict foreign column name to letters (upper- and lowercase), numbers and the _ -->
	<xs:simpleType name="foreign_name">
		<xs:restriction base="xs:string">
			<xs:pattern value="[\w_]+"/>
		</xs:restriction>
	</xs:simpleType>

	<xs:complexType name="parameter">
		<xs:attribute name="name" type="xs:string" use="required"/>
		<xs:attribute name="value" type="xs:string" use="required"/>
	</xs:complexType>

	<xs:complexType name="validator">
		<xs:sequence>
			<xs:element name="rule" type="rule" maxOccurs="unbounded"/>
		</xs:sequence>
		<xs:attribute name="column" type="column_name" use="required"/>
		<xs:attribute name="translate" type="xs:string" use="optional"/>
	</xs:complexType>

	<xs:complexType name="vendor">
		<xs:sequence>
			<xs:element name="parameter" type="parameter" maxOccurs="unbounded"/>
		</xs:sequence>
		<xs:attribute name="type" use="required"/>
	</xs:complexType>

	<xs:complexType name="rule">
		<xs:attribute name="name" type="rulename" use="required"/>
		<xs:attribute name="value" type="xs:string" use="optional"/>
		<xs:attribute name="size" type="xs:positiveInteger" use="optional"/>
		<xs:attribute name="message" type="xs:string" use="optional"/>
		<xs:attribute name="class" type="xs:string" use="optional"/>
	</xs:complexType>

	<xs:complexType name="id-method-parameter">
		<xs:attribute name="name" type="xs:string" use="optional"/>
		<xs:attribute name="value" type="xs:string" use="required"/>
	</xs:complexType>

	<xs:complexType name="index">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="index-column" type="index-column" minOccurs="1" maxOccurs="unbounded"/>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="name" type="index_name" use="optional"/>
	</xs:complexType>

	<xs:complexType name="unique">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="unique-column" type="unique-column" minOccurs="1" maxOccurs="unbounded"/>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="name" type="index_name" use="optional"/>
	</xs:complexType>

	<xs:complexType name="index-column">
		<xs:sequence>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:sequence>
		<xs:attribute name="name" type="column_name" use="required"/>
		<xs:attribute name="size" type="xs:positiveInteger" use="optional"/>
	</xs:complexType>

	<xs:complexType name="unique-column">
		<xs:sequence>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:sequence>
		<xs:attribute name="name" type="column_name" use="required"/>
		<xs:attribute name="size" type="xs:positiveInteger" use="optional"/>
	</xs:complexType>

	<xs:complexType name="inheritance">
		<xs:attribute name="key" type="xs:string" use="required"/>
		<xs:attribute name="class" type="xs:string" use="required"/>
		<xs:attribute name="package" type="xs:string" use="optional"/>
		<xs:attribute name="extends" type="xs:string" use="optional"/>
	</xs:complexType>

	<xs:complexType name="reference">
		<xs:attribute name="local" type="column_name" use="required"/>
		<xs:attribute name="foreign" type="column_name" use="required"/>
	</xs:complexType>

	<xs:complexType name="behavior">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="parameter" type="parameter" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="name" type="xs:string" use="optional"/>
	</xs:complexType>

	<xs:complexType name="column">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="inheritance" type="inheritance" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="name" type="column_name" use="required"/>
		<xs:attribute name="phpName" type="php_name" use="optional"/>
		<xs:attribute name="peerName" type="php_class" use="optional"/>
		<xs:attribute name="prefix" type="column_name" use="optional"/>
		<xs:attribute name="accessorVisibility" type="visibility" use="optional"/>
		<xs:attribute name="mutatorVisibility" type="visibility" use="optional"/>
		<xs:attribute name="primaryKey" type="xs:boolean" default="false"/>
		<xs:attribute name="required" type="xs:boolean" default="false"/>
		<xs:attribute name="type" type="datatype" default="VARCHAR"/>
		<xs:attribute name="sqlType" type="sql_type" use="optional"/>
		<xs:attribute name="phpType" type="php_type" use="optional"/>
		<xs:attribute name="size" type="xs:nonNegativeInteger" use="optional"/>
		<xs:attribute name="scale" type="xs:nonNegativeInteger" use="optional"/>
		<xs:attribute name="default" type="xs:string" use="optional"/>
		<xs:attribute name="defaultValue" type="xs:string" use="optional"/>
		<xs:attribute name="defaultExpr" type="xs:string" use="optional"/>
		<xs:attribute name="autoIncrement" type="xs:boolean" default="false"/>
		<xs:attribute name="inheritance" type="inh_option" default="false"/>
		<xs:attribute name="inputValidator" type="xs:string" use="optional"/>
		<xs:attribute name="phpNamingMethod" type="phpnamingmethod" use="optional"/>
		<xs:attribute name="description" type="xs:string" use="optional"/>
		<xs:attribute name="lazyLoad" type="xs:boolean" default="false"/>
		<xs:attribute name="nodeKeySep" type="xs:string" use="optional"/>
		<xs:attribute name="nodeKey" type="xs:string" use="optional"/>
		<xs:attribute name="nestedSetLeftKey" type="xs:boolean" default="false"/>
		<xs:attribute name="nestedSetRightKey" type="xs:boolean" default="false"/>
		<xs:attribute name="treeScopeKey" type="xs:boolean" default="false"/>
		<xs:attribute name="require" type="xs:string" use="optional"/>
		<xs:attribute name="primaryString" type="xs:boolean" default="false"/>
	</xs:complexType>

	<xs:complexType name="foreign-key">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="reference" type="reference" minOccurs="1" maxOccurs="unbounded"/>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="foreignTable" type="table_name" use="required"/>
		<xs:attribute name="name" type="foreign_name" use="optional"/>
		<xs:attribute name="phpName" type="php_name" use="optional"/>
		<xs:attribute name="refPhpName" type="php_name" use="optional"/>
		<xs:attribute name="onDelete" type="delete" default="none"/>
		<xs:attribute name="onUpdate" type="update" default="none"/>
	</xs:complexType>

	<xs:complexType name="external-schema">
		<xs:attribute name="filename" type="file" use="required"/>
	</xs:complexType>

	<xs:complexType name="table">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="column" type="column" maxOccurs="unbounded"/>
			<xs:element name="foreign-key" type="foreign-key" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element name="index" type="index" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element name="unique" type="unique" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element name="id-method-parameter" type="id-method-parameter" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element name="validator" type="validator" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element name="behavior" type="behavior" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element ref="vendor" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="name" type="table_name" use="required"/>
		<xs:attribute name="phpName" type="php_class" use="optional"/>
		<xs:attribute name="columnPrefix" type="column_name" use="optional"/>
		<xs:attribute name="defaultAccessorVisibility" type="visibility" use="optional"/>
		<xs:attribute name="defaultMutatorVisibility" type="visibility" use="optional"/>
		<xs:attribute name="idMethod" type="idmethod" use='optional'/>
		<xs:attribute name="allowPkInsert" type="xs:boolean" default="false" use="optional"/>
		<xs:attribute name="skipSql" type="xs:boolean" default="false"/>
		<xs:attribute name="readOnly" type="xs:boolean" default="false"/>
		<xs:attribute name="abstract" type="xs:boolean" default="false"/>
		<xs:attribute name="baseClass" type="php_class" use="optional"/>
		<xs:attribute name="basePeer" type="php_class" use="optional"/>
		<xs:attribute name="alias" type="table_name" use="optional"/>
		<xs:attribute name="package" type="xs:string" use="optional"/>
		<xs:attribute name="interface" type="xs:string" use="optional"/>
		<xs:attribute name="phpNamingMethod" type="phpnamingmethod" use='optional'/>
		<xs:attribute name="heavyIndexing" type="xs:boolean" use="optional"/>
		<xs:attribute name="description" type="xs:string"/>
		<xs:attribute name="treeMode" type="treemode" use="optional"/>
		<xs:attribute name="reloadOnInsert" type="xs:boolean" default="false"/>
		<xs:attribute name="reloadOnUpdate" type="xs:boolean" default="false"/>
	</xs:complexType>

	<xs:complexType name="database">
		<xs:choice maxOccurs="unbounded">
			<xs:element name="external-schema" type="external-schema" minOccurs="0" maxOccurs="unbounded"/>
			<xs:element name="table" type="table" minOccurs="1" maxOccurs="unbounded"/>
			<xs:element name="behavior" type="behavior" minOccurs="0" maxOccurs="unbounded"/>
		</xs:choice>
		<xs:attribute name="name" type="xs:string" use="optional"/>
		<xs:attribute name="defaultIdMethod" type="dbidmethod" default="none"/>
		<xs:attribute name="defaultTranslateMethod" type="xs:string" use="optional"/>
		<xs:attribute name="defaultAccessorVisibility" type="visibility" use="optional"/>
		<xs:attribute name="defaultMutatorVisibility" type="visibility" use="optional"/>
		<xs:attribute name="package" type="php_class" use="optional"/>
		<xs:attribute name="baseClass" type="php_class" use="optional"/>
		<xs:attribute name="basePeer" type="php_class" use="optional"/>
		<xs:attribute name="defaultPhpNamingMethod" type="phpnamingmethod" default="underscore"/>
		<xs:attribute name="heavyIndexing" type="xs:boolean" default="false"/>
	</xs:complexType>
</xs:schema>
