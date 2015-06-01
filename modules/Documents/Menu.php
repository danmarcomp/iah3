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
 * $Id: Menu.php,v 1.8 2006/01/17 22:50:47 majed Exp $
 * Description:  TODO To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

// longreach - most file contents replaced


global $mod_strings;
global $current_user;

$canEdit = ACLController::checkAccess('Documents', 'edit', true);
$canList = ACLController::checkAccess('Documents', 'list', true);

$module_menu = Array();
	
if ($canEdit) $module_menu[] = Array("index.php?module=Documents&action=EditView&return_module=Documents&return_action=DetailView", $mod_strings['LNK_NEW_DOCUMENT'],"CreateDocuments");

if ($canList) $module_menu[] = Array("index.php?module=Documents&action=index", $mod_strings['LNK_DOCUMENT_LIST'],"Documents");

;
global $current_user;
$actions = ACLAction::getUserActions($current_user->id, false);
if (ACLController::checkModuleAllowed('HR', $actions)) {
	if ($canEdit) $module_menu[] = array(
		"index.php?module=Documents&action=EditView&section=hr&return_module=Documents&return_action=DetailView",
		$mod_strings['LNK_HR_NEW_DOCUMENT'], "CreateHRDocument");
	if ($canList) $module_menu[] = array(
		"index.php?module=Documents&action=index&layout=HR&return_module=Documents&return_action=DetailView",
		$mod_strings['LNK_HR_DOCUMENT_LIST'], "HRDocuments");
}
// longreach - end added

if(ACLController::checkAccess('Documents','list', true)) $module_menu[] = Array('#', '<span style="display: none">wp_shortcut_fill_0</span>', '');

?>
