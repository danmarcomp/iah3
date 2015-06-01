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
// longreach - added
$module_menu = Array();
if(ACLController::checkAccess('Bugs', 'edit', true))
$module_menu [] =	Array("index.php?module=Bugs&action=EditView&return_module=Bugs&return_action=DetailView", $mod_strings['LNK_NEW_BUG'],"CreateBugs", 'Bugs');
if(ACLController::checkAccess('Bugs', 'list', true))
$module_menu [] =		Array("index.php?module=Bugs&action=index&return_module=Bugs&return_action=DetailView", $mod_strings['LNK_BUG_LIST'],"Bugs", 'Bugs');



// longreach - added - moved from admin
global $current_user;
if(is_admin($current_user)) {
	$module_menu [] =	Array("index.php?module=SoftwareProducts&action=EditView", $mod_strings['LNK_CREATE_PRODUCTS'],"CreateSoftwareProducts", 'Bugs');
	$module_menu [] =	Array("index.php?module=SoftwareProducts&action=index", $mod_strings['LNK_PRODUCTS'],"SoftwareProducts", 'Bugs');
	$module_menu [] =	Array("index.php?module=Releases&action=index", $mod_strings['LNK_RELEASES'],"Releases", 'Bugs');
}


if(ACLController::checkAccess('Bugs','list', true)) $module_menu[] = Array('#', '<span style="display: none">wp_shortcut_fill_0</span>', '');

?>
