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

class EventSession extends SugarBean
{
	var $id;
	var $session_id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $assigned_user_name;
	var $created_by;
	var $name;
	var $description;

	var $session_number;
	var $date_start;
	var $date_end;
	var $no_date_start;
	var $no_date_end;
	var $url;
	var $phone;
	var $phone_password;
	var $location;
	var $location_url;
	var $location_maplink;
	var $website;
	var $tracking_code;
	var $breakfast;
	var $lunch;
	var $dinner;
	var $refreshments;
	var $parking;
	var $speaker1;
	var $host1;
	var $speaker2;
	var $host2;
	var $speaker3;
	var $host3;
	var $speaker4;
	var $host4;
	var $speaker5;
	var $host5;
	var $email_confirmation;
	var $attendee_limit;
	var $attendee_overflow;
	var $calendar_post;
	var $calendar_color;
	var $event_id;

	var $registrant_count;

	var $new_schema = true;
	var $module_dir = 'EventSessions';
	var $table_name = 'event_sessions';
	var $object_name = 'EventSession';
	
	var $additional_column_fields = array(
		'assigned_user_name', 'registrant_count',
	);

	function EventSession()
	{
		parent::SugarBean();
	}


	function get_summary_text()
	{
		return '' . $this->name;
	}

	function retrieveByNumber($event_id, $number)
	{
		$query = "SELECT id FROM event_sessions WHERE event_id = '{$event_id}' AND session_number='$number' AND deleted = 0";
		$res = $this->db->query($query, true);
		if ($row = $this->db->fetchByAssoc($res)) {
			return $this->retrieve($row['id']);
		} else {
			return null;
		}
	}

	function fill_in_additional_list_fields()
	{
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$query = "SELECT COUNT(*) AS c FROM events_customers  WHERE events_customers.deleted = 0 AND events_customers.registered = 1 AND events_customers.session_id = '{$this->id}'";
		$res = $this->db->query($query, true);
		$row = $this->db->fetchByAssoc($res);
		$this->registrant_count = (int)$row['c'];
	}

	function fill_in_additional_detail_fields()
	{
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$query = "SELECT COUNT(*) AS c FROM events_customers  WHERE events_customers.deleted = 0 AND events_customers.registered = 1 AND events_customers.session_id = '{$this->id}'";
		$res = $this->db->query($query, true);
		$row = $this->db->fetchByAssoc($res);
		$this->registrant_count = (int)$row['c'];

		$query = "SELECT name FROM events WHERE id = '{$this->event_id}'";
		$res = $this->db->query($query, true);
		if ($row = $this->db->fetchByAssoc($res)) {
			$this->event_name = $row['name'];
		}
	}

	function leads_attendance()
	{
		$query = "SELECT leads.*, events_customers.registered, events_customers.attended FROM leads LEFT JOIN events_customers ON leads.id = events_customers.customer_id AND events_customers.deleted = 0 WHERE leads.deleted = 0 AND events_customers.session_id='{$this->id}'";
		return $query;
	}

	function accounts_attendance()
	{
		$query = "SELECT accounts.*, events_customers.registered, events_customers.attended FROM accounts LEFT JOIN events_customers ON accounts.id = events_customers.customer_id AND events_customers.deleted = 0 WHERE accounts.deleted = 0 AND events_customers.session_id='{$this->id}'";
		return $query;
	}

	function contacts_attendance()
	{
		$query = "SELECT contacts.*, events_customers.registered, events_customers.attended, accounts.id AS account_id, accounts.name AS account_name "
			." FROM contacts LEFT JOIN accounts_contacts ac ON ac.contact_id=contacts.id AND NOT ac.deleted LEFT JOIN accounts ON accounts.id=ac.account_id "
			." LEFT JOIN events_customers ON contacts.id = events_customers.customer_id AND events_customers.deleted = 0 WHERE contacts.deleted = 0 AND events_customers.session_id='{$this->id}'";
		return $query;
	}

	function get_list_view_data()
	{
		global $app_list_strings;
		$data = parent::get_list_view_data();
		$data['REGISTRANT_COUNT'] = $this->registrant_count;
		$data['EVENT_FORMAT'] = $app_list_strings['event_format_dom'][$data['EVENT_FORMAT']];
		if (isset($data['EVENT_TYPE_ID'])) {
			$data['EVENT_TYPE_NAME'] = $this->fill_type_name($data['EVENT_TYPE_ID']);
		}
		return $data;
	}

	function fill_type_name($type_id) {
		static $types = array();
		if (!isset($types[$type_id])) {
			$res = $this->db->query("SELECT name FROM event_types WHERE id='$type_id'", true);
			$row = $this->db->fetchByAssoc($res);
			if ($row) {
				$types[$type_id] = $row['name'];
			} else {
				$types[$type_id] = $row['name'];
			}
		}
		return $types[$type_id];
	}

	function bean_implements($interface){
		switch($interface){
			case 'ACL':return true;
		}
		return false;
	}

	function create_export_query(&$order_by, &$where)
	{
		$custom_join = $this->custom_fields->getJOIN();
		$query = "SELECT event_sessions.*,
					events.num_sessions,
					events.event_type_id,
					event_types.name as event_type_name,
					events.format,
					events.product_id,
					products.name AS product_name
					";
		
		if($custom_join) {
			$query .=  $custom_join['select'];
		}

		$query .= "FROM event_sessions ";

		if($custom_join) {
			$query .=  $custom_join['join'];
		}
		$query .= " LEFT JOIN events ON events.id = event_sessions.event_id AND events.deleted = 0 ";
		$query .= " LEFT JOIN event_types ON event_types.id = events.event_type_id AND event_types.deleted = 0 ";
		$query .= " LEFT JOIN products ON products.id = events.product_id AND products.deleted = 0 ";
		
		$where_auto = " event_sessions.deleted=0 ";
		
		if($where != "") {
			$query .= "where ($where) AND ".$where_auto;
		} else {
			$query .= "where ".$where_auto;
		}

		if (!empty($order_by)) {
			$query .= " ORDER BY $order_by";
		}
		
		return $query;
	}

	function create_list_query($order_by, $where, $show_deleted = 0)
	{
		$custom_join = false;
		if(isset($this->custom_fields))
			$custom_join = $this->custom_fields->getJOIN();
		$query = "SELECT event_sessions.* ";
		if($custom_join) {
			$query .= $custom_join['select'];
		}
		$query .= " FROM event_sessions LEFT JOIN events ON events.id = event_sessions.event_id ";
		if($custom_join) {
			$query .= $custom_join['join'];
		}
		
		$where_auto = '1=1';
		if($show_deleted == 0){
			$where_auto = "$this->table_name.deleted=0";
		} else if($show_deleted == 1) {
			$where_auto = "$this->table_name.deleted=1";
		}
		if($where != "")
		$query .= "where ($where) AND $where_auto";
		else
		$query .= "where $where_auto";

		if(!empty($order_by))
		$query .= " ORDER BY $order_by";


		return $query;
	}

	function get_search_types()
	{
		require_once 'modules/EventTypes/EventType.php';
		$type = new EventType;
		return get_event_types();
	}

    /**
     * Get related session ID
     *
     * @static
     * @param string $event_id
     * @param int $current_number - currenct session number
     * @param string $type: next, previous
     * @return null|string
     */
    static function getRelatedSession($event_id, $current_number, $type) {
        global $db;

        $operator = null;
        $desc = '';

        if ($type == 'next') {
            $operator = '>';
        } elseif ($type == 'previous') {
            $desc = 'DESC';
            $operator = '<';
        }

        if ($operator != null) {
            $query = "SELECT id FROM event_sessions WHERE event_id = '{$event_id}' AND session_number ".$operator." '{$current_number}' AND deleted = 0 ORDER BY session_number ".$desc." LIMIT 1";
            $res = $db->query($query, true);

            if ($row = $db->fetchByAssoc($res)) {
                return $row['id'];
            } else {
                return null;
            }
        }

        return null;
    }

    static function calc_registrants($spec) {
        $count = 0;

        if (isset($spec['raw_values']['id'])) {
            global $db;

            $query = "SELECT COUNT(*) AS c FROM events_customers
                WHERE events_customers.deleted = 0 AND events_customers.registered = 1
                AND events_customers.session_id = '".$spec['raw_values']['id']."'";

            $result = $db->query($query);
            $data = $db->fetchByAssoc($result);

            if (isset($data['c']))
                $count = (int)$data['c'];
        }

        return $count;
    }

    static function disable_dates(DetailManager &$mgr) {
        global $pageInstance;
        $pageInstance->add_js_literal("disableDate('start');disableDate('end');", null, LOAD_PRIORITY_FOOT);
    }

    static function go_to_next(RowUpdate &$upd) {
        if (isset($_REQUEST['next_after_save'])) {
            $params = '&previous_id=' . $upd->getPrimaryKeyValue();

            if (! $upd->new_record) {
                $next = EventSession::getRelatedSession($upd->getField('event_id'), $upd->getField('session_number'), 'next');

                if ($next)
                    $params = '&record=' . $next;
            }

            $return_url = 'index.php?module=EventSessions&action=EditView' . $params;
            echo '<script>SUGAR.util.loadUrl("'.javascript_escape($return_url).'");</script>';
        }
    }

    static function remove_relation(RowUpdate $upd, $link_name = null) {
        $link = $upd->getLinkUpdate();

        if ($link && $link->model_name = 'EventsCustomer') {
            $lq = new ListQuery('EventsCustomer');
            $lq->addSimpleFilter('session_id', $link->updates['session_id']);
            $lq->addSimpleFilter('customer_id', $link->updates['customer_id']);
            $lq->addSimpleFilter('customer_type', $link->updates['customer_type']);
            $result = $lq->runQuerySingle();

            if (! $result->failed) {
                $new_update = RowUpdate::for_result($result);
                $new_update->markDeleted();
            }
        }
    }

    static function init_session(DetailManager &$mgr) {
        global $pageInstance;
        $hide_button = true;

        if ($mgr->record->new_record) {
            $pageInstance->add_js_literal("disableNumber();", null, LOAD_PRIORITY_FOOT);
            $init_data = array('session_number' => 1);

            if (! empty($_REQUEST['previous_id'])) {
                $fields = array('session_number', 'name', 'event', 'assigned_user', 'date_start', 'date_end', 'no_date_start', 'no_date_end', 'url',
                    'phone', 'phone_password', 'location', 'location_url', 'location_maplink', 'website', 'tracking_code',
                    'breakfast', 'lunch', 'dinner', 'refreshments', 'parking', 'speaker1', 'host1', 'speaker2', 'host2', 'speaker3', 'host3',
                    'speaker4', 'host4', 'speaker5', 'host5', 'attendee_limit', 'attendee_overflow', 'calendar_post', 'calendar_color', 'description');

                $lq = new ListQuery('EventSession', $fields);
                $lq->addFilterPrimaryKey($_REQUEST['previous_id']);
                $result = $lq->runQuerySingle();

                if (! $result->failed) {
                    $init_data = $result->row;
                    unset($init_data['id']);
                    $next_number = 1;
                    $sessions_num = EventSession::getSessionsNum($init_data['event_id']);

                    if ($sessions_num != null) {
                        if ($init_data['session_number'] < $sessions_num)
                            $next_number = $init_data['session_number'] + 1;
                    }

                    if ($next_number < $sessions_num)
                        $hide_button = false;

                    $init_data['session_number'] = $next_number;
                }
            }

            require_once('include/database/RowUpdate.php');
            $upd = new RowUpdate($mgr->record);
            $upd->set($init_data);
            $upd->updateResult($mgr->record);
        } else {
            $number = $mgr->record->getField('session_number');
            $event_id = $mgr->record->getField('event_id');
            $next = EventSession::getRelatedSession($event_id, $number, 'next');

            if ($next) {
                $hide_button = false;
            } else {
                $sessions_num = EventSession::getSessionsNum($event_id);

                if ($sessions_num != null && $number < $sessions_num)
                    $hide_button = false;
            }
        }

        if ($hide_button) {
            $pageInstance->add_js_literal("changeNextButtonView(true);", null, LOAD_PRIORITY_FOOT);
        }
    }

    static function getSessionsNum($event_id) {
        $event = ListQuery::quick_fetch_row('Event', $event_id, array('num_sessions'));
        $num_sessions = null;

        if ($event != null)
            $num_sessions = $event['num_sessions'];

        return $num_sessions;
    }
}


