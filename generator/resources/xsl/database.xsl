<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" indent="yes" encoding="UTF-8" />

	<xsl:template match="/">
		<xsl:apply-templates select='database'/>
	</xsl:template>

	<xsl:template match='database'>
		<database>
			<xsl:if test='not(boolean(@defaultIdMethod))'>
				<xsl:attribute name='defaultIdMethod'>none</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@defaultPhpNamingMethod))'>
				<xsl:attribute name='defaultPhpNamingMethod'>underscore</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@heavyIndexing))'>
				<xsl:attribute name='heavyIndexing'>false</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='external-schema'/>
			<xsl:apply-templates select='table'/>
		</database>
	</xsl:template>

	<xsl:template match='@defaultPhPNamingMethod'>
		<xsl:attribute name='defaultPhPNamingMethod'><xsl:value-of select='translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")'/></xsl:attribute>
	</xsl:template>

	<xsl:template match='@onDelete'>
		<xsl:choose>
			<xsl:when test='.=""'>
				<xsl:attribute name='onDelete'>none</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name='onDelete'><xsl:value-of select='translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")'/></xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match='@OnDelete'>
		<xsl:choose>
			<xsl:when test='.=""'>
				<xsl:attribute name='onDelete'>none</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name='onDelete'><xsl:value-of select='translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")'/></xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match='@onUpdate'>
		<xsl:choose>
			<xsl:when test='.=""'>
				<xsl:attribute name='onUpdate'>none</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name='onUpdate'><xsl:value-of select='translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")'/></xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match='@OnUpdate'>
		<xsl:choose>
			<xsl:when test='.=""'>
				<xsl:attribute name='onUpdate'>none</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name='onUpdate'><xsl:value-of select='translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")'/></xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match='@IdMethod'>
		<xsl:attribute name='idMethod'><xsl:value-of select='.'/></xsl:attribute>
	</xsl:template>

	<xsl:template match='@*' priority='-1'>
		<xsl:copy-of select='.'/>
	</xsl:template>

	<xsl:template match='external-schema'>
		<external-schema>
			<xsl:apply-templates select='@*'/>
		</external-schema>
	</xsl:template>

	<xsl:template match='table'>
		<table>
			<xsl:if test='not(boolean(@skipSql))'>
				<xsl:attribute name='skipSql'>false</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@abstract))'>
				<xsl:attribute name='abstract'>false</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='column'/>
			<xsl:apply-templates select='foreign-key'/>
			<xsl:apply-templates select='index'/>
			<xsl:apply-templates select='unique'/>
			<xsl:apply-templates select='id-method-parameter'/>
			<xsl:apply-templates select='validator'/>
			<xsl:apply-templates select='vendor'/>
		</table>
	</xsl:template>

	<xsl:template match='foreign-key'>
		<foreign-key>
			<xsl:if test='not(boolean(@onDelete)) and not(boolean(@OnDelete))'>
				<xsl:attribute name='onDelete'>none</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@onUpdate)) and not(boolean(@OnUpdate))'>
				<xsl:attribute name='onUpdate'>none</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='reference'/>
			<xsl:apply-templates select='vendor'/>
		</foreign-key>
	</xsl:template>

	<xsl:template match='index'>
		<index>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='index-column'/>
		</index>
	</xsl:template>

	<xsl:template match='unique'>
		<unique>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='unique-column'/>
		</unique>
	</xsl:template>

	<xsl:template match='unique-column'>
		<unique-column>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='vendor'/>
		</unique-column>
	</xsl:template>

	<xsl:template match='index-column'>
		<index-column>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='vendor'/>
		</index-column>
	</xsl:template>

	<xsl:template match='id-method-parameter'>
		<id-method-parameter>
			<xsl:if test='not(boolean(@name))'>
				<xsl:attribute name='name'>default</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select='@*'/>
		</id-method-parameter>
	</xsl:template>

	<xsl:template match='validator'>
		<validator>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='rule'/>
		</validator>
	</xsl:template>

	<xsl:template match='rule'>
		<rule>
			<xsl:if test='not(boolean(@name))'>
				<xsl:attribute name='name'>class</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select='@*'/>
		</rule>
	</xsl:template>

	<xsl:template match='parameter'>
		<parameter>
			<xsl:apply-templates select='@*'/>
		</parameter>
	</xsl:template>

	<xsl:template match='vendor'>
		<vendor>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='parameter'/>
		</vendor>
	</xsl:template>

	<xsl:template match='inheritance'>
		<inheritance>
			<xsl:apply-templates select='@*'/>
		</inheritance>
	</xsl:template>

	<xsl:template match='column'>
		<column>
			<xsl:if test='not(boolean(@primaryKey))'>
				<xsl:attribute name='primaryKey'>false</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@required))'>
				<xsl:attribute name='required'>false</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@type))'>
				<xsl:attribute name='type'>VARCHAR</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@autoIncrement))'>
				<xsl:attribute name='autoIncrement'>false</xsl:attribute>
			</xsl:if>
			<xsl:if test='not(boolean(@lazyLoad))'>
				<xsl:attribute name='lazyLoad'>false</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select='@*'/>
			<xsl:apply-templates select='inheritance'/>
			<xsl:apply-templates select='vendor'/>
		</column>
	</xsl:template>

	<xsl:template match='reference'>
		<reference>
			<xsl:apply-templates select='@*'/>
		</reference>
	</xsl:template>

</xsl:stylesheet>
