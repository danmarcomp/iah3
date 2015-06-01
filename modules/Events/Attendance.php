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

global $odd_bg;
global $even_bg;
global $mod_strings;
global $theme;


require_once 'modules/Events/Event.php';
require_once 'XTemplate/xtpl.php';
require_once "themes/$theme/layout_utils.php";


$event = new Event;
$event->retrieve($_REQUEST['event_id']);

$type_key = $_REQUEST['type'];
$customer_id = $_REQUEST['customer_id'];

if ($type_key == 'Leads') {
	require_once 'modules/Leads/Lead.php';
	$customer = new Lead;
} elseif ($type_key == 'Accounts') {
	require_once 'modules/Accounts/Account.php';
	$customer = new Account;
} else {
	sugar_die('Unknown/unsupported customer type');
}
$customer->retrieve($customer_id);

$xtpl = new XTemplate('modules/Events/Attendance.html');

$query = "SELECT events_attendance.*, event_sessions.session_number, event_sessions.name as session_name, event_sessions.date_start, event_sessions.date_end, event_sessions.id AS sess_id FROM event_sessions LEFT JOIN events_attendance ON events_attendance.deleted = 0 AND customer_type = '{$type_key}' AND customer_id = '{$customer_id}' AND events_attendance.session_id=event_sessions.id WHERE event_id = '{$event->id}' AND event_sessions.deleted = 0 ";

$res = $event->db->query($query, true);
$oddRow = false;

$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('EVENT_ID', $event->id);
$xtpl->assign('EVENT_NAME', $event->name);
$xtpl->assign('CUSTOMER_NAME', $customer->name);
$xtpl->assign('CUSTOMER_ID', $customer->id);
$xtpl->assign('CUSTOMER_TYPE_NAME', $app_list_strings['moduleListSingular'][$type_key]);
$xtpl->assign('CUSTOMER_TYPE', $type_key);


while ($row = $event->db->fetchByAssoc($res)) {
	if (!empty($_POST['save'])) {
		$registered = empty($_POST['registered'][$row['sess_id']]) ? 0 : 1;
		$attended = empty($_POST['attended'][$row['sess_id']]) ? 0 : 1;
		if ($row['id']) {
			$query = "UPDATE events_attendance SET registered=$registered, attended = $attended WHERE id='{$row['id']}'";
		} else {
			$query ="INSERT INTO events_attendance SET registered=$registered, attended = $attended,  id='".create_guid()."', customer_type='$type_key', customer_id='$customer_id', session_id='{$row['sess_id']}'";
		}
		$event->db->query($query, true);
		continue;
	}
	if (!empty($row['date_start'])) {
		$row['date_start'] = $timedate->to_display_date_time($row['date_start']);
	}
	if (!empty($row['date_end'])) {
		$row['date_end'] = $timedate->to_display_date_time($row['date_end']);
	}
	if($oddRow)	{
		$ROW_COLOR = 'oddListRow';
		$BG_COLOR =  $odd_bg;
	} else {
		$ROW_COLOR = 'evenListRow';
		$BG_COLOR =  $even_bg;
	}
	$oddRow = !$oddRow;
	$xtpl->assign('ROW_COLOR', $ROW_COLOR);
	$xtpl->assign('BG_COLOR', $BG_COLOR);
	$xtpl->assign('ROW', $row);
	if (!isset($row['registered']) || $row['registered']) {
		$xtpl->assign('REGISTERED', 'checked');
	} else {
		$xtpl->assign('REGISTERED', '');
	}
	if (!empty($row['attended'])) {
		$xtpl->assign('ATTENDED', 'checked');
	} else {
		$xtpl->assign('ATTENDED', '');
	}
	$xtpl->parse('main.row');
}

if (empty($_POST['save'])) {
	insert_popup_header($theme);
	$xtpl->parse('main');
	$xtpl->out('main');
	insert_popup_footer();
} else {
	echo '<script type="text/javascript">window.close();</script>';
}
