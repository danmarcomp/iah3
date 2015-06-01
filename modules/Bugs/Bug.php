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


// Bug is used to store customer information.
class Bug extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;



	var $bug_number;
	var $description;
	var $name;
	var $status;
	var $priority;

	// These are related
	var $resolution;
	var $found_in_release;
	var $release_name;
	var $fixed_in_release_name;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $account_id;
	var $contact_id;
	var $case_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
	var $assigned_user_name;
	var $type;



	// longreach - added
	var $planned_for_release;
	var $planned_for_release_name;
	var $is_private;
	var $product_id;
	var $product_name;



	//BEGIN Additional fields being added to Bug Tracker
	
	var $fixed_in_release;
	var $work_log;
	var $source;
	var $product_category;
	//END Additional fields being added to Bug Tracker
	
	var $module_dir = 'Bugs';
	var $table_name = "bugs";
	var $rel_account_table = "accounts_bugs";
	var $rel_contact_table = "contacts_bugs";
	var $rel_case_table = "cases_bugs";

	var $object_name = "Bug";


	var $new_schema = true;

	
	function save($check_notify = FALSE){
		// longreach - start added
		if (empty($this->id) || !empty($this->new_with_id)) {
			$this->bug_number = $this->getNextSequenceValue();
		}
		// longreach - end adedd
		return parent::save($check_notify);
	}

	// longreach - start added
	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('bug_number_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('bug_number_sequence');
	}

	function get_view_closed_where_basic($params)
	{
		return empty($params['value']) ? "(bugs.status != 'Closed' AND bugs.status != 'Rejected')" : '1';
	}
	function get_view_closed_where_advanced($params)
	{
		return '1';
	}

	function getDefaultListWhereClause()
	{
		return "(bugs.status != 'Closed' AND bugs.status != 'Rejected')";
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $fields = array('priority', 'name', 'description', 'status', 'type',
            'found_in_release', 'product_id', 'account_id', 'contact_id');
        $field = null;

        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            if (isset($input[$field])) {
                $update[$field] = urldecode($input[$field]);
            }
        }

        if (! empty($input['acase_id'])) {
            $case_fields = array('name', 'description', 'account_id', 'cust_contact_id');
            $case = ListQuery::quick_fetch_row('aCase', $input['acase_id'], $case_fields);

            if ($case) {
                for ($i = 0; $i < sizeof($case_fields); $i++) {
                    $field = $case_fields[$i];
                    if ($field == 'cust_contact_id') {
                        $case['contact_id'] = $case['cust_contact_id'];
                        $field = 'contact_id';
                    }
                    if (! empty($case[$field]) && empty($update[$field]))
                        $update[$field] = $case[$field];
                }
            }
        }

        $upd->set($update);
    }

    static function init_from_email(RowUpdate &$upd, $input) {
        if (array_get_default($input, 'return_module') == 'Emails') {
            $email_id = array_get_default($input, 'return_record');
            $email = ListQuery::quick_fetch('Email', $email_id, array('name', 'description', 'contact_id', 'parent_type', 'parent_id'));

            if ($email) {
                $update = array('name' => $email->getField('name'),
                    'description' => $email->getField('description'),
                    'source' => 'Email', 'contact_id' => $email->getField('contact_id'));
                if($email->getField('parent_type') == 'Accounts')
                	$update['account_id'] = $email->getField('parent_id');

                $upd->set($update);
            }
        }
    }

    static function before_save(RowUpdate $upd) {
    }
    
	static function after_save(RowUpdate $upd) {
		$acc_id = $upd->getField('account_id');
		if($acc_id && $upd->getFieldUpdated('account_id')) {
			$upd->addUpdateLink('accounts', $acc_id);
		}

		$ctc_id = $upd->getField('contact_id');
		if($ctc_id && $upd->getFieldUpdated('contact_id')) {
			$upd->addUpdateLink('contacts', $ctc_id);
		}

        if (array_get_default($_REQUEST, 'return_module') == 'Emails')
            self::set_email_parent($upd);
	}

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'BUG_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'BUG_TYPE' => array('field' => 'type'),
            'BUG_PRIORITY' => array('field' => 'priority'),
            'BUG_STATUS' => array('field' => 'status'),
            'BUG_RESOLUTION' => array('field' => 'resolution'),
            'BUG_RELEASE' => array('field' => 'found_in'),
            'BUG_DESCRIPTION' => array('field' => 'description'),
            'BUG_WORK_LOG' => array('field' => 'work_log'),
            'BUG_BUG_NUMBER' => array('field' => 'bug_number')
        );

        $manager = new NotificationManager($upd, 'BugAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }

    static function get_activity_status(RowUpdate $upd) {
        $status = null;
        $new_bug_status = $upd->getField('status');
        $orig_bug_status = $upd->getField('status', null, true);

        $closed_bug_statuses = array('Closed', 'Rejected');

        if (in_array($new_bug_status, $closed_bug_statuses) && ! in_array($orig_bug_status, $closed_bug_statuses)) {
            $status = 'closed';
        } elseif ($new_bug_status == 'Assigned' && in_array($orig_bug_status, $closed_bug_statuses)) {
            $status = 'reopened';
        }

        return $status;
    }
}
?>
