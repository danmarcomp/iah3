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
 * $Id: Menu.php,v 1.38 2006/03/17 00:51:06 majed Exp $
 * Description:  TODO To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

global $mod_strings;

// longreach - added
$module_menu = Array();
if(empty($GLOBALS['current_user']->id)) return;


if(ACLController::checkAccess('Contacts', 'edit', true))$module_menu[] = Array("index.php?module=Contacts&action=EditView&return_module=Contacts&return_action=DetailView", $mod_strings['LNK_NEW_CONTACT'],"CreateContacts", 'Contacts');
if(ACLController::checkAccess('Contacts', 'edit', true))$module_menu[] = Array("index.php?module=Contacts&action=EditView&layout=BusinessCard", $mod_strings['LBL_ADD_BUSINESSCARD'],"CreateContacts", 'Contacts');
if(ACLController::checkAccess('Accounts', 'edit', true))$module_menu[] = Array("index.php?module=Accounts&action=EditView&return_module=Accounts&return_action=DetailView", $mod_strings['LNK_NEW_ACCOUNT'],"CreateAccounts", 'Accounts');
if(ACLController::checkAccess('Leads', 'edit', true))$module_menu[] =	Array("index.php?module=Leads&action=EditView&return_module=Leads&return_action=DetailView", $mod_strings['LNK_NEW_LEAD'],"CreateLeads", 'Leads');
if(ACLController::checkAccess('Opportunities', 'edit', true))$module_menu[] =Array("index.php?module=Opportunities&action=EditView&return_module=Opportunities&return_action=DetailView", $mod_strings['LNK_NEW_OPPORTUNITY'],"CreateOpportunities", 'Opportunities');

if(ACLController::checkAccess('Quotes', 'edit', true))$module_menu[] = Array("index.php?module=Quotes&action=EditView&return_module=Quotes&return_action=DetailView", $mod_strings['LNK_NEW_QUOTE'],"CreateQuotes", 'Quotes');
if(ACLController::checkAccess('Invoice', 'edit', true))$module_menu[] = Array("index.php?module=Invoice&action=EditView&return_module=Invoice&return_action=DetailView", $mod_strings['LNK_NEW_INVOICE'],"CreateInvoice", 'Invoice');


if(ACLController::checkAccess('Cases', 'edit', true))$module_menu[] =Array("index.php?module=Cases&action=EditView&return_module=Cases&return_action=DetailView", $mod_strings['LNK_NEW_CASE'],"CreateCases", 'Cases');
if(ACLController::checkAccess('Bugs', 'edit', true))$module_menu[] = Array("index.php?module=Bugs&action=EditView&return_module=Bugs&return_action=DetailView", $mod_strings['LNK_NEW_BUG'],"CreateBugs", 'Bugs');

// longreach - emails moved up
if(ACLController::checkAccess('Emails', 'edit', true))$module_menu[] = Array("index.php?module=Emails&action=EditView&type=out&return_module=Emails&return_action=DetailView", $mod_strings['LNK_COMPOSE_EMAIL'],"CreateEmails", 'Emails');
if(ACLController::checkAccess('Meetings', 'edit', true))$module_menu[] = Array("index.php?module=Meetings&action=EditView&return_module=Meetings&return_action=DetailView", $mod_strings['LNK_NEW_MEETING'],"CreateMeetings", 'Meetings');
if(ACLController::checkAccess('Calls', 'edit', true))$module_menu[] = Array("index.php?module=Calls&action=EditView&return_module=Calls&return_action=DetailView", $mod_strings['LNK_NEW_CALL'],"CreateCalls", 'Calls');
if(ACLController::checkAccess('Tasks', 'edit', true))$module_menu[] = Array("index.php?module=Tasks&action=EditView&return_module=Tasks&return_action=DetailView", $mod_strings['LNK_NEW_TASK'],"CreateTasks", 'Tasks');

?>
