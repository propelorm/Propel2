<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
    xmlns:exsl="http://exslt.org/common"
    xmlns:str="http://exslt.org/strings"
    xmlns:date="http://exslt.org/dates-and-times"
    extension-element-prefixes="exsl str date">
<xsl:include href="str.replace.function.xsl"/>
<xsl:output method="html" indent="yes" encoding="US-ASCII"
  doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN"/>
<xsl:decimal-format decimal-separator="." grouping-separator=","/>
<!--
   Copyright 2001-2004 The Apache Software Foundation

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
 -->

<!--

 Sample stylesheet to be used with Phing/PHPUnit2 output.
 Based on JUnit stylesheets from Apache Ant.

 It creates a non-framed report that can be useful to send via
 e-mail or such.

 @author Michiel Rook <a href="mailto:michiel@trendserver.nl"/>
 @author Stephane Bailliez <a href="mailto:sbailliez@apache.org"/>
 @author Erik Hatcher <a href="mailto:ehatcher@apache.org"/>

-->
<xsl:template match="testsuites">
    <html>
        <head>
            <title>Unit Test Results</title>
    <style type="text/css">
    body {
        font-family: verdana,arial,helvetica;
        color:#000000;
        font-size: 12px;
    }
    table tr td, table tr th {
        font-family: verdana,arial,helvetica;
        font-size: 12px;
    }
    table.details tr th{
        font-family: verdana,arial,helvetica;
        font-weight: bold;
        text-align:left;
        background:#a6caf0;
    }
    table.details tr td{
        background:#eeeee0;
    }

    p {
        line-height:1.5em;
        margin-top:0.5em; margin-bottom:1.0em;
        font-size: 12px;
    }
    h1 {
        margin: 0px 0px 5px;
        font-family: verdana,arial,helvetica;
    }
    h2 {
        margin-top: 1em; margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h3 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h4 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h5 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h6 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    .Error {
        font-weight:bold; color:red;
    }
    .Failure {
        font-weight:bold; color:purple;
    }
    .small {
       font-size: 9px;
    }
    a {
      color: #003399;
    }
    a:hover {
      color: #888888;
    }
    </style>
        </head>
        <body>
            <a name="top"></a>
            <xsl:call-template name="pageHeader"/>

            <!-- Summary part -->
            <xsl:call-template name="summary"/>

            <!-- Package List part -->
            <xsl:call-template name="packagelist"/>

            <hr size="1" width="95%" align="left"/>

            <!-- For each package create its part -->
            <xsl:call-template name="packages"/>

            <hr size="1" width="95%" align="left"/>

            <!-- For each class create the  part -->
            <xsl:call-template name="classes"/>

            <xsl:call-template name="pageFooter"/>
        </body>
    </html>
</xsl:template>



    <!-- ================================================================== -->
    <!-- Write a list of all packages with an hyperlink to the anchor of    -->
    <!-- of the package name.                                               -->
    <!-- ================================================================== -->
    <xsl:template name="packagelist">
        <table class="details" border="0" cellpadding="5" cellspacing="2" width="95%">
            <xsl:call-template name="testsuite.test.header"/>
            <!-- list all packages recursively -->
            <xsl:for-each select="./testsuite[not(./@package = preceding-sibling::testsuite/@package)]">
                <xsl:sort select="@package"/>
                <xsl:variable name="testsuites-in-package" select="/testsuites/testsuite[./@package = current()/@package]"/>
                <xsl:variable name="assertionCount" select="sum($testsuites-in-package/@assertions)"/>
                <xsl:variable name="testCount" select="sum($testsuites-in-package/@tests)"/>
                <xsl:variable name="errorCount" select="sum($testsuites-in-package/@errors)"/>
                <xsl:variable name="failureCount" select="sum($testsuites-in-package/@failures)"/>
                <xsl:variable name="timeCount" select="sum($testsuites-in-package/@time)"/>

                <!-- write a summary for the package -->
                <tr valign="top">
                    <!-- set a nice color depending if there is an error/failure -->
                    <xsl:attribute name="class">
                        <xsl:choose>
                            <xsl:when test="$failureCount &gt; 0">Failure</xsl:when>
                            <xsl:when test="$errorCount &gt; 0">Error</xsl:when>
                        </xsl:choose>
                    </xsl:attribute>
                    <td><a href="#{@package}"><xsl:value-of select="@package"/></a></td>
                    <td><xsl:value-of select="$assertionCount"/></td>
                    <td><xsl:value-of select="$testCount"/></td>
                    <td><xsl:value-of select="$errorCount"/></td>
                    <td><xsl:value-of select="$failureCount"/></td>
                    <td>
                    <xsl:call-template name="display-time">
                        <xsl:with-param name="value" select="$timeCount"/>
                    </xsl:call-template>
                    </td>
                </tr>
            </xsl:for-each>
        </table>
    </xsl:template>


    <!-- ================================================================== -->
    <!-- Write a package level report                                       -->
    <!-- It creates a table with values from the document:                  -->
    <!-- Name | Tests | Errors | Failures | Time                            -->
    <!-- ================================================================== -->
    <xsl:template name="packages">
        <h2>Packages</h2>
        <!-- create an anchor to this package name -->
        <xsl:for-each select="/testsuites/testsuite[not(./@package = preceding-sibling::testsuite/@package)]">
            <xsl:sort select="@package"/>
                <a name="{@package}"></a>
                <h3>Package <xsl:value-of select="@package"/></h3>

                <table class="details" border="0" cellpadding="5" cellspacing="2" width="95%">
                    <xsl:call-template name="testsuite.test.header"/>

                    <!-- match the testsuites of this package -->
                    <xsl:apply-templates select="/testsuites/testsuite[./@package = current()/@package]" mode="print.test"/>
                </table>
                <p/>
        </xsl:for-each>
    </xsl:template>

    <xsl:template name="classes">
        <h2>Test Cases</h2>
        <xsl:for-each select="testsuite">
            <xsl:sort select="@name"/>
            <!-- create an anchor to this class name -->
            <a name="{@name}"></a>
            <h3>TestCase <xsl:value-of select="@name"/></h3>

            <table class="details" border="0" cellpadding="5" cellspacing="2" width="95%">
              <xsl:call-template name="testcase.test.header"/>
              <!--
              test can even not be started at all (failure to load the class)
              so report the error directly
              -->
                <xsl:if test="./error">
                    <tr class="Error">
                        <td colspan="4"><xsl:apply-templates select="./error"/></td>
                    </tr>
                </xsl:if>
                <xsl:apply-templates select="./testcase" mode="print.test"/>
            </table>
            <p/>
        </xsl:for-each>
    </xsl:template>

    <xsl:template name="summary">
        <h2>Summary</h2>
        <xsl:variable name="assertionCount" select="sum(testsuite/@assertions)"/>
        <xsl:variable name="testCount" select="sum(testsuite/@tests)"/>
        <xsl:variable name="errorCount" select="sum(testsuite/@errors)"/>
        <xsl:variable name="failureCount" select="sum(testsuite/@failures)"/>
        <xsl:variable name="timeCount" select="sum(testsuite/@time)"/>
        <xsl:variable name="successRate" select="($testCount - $failureCount - $errorCount) div $testCount"/>
        <table class="details" border="0" cellpadding="5" cellspacing="2" width="95%">
        <tr valign="top">
            <th>Assertions</th>
            <th>Tests</th>
            <th>Failures</th>
            <th>Errors</th>
            <th>Success rate</th>
            <th>Time</th>
        </tr>
        <tr valign="top">
            <xsl:attribute name="class">
                <xsl:choose>
                    <xsl:when test="$failureCount &gt; 0">Failure</xsl:when>
                    <xsl:when test="$errorCount &gt; 0">Error</xsl:when>
                </xsl:choose>
            </xsl:attribute>
            <td><xsl:value-of select="$assertionCount"/></td>
            <td><xsl:value-of select="$testCount"/></td>
            <td><xsl:value-of select="$failureCount"/></td>
            <td><xsl:value-of select="$errorCount"/></td>
            <td>
                <xsl:call-template name="display-percent">
                    <xsl:with-param name="value" select="$successRate"/>
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="display-time">
                    <xsl:with-param name="value" select="$timeCount"/>
                </xsl:call-template>
            </td>

        </tr>
        </table>
        <table border="0" width="95%">
        <tr>
        <td style="text-align: justify;">
        Note: <i>failures</i> are anticipated and checked for with assertions while <i>errors</i> are unanticipated.
        </td>
        </tr>
        </table>
    </xsl:template>

<!-- Page HEADER -->
<xsl:template name="pageHeader">
    <h1>Unit Test Results</h1>
    <table width="100%">
    <tr>
        <td align="left"></td>
        <td align="right">Designed for use with <a href='http://pear.php.net/package/PHPUnit2'>PHPUnit2</a> and <a href='http://phing.info/'>Phing</a>.</td>
    </tr>
    </table>
    <hr size="1"/>
</xsl:template>

<!-- Page Footer -->
<xsl:template name="pageFooter">
    <table width="100%">
      <tr><td><hr noshade="yes" size="1"/></td></tr>
      <tr><td class="small">Report generated at <xsl:value-of select="date:date-time()"/></td></tr>
    </table>
</xsl:template>

<xsl:template match="testsuite" mode="header">
    <tr valign="top">
        <th width="80%">Name</th>
        <th>Assertions</th>
        <th>Tests</th>
        <th>Errors</th>
        <th>Failures</th>
        <th nowrap="nowrap">Time(s)</th>
    </tr>
</xsl:template>

<!-- class header -->
<xsl:template name="testsuite.test.header">
    <tr valign="top">
        <th width="80%">Name</th>
        <th>Assertions</th>
        <th>Tests</th>
        <th>Errors</th>
        <th>Failures</th>
        <th nowrap="nowrap">Time(s)</th>
    </tr>
</xsl:template>

<!-- method header -->
<xsl:template name="testcase.test.header">
    <tr valign="top">
        <th>Name</th>
        <th>Status</th>
        <th width="80%">Type</th>
        <th>Assertions</th>
        <th nowrap="nowrap">Time (s)</th>
    </tr>
</xsl:template>


<!-- class information -->
<xsl:template match="testsuite" mode="print.test">
    <tr valign="top">
        <!-- set a nice color depending if there is an error/failure -->
        <xsl:attribute name="class">
            <xsl:choose>
                <xsl:when test="@failures[.&gt; 0]">Failure</xsl:when>
                <xsl:when test="@errors[.&gt; 0]">Error</xsl:when>
            </xsl:choose>
        </xsl:attribute>

        <!-- print testsuite information -->
        <td><a href="#{@name}"><xsl:value-of select="@name"/></a></td>
        <td><xsl:value-of select="@assertions"/></td>
        <td><xsl:value-of select="@tests"/></td>
        <td><xsl:value-of select="@errors"/></td>
        <td><xsl:value-of select="@failures"/></td>
        <td>
            <xsl:call-template name="display-time">
                <xsl:with-param name="value" select="@time"/>
            </xsl:call-template>
        </td>
    </tr>
</xsl:template>

<xsl:template match="testcase" mode="print.test">
    <tr valign="top">
        <xsl:attribute name="class">
            <xsl:choose>
                <xsl:when test="failure | error">Error</xsl:when>
            </xsl:choose>
        </xsl:attribute>
        <td><xsl:value-of select="@name"/></td>
        <xsl:choose>
            <xsl:when test="failure">
                <td>Failure</td>
                <td><xsl:apply-templates select="failure"/></td>
            </xsl:when>
            <xsl:when test="error">
                <td>Error</td>
                <td><xsl:apply-templates select="error"/></td>
            </xsl:when>
            <xsl:otherwise>
                <td>Success</td>
                <td></td>
            </xsl:otherwise>
        </xsl:choose>
        <td><xsl:value-of select="@assertions"/></td>
        <td>
            <xsl:call-template name="display-time">
                <xsl:with-param name="value" select="@time"/>
            </xsl:call-template>
        </td>
    </tr>
</xsl:template>


<xsl:template match="failure">
    <xsl:call-template name="display-failures"/>
</xsl:template>

<xsl:template match="error">
    <xsl:call-template name="display-failures"/>
</xsl:template>

<!-- Style for the error and failure in the tescase template -->
<xsl:template name="display-failures">
    <xsl:choose>
        <xsl:when test="not(@message)">N/A</xsl:when>
        <xsl:otherwise>
            <xsl:value-of select="@message"/>
        </xsl:otherwise>
    </xsl:choose>
    <!-- display the stacktrace -->
    <code>
        <br/><br/>
		<xsl:value-of select="."/>
        <xsl:call-template name="br-replace">
            <xsl:with-param name="word" select="."/>
        </xsl:call-template>
    </code>
    <!-- the later is better but might be problematic for non-21" monitors... -->
    <!--pre><xsl:value-of select="."/></pre-->
</xsl:template>

<xsl:template name="JS-escape">
    <xsl:param name="string"/>
    <xsl:param name="tmp1" select="str:replace(string($string),'\','\\')"/>
    <xsl:param name="tmp2" select="str:replace(string($tmp1),&quot;'&quot;,&quot;\&apos;&quot;)"/>
    <xsl:value-of select="$tmp2"/>
</xsl:template>


<!--
    template that will convert a carriage return into a br tag
    @param word the text from which to convert CR to BR tag
-->
<xsl:template name="br-replace">
    <xsl:param name="word"/>
    <xsl:param name="br"><br/></xsl:param>
    <xsl:value-of select='str:replace(string($word),"&#xA;",$br)'/>
</xsl:template>

<xsl:template name="display-time">
    <xsl:param name="value"/>
    <xsl:value-of select="format-number($value,'0.000')"/>
</xsl:template>

<xsl:template name="display-percent">
    <xsl:param name="value"/>
    <xsl:value-of select="format-number($value,'0.00%')"/>
</xsl:template>

</xsl:stylesheet>

