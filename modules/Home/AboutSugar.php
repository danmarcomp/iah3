<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/

//NOTE: Under the Sugar Public License referenced above, you are required to leave in all copyright statements in both
//the code and end-user application.

?>
<span class="body">
<p><img src="include/images/sugarsales_lg.png" alt="Sugar Suite" width="425" height="30"><br>
<b>Version <?php echo $sugar_version; ?>
<?php
    if( is_file( "custom_version.php" ) ){
        include( "custom_version.php" );
        print( "&nbsp;&nbsp;&nbsp;" . $custom_version );
    }
?>
</b></p>

<p>Copyright &copy; 2004 -2005 <A href="http://www.sugarcrm.com" target="_blank" class="body">SugarCRM Inc.</A> All Rights Reserved. 
<?php




echo "<A href='http://www.sugarcrm.com/SPL' target='_blank' class='body'>View License Agreement</A><br>";



?>
SugarCRM<span class="tm">TM</span>, 
<?php



echo "Sugar Open Source<span class='tm'>TM</span> ";




?>
and Sugar Suite<span class="tm">TM</span> are
<a href="http://www.sugarcrm.com/crm/open-source/trademark-information.html"
	target="_blank" class="body">trademarks</a> of SugarCRM Inc.</p>

<p><table cellspacing="0" cellpadding="0" border="0" class="contentBox">
<tr>
    <td class="body" style="padding-right: 10px;" valign="top"><B>Silicon Valley Corporate Office</B><br>

<IMG src="include/images/corp_office.jpg" alt="Silicon Valley Corporate Office" usemap="#office" border="0">
<map name="office">
<area alt="" shape="poly" coords="27,89,123,94,123,117,25,112" onclick='return window.open("index.php?module=Home&action=PopupSugar","test","width=300,height=400,resizable=0,scrollbars=0");'>
</map></td>
    <td class="body" valign="top" style="padding-right: 10px;">
<p>	<B>SugarCRM Inc.</B><BR>
        10050 North Wolfe Road<BR>
        Suite SW2-130<BR>
		Cupertino, CA 95014 USA</p>
		
<p>		<B>Contact Information</B><BR>
Web: <a href="http://www.sugarcrm.com" target="_blank" class="body">http://www.sugarcrm.com</a><BR>
Sales: <a href="mailto:sales@sugarcrm.com"  class="body">sales@sugarcrm.com</a><br>
Support: <a href="mailto:support@sugarcrm.com" class="body">support@sugarcrm.com</a> <BR>
Phone: +1 408.454.6900</p>
<B>Founders</B>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
    <td class="body"><LI>John Roberts</LI>
          <LI>Clint Oram</LI>
          <LI>Jacob Taylor</LI>
          </td>
</tr>
</table>

          
	</td>

</tr>
</table></p>

</span>

<p><B>Thanks to the following developers for their contributions:</B>
<LI>Marcelo Leite - Contributed Upgrade Wizard and other fixes.</LI>
<LI>The Sugar Developer Community - bug reports (with fixes!), outstanding feature requests and unbelievable support and input.</LI>

<P>&nbsp;</p>
<P><B>Source Code</B></p>
<LI>Sugar Suite - The world's most popular sales force automation application created by SugarCRM Inc. (<A href="http://www.sugarcrm.com" target="_blank">http://www.sugarcrm.com</A>)</LI>
<LI>XTemplate - A template engine for PHP created by Barnab�s Debreceni (<A href="http://sourceforge.net/projects/xtpl" target="_blank">http://sourceforge.net/projects/xtpl</A>)</LI>
<LI>Log4php - A PHP port of Log4j, the most popular Java logging framework, created by Ceki G�lc� (<a href="http://www.vxr.it/log4php" target="_blank">http://www.vxr.it/log4php</a>)</LI>
<LI>NuSOAP - A set of PHP classes that allow developers to create and consume web services created by NuSphere Corporation and Dietrich Ayala (<a href="http://dietrich.ganx4.com/nusoap" target="_blank">http://dietrich.ganx4.com/nusoap</a>)</LI>
<LI>JS Calendar - A calendar for entering dates created by Mihai Bazon (<a href="http://www.dynarch.com/mishoo/calendar.epl" target="_blank">http://www.dynarch.com/mishoo/calendar.epl</a>)</LI>
<LI>PHP PDF - A library for creating PDF documents created by Wayne Munro (<a href="http://ros.co.nz/pdf/" target="_blank">http://ros.co.nz/pdf/</a>)
<LI>DOMIT! - An xml parser for PHP based on the Document Object Model (DOM) Level 2 Spec. (<a href="http://sourceforge.net/projects/domit-xmlparser/" target="_blank">http://sourceforge.net/projects/domit-xmlparser</a>)</LI>
<LI>DOMIT RSS - An RSS feed parser based on the DOMIT pure PHP XML parser. (<a href="http://sourceforge.net/projects/domit-rssparser/" target="_blank">http://sourceforge.net/projects/domit-rssparser</a>)</LI>
<LI>JSON.php - A PHP script to convert to and from JSON data format by Michal Migurski. (<a href="http://mike.teczno.com/json.html">http://mike.teczno.com/json.html</a>)</LI>
<LI>HTTP_WebDAV_Server - A WebDAV Server Implementation in PHP. (<a href="http://pear.php.net/package/HTTP_WebDAV_Server">http://pear.php.net/package/HTTP_WebDAV_Server</a>)</LI>
<LI>JavaScript O Lait - A library of reusable modules and components to enhance JavaScript by Jan-Klaas Kollhof. (<a href="http://jsolait.net/">http://jsolait.net/</a>)</LI>
<LI>class_webdav_client - a php based webdav client class by Christian Juerges. (<a href="http://www.phpclasses.org/browse/package/1402.html">http://www.phpclasses.org/browse/package/1402.html</a>)</LI>
<LI>HTML Area - provides HTML Editing capabilities. (<a href=" http://www.dynarch.com/projects/htmlarea/">http://www.dynarch.com/projects/htmlarea/</a>)</LI>
<LI>PclZip - library offers compression and extraction functions for Zip formatted archives by Vincent Blavet (<a href="http://www.phpconcept.net/pclzip/index.en.php">http://www.phpconcept.net/pclzip/index.en.php/</a>)</LI>
<?php





?>
