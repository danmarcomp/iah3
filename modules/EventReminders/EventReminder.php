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


require_once('data/SugarBean.php');
require_once('include/utils.php');

class EventReminder extends SugarBean {

	// Stored fields
  	var $id;
	var $name;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $description;
	var $deleted;
	var $template_id;
	var $event_id;
	var $hours;
	var $minutes;
	var $total_sent;
	var $date_send;
	var $send_on_registration;

	
	// not stored
	var $created_by_name;
	var $modified_user_name;
	var $template_name;
	
	
	var $table_name = 'event_reminders';
	var $object_name = 'EventReminder';
	var $module_dir = 'EventReminders';
	var $new_schema = true;


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'created_by_name',
		'modified_user_name',
		'template_name',
	);

	
	function EventReminder()
   	{
		parent::SugarBean();
	}


	function get_summary_text()
	{
		// do not show in tracker
		return null;
	}

	/// This function fills in data for the detail view only.
	function fill_in_additional_detail_fields()
	{
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_user_name = get_assigned_user_name($this->modified_user_id);
	}

	function get_list_view_data()
	{
		$temp_array = $this->get_list_view_array();
		if ($this->send_on_registration) {
			$temp_array['DATE_SEND'] = 'Immediately';
		} else {
			$temp_array['DATE_SEND']  = $this->date_send;
		}
		return $temp_array;
	}

	function bean_implements($interface)
	{
		return false;
	}

    static function set_session(DetailManager &$mgr) {
        global $pageInstance;
        $session_id = '';

        if ($mgr->record->new_record && isset($_REQUEST['session_id'])) {
            $session_id = $_REQUEST['session_id'];
        }

        $pageInstance->add_js_literal("document.forms.DetailForm.session_id.value='" .$session_id. "';", null, LOAD_PRIORITY_FOOT);
    }

    static function get_event_name($id) {
        $session = ListQuery::quick_fetch_row('EventSession', $id, array('event'));
        if ($session != null && isset($session['event'])) {
            return $session['event'];
        }
    }
}

