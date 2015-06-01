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

 * Description:	 TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('data/SugarBean.php');
require_once('modules/Contacts/Contact.php');
require_once('modules/Users/User.php');

// Meeting is used to store customer information.
class Meeting extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $modified_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;


	// longreach - added
	var $is_private;
	var $is_daylong;
	// longreach - added
	var $email_reminder_time;


	var $description;
	var $name;
	var $location;
	var $status;
	var $date_start;
	var $date_end;
	var $parent_type;
	var $parent_id;
	var $field_name_map;
	var $contact_id;
	var $user_id;
	var $reminder_time;
	var $required;
	var $accept_status;
	var $parent_name;
	var $contact_name;
	var $contact_phone;
	var $contact_email;
	var $account_id;
	var $opportunity_id;
	var $case_id;
	var $assigned_user_name;
	var $outlook_id;

	var $recurrence_of_id;

	var $recur_copy_relations = array('Users' => 'users', 'Contacts' => 'contacts');


	var $update_vcal = true;
	var $contacts_arr;
	var $users_arr;
		// when assoc w/ a user/contact:
	var $table_name = "meetings";
	var $rel_users_table = "meetings_users";
	var $rel_contacts_table = "meetings_contacts";
	var $module_dir = "Meetings";
	var $object_name = "Meeting";

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = array('assigned_user_name', 'assigned_user_id', 'contact_id', 'user_id', 'contact_name', 'accept_status');

	var $relationship_fields = array('account_id'=>'account','opportunity_id'=>'opportunity','case_id'=>'case',
									 'assigned_user_id'=>'users','contact_id'=>'contacts', 'user_id'=>'users');

	// so you can run get_users() twice and run query only once
	var $cached_get_users = null;
	var $new_schema = true;

	var $update_recurring = true;
	
	var $recur_chain_fields  = array(
		"assigned_user_id"
		, "description"
		, "name"
		, "status"
		, "location"
		, "duration"
		, 'reminder_time'
		, 'is_daylong'
	);

	function Meeting() {
		parent::SugarBean();
		if (!in_array('recurrence_rules', $this->additional_column_fields)) {
			$this->additional_column_fields[] = 'recurrence_rules';
		}
	}


	/**
	 * Stub for integration
	 * @return bool
	 */
	function hasIntegratedMeeting() {
		return false;
	}

    // this is for calendar
	function mark_deleted($id, $break_rules = true) {
		global $current_user;

		if ($break_rules) {
			require_once 'modules/Recurrence/RecurrenceRule.php';
			$m = new Meeting;
			global $disable_date_format;
			$save_date_format = $disable_date_format;
			$disable_date_format = true;
			if ($m->retrieve($id)) {
				$disable_date_format = $save_date_format;
				$r = new RecurrenceRule;
				if ($r->is_recurring($m) || $m->recurrence_of_id) {
					$r->breakAndDelete($m, 'all');
				}
			}
			$disable_date_format = $save_date_format;
		}

		parent::mark_deleted($id);
	}

	function get_meeting_users() {
		$template = new User();
		// First, get the list of IDs.
		$query = "SELECT meetings_users.required, meetings_users.accept_status, meetings_users.user_id from meetings_users where meetings_users.meeting_id='$this->id' AND meetings_users.deleted=0";
		$GLOBALS['log']->debug("Finding linked records $this->object_name: ".$query);
		$result = $this->db->query($query, true);
		$list = Array();

		while($row = $this->db->fetchByAssoc($result)) {
			$template = new User(); // PHP 5 will retrieve by reference, always over-writing the "old" one
			$record = $template->retrieve($row['user_id']);
			$template->required = $row['required'];
			$template->accept_status = $row['accept_status'];

			if($record != null) {
				// this copies the object into the array
				$list[] = $template;
			}
		}
		return $list;
	}

    function get_meeting_contacts() {
        $template = new Contact();
        // First, get the list of IDs.
        $query = "SELECT meetings_contacts.required, meetings_contacts.accept_status, meetings_contacts.contact_id from meetings_contacts where meetings_contacts.meeting_id='$this->id' AND meetings_contacts.deleted=0";
        $GLOBALS['log']->debug("Finding linked records $this->object_name: ".$query);
        $result = $this->db->query($query, true);
        $list = Array();

        while($row = $this->db->fetchByAssoc($result)) {
            $template = new Contact(); // PHP 5 will retrieve by reference, always over-writing the "old" one
            $record = $template->retrieve($row['contact_id']);
            $template->required = $row['required'];
            $template->accept_status = $row['accept_status'];

            if($record != null) {
                // this copies the object into the array
                $list[] = $template;
            }
        }
        return $list;
    }

	// longreach - start added
	function get_recurrence_forward_instances()
	{
		return 30;
	}
	function get_view_closed_where($params)
	{
		return empty($params['value']) ? "meetings.status = 'Planned'" : '1';
	}

	function get_view_closed_where_advanced()
	{
		return ' 1 ';
	}

	function getDefaultListWhereClause()
	{
		return "meetings.status = 'Planned'";
	}

	function recur_pre_save()
	{
		$this->google_id = null;
	}

	function save_relationship_changes($is_update)
	{
		global $disable_date_format;
		parent::save_relationship_changes($is_update);
		if (!empty($this->contact_id)) {
			require_once 'modules/Contacts/Contact.php';
			$obj = new Contact;
			if ($obj->retrieve($this->contact_id)) {
				$save_date_format = $disable_date_format;
				$obj->prohibit_workflow = !empty($this->prohibit_workflow);
				$disable_date_format = true;
				$obj->last_activity_date = gmdate('Y-m-d H:i:s');
				$obj->save(false);
				$disable_date_format = $save_date_format;
			}
		}
	}
	
	static function make_day_long_duration($user=null, $date_start) {
		global $timedate;
		if(! isset($user))
			$user = $GLOBALS['current_user'];
		if(isset($user->day_begin_hour)) {
			$hrs = $user->day_begin_hour;
			$startTime = sprintf('%02d:%02d', floor($hrs), ($hrs - floor($hrs)) * 60);
			$hrs = $user->day_end_hour;
			$endTime = sprintf('%02d:%02d', floor($hrs), ($hrs - floor($hrs)) * 60);
		}
		if(empty($startTime) || empty($endTime)) {
			$startTime = "09:00";
			$endTime = "18:00";
		}
		list($startHour, $startMin) = explode(":", $startTime);
		list($endHour, $endMin) = explode(":", $endTime);

        $d = $date_start . ' ' . $startTime.':00';
        $d =  $timedate->swap_formats($d, 'Y-m-d H:i:s', $timedate->get_date_time_format());
        $d = $timedate->to_db($d);

        $duration = (($endHour - $startHour) * 60) + ($endMin - $startMin);

        return array('duration' => $duration, 'date_start' => $d);
	}

	static function mutateDetailButtons($detail, &$buttons) {
        $buttons['delete'] = array(
            'vname' => 'LBL_DELETE_BUTTON_LABEL',
            'accesskey' => 'LBL_DELETE_BUTTON_KEY',
            'order' => 30,
            'confirm' => 'NTC_DELETE_CONFIRMATION',
            'icon' => 'icon-delete',
            'acl' => 'delete',
            'params' => array(
                'record_perform' => 'delete',
            ),
            'type' => 'button',
            'hidden' => 'bean.is_recurring'
        );
        $buttons['delete_instance'] = array(
            'vname' => 'LBL_DELETE_BUTTON_LABEL',
            'accesskey' => 'LBL_DELETE_BUTTON_KEY',
            'order' => 30,
            'icon' => 'icon-delete',
            'type' => 'group',
            'hidden' => '! bean.is_recurring'
        );

        $delete_group = array('this' => 'LBL_DELETE_INSTANCE_SINGLE',
            'all' => 'LBL_DELETE_INSTANCE_ALL');
		

        foreach ($delete_group as $name => $label) {
            $buttons['delete_' . $name] = array(
                'vname' => translate($label, 'Recurrence'),
                'confirm' => 'NTC_DELETE_CONFIRMATION',
                'icon' => 'icon-delete',
                'acl' => 'delete',
                'params' => array(
                    'record_perform' => 'delete',
                    'break_sequence' => $name
                ),
                'type' => 'button',
                'group' => 'delete_instance'
			);
			if ($name == 'this') {
				$buttons['delete_' . $name]['hidden'] = '!bean.recurrence_of_id';
			}
        }
    }

    /**
     * @static
     * @param string $id
     * @param string $recurrence_of_id
     * @return bool
     */
    static function is_recurring($id, $recurrence_of_id) {
        require_once 'modules/Recurrence/RecurrenceRule.php';
        $rule = new RecurrenceRule;
        if ($rule->is_recurring('Meetings', $id) || ! empty($recurrence_of_id) ) {
            return true;
        } else {
            return false;
        }
    }

    static function can_edit_recurrence($id, $recurrence_of_id) {
        $rule = new RecurrenceRule;
		require_once 'include/database/ListQuery.php';
		$lq = new ListQuery('Meeting');
		$lq->addFilterClause(
			array(
				'operator' => 'OR',
				'multiple' => array(
					array(
						'field' => 'recurrence_of_id',
						'value' =>  $id
					),
					array(
						'field' => 'id',
						'value' =>  $recurrence_of_id
					),
				)
			)
		);
		$res = $lq->runQuerySingle();
		return $res->failed || !$res->row;
    }

    /**
     * @static
     * @param string $current_id - current instance ID
     * @param string $index - current recurring index
     * @param string $type: 'next' or 'prev'
     * @return null
     */
    static function get_recurring_instance_id($current_id, $index, $type) {
        global $db;

        if ($type == 'next') {
            $operator = '>';
        } else {
            $operator = '<';
        }

        $query = "SELECT id FROM `meetings` WHERE recurrence_of_id = '$current_id' AND recurrence_index {$operator} '$index' ORDER BY recurrence_index LIMIT 1";
        $result = $db->query($query, true, "Error retrieving recurring object");
        $id = null;

        if ($row = $db->fetchByAssoc($result))
            $id = $row['id'];

        return $id;
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $fields = array('name', 'description', 'date_start', 'status', 'duration',
            'contact_name', 'contact_id', 'parent_name', 'parent_id', 'parent_type');
        $field = null;

        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            if (isset($input[$field])) {
                $update[$field] = urldecode($input[$field]);
            }
        }

        if (! isset($update['date_start'])) {
			$tm = time();
            $tm = ceil($tm / 3600) * 3600;
            $update['date_start'] = gmdate('Y-m-d H:i:s', $tm);
        }
        
        global $current_user;
        $remind = $current_user->getPreference('reminder_time');
        if($remind) $update['reminder_time'] = $remind;

        $upd->set($update);

        if(! $upd->getField('duration'))
        	$upd->set('duration', 30);
    }

    static function init_dialog(RowUpdate &$upd, $input) {
        $update = array();
        global $timedate;

        if (isset($input['is_daylong']))
            $update['is_daylong'] = $input['is_daylong'];

        if (! empty($input['duration_hours']) || ! empty($input['duration_minutes'])) {
            $duration = null;
            if (isset($input['duration_hours']))
                $duration += $input['duration_hours'] * 60;
            if (isset($input['duration_minutes']))
                $duration += $input['duration_minutes'];

            $update['duration'] = $duration;
        }
        else if(! $upd->getField('id') && ! $upd->getField('duration'))
			$update['duration'] = 60;

        if (! empty($input['date_start']) && (! empty($input['time_hour_start']) || ! empty($input['time_start'])) ) {
            $dt_start = $input['date_start'];
            if(! preg_match('/\d{4}-\d{2}-\d{2}/', $dt_start))
                $dt_start = gmdate('Y-m-d');

            if (! empty($input['time_start'])) {

                $tm_start = $input['time_start'];
                if(! preg_match('/\d{2}:\d{2}(:\d{2})/', $tm_start))
                    $tm_start = gmdate('H:i:s');

            } else if(isset($input['time_hour_start'])) {

                $h = $input['time_hour_start'];
                $m = array_get_default($input, 'time_minute_start', '00');
                $tm_start = sprintf('%02d:%02d:00', $h, $m);

            } else {
                $tm_start = '';
            }

            $update['date_start'] = $timedate->handle_offset($dt_start .' '.$tm_start, $timedate->get_db_date_time_format(), false);
        } elseif (! empty($input['date_start'])) {
            $update['date_start'] = $timedate->handle_offset($input['date_start'], $timedate->get_db_date_time_format(), false);
            $update['date_start'] = $input['date_start'];
        }

        if (sizeof($update) > 0)
            $upd->set($update);
    }

    /**
     * Set Meeting/Call invite status
     *
     * @static
     * @param RowUpdate $upd
     * @param RowResult $entity
     * @param string $status: accept, decline, tentative
     * @return void
     */
    static function set_invite_status(RowUpdate &$upd, RowResult $entity, $status) {
        $link_name = null;

        if ($entity->base_model == 'User') {
            $link_name = 'users';
        } elseif ($entity->base_model == 'Contact') {
            $link_name = 'contacts';
        }

        if ($link_name != null) {
            $id = $entity->getField('id');
            $params[$id] = array('name' => 'accept_status', 'value' => $status);
            $upd->addUpdateLink($link_name, $id, $params);
        }
    }

    static function get_invites($id, $type = 'users') {
        global $db;

        $id_name = 'user_id';
        if ($type == 'contacts')
            $id_name = 'contact_id';

        $query = "SELECT $type.id, $type.first_name, $type.last_name, $type.email1, $type.email2 FROM meetings_$type
            LEFT JOIN $type ON meetings_$type.$id_name = $type.id
            WHERE meetings_$type.meeting_id='$id' AND ( meetings_$type.accept_status IS NULL OR meetings_$type.accept_status = 'none') AND meetings_$type.deleted = 0";

        $result = $db->query($query, true);
        $list = array();

        while($row = $db->fetchByAssoc($result)) {
            $invite = array(
                'id' => $row['id'],
                'full_name' => $row['first_name'] .' '. $row['last_name'],
                'receive_notifications' => 1,
                'email1' => $row['email1'],
                'email2' => $row['email2'],
                'user_name' => '',
                'type' => $type
            );

            $list[] = $invite;
        }

        return $list;
    }

    static function get_notification_recipients(NotificationManager $manager) {
        $meeting_id = $manager->updated_bean->getField('id');
        $users = Meeting::get_invites($meeting_id, 'users');
        $contacts = Meeting::get_invites($meeting_id, 'contacts');
        $list = array_merge($contacts, $users);

        return $list;
    }

    static function before_save(RowUpdate $upd) {
        require_once('modules/Calendar/DateTimeUtil.php');
        $start = $upd->getField('date_start');
        $dt_start = explode(' ', $start, 2);

        if ($upd->getField('is_daylong')) {
            $daylong_params = self::make_day_long_duration(null, $dt_start[0]);
            $duration = $daylong_params['duration'];
            $upd->set('duration', $duration);
            $upd->set('date_start', $daylong_params['date_start']);
            $dt_start = explode(' ', $daylong_params['date_start'], 2);
        } else {
            $duration = $upd->getField('duration');
        }

        if(count($dt_start) == 2) {
            $date_time_start = DateTimeUtil::get_time_start($dt_start[0], $dt_start[1]);
            $dt_end = DateTimeUtil::get_time_end($date_time_start, 0, $duration);
            $upd->set('date_end', $dt_end->get_mysql_date());
        }
    }

    static function after_save(RowUpdate $upd) {
        require_once 'modules/Recurrence/RecurrenceRule.php';
		$rule = new RecurrenceRule;
		if (isset($_REQUEST['recurrence_rules'])) {
			$rule->update_rules_from_JSON('Meetings', $upd->getPrimaryKeyValue(), $_REQUEST['recurrence_rules'], true);
		}
		$update_type = array_get_default($_REQUEST, 'break_sequence');
		if ($update_type && self::is_recurring($upd->getPrimaryKeyValue(), $upd->getField('recurrence_of_id'))) {
			$rule->updateInstances($upd, $update_type);
		}
    }

    static function update_invitees(RowUpdate $upd) {
        require_once('modules/Meetings/ActivityInvite.php');
        $row_updates = $upd->getRelatedData('event_scheduler_rows');
		$linked = ActivityInvite::update_links($upd, $row_updates);
        $send = $upd->getRelatedData('send_invites');
        if ($send && $linked)
            self::send_invites($upd);
    }

    static function send_invites(RowUpdate &$upd) {
        require_once('include/layout/NotificationManager.php');

        $vars = array(
            'MEETING_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'MEETING_STATUS' => array('field' => 'status'),
            'MEETING_LOCATION' => array('field' => 'location'),
            'MEETING_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'MeetingInvite', $vars);
        $manager->sendMails();
    }

	static function notify_on(&$on) {
		if ($on) {
			$on = !empty($_REQUEST['send_invites']);
		}
	}

    static function after_delete(RowUpdate $upd) {
        require_once('modules/Meetings/ActivityInvite.php');
        ActivityInvite::handle_delete($upd);

        if (self::is_recurring($upd->getPrimaryKeyValue(), $upd->getField('recurrence_of_id'))) {
            require_once 'modules/Recurrence/RecurrenceRule.php';
            $rule = new RecurrenceRule;
            $rule->deleteInstances($upd, array_get_default($_REQUEST, 'break_sequence', 'this'));
        }
    }
    
    static function update_user_vcal(RowUpdate $upd, $link_name=null) {
    	if($link_name == 'users') {
			require_once('modules/Meetings/ActivityInvite.php');
			$link = $upd->getLinkUpdate();
			ActivityInvite::update_vcal($link->getField('user_id'));
    	}
    }

    static function get_activity_status(RowUpdate $upd) {
        $status = null;

        if (! $upd->new_record) {
            if ($upd->getField('date_start') != $upd->getField('date_start', null, true))
                $status = 'moved';
        }

        return $status;
    }
}
?>
