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

require_once 'data/SugarBean.php';

class Event extends SugarBean
{
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $assigned_user_name;
	var $created_by;
	var $name;
	var $description;
	var $num_sessions;
	var $event_type_id;
	var $format;
	var $product_id;
	var $product_name;

	var $registrant_count;


	var $new_schema = true;
	var $module_dir = 'Events';
	var $table_name = 'events';
	var $object_name = 'Event';

	function Event()
	{
		parent::SugarBean();
		$this->additional_column_fields[] = 'product_name';
		$this->additional_column_fields[] = 'assigned_user_name';
		$this->additional_column_fields[] = 'registrant_count';
		$this->additional_column_fields[] = 'date_start';
		$this->additional_column_fields[] = 'registered';
	}

	function get_summary_text()
	{
		return '' . $this->name;
	}

	function fill_in_additional_detail_fields()
	{
		$query = "SELECT products.name as product_name FROM products WHERE id = '{$this->product_id}' AND deleted = 0";
		$res = $this->db->query($query, true);
		if ($row = $this->db->fetchByAssoc($res)) {
			$this->product_name = $row['product_name'];
		} else {
			$this->product_name = '';
		}
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
	}

	function get_next_session_number()
	{
		$query = "SELECT session_number FROM event_sessions WHERE event_id='{$this->id}' AND deleted = 0 ORDER BY session_number";
		$res = $this->db->query($query, true);
		$next = 1;
		while ($row = $this->db->fetchByAssoc($res)) {
			if ($row['session_number'] > $next) {
				break;;
			}
			$next ++;
		}
		if ($next > $this->num_sessions && $this->num_sessions) {
			$next = 0;
		}
		return $next;
	}

	function mark_deleted($id)
	{
		$this->db->query("UPDATE event_sessions SET deleted = 1 WHERE event_id = '$id'", true);
		parent::mark_deleted($id);
	}

	function create_list_query($order_by, $where, $show_deleted = 0)
	{
		$custom_join = $this->custom_fields->getJOIN();
	    $query = "SELECT ";

        $query .= "events.*, event_sessions.session_number, event_sessions.date_start, event_sessions.date_end, event_sessions.attendee_limit ";
		if($custom_join){
			$query .=  $custom_join['select'];
		}
		$query .= " FROM  events ";
		$query .= "LEFT JOIN event_sessions ON (events.id=event_sessions.event_id  AND NOT event_sessions.deleted) ";
		if($custom_join){
			$query .=  $custom_join['join'];
		}
		$where_auto = '1=1';
		if($show_deleted == 0){
    	   	$where_auto = " events.deleted=0 ";
		} else if($show_deleted == 1){
			$where_auto = " events.deleted=1 ";	
		}
		if($where != "")
			$query .= "where ($where) AND ".$where_auto;
		else
        	$query .= "where ".$where_auto;
        if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY $this->table_name.name";
		return $query;
	}

	function get_list_view_data()
	{
		global $timedate;
		$query = "SELECT COUNT(*) AS c FROM events_customers LEFT JOIN event_sessions ON event_sessions.id=events_customers.session_id LEFT JOIN events_attendance on events_attendance.session_id = event_sessions.id AND events_attendance.customer_id = events_customers.customer_id AND events_attendance.deleted=0 WHERE events_customers.deleted = 0 AND events_attendance.registered = 1 OR events_attendance.registered is NULL AND event_sessions.event_id = '{$this->id}'";
		$res = $this->db->query($query, true);
		$row = $this->db->fetchByAssoc($res);
		$this->registrant_count = (int)$row['c'];
		$data = parent::get_list_view_data();
		if($data['DATE_START'] == '0000-00-00 00:00:00') {
			$data['DATE_START'] = '';
		} else {
			$data['DATE_START'] = $timedate->to_display_date_time($data['DATE_START']);
		}
		if($data['DATE_END'] == '0000-00-00 00:00:00') {
			$data['DATE_END'] = '';
		} else {
			$data['DATE_END'] = $timedate->to_display_date_time($data['DATE_END']);
		}
		return $data;
	}

	function bean_implements($interface){
		/*
		switch($interface){
			case 'ACL':return true;
		}
		 */
		return false;
	}



}


