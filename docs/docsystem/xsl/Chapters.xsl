<?xml version="1.0" encoding="ISO-8859-1" ?>
<!--
// {{{ Header
-File         $Id: Chapters.xsl,v 1.1 2004/07/08 01:17:05 hlellelid Exp $
-License      LGPL (http://www.gnu.org/copyleft/lesser.html)
-Copyright    2002, The Turing Studio, Inc.
-Author       alex black, enigma@turingstudio.com
// }}}
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output omit-xml-declaration="yes" indent="no" method="xml" encoding="iso-8859-1" />

<xsl:template match="//html">
<xsl:for-each select="//h1"><xsl:text>new Array('</xsl:text><xsl:value-of select="$file_name" /><xsl:text>','</xsl:text><xsl:value-of select="." /><xsl:text>'),</xsl:text></xsl:for-each>
</xsl:template>

</xsl:stylesheet>
