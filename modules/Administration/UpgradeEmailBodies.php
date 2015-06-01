<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


action_restricted_for('demo');

require_once 'include/utils/html_utils.php';

global $db;

$i = 0;

echo '<p>';
echo get_module_title($mod_strings['LBL_UPGRADE_EMAIL_BODIES'], $mod_strings['LBL_UPGRADE_EMAIL_BODIES_DESC'], false);
echo '</p>';

$qDone = "SELECT * FROM versions WHERE name = 'Email Bodies for Fulltext Search'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
$done = $rowsDone > 0;

if ($done) {
	echo $mod_strings['LBL_UPGRADE_EMAIL_BODIES_DONE'];
	return;
} else if (empty($_REQUEST['upgrade_emails_confirm'])) {
	echo $mod_strings['LBL_UPGRADE_EMAIL_BODIES_CONFIRM1'] . ' <a href="index.php?module=Administration&action=UpgradeEmailBodies&upgrade_emails_confirm=1">' . $mod_strings['LBL_UPGRADE_EMAIL_BODIES_CONFIRM2'] . '</a>';
	return;
} elseif (!isset($_SESSION['upgrade_emails_count'])) {
	$res = $db->query("SELECT COUNT(email_id) c FROM emails_bodies WHERE upgraded = 0");
	$row = $db->fetchByAssoc($res);
	$_SESSION['upgrade_emails_count'] = $row['c'];
	$_SESSION['upgrade_emails_done'] = 0;
}

$res = $db->query("SELECT email_id, description, description_html FROM emails_bodies WHERE upgraded =0 limit 0, 2000");
while ($row = $db->fetchByAssoc($res)) {
	if (strlen($row['description_html']) && !strlen($row['description'])) {
		$db->query ("UPDATE emails_bodies SET description = '" . $db->quote(html2plaintext($row['description_html'])) . "', upgraded=1 WHERE email_id = '" . $row['email_id'] . "' ");
	} else {
		$db->query ("UPDATE emails_bodies SET upgraded=1 WHERE email_id = '" . $row['email_id'] . "' ");
	}
	$i++;
}

$_SESSION['upgrade_emails_done'] += $i;

echo sprintf($mod_strings['LBL_UPGRADE_EMAIL_BODIES_COUNT'], $_SESSION['upgrade_emails_done'], $_SESSION['upgrade_emails_count']);

if ($i &&  $_SESSION['upgrade_emails_done'] < $_SESSION['upgrade_emails_count']) {
?>
<script type="text/javascript">
	function reloadwindow()
	{
		window.location="index.php?module=Administration&action=UpgradeEmailBodies&upgrade_emails_confirm=1";
	}
	if(window.addEventListener){
		window.addEventListener("load", reloadwindow, false);
	} else {
		window.attachEvent("onload", reloadwindow);
	}
</script>

<?php
} elseif ($_SESSION['upgrade_emails_done'] >= $_SESSION['upgrade_emails_count']) {
		require_once('modules/Versions/Version.php');
		Version::mark_upgraded('Email Bodies for Fulltext Search', '5.0.0', '5.0.0');
?>
<script type="text/javascript">
	function reloadwindow()
	{
		window.location="index.php?module=Administration&action=Maintain";
	}
	if(window.addEventListener){
		window.addEventListener("load", reloadwindow, false);
	} else {
		window.attachEvent("onload", reloadwindow);
	}
</script>
<?php
}
