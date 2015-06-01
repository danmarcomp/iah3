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

require_once('include/dir_inc.php');

$json_supported_actions['change_backup_dir'] = array();

function json_change_backup_dir() {
	global $current_user;
	
	$source = $_REQUEST['source'];
	$dir = $_REQUEST['dir'];
	str_replace("\\", "/", $dir);
	$dirLen = strlen($dir);
	if ($dir[$dirLen - 1] != "/") $dir .= "/"; 
	$message = "";
	$error = 0;
	
	if( $dir == "" || ($source != "backup" && $source != "cache")) {
		$error = 1;
   		$message = 2; // directory error
	} elseif( !is_dir( $dir ) ) {
		if( !mkdir_recursive( $dir ) ) {
			$error = 1;			
			$message = 3; // directory exists
		}
	} elseif( !is_writable( $dir ) ) {
		$error = 1;		
		$message = 4; // not writable
	}
	
	if ($error == 0) {
		if ($source == "backup") {
            AppConfig::set_local("backup.dir", $dir);
			//$current_user->setPreference('backup_dir', $dir, 0, 'Backup');
		} else {
            AppConfig::set_local("backup.cache_dir", $dir);
		}
        AppConfig::save_local();
		$message = 1;//changed successfuly
	}		

	$result = array("error" => $error, "message" => $message, "source" => $source);	
	json_return_value($result);
}
?>