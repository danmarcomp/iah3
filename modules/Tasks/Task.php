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

// Task is used to store customer information.
class Task extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $modified_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;


	var $description;
	var $name;
	var $status;
	var $date_due;
	var $date_start;
	var $priority;
	var $parent_type;
	var $parent_id;
	var $contact_id;

	var $parent_name;
	var $contact_name;
	var $contact_phone;
	var $contact_email;
	var $assigned_user_name;



	// longreach - start added
	var $effort_estim;
	var $effort_estim_unit;
	var $effort_actual;
	var $effort_actual_unit;
	var $is_private;
	// longreach - end added


	var $table_name = "tasks";

	var $object_name = "Task";
	var $module_dir = 'Tasks';

	// This is used to retrieve related fields from form posts.
	// longreach - added email_id
	var $additional_column_fields = Array('assigned_user_name', 'assigned_user_id', 'contact_name', 'contact_phone', 'contact_email', 'parent_name', 'email_id');
	// longreach - added
	var $relationship_fields = Array('email_id'=>'emails', );


	var $new_schema = true;



	// longreach - start added
	function get_effort_estimated() {
		global $app_list_strings, $app_strings;
		if(isset($this->effort_estim) && $this->effort_estim != 0) {
			if($this->effort_estim_unit == 'hours')
				return sprintf('%d:00 ', $this->effort_estim).$app_strings['LBL_HOURS_SHORT'];
			$unit = $app_list_strings['task_effort_unit_dom'][$this->effort_estim_unit];
			return sprintf('%d', $this->effort_estim) . ' ' . $unit;
		}
	}

	function get_effort_actual() {
		global $app_list_strings, $app_strings;
		if(isset($this->effort_actual) && $this->effort_actual != 0) {
			if($this->effort_actual_unit == 'hours')
				return sprintf('%d:00 ', $this->effort_actual).$app_strings['LBL_HOURS_SHORT'];
			$unit = $app_list_strings['task_effort_unit_dom'][$this->effort_actual_unit];
			return sprintf('%d', $this->effort_actual) . ' ' . $unit;
		}
	}
	function get_view_closed_where($params)
	{
		return empty($params['value']) ? "tasks.status <> 'Completed'" : '1';
	}
	function get_view_closed_where_advanced($params)
	{
		return  '1';
	}

	function getDefaultListWhereClause()
	{
		return "tasks.status <> 'Completed'";
	}

    static function init_record(RowUpdate &$upd, $input) {
    	$pid = array_get_default($input, 'parent_id');
    	if(array_get_default($input, 'parent_type') == 'Contacts') {
    		$upd->set('contact_id', $pid);
    	} else if( ($cid = array_get_default($input, 'contact_id')) ) {
    		$upd->set('contact_id', $cid);
    	}
    	
    	if( ($cid = $upd->getField('contact_id')) && (! $pid || $cid == $pid) ) {
			unset($_REQUEST['parent_type']);
			unset($_REQUEST['parent_id']);
        	if( ($ctc = ListQuery::quick_fetch('Contact', $cid, array('primary_account_id'))) ) {
				$upd->set('parent_type', 'Accounts');
				$upd->set('parent_id', $ctc->getField('primary_account_id'));
			}
    	}
    }

    static function init_from_email(RowUpdate &$upd, $input) {
        if (array_get_default($input, 'return_module') == 'Emails') {
            $email_id = $input['return_record'];
            $email = ListQuery::quick_fetch('Email', $email_id, array('name', 'description', 'contact'));

            if ($email) {
                $update = array('name' => $email->getField('name'), 'description' => $email->getField('description'),
                    'contact_id' => $email->getField('contact_id'));

                $upd->set($update);
            }
        }
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'TASK_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'TASK_PRIORITY' => array('field' => 'priority'),
            'TASK_DUEDATE' => array('field' => 'date_due'),
            'TASK_STATUS' => array('field' => 'status'),
            'TASK_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'TaskAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }

    static function set_related(RowUpdate $upd) {
        if (array_get_default($_REQUEST, 'return_module') == 'Emails')
            self::set_email_parent($upd);
    }
    
    static function clear_vcal(RowUpdate $upd) {
    	require_once('modules/vCals/vCal.php');
		vCal::clear_sugar_vcal($upd->getField('assigned_user_id'));
	}

    static function get_activity_status(RowUpdate $upd) {
        $status = null;

        if ($upd->getField('status') == 'Completed' && $upd->getField('status', null, true) != 'Completed') {
            $status = 'closed';
        } elseif ($upd->getField('status') == 'In Progress' && $upd->getField('status', null, true) == 'Completed') {
            $status = 'reopened';
        }

        return $status;
    }
}
?>
