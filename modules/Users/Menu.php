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
global $current_user;

$module_menu=Array();
if(is_admin($current_user))
{
$module_menu = Array(
	Array("index.php?module=Users&action=EditView&return_module=Users&return_action=DetailView", $mod_strings['LNK_NEW_USER'],"CreateUsers"),
	Array("index.php?module=Users&action=index&return_module=Users&return_action=DetailView", $mod_strings['LNK_USER_LIST'],"Users"),
	Array("index.php?module=ACLRoles&action=index", $mod_strings['LBL_ROLES_SUBPANEL_TITLE'],"ACLRoles"),
	Array("index.php?module=SecurityGroups&action=index", $mod_strings['LBL_SECURITYGROUPS_SUBPANEL_TITLE'],"SecurityGroups"),
	);
}
/*
	array_push($module_menu, Array("index.php?module=Users&action=EditTabs&return_module=Users&return_action=DetailView", $mod_strings['LNK_EDIT_TABS'],"Users"))
*/
?>
