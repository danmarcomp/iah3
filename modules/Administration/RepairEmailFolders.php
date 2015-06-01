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

require_once('modules/Users/User.php');
require_once('modules/EmailFolders/EmailFolder.php');

global $current_user;
if(! is_admin($current_user))
	die('Admin Only Section');

$lq = new ListQuery('User', array('id', 'name'));
$lq->addSimpleFilter('portal_only', 0, 'eq');
$result = $lq->runQuery();
$actions = 0;

foreach($result->getRowIndexes() as $idx) {
	$user = $result->getRowResult($idx);
	$output = EmailFolder::create_default_folders_for_user($user);
	foreach($output as $line)
		print "$line<br>";
	if($output)
		print "<br>";
	$actions += count($output);
	$msgs = EmailFolder::repair_unfiled_emails($user);
	if($msgs == -1)
		print "{$user->user_name} has no Inbox folder after repair?<br><br>";
	else if($msgs) {
		print "$msgs unfiled emails assigned to folders.<br><br>";
		$actions ++;
	}
}

// create group folders
$output = EmailFolder::create_default_group_folders();
foreach($output as $line)
	print "$line<br>";
if($output)
	print "<br>";
$actions += count($output);
$msgs = EmailFolder::repair_unfiled_emails();
if($msgs == -1)
	print "Group has no Inbox folder after repair?<br><br>";
else if($msgs) {
	print "$msgs unfiled Group emails assigned to folders.<br><br>";
	$actions ++;
}

print "<br>Finished" . ($actions ? "." : " - no action taken (default email folders are intact).");

?>
