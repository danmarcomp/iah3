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


$module_menu = Array();

if(ACLController::checkAccess('Booking', 'edit', true))
	$module_menu[] = array("index.php?module=Booking&action=EditView&layout=Quick&return_module=Booking&return_action=DetailView",
		$mod_strings['LNK_ADD_HOURS'], 'CreateBookedHours');

if(ACLController::checkAccess('Booking', 'list', true) && ! using_grouped_tabs())
	$module_menu[] = array('index.php?module=Booking&action=index',
		$mod_strings['LNK_HOURS_LIST'], 'BookedHours');

if (ACLController::checkAccess('Timesheets', 'edit', true)) $module_menu[] = Array("index.php?module=Timesheets&action=EditView&return_module=Timesheets&return_action=DetailView", $mod_strings['LNK_NEW_TIMESHEET'], "CreateTimesheet");

if (ACLController::checkAccess('Timesheets', 'list', true)) $module_menu[] = Array("index.php?module=Timesheets&action=index", $mod_strings['LNK_TIMESHEETS_LIST'], "Timesheets");

if(ACLController::checkAccess('BookingCategories', 'edit', true) && ! using_grouped_tabs())
	$module_menu[] = array("index.php?module=BookingCategories&action=EditView&return_module=BookingCategories&return_action=DetailView",
		$mod_strings['LNK_ADD_BOOKING_CAT'], 'CreateBookingCategories');

if(ACLController::checkAccess('BookingCategories', 'list', true) && ! using_grouped_tabs())
	$module_menu[] = array('index.php?module=BookingCategories&action=index',
		$mod_strings['LNK_BOOKING_CAT_LIST'], 'BookingCategories');

?>
