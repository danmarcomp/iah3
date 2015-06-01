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
require_once('modules/Opportunities/Opportunity.php');
require_once('modules/Cases/Case.php');
require_once('modules/Tasks/Task.php');
require_once('modules/Notes/Note.php');
require_once('modules/Meetings/Meeting.php');
require_once('modules/Calls/Call.php');
require_once('modules/Emails/Email.php');

// Lead is used to store profile information for people who may become customers.
class Lead extends SugarBean {
	var $field_name_map;
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;



	var $description;
	var $salutation;
	var $first_name;
	var $last_name;
	var $title;
	var $department;
	var $reports_to_id;
	var $do_not_call;
	var $phone_home;
	var $phone_mobile;
	var $phone_work;
	var $phone_other;
	var $phone_fax;
	var $refered_by;
	var $email1;
	var $email2;
	var $email_opt_out;
	var $primary_address_street;
	var $primary_address_city;
	var $primary_address_state;
	var $primary_address_postalcode;
	var $primary_address_country;
	var $alt_address_street;
	var $alt_address_city;
	var $alt_address_state;
	var $alt_address_postalcode;
	var $alt_address_country;
	var $name;
	var $full_name;
	var $portal_name;
	var $portal_app;
	var $contact_id;
	var $contact_name;
	var $account_id;
	var $opportunity_id;
	var $opportunity_name;
	var $opportunity_amount;
	//used for vcard export only
	var $birthdate;
	var $invalid_email;
	var $status;
	var $status_description;

	var $lead_source;
	var $lead_source_description;
	// These are for related fields
	var $account_name;
	var $account_site;
	var $account_description;
	var $case_role;
	var $case_rel_id;
	var $case_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
	var $assigned_user_name;
	var $campaign_id;
    var $alt_address_street_2;
    var $alt_address_street_3;
    var $primary_address_street_2;
    var $primary_address_street_3;
     
	// longreach - start added
	var $partner;
	var $category;
	var $category2;
	var $category3;
	var $category4;
	var $category5;
	var $category6;
	var $category7;
	var $category8;
	var $category9;
	var $category10;
	var $temperature;
	var $date_converted;

	var $portal_active;
	var $partner_id;
	var $partner_name;
	var $partner_code;
	var $last_activity_date;
	var $website;
	var $campaign_name;
	// longreach - end   added





	var $table_name = "leads";

	var $object_name = "Lead";
	var $object_names = "Leads";
	var $module_dir = "Leads";


	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id',
		'partner_name', 'partner_code',
	);
	var $relationship_fields = Array('email_id'=>'emails');


	function converted_lead($leadid, $contactid, $accountid, $opportunityid){
		// longreach - added
		$date = gmdate('Y-m-d H:i:s');
		// longreach - modified - added date_converted
    	$query = "UPDATE leads set status='Converted', converted='1', contact_id=$contactid, account_id=$accountid, opportunity_id=$opportunityid, date_converted='$date' where  id=$leadid and deleted=0";
		$this->db->query($query,true,"Error converting lead: ");
    }


	function set_notification_body($xtpl, $lead)
	{
		global $app_list_strings;		
		
		$xtpl->assign("LEAD_NAME", trim($lead->first_name . " " . $lead->last_name));
		$xtpl->assign("LEAD_SOURCE", (isset($lead->lead_source) ? $app_list_strings['lead_source_dom'][$lead->lead_source] : ""));
		$xtpl->assign("LEAD_STATUS", (isset($lead->status)? $app_list_strings['lead_status_dom'][$lead->status]:""));
		$xtpl->assign("LEAD_DESCRIPTION", $lead->description);

		return $xtpl;
	}

	/**
	 * Generate the name field from the first_name and last_name fields.
	 */
	function _create_proper_name_field() {
        global $locale;
        $full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
        $this->name = $full_name;
        $this->full_name = $full_name; 

	}
	
    
	function save($check_notify = false) {
		// longreach - start added
		if(isset($this->portal_name) && $this->portal_name !== '') {
			$query = sprintf(
				"UPDATE leads SET portal_name = '' WHERE portal_name = '%s' AND id != '%s'",
				PearDatabase::quote($this->portal_name),
				PearDatabase::quote($this->id)
			);
			$this->db->query($query);
			$query = sprintf(
				"UPDATE contacts SET portal_name = '' WHERE portal_name = '%s'",
				PearDatabase::quote($this->portal_name)
			);
			$this->db->query($query);
		}
		// longreach - end added
        if(empty($this->status)) $this->status = 'New';
        return parent::save($check_notify);
	}

	// longreach - start added
	function get_status_where_basic($param)
	{
		return 1;
	}
	function get_status_where_advanced($param)
	{
		$status = $param['value'];
		if($status != "" && $status != 'Other' && $status != "empty") {
			return "leads.status='" . PearDatabase::quote($status) . "'";
		} elseif ($status == 'Other') {
			return "(leads.status <> 'Converted' AND leads.status <> 'Dead')";
		} else {
			return 1;
		}

	}
	function get_view_closed_where_basic($param)
	{
		if (empty($param['value'])) {
			return "(leads.status <> 'Converted' AND leads.status <> 'Dead')";
		} else {
			return '1';
		}
	}
	
	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_search_status_options()
	{
		global $app_list_strings, $app_strings, $mod_strings;
		$status_dom = $app_list_strings['lead_status_dom'];
		unset($status_dom['']);
		$status_dom = array('empty' => $app_strings['LBL_NONE'], 'Other' => $mod_strings['LBL_ALL_ACTIVE']) + $status_dom;
		return $status_dom;
	}
	
	function getDefaultListWhereClause()
	{
		return "(leads.status <> 'Converted' AND leads.status <> 'Dead')";
	}

	
	function remove_redundant_http()
	{
		if(preg_match("~^http://(.*)$~i", $this->website, $m))
		{
			$this->website = $m[1];
		}
	}

	function events_attendance()
	{
		$query = "SELECT event_sessions.*, events_customers.registered, events_customers.attended FROM event_sessions LEFT JOIN events_customers ON event_sessions.id = events_customers.session_id AND events_customers.deleted = 0 WHERE event_sessions.deleted = 0 AND events_customers.customer_id='{$this->id}'";
		return $query;
	}


	static function find_duplicates(RowUpdate &$upd, $redirect = true, $type = '') {
		$op = 'AND';
		switch ($type) {
			case 'name_only':
				$check_name = true;
				$check_email = false;
				break;
			case 'email_only':
				$check_name = false;
				$check_email = true;
				break;
			case 'name_and_email':
				$check_name = true;
				$check_email = true;
				break;
			default:
				$check_name = true;
				$check_email = true;
				$op = 'OR';
				break;
		}
		$clauses = array('multiple' => array(), 'operator' => $op);
		if ($check_name) {
			if($upd->getField('first_name') && $upd->getField('last_name')) {
				$clauses['multiple'][] = array(
					'operator' => 'AND',
					'multiple' => array(
						array(
							'field' => 'first_name',
							'value' => $upd->getField('first_name'),
						),
						array(
							'field' => 'last_name',
							'value' => $upd->getField('last_name'),
						),
					),
				);
			} else {
				$clauses['multiple'][] = array(
					'field' => 'last_name',
					'value' => $upd->getField('last_name'),
				);
			}
		}

		if ($check_email) {
			if($upd->getField('email1')) {
				$clauses['multiple'][] = array(
					'operator' => 'OR',
					'multiple' => array(
						array(
							'field' => 'email1',
							'value' => $upd->getField('email1'),
						),
						array(
							'field' => 'email2',
							'value' => $upd->getField('email1'),
						),
					),
				);
			}

			if($upd->getField('email2')) {
				$clauses['multiple'][] = array(
					'operator' => 'OR',
					'multiple' => array(
						array(
							'field' => 'email1',
							'value' => $upd->getField('email2'),
						),
						array(
							'field' => 'email2',
							'value' => $upd->getField('email2'),
						),
					),
				);
			}
		}

		$clauses = array($clauses);

        require_once('include/layout/DuplicateManager.php');
        $manager = new DuplicateManager($upd, $_REQUEST);
        return $manager->check($clauses, $redirect);
    }


    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if (! empty($input['prospect_id'])) {
            $prospect = ListQuery::quick_fetch_row('Prospect', $input['prospect_id']);

            if ($prospect != null) {
                foreach($prospect as $key => $value) {
                    if ($key == 'id' or $key=='deleted' )
                        continue;
                    $update[$key] = $prospect[$key];
                }
            }

        } else {
            foreach ($input as $name => $value){
                $update[$name] = $value;
            }
        }

        $upd->set($update);
    }

    static function init_from_email(RowUpdate &$upd, $input) {
        if (isset($input['return_module']) && $input['return_module'] == 'Emails') {
            $email_id = $input['return_record'];
            $email = ListQuery::quick_fetch('Email', $email_id, array('description', 'from_addr', 'from_name'));

            if ($email) {
                require_bean('Email');

                $update = array('description' => $email->getField('description'), 'email1' => $email->getField('from_addr'),
                    'first_name' => Email::getName($email->getField('from_name'), 'first'),
                    'last_name' => Email::getName($email->getField('from_name'), 'last'),
                    'lead_source' => 'Email');

                $upd->set($update);
            }
        }
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'LEAD_NAME' => array('field' => 'name', 'in_subject' => true),
            'LEAD_SOURCE' => array('field' => 'lead_source'),
            'LEAD_STATUS' => array('field' => 'status'),
            'LEAD_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'LeadAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }

    static function set_related(RowUpdate $upd) {
        if (array_get_default($_REQUEST, 'return_module') == 'Emails')
            self::set_email_parent($upd);
    }

    static function update_prospect(RowUpdate $upd) {
        $prospect_id = array_get_default($_REQUEST, 'return_record');

        if (array_get_default($_REQUEST, 'return_module') == 'Prospects' && $prospect_id) {
            $prospect_result = ListQuery::quick_fetch('Prospect', $prospect_id);
            if ($prospect_result) {
                $prospect_update = RowUpdate::for_result($prospect_result);
                $prospect_update->set('lead_id', $upd->getPrimaryKeyValue());
                $prospect_update->save();
            }
        }

    }

	static function add_massupdate_fields(&$fields) {
		$out = array();
		foreach ($fields as $f => $def) {
			$out[$f] = $def;
			if ($f == 'assigned_user') {
				$out['reassign_objects'] = array(
					'vname' =>  'LBL_REASSIGN_OBJECTS',
					'type' => 'multienum',
					'options' => 'reassign_leads_dom',
					'multi_select_group' => 'reassign_objects_group',
					'multi_select_count' => 10,
					'source' => array('type' => 'non-db'),
                    'massupdate' => true
				);
			}
		}
		$fields = $out;
	}
	
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids) {
		if ($perform == 'update') {
			require_once 'include/Reassign/ObjectsReassign.php';
            $objects = array();
            $userId = null;

            if (isset($_REQUEST['assigned_user_id']))
                $userId = $_REQUEST['assigned_user_id'];
            if (isset($_REQUEST['reassign_objects']))
                $objects = $_REQUEST['reassign_objects'];

            if (empty($userId) || empty($objects)) return;

			$objects = explode('^,^', $objects);
			if ($uids == 'all') $uids = null;

			ObjectsReassign::reassign('Lead', $uids, $userId, $objects);
		}
	}
	
	
	static function get_activity_status(RowUpdate $upd) {
        $status = null;

		if ($upd->getField('contact_id') && !$upd->getField('contact_id', null, true)) {
			return array(
				'status' => 'converted',
				'converted_to_type' => 'Contacts',
				'converted_to_id' => $upd->getField('contact_id'),
			);
        }
        return $status;
    }
}
?>
