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


global $mod_strings;
$module_menu = Array();

if (ACLController::checkAccess('MonthlyServices', 'edit', true)) $module_menu[] = Array("index.php?module=MonthlyServices&action=EditView&return_module=MonthlyServices&return_action=DetailView", $mod_strings['LNK_NEW_SERVICE'], "CreateMonthlyServices");

if (ACLController::checkAccess('MonthlyServices', 'list', true)) $module_menu[] = Array("index.php?module=MonthlyServices&action=index", $mod_strings['LNK_SERVICES_LIST'], "MonthlyServices");

if(ACLController::checkAccess('BookingCategories', 'list', true)) $module_menu[] = array("index.php?module=BookingCategories&action=index&booking_class_basic=services-monthly&searchFormTab=basic_search&query=true", $mod_strings['LNK_SERVICE_CATEGORY_LIST'], 'BookingCategories');

if(ACLController::checkAccess('BookingCategories', 'edit', true)) $module_menu[] = array("index.php?module=BookingCategories&action=EditView&booking_class=services-monthly&return_module=MonthlyServices&return_action=index", $mod_strings['LNK_ADD_SERVICE_CATEGORY'], 'CreateBookingCategories');

?>