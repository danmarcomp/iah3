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

$json_supported_actions['portal_get_events'] = array('login_required' => 'portal');

function json_portal_get_events()
{
	global $db, $current_user;
	if($_SESSION['type'] != 'contact' && empty($_SESSION['registered_lead'])){
		return json_bad_request(array('error' => 'no_access'));
	}
	$fields = array('where', 'order_by', 'date_start', 'date_end', 'select_fields', 'row_offset', 'limit');
	foreach ($fields as $f) {
		$$f = array_get_default($_REQUEST, $f);
	}
	$limit = (int)$limit;
	$row_offset = (int)$row_offset;
	require_once 'modules/Events/Event.php';
	$template = new Event;
	$query = "SELECT DISTINCT(events.id) FROM events LEFT JOIN event_sessions ON events.id = event_sessions.event_id AND event_sessions.deleted = 0 WHERE events.deleted = 0 AND event_sessions.deleted = 0 AND event_sessions.calendar_post ";
	if (!empty($where)) {
		$query .= ' AND (' . $where . ')';
	}
	if (!empty($date_start)) {
		$query .= " AND event_sessions.date_start >= '" . $db->quote($date_start) . "' AND !event_sessions.no_date_start";
	}
	if (!empty($date_end)) {
		$query .= " AND event_sessions.date_end <= '" . $db->quote($date_end) . "' AND !event_sessions.no_date_start";
	}

	if (empty($date_start) && empty($date_end)) {
		$time = gmdate('Y-m-d H:i:s', strtotime('today 00:00:00 -1 day GMT'));
		$query .= " AND event_sessions.date_start >= '$time' ";
	}

	$list = $template->build_related_list_where($query, $template, '', '', $order_by, $limit, $row_offset);
	$total = $template->get_related_list_size($query, $template, '', '');
    $output_list = Array();
    $field_list = array();
	$custid =  empty($_SESSION['registered_lead']) ? $_SESSION['contact_id'] : $_SESSION['lead_id'];
    foreach($list as $value)
	{
		$q = "SELECT MIN(date_start) AS date_start FROM event_sessions WHERE event_id = '" . $value->id . "' AND deleted = 0 and ! no_date_start";
		$res = $db->query($q);
		$row = $db->fetchByAssoc($res);
		$value->date_start = '' . $row['date_start'];
		$value->field_defs['date_start']['type'] = 'relate';

		$q = "SELECT COUNT(events_customers.id) AS c FROM event_sessions LEFT JOIN events_customers ON event_sessions.id = events_customers.session_id WHERE events_customers.customer_id='" . $custid . "' AND event_sessions.deleted = 0 AND events_customers.deleted = 0 AND event_sessions.event_id = '" . $value->id . "'";
		$res = $db->query($q);
		$row = $db->fetchByAssoc($res);
		$value->registered = (bool)$row['c'];
		$value->field_defs['registered'] = array(
			'name' => 'registered',
			'type' => 'bool',
		);

    	// Create the row record
    	$arRow = array();
		// Loop through the requested fields
		if (empty($select_fields)) {
			$select_fields = array_merge($value->column_fields, $value->additional_column_fields);
		}

		$output_list[] = json_convert_bean($value, $select_fields);

        $_SESSION['viewable']['Events'][$value->id] = $value->id;
    }
	$ret = array(
		'total_count' => $total,
		'result_count'=>sizeof($output_list),
		'next_offset'=>0,
		'list' => $output_list, 
	);
	json_return_value($ret);
}


$json_supported_actions['portal_register_event'] = array('login_required' => 'portal');
function json_portal_register_event()
{
	$id = array_get_default($_REQUEST, 'event_id');
	if($_SESSION['type'] == 'lead' && empty($_SESSION['registered_lead'])){
		return json_bad_request(array('error' => 'no_access'));
	}
	global $db;
	if (!empty($_SESSION['registered_lead'])) {
		$account = ListQuery::quick_fetch('Lead', $_SESSION['lead_id']);
	} else {
		$account = ListQuery::quick_fetch('Contact', $_SESSION['contact_id']);
	}
	if(! $account) {
		return json_bad_request(array('error' => 'no_access'));
	}
	$upd = RowUpdate::for_result($account);

	$ids = array_get_default($_REQUEST, 'session_ids');
	if(! is_array($ids)) $ids = array_filter(explode(',', $ids));
	$ids = array_flip($ids);

	$query = "SELECT id FROM event_sessions WHERE event_id ='{$id}' AND deleted = 0";
	$res = $db->query($query, true);
	while ($row = $db->fetchByAssoc($res)) {
		if (! $ids || isset($ids[$row['id']])) {
			$upd->addUpdateLink('eventsessions', $row['id']);
		}
	}
	$ret = 1;
	json_return_value($ret);
}


function portal_add_registration_info(&$arRecords)
{
	if (empty($arRecords)) {
		return;
	}

	global $db;

	$custid =  empty($_SESSION['registered_lead']) ? $_SESSION['contact_id'] : $_SESSION['lead_id'];

	$session_ids = array();
	foreach ($arRecords as $session) {
		$session_ids[] = $session['id'];
	}

	$registered = array();

	$ids_clause = "events_customers.session_id IN ('" . join("','", $session_ids) . "')";
	$query = "SELECT session_id FROM events_customers WHERE customer_id='" . $db->quote($custid) . "' AND deleted = 0 AND registered AND $ids_clause";
	$res = $db->query($query, true);

	while ($row = $db->fetchByAssoc($res)) {
		$registered[$row['session_id']] = 1;
	}

	foreach ($arRecords as $i => $session) {
		$arRecords[$i]['registered'] = isset($registered[$session['id']]) ? 1 : 0;
	}
}


