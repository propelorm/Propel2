<?xml version="1.0" encoding="ISO-8859-1" ?>
<!--
// {{{ Header
-File         $Id: Toc.xsl,v 1.1 2004/07/08 01:17:05 hlellelid Exp $
-License      LGPL (http://www.gnu.org/copyleft/lesser.html)
-Copyright    2002, The Turing Studio, Inc.
-Author       alex black, enigma@turingstudio.com
// }}}
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output omit-xml-declaration="yes" indent="yes" method="html" encoding="iso-8859-1" />
	
    <xsl:template match="/">	
    <ul>		
        <xsl:for-each select="//h1">
        <li>
            <a>
                <xsl:attribute name="href">
                    <xsl:text>../</xsl:text><xsl:value-of select="$file_name" />
                </xsl:attribute>
                <xsl:if test="$mode = 'frame'">
                    <xsl:attribute name="target"><xsl:text>Content</xsl:text></xsl:attribute>
                </xsl:if>
                <xsl:value-of select="." />
            </a>
        </li>
        </xsl:for-each>
        <ul>
        <xsl:for-each select="//h2">
            <li>
                <xsl:if test="a">
                <a>
                    <xsl:apply-templates select="a" />
                    <xsl:value-of select="." />
                </a>
                </xsl:if>
                <xsl:if test="not(a)">
                    <xsl:value-of select="." />
                </xsl:if>
            </li>
        </xsl:for-each>
        </ul>
    </ul>
    </xsl:template>

    <xsl:template match="a">
		<xsl:attribute name="href">
    		<xsl:text>../</xsl:text><xsl:value-of select="$file_name" />
    		<xsl:if test="@name">
        		<xsl:text>#</xsl:text><xsl:value-of select="@name" />
            </xsl:if>
		</xsl:attribute>
        <xsl:if test="$mode = 'frame'">
            <xsl:attribute name="target"><xsl:text>Content</xsl:text></xsl:attribute>
        </xsl:if>
    </xsl:template>      

</xsl:stylesheet>
