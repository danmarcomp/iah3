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

global $current_user;
if(! is_admin($current_user))
	die('Admin Only Section');

require_once('modules/Versions/Version.php');
require_once('modules/Administration/updater_utils.php');
global $db;

$qDone = "SELECT * FROM versions WHERE name = 'Calls Meetings Dates'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}

if(! $done) {
    
    additionalDbUpdate();

	//unset($_SESSION['upgrade_calls_meetings']);
	Version::mark_upgraded('Calls Meetings Dates', '7.0', '7.0');
	echo '<p>'.translate('LBL_UPGRADE_COMPLETE', 'Administration').'</p>';

} else {
	echo '<p>'.translate('LBL_UPGRADE_PERFORMED', 'Administration').'</p>';
}


$qDone = "SELECT * FROM versions WHERE name = 'Update Custom Fields'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	echo '<p>'.translate('LBL_UPGRADE_PERFORMED', 'Administration').'</p>';
}


$qDone = "SELECT * FROM versions WHERE name = 'Update Custom Fields'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}
if(! $done) {
    $fail = updateFieldsMetaData();

    if (! $fail) {
        unset($_SESSION['upgrade_calls_meetings']);
        Version::mark_upgraded('Update Custom Fields', '7.0', '7.0');
        echo '<p>'.translate('LBL_UPGRADE_COMPLETE', 'Administration').'</p>';
    }
}

/*if(! $done) {
	$q = "SELECT * FROM fields_meta_data WHERE NOT deleted";
	$r = $db->query($q);
	$qs = array();
	$fail = false;
	if($r) {
		while( ($row = $db->fetchByAssoc($r)) ) {
			if(! array_key_exists('custom_bean', $row)) {
				echo '<p>Perform database repair first.</p>';
				$fail = true;
				break;
			}
			if(empty($row['custom_bean'])) {
				$bean = AppConfig::module_primary_bean($row['custom_module']);
				$qs[] = "UPDATE fields_meta_data SET custom_bean='$bean' WHERE id='{$row['id']}'";
			}
		}
	}
	
	if(! $fail) {
		foreach($qs as $q)
			$db->query($q);
		unset($_SESSION['upgrade_calls_meetings']);
		Version::mark_upgraded('Update Custom Fields', '7.0', '7.0');
		echo '<p>'.translate('LBL_UPGRADE_COMPLETE', 'Administration').'</p>';
	}
}*/

