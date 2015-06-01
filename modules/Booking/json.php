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


$json_supported_actions['create_booked_hours'] = array();
function json_create_booked_hours() {
	require_once 'modules/Booking/utils.php';
	$result = booking_quick_save();
	json_return_value($result);
}

$json_supported_actions['get_booked_hours'] = array();

function json_get_booked_hours() {
	global $current_user;
    require_once 'modules/Booking/BookedHours.php';
    $hours = new BookedHours;
    $uid = array_get_default($_REQUEST, 'user_id', $current_user->id);
    $ts_id = array_get_default($_REQUEST, 'timesheet_id', '');
    $status = ''; //array_get_default($_REQUEST, 'status', 'pending');
    $dt_start = array_get_default($_REQUEST, 'date_start');
    $dt_end = array_get_default($_REQUEST, 'date_end');
    if(! $dt_start || ! $dt_end) {
    	$dt_start = gmdate('Y-m-d', time()-7*24*3600);
    	$dt_end = gmdate('Y-m-d');
    }
    $data =& $hours->query_hours(false, $ts_id, $uid, $status, $dt_start, $dt_end, true);
    $arr = array('order' => array_keys($data), 'hours' => $data);
    json_return_value($arr);
}
?>