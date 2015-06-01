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
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

global $mod_strings;
global $sugar_version, $sugar_flavor, $server_unique_key, $current_language;

$module_menu = Array(
	Array(
		"index.php?module=Users&action=EditView&return_module=Users&return_action=DetailView",
		$mod_strings['LNK_NEW_USER'],
		"CreateUsers"
	),
	Array(
		"index.php?module=Administration&action=Maintain",
		$mod_strings['LBL_MAINTAIN_TITLE'],
		"Repair"
	),
);

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "SupportPortal") {



	$module_menu[] = Array("index.php?module=Administration&action=SupportPortal&view=documentation&help_module=Administration&edition={$sugar_flavor}&key={$server_unique_key}&language={$current_language}", $mod_strings['LBL_DOCUMENTATION_TITLE'],"OnlineDocumentation");
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "UpgradeFields") {
	$module_menu[] = array('index.php?module=EditCustomFields&action=index',$mod_strings['LNK_SELECT_CUSTOM_FIELD'], 'Administration');
	$module_menu[] = array('index.php?module=Administration&action=UpgradeFields',$mod_strings['LNK_REPAIR_CUSTOM_FIELD'], 'Administration');
}

?>
