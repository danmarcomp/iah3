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

// longreach - added
global $app_strings;

global $mod_strings;
if (!isset($module_menu) || !is_array($module_menu)) $module_menu = Array();


global $mod_strings;

$module_menu[] = Array("index.php?module=Calendar&action=index&view_type=day&target_date_special=today", $app_strings['LBL_CALENDAR_TODAY'],"Calendar");

if(ACLController::checkAccess('Meetings', 'edit', true)) $module_menu[] = Array("index.php?module=Meetings&action=EditView&return_module=Meetings&return_action=DetailView", $mod_strings['LNK_NEW_MEETING'],"CreateMeetings");
if(ACLController::checkAccess('Calls', 'edit', true))$module_menu[]=Array("index.php?module=Calls&action=EditView&return_module=Calls&return_action=DetailView", $mod_strings['LNK_NEW_CALL'],"CreateCalls");
if(ACLController::checkAccess('Tasks', 'edit', true)) $module_menu[] = Array("index.php?module=Tasks&action=EditView&return_module=Tasks&return_action=DetailView", $mod_strings['LNK_NEW_TASK'],"CreateTasks");
if(ACLController::checkAccess('Notes', 'edit', true)) $module_menu[] = Array("index.php?module=Notes&action=EditView&return_module=Notes&return_action=DetailView", $app_strings['LNK_NEW_NOTE'],"CreateNotes");

if(ACLController::checkAccess('Meetings', 'list', true))$module_menu[]=Array("index.php?module=Meetings&action=index&return_module=Meetings&return_action=DetailView", $mod_strings['LNK_MEETING_LIST'],"Meetings");
if(ACLController::checkAccess('Calls', 'list', true))$module_menu[]=Array("index.php?module=Calls&action=index&return_module=Calls&return_action=DetailView", $mod_strings['LNK_CALL_LIST'],"Calls");
if(ACLController::checkAccess('Tasks', 'list', true))$module_menu[]=Array("index.php?module=Tasks&action=index&return_module=Tasks&return_action=DetailView", $mod_strings['LNK_TASK_LIST'],"Tasks");
if(ACLController::checkAccess('Notes', 'list', true))$module_menu[]=Array("index.php?module=Notes&action=index&return_module=Notes&return_action=DetailView", $mod_strings['LNK_NOTE_LIST'],"Notes");

if(ACLController::checkAccess('Vacations', 'list', true))$module_menu[]=Array("index.php?module=Vacations&action=index&return_module=Vacations&return_action=DetailView", $mod_strings['LNK_LEAVE_LIST'], "Vacations");
if(ACLController::checkAccess('Vacations', 'edit', true)) {
	$module_menu[]=Array("index.php?module=Vacations&action=EditView&leave_type=vacation&return_module=Vacations&return_action=DetailView", $mod_strings['LNK_LEAVE_VACATION'], "CreateVacations");
	$module_menu[]=Array("index.php?module=Vacations&action=EditView&leave_type=sick&return_module=Vacations&return_action=DetailView", $mod_strings['LNK_LEAVE_SICK'], "CreateVacations");
}
	

