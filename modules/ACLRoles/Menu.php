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
global $mod_strings;
$module_menu = Array(
	Array("index.php?module=ACLRoles&action=EditView", $mod_strings['LBL_CREATE_ROLE'],"CreateACLRoles"),
	Array("index.php?module=ACLRoles&action=index", $mod_strings['LIST_ROLES'],"ACLRoles"),
	//Array("index.php?module=ACLRoles&action=ListUsers", $mod_strings['LIST_ROLES_BY_USER'],"Roles"),
	Array("index.php?module=ACLRoles&action=DetailView&record=".DEFAULT_ROLE_ID, $mod_strings['LBL_DEFAULT_ROLE'],"ACLRoles"),
	
	Array("index.php?module=SecurityGroups&action=index", $mod_strings['LBL_SECURITYGROUPS_SUBPANEL_TITLE'],"SecurityGroups"),
	Array("index.php?module=Users&action=index", $mod_strings['LBL_USERS_SUBPANEL_TITLE'],"Users"),
	);
?>
