<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/services">
    <html>
    <head>
        <title><xsl:value-of select="title" /></title>
        <link rel="stylesheet" href="/openmailadmin/design/shadow.css" type="text/css" />
    </head>
    <body><div class="body">
        <xsl:for-each select="service">
            <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <table border="0" cellpadding="2" cellspacing="0">
                            <tr><td class="caption">&#160;<xsl:value-of select="@typename" />&#160;</td></tr>
                        </table>
                    </td>
                    <td class="sh_hor">
                        <img border="0" src="/openmailadmin/images/sh_lu.gif" width="6" height="6" alt="\" />
                    </td>
                </tr>
            </table>
            <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <table border="0" cellpadding="1" cellspacing="1">
                            <xsl:for-each select="application">
                            <tr>
                                <td class="std" width="580"><b><a><xsl:attribute name="href"><xsl:value-of select="@href" /></xsl:attribute>
                                <xsl:attribute name="title"><xsl:value-of select="@name" /></xsl:attribute>
                                <xsl:value-of select="@name" /></a></b><br /><xsl:value-of select="." /></td>
                            </tr>
                            </xsl:for-each>
                        </table>
                    </td>
                    <td class="sh_hor">
                        <img border="0" src="/openmailadmin/images/sh_lu.gif" width="6" height="6" alt="\" />
                    </td>
                </tr>
                <tr>
                    <td class="sh_ver">
                        <img border="0" src="/openmailadmin/images/sh_ro.gif" width="6" height="6" alt="+" />
                    </td>
                    <td align="right" class="sh_ver">
                        <img border="0" src="/openmailadmin/images/sh_lo.gif" width="6" height="6" alt="\" />
                    </td>
                </tr>
            </table>
            <br /><br />
        </xsl:for-each>
    </div></body>
    </html>
</xsl:template>
</xsl:stylesheet>