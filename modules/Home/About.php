<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

theme_hide_side_menu(true, true);

require_once('sugar_version.php');
$version = $sugar_version;
if(! empty($rcs_revision_number)) $version .= " (r$rcs_revision_number)";

?>

<style type="text/css">
.mobile #office-img {
	width: 80px; height: 120px;
}
#about-inner {
	margin-left: 2em; margin-right: 2em;
}
.mobile #about-inner {
	margin-left: 0.5em; margin-right: 0.5em;
}
.mobile #about-inner ul {
	padding-left: 1.5em;
	font-size: 10px;
}
</style>

<div class="body" style="width: 95%">
<div id="about-inner">

<b>info@hand Version <?php echo $version; ?></b><br>
<?php
	require_once('vendor_info.php');
	$who = licensee(); $num = max_users();
	if($who != 'Demo Version') {
?>
This installation is exclusively licensed to <b><?php echo $who ?></b> for a maximum of <b><?php echo $num ?></b> active user(s).<br>
<?php
	}
?>
<p>
Copyright &copy; 2004-2013 1CRM Corp. All Rights Reserved. <a href="http://www.1crm.com/swlicense.pdf" target="_blank" class="body">View License Agreement</a>.<br>
Initially based on code from the SugarCRM Open Source Project<br>
Copyright &copy; 2004-2008 SugarCRM Inc. All Rights Reserved. <a href="http://www.sugarcrm.com/SPL" target="_blank" class="body" rel="nofollow">View License Agreement</a>.
</p>

<div style="box-shadow: 0 2px 4px #555; -moz-box-shadow: 0 2px 4px #555; -webkit-box-shadow: 0 2px 4px #555; padding: 0; float: right; margin: 8px" id="office-outer"><img src="modules/Home/LongReachOffices.jpg" border="0" width="270" height="322" style="vertical-align: bottom" id="office-img"></div>

<?php
$email_sales = $vendor_info['email_sales'];
$email_support = $vendor_info['email_support'];
$phone = $vendor_info['phone'];
$email_icon = '<div class="input-icon icon-temail"></div>';
$link_icon = '<div class="input-icon icon-tlink"></div>';
$phone_icon = '<div class="input-icon icon-tphone"></div>';
if(AppConfig::current_user_id()) {
	global $locale;
	$sales_name = array_get_default($vendor_info, 'email_sales_name', $vendor_info['name']);
	$email_sales = $locale->getUserFormatEmail($email_sales, $sales_name, '', '', array('link_class' => 'body'));
	$support_name = array_get_default($vendor_info, 'email_support_name', $vendor_info['name']);
	$email_support = $locale->getUserFormatEmail($email_support, $support_name, '', '', array('link_class' => 'body'));
	$phone = $locale->getUserFormatPhone($phone, array('link_class' => 'body'));
}

if(! empty($vendor_info['facebook_url'])) {
	$fb = "<a href=\"{$vendor_info['facebook_url']}\" target=\"_blank\" class=\"body\">" .
		"<img src=\"modules/SocialAccounts/icons/facebook.png\" width=32 height=32 alt=\"Facebook\"></a>";
} else
	$fb = '';
if(! empty($vendor_info['twitter_url'])) {
	$twitter = "<a href=\"{$vendor_info['twitter_url']}\" target=\"_blank\" class=\"body\">" .
		"<img src=\"modules/SocialAccounts/icons/twitter.png\" width=32 height=32 alt=\"Twitter\"></a>";
} else
	$twitter = '';

echo <<<EOS
<h3>{$vendor_info['name']}</h3>
{$vendor_info['address_street']}<br>
{$vendor_info['address_city']}, {$vendor_info['address_state']}&nbsp; {$vendor_info['address_postalcode']}<br>
{$vendor_info['address_country']}

<div id="contact-info">
<h4>Contact Information:</h4>
Web: {$link_icon}&nbsp;<a href="{$vendor_info['url']}" target="_blank" class="body">{$vendor_info['url']}</a><br>
Sales: {$email_icon}&nbsp;{$email_sales}<br>
Support: {$email_icon}&nbsp;{$email_support}<br> 
Phone: {$phone_icon}&nbsp;{$phone}<br>
{$fb} {$twitter}
</div>
EOS;
?>

<h4>1CRM Developers:</h4>
Andrew Whitehead<br>
Andrey Demenev<br>
Alexander Ivanenko<br>
<br>
<img src="modules/Home/flags/Canada.png" width="64" height="33" alt="Canada" title="Canada">
<img src="modules/Home/flags/USA.png" width="60" height="33" alt="USA" title="USA">
<img src="modules/Home/flags/Russia.png" width="48" height="33" alt="Russia" title="Russia">

<h4>SugarCRM thanks the following developers for their contributions:</h4>
<ul>
<li>Marcelo Leite - Contributed Upgrade Wizard enhancements and many other minor fixes and features.</li>
<li>Mike Dawson of Gamma Code Corporation (<a href="http://www.gammacode.com/" target="_blank">www.gammacode.com</a>) - Contributed enhancements to e-mail notification feature.</li>
<li>Erik Mitchell and Ray Gauss II of the OpenLDAP/Active Directory Authentication project (<a href="http://www.sugarforge.org/projects/ldapauth" target="_blank">www.sugarforge.org/projects/ldapauth</a>) - Contributed integration to support LDAP and Active Directory.</li>
<li>The Sugar Developer Community (<a href="http://www.sugarforge.org" target="_blank" rel="nofollow">www.sugarforge.org</A>) - bug reports (with fixes!), outstanding feature requests and unbelievable support and input.</li>
</ul>

<h4>We also gratefully acknowledge the following source code contributions:</h4>
<ul>
<li><a target="_blank" href="http://www.sugarcrm.com" rel="nofollow">Sugar Suite - By SugarCRM Inc.</a></li>
<li><a target="_blank" href="http://sourceforge.net/projects/xtpl">XTemplate</a> - A template engine for PHP created by Barnabás Debreceni</li>
<li><a target="_blank" href="http://www.vxr.it/log4php">Log4php</a> - A PHP port of Log4j, the most popular Java logging framework, created by Ceki Gülcü</li>
<li><a target="_blank" href="http://dietrich.ganx4.com/nusoap">NuSOAP</a> - A set of PHP classes that allow developers to create and consume web services created by NuSphere Corporation and Dietrich Ayala</li>
<li><a target="_blank" href="http://www.dynarch.com/projects/calendar/">JS Calendar</a> - A calendar for entering dates created by Mihai Bazon</li>
<li><a target="_blank" href="http://sourceforge.net/projects/domit-xmlparser">DOMIT!</a> - An xml parser for PHP based on the Document Object Model (DOM) Level 2 Spec</li>
<li><a target="_blank" href="http://mike.teczno.com/json.html">JSON.php</a> - A PHP script to convert to and from JSON data format by Michal Migurski</li>
<li><a target="_blank" href="http://pear.php.net/package/HTTP_WebDAV_Server">HTTP_WebDAV_Server</a> - A WebDAV Server Implementation in PHP</li>
<li><a href="http://www.phpconcept.net/pclzip/index.en.php" target="_blank">PclZip</a> - A library for handling Zip-formatted archives - by Vincent Blavet</li>
<li><a href="http://smarty.php.net/" target="_blank">Smarty</a> - A template engine for PHP</li>
<li><a href="http://ckeditor.com/" target="_blank">CKEditor</a> - The text editor for Internet, by CKSource</li>
<li><a href="http://labs.corefive.com/Projects/FileManager/" target="_blank">FileManager</a> - Web-based file browser for CKEditor, by Core Five Labs</li>
<li><a href="http://developer.yahoo.com/yui/" target="_blank">Yahoo! User Interface Library</a> - The UI Library Utilities facilitate the implementation of rich client-side features</li>
<li><a href="http://phpmailer.sourceforge.net/" target="_blank">PHPMailer</a> - A full featured email transfer class for PHP</li>
<li><a target="_blank" href="http://www.phpclasses.org/browse/package/1402.html">class_webdav_client</a> - a php based webdav client class by Christian Juerges</li>
<li><a target="_blank" href="http://www.squirrelmail.org/plugin_view.php?id=62">TNEF Attachment Decoder plugin for SquirrelMail</a> - created by Bernd Wiegmann</li>
<li><a target="_blank" href="http://sourceforge.net/projects/tcpdf/">TCPDF</a> - A library for generating PDF documents - created by Nicola Asuni; based on FPDF by Olivier Plathey</li>
<li><a href="http://www.sugarcrm.com/forums/showthread.php?t=15800" target="_blank">Forums dashlet extension</a> - created by Ryuhei Uchida at <a href="http://www.sugarforum.jp/">CareBrains</a></li>
<li><a href="http://www.sugarforge.org/projects/sugarprint/" target="_blank">SugarPrint</a> original code and concept - created by <a href="http://www.sugarcrm.com/forums/member.php?userid=618" target="_blank">Kenneth Brill</a></li>
<li><a href="http://www.epdf.com" target="_blank">Quick Campaign</a> - contributed by <a href="http://www.epdf.com" target="_blank">GROUPWARE, Inc</a></li>
<li><a href="http://crisp.tweakblogs.net/blog/cat/716" target="_blank">JSMin+</a> - a javascript minifier based on Brendan Eich's Narcissus, by Tino Zijdel</li>
<li><a href="http://code.google.com/p/cssmin/" target="_blank">cssmin</a> - a simple CSS minifier by Joe Scylla</li>
<li><a href="http://www.schillmania.com/projects/soundmanager2/" target="_blank">SoundPlayer2</a> - Javascript-driven sound player by Scott Schiller</li>
<li>Includes icons from the <a href="http://www.famfamfam.com/lab/icons/silk/" target="_blank">Silk icon set</a> by Mark James</li>
</ul>

</div>
</div>
