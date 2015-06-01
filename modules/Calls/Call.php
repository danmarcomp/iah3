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

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/




require_once('data/SugarBean.php');
require_once('modules/Contacts/Contact.php');
require_once('modules/Users/User.php');

// Call is used to store customer information.
class Call extends SugarBean {
	var $field_name_map;
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $modified_user_id;


	// longreach - added
	var $is_private;
	
	var $description;
	var $name;
	var $status;
	var $date_start;
	var $date_end;
	var $parent_type;
	var $parent_id;
	var $contact_id;
	var $user_id;
	var $direction;
	var $reminder_time;
	var $required;
	var $accept_status;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $parent_name;
	var $contact_name;
	var $contact_phone;
	var $contact_email;
	var $account_id;
	var $opportunity_id;
	var $case_id;
	var $assigned_user_name;
	var $note_id;
	var $outlook_id;

	// longreach - added
	var $email_reminder_time;


	var $update_vcal = true;
	var $contacts_arr;
	var $users_arr;
	var $default_call_name_values = array('Assemble catalogs', 'Make travel arrangements', 'Send a letter', 'Send contract', 'Send fax', 'Send a follow-up letter', 'Send literature', 'Send proposal', 'Send quote');
	var $minutes_value_default = 15;
	var $minutes_values = array('0'=>'00','15'=>'15','30'=>'30','45'=>'45');
	var $table_name = "calls";
	var $rel_users_table = "calls_users";
	var $rel_contacts_table = "calls_contacts";
	var $module_dir = 'Calls';
	var $object_name = "Call";
	var $new_schema = true;
	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = array('assigned_user_name', 'assigned_user_id', 'contact_id', 'user_id', 'contact_name');
	var $relationship_fields = array(	'account_id'		=> 'accounts',
										'opportunity_id'	=> 'opportunities',
										'contact_id'		=> 'contacts',
										'case_id'			=> 'cases',
										'user_id'			=> 'users',
										'assigned_user_id'	=> 'users',
										'note_id'			=> 'notes',
								);



	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_contacts()
	{
		// First, get the list of IDs.
		$query = "SELECT contact_id as id from calls_contacts where call_id='$this->id' AND deleted=0";

		return $this->build_related_list($query, new Contact());
	}


	function get_call_users() {
		$template = new User();
		// First, get the list of IDs.
		$query = "SELECT calls_users.required, calls_users.accept_status, calls_users.user_id from calls_users where calls_users.call_id='$this->id' AND calls_users.deleted=0";
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

    function get_call_contacts() {
        $template = new Contact();
        // First, get the list of IDs.
        $query = "SELECT calls_contacts.required, calls_contacts.accept_status, calls_contacts.contact_id from calls_contacts where calls_contacts.call_id='$this->id' AND calls_contacts.deleted=0";
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
	function get_view_closed_where($params)
	{
		return empty($params['value']) ? "calls.status = 'Planned'" : '1';
	}

	function get_view_closed_where_advanced()
	{
		return '1';
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

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $fields = array('name', 'description', 'date_start', 'status', 'direction',
            'duration', 'contact_name', 'contact_id', 'parent_name', 'parent_id', 'parent_type', 'phone_number');
        $field = null;

        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            if (isset($input[$field])) {
                $update[$field] = urldecode($input[$field]);
            }
        }

		if (! isset($update['date_start'])) {
			$tm = time();
            //$tm = ceil($tm / 3600) * 3600;
            $update['date_start'] = gmdate('Y-m-d H:i:s', $tm);
        }

        if (! isset($update['duration']))
            $update['duration'] = 5;

        if (! isset($update['parent_type']) && isset($input['return_module']) && $input['return_module'] != 'Calls')
            $update['parent_type'] = $input['return_module'];

        if (! isset($update['phone_number']) && (! empty($update['parent_type']) && ! empty($update['parent_id']))) {
            $bean = AppConfig::module_primary_bean($update['parent_type']);
            $parent = ListQuery::quick_fetch_row($bean, $update['parent_id'], array('phone_office', 'phone_work'));
            $phone = '';

            if (isset($parent['phone_office'])) {
                $phone = $parent['phone_office'];
            } elseif (isset($parent['phone_work'])) {
                $phone = $parent['phone_work'];
            }

            if ($phone != '')
                $update['phone_number'] = $phone;
        }
        
        global $current_user;
        $remind = $current_user->getPreference('reminder_time');
        if($remind) $update['reminder_time'] = $remind;

        $upd->set($update);
    }

    static function init_dialog(RowUpdate &$upd, $input) {
        require_once('modules/Meetings/Meeting.php');
        Meeting::init_dialog($upd, $input);
    }

    function set_notification_body($xtpl, $call) {
        global $app_list_strings;
        global $current_user;
        global $app_list_strings;
        global $timedate;

        $prefDate = User::getUserDateTimePreferences($call->current_notify_user);

        // longreach - start added
        if(! is_array($prefDate)) {
            // current_notify_user may actually be a Contact
            $prefDate = User::getUserDateTimePreferences($current_user);
        }
        // longreach - end added

        /* longreach - removed
        $x = date($prefDate['date']." ".$prefDate['time'], strtotime(($call->date_start . " " . $call->time_start)));
        $xOffset = $timedate->handle_offset($x, $prefDate['date']." ".$prefDate['time'], true, $current_user);
        */
        // longreach - start added
        $x = $call->date_start . " " . $call->time_start;
        $db_dtf = $timedate->get_db_date_time_format();
        $xOffset = $timedate->handle_offset($x, $db_dtf, true, $current_user);
        $xOffset = $timedate->swap_formats($xOffset, $db_dtf, $prefDate['date']." ".$prefDate['time']);
        // longreach - end added


        if ( strtolower(get_class($call->current_notify_user)) == 'contact' ) {
            $xtpl->assign("ACCEPT_URL", AppConfig::site_url().
                  '/acceptDecline.php?module=Calls&contact_id='.$call->current_notify_user->id.'&record='.$call->id);
        } else {
            $xtpl->assign("ACCEPT_URL", AppConfig::site_url().
                  '/acceptDecline.php?module=Calls&user_id='.$call->current_notify_user->id.'&record='.$call->id);
        }

        $xtpl->assign("CALL_TO", $call->current_notify_user->new_assigned_user_name);
        $xtpl->assign("CALL_SUBJECT", $call->name);
        $xtpl->assign("CALL_STARTDATE", $xOffset . " " . (!empty($app_list_strings['dom_timezones_extra'][$prefDate['userGmtOffset']]) ? $app_list_strings['dom_timezones_extra'][$prefDate['userGmtOffset']] : $prefDate['userGmt']));
        $xtpl->assign("CALL_HOURS", $call->duration);
        $xtpl->assign("CALL_MINUTES", $call->duration);
        $xtpl->assign("CALL_STATUS", ((isset($call->status))?$app_list_strings['call_status_dom'][$call->status] : ""));
        $xtpl->assign("CALL_DESCRIPTION", $call->description);
		
        return $xtpl;
    }

    static function get_invites($id, $type = 'users') {
        global $db;

        $id_name = 'user_id';
        if ($type == 'contacts')
            $id_name = 'contact_id';

        $query = "SELECT $type.id, $type.first_name, $type.last_name, $type.email1, $type.email2 FROM calls_$type
            LEFT JOIN $type ON calls_$type.$id_name = $type.id
            WHERE calls_$type.call_id='$id' AND ( calls_$type.accept_status IS NULL OR calls_$type.accept_status = 'none') AND calls_$type.deleted = 0";

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
        $call_id = $manager->updated_bean->getField('id');
        $users = Call::get_invites($call_id, 'users');
        $contacts = Call::get_invites($call_id, 'contacts');
        $list = array_merge($contacts, $users);

        return $list;
    }

	static function before_save(RowUpdate $upd) {
		$start = $upd->getField('date_start');
		$dt_start = explode(' ', $start, 2);
		$duration = $upd->getField('duration');
		if(count($dt_start) == 2) {
            require_once('modules/Calendar/DateTimeUtil.php');
			$date_time_start = DateTimeUtil::get_time_start($dt_start[0], $dt_start[1]);
			$dt_end = DateTimeUtil::get_time_end($date_time_start, 0, $duration);
			$upd->set('date_end', $dt_end->get_mysql_date());
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
            'CALL_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'CALL_STATUS' => array('field' => 'status'),
            'CALL_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'CallInvite', $vars);
        $manager->sendMails();
    }

    static function set_phone_number(DetailManager $mgr) {
        if ($mgr->perform == 'view') {
            $rec = $mgr->getRecord();
            $pn = format_phone($rec->getField('phone_number'));
            $mgr->layout->addFormHiddenFields(array('phone_number' => $pn), false);
        }
   	}

    static function after_delete(RowUpdate $upd) {
        require_once('modules/Meetings/ActivityInvite.php');
        ActivityInvite::handle_delete($upd);
    }
    
    static function update_user_vcal(RowUpdate $upd, $link_name=null) {
    	if($link_name == 'users') {
			require_once('modules/Meetings/ActivityInvite.php');
			$link = $upd->getLinkUpdate();
			ActivityInvite::update_vcal($link->getField('user_id'));
    	}
    }

    static function get_activity_status(RowUpdate $upd) {
        require_once('modules/Meetings/Meeting.php');
        return Meeting::get_activity_status($upd);
    }
}
?>
