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


// Contact is used to store customer information.
class Contact extends SugarBean {
    var $field_name_map;
	// Stored fields
	var $id;
	var $name = '';
	var $lead_source;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;

	// longreach - start added
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
	var $primary_contact_for;
	var $business_role;
	var $partner_id;
	// longreach - end added

	var $description;
	var $salutation;
	var $first_name;
	var $last_name;
	var $title;
	var $department;
	var $birthdate;
	var $reports_to_id;
	var $do_not_call;
	var $phone_home;
	var $phone_mobile;
	var $phone_work;
	var $phone_other;
	var $phone_fax;
	var $email1;
	var $email_and_name1;
	var $email_and_name2;
	var $email2;
	var $assistant;
	var $assistant_phone;
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
	var $portal_name;
	var $portal_app;
	var $portal_active;
	var $contacts_users_id;
	// These are for related fields
	var $bug_id;
	var $account_name;
	var $account_id;
	var $report_to_name;
	var $opportunity_role;
	var $opportunity_rel_id;
	var $opportunity_id;
	var $case_role;
	var $case_rel_id;
	var $case_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
	var $assigned_user_name;
	var $accept_status;
    var $accept_status_id;
    var $accept_status_name;
    var $alt_address_street_2;
    var $alt_address_street_3;
    var $opportunity_role_id;
    var $portal_password;
    var $primary_address_street_2;
    var $primary_address_street_3;
	var $email_accounts;
	var $campaign_id;
	var $campaign_name;
	var $last_activity_date;
	var $invalid_email;
	var $table_name = "contacts";
	var $rel_account_table = "accounts_contacts";
	//This is needed for upgrade.  This table definition moved to Opportunity module.
	var $rel_opportunity_table = "opportunities_contacts";

	var $object_name = "Contact";
	var $module_dir = 'Contacts';

	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('bug_id', 'assigned_user_name', 'account_name', 'account_id', 'opportunity_id', 'case_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id'
		// longreach - added
		, 'full_name', 'name', 'created_by_name', 'modified_by_name', 'assigned_user_name', 'email_accounts',
	);

	var $relationship_fields = Array('account_id'=> 'accounts','bug_id' => 'bugs', 'call_id'=>'calls','case_id'=>'cases','email_id'=>'emails',
								'meeting_id'=>'meetings','note_id'=>'notes','task_id'=>'tasks', 'opportunity_id'=>'opportunities', 'contacts_users_id' => 'user_sync'
								);

	// need to override to have a name field created for this class
	function retrieve($id = -1, $encode=true)
	{
		$ret_val = parent::retrieve($id, $encode);

		$this->_create_proper_name_field();

		return $ret_val;
	}

	/**
	 * Generate the name field from the first_name and last_name fields.
	 */
	function _create_proper_name_field() {
		global $locale;

		$full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
/*		if(!empty($this->first_name))
		{
			$full_name = $this->first_name;
		}

		if(!empty($full_name) && !empty($this->last_name))
		{
			$full_name .= ' ' . $this->last_name;
		}
		elseif(empty($full_name) && !empty($this->last_name))
		{
			$full_name = $this->last_name;
		}
*/
		$this->name = $full_name;
		$this->full_name = $full_name; //used by campaigns
	}

	// longreach - start added
	function getB2CAccountName()
	{
		global $locale;
		$format = AppConfig::setting('company.b2c_name_format');
		return $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation, $format);
	}


		/**
		loads the contacts_users relationship to populate a checkbox
		where a user can select if they would like to sync a particular
		contact to Outlook
	*/
	function load_contacts_users_relationship(){
		global $current_user;

		$this->load_relationship("user_sync");
		$query_array=$this->user_sync->getQuery(true);

		$query_array['where'] .= " AND users.id = '$current_user->id'";

		$query='';
		foreach ($query_array as $qstring) {
			$query.=' '.$qstring;
		}

		$list = $this->build_related_list($query, new User());
		if(!empty($list)){
			//this should only return one possible value so set it
			$this->contacts_users_id = $list[0]->id;
			$this->cleanup_list($list);
		}
	}

	function set_notification_body($xtpl, $contact)
	{
		$xtpl->assign("CONTACT_NAME", trim($contact->first_name . " " . $contact->last_name));
		$xtpl->assign("CONTACT_DESCRIPTION", $contact->description);

		return $xtpl;
	}

	function get_contact_id_by_email($email)
	{
		$email = trim($email);
		if(empty($email)){
			//email is empty, no need to query, return null
			return null;
		}
		$email = PearDatabase::quote($email);

		$where_clause = "(email1='$email' OR email2='$email') AND deleted=0";

                $query = "SELECT * FROM $this->table_name WHERE $where_clause";
                $GLOBALS['log']->debug("Retrieve $this->object_name: ".$query);
		        //requireSingleResult has beeen deprecated.
                //$result = $this->db->requireSingleResult($query, true, "Retrieving record $where_clause:");
				$result = $this->db->limitQuery($query,0,1,true, "Retrieving record $where_clause:");

                if( empty($result))
                {
                        return null;
                }

                $row = $this->db->fetchByAssoc($result, -1, true);
		return $row['id'];

	}

	function save_relationship_changes($is_update) {
		//if account_id was replaced unlink the previous account_id.
		//this rel_fields_before_value is populated by sugarbean during the retrieve call.
		if (!empty($this->account_id) and !empty($this->rel_fields_before_value['account_id']) and
				(trim($this->account_id) != trim($this->rel_fields_before_value['account_id']))) {
				//unlink the old record.
				$this->load_relationship('accounts');
				$this->accounts->delete($this->id,$this->rel_fields_before_value['account_id']);
		}
		parent::save_relationship_changes($is_update);
	}


	// longreach - start added

	function save($notify = false)
	{
		$query = sprintf(
			"UPDATE contacts SET portal_name = '' WHERE portal_name = '%s' AND id != '%s'",
			PearDatabase::quote($this->portal_name),
			PearDatabase::quote($this->id)
		);
		$this->db->query($query);
		$query = sprintf(
			"UPDATE leads SET portal_name = '' WHERE portal_name = '%s'",
			PearDatabase::quote($this->portal_name)
		);
		$this->db->query($query);
		if (AppConfig::is_B2C() && !empty($this->primary_contact_for)) {
			require_once 'modules/Accounts/Account.php';
			$acc = new Account;
			$acc->retrieve($this->primary_contact_for);
			$acc->name = $this->getB2CAccountName();
			//$acc->update_date_modified = false;
			$acc->save(false);
			$acc->cleanup();
		}
		if (!empty($this->id) && empty($this->new_with_id)) {
			$query = "UPDATE google_sync SET google_sync_error=0 WHERE related_type='Contacts' AND related_id='{$this->id}'";
			$this->db->query($query);
		}
		return parent::save($notify);
	}

	function events_attendance()
	{
		$query = "SELECT event_sessions.*, events_customers.registered, events_customers.attended FROM event_sessions LEFT JOIN events_customers ON event_sessions.id = events_customers.session_id AND events_customers.deleted = 0 WHERE event_sessions.deleted = 0 AND events_customers.customer_id='{$this->id}'";
		return $query;
	}

	function has_sync_error()
	{
		global $current_user;
		$res = $this->db->query("SELECT * FROM google_sync WHERE related_type='Contacts' AND related_id='{$this->id}' AND google_sync_error AND user_id='{$current_user->id}'");
		if ($this->db->fetchByAssoc($res)) {
			return true;
		}
		return false;
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
        
        $lead_id = array_get_default($input, 'lead_id');
        $case_id = array_get_default($input, 'acase_id');
        if($lead_id && ($lead = ListQuery::quick_fetch('Lead', $lead_id))) {
        	$lead_up = RowUpdate::for_result($lead);
			$fields = array('salutation', 'first_name', 'last_name',
				'phone_work', 'phone_home', 'phone_mobile', 'phone_other', 'phone_fax',
				'email1', 'email2', 'title', 'department', 'lead_source',
				'do_not_call', 'email_opt_out', 'invalid_email', 'partner_id',
				'description');
			$skip = array('id', 'id_c', 'portal_name', 'portal_app', 'portal_active',
				'category', 'date_entered', 'date_modified', 'deleted',
				'modified_user_id', 'assigned_user_id', 'created_by', 'reports_to_id',
				'last_activity_date', 'campaign_id',
			);
			$upd->copyAddress($lead_up, 'primary_address', 'primary_address');
			$upd->copyAddress($lead_up, 'alt_address', 'alt_address');
			foreach($fields as $f) {
				$update[$f] = $lead->getField($f);
			}
            //set categories
            $update['categories'] = $lead->getField('category');
            for ($i = 2; $i <= 10; $i++) {
                $cat = $lead->getField('category' . $i);
                if (! empty($update['categories']) && ! empty($cat))
                    $update['categories'] .= '^,^';
                $update['categories'] .= $cat;
            	$skip[] = 'category' . $i;
            }
			array_extend($skip, $fields);
			$fs_extend = array_intersect($lead->getFieldNames(), $upd->getFieldNames());
			// copy any additional matching fields
			foreach($fs_extend as $k) {
				if(in_array($k, $skip) || isset($update[$k]) || strpos($k, '_address_') !== false) continue;
				$update[$k] = $lead->getField($k);
			}

			// FIXME - query for existing account by name, set primary_account_id
			if( ($aname = $lead->getField('account_name')) ) {
				$acc = ListQuery::quick_fetch_key('Account', 'name', $aname);
				if($acc) {
					$update['primary_account_id'] = $acc->getField('id');
					$update['phone_work'] = $acc->getField('phone_office');
				}
			}
        } elseif ($case_id) {
            $case = ListQuery::quick_fetch_row('aCase', $case_id, array('account_id'));
            if (isset($case['account_id']))
                $update['primary_account_id'] = $case['account_id'];
        }

        $fields = array('account_id', 'first_name', 'last_name', 'phone_work',
            'email1', 'salutation', 'partner_id');
		foreach($fields as $field) {
            if (isset($input[$field])) {
                $update[$field] = $input[$field];
                if($field == 'account_id')
                	$update['primary_account_id'] = $input[$field];
            }
        }
        
        $upd->set($update);

        if(! $upd->getField('last_name') && isset($input['name'])) {
        	$new_name = preg_split('~\s+~', $input['name']);
        	if(count($new_name)) {
        		$upd->set('last_name', array_pop($new_name));
        		$upd->set('first_name', join(' ', $new_name));
        	}
        }

		if(! $lead_id) {
			$acc_id = $upd->getField('primary_account_id');
			if($acc_id && ($acc = ListQuery::quick_fetch('Account', $acc_id))) {
				$acc_up = RowUpdate::for_result($acc);
				$upd->copyAddress($acc_up, 'billing_address', 'primary_address');
				if(! $upd->getField('phone_work'))
					$upd->set('phone_work', $acc->getField('phone_office'));
			}
		}
    }

    static function init_from_email(RowUpdate &$upd, $input) {
        require_bean('Lead');
        Lead::init_from_email($upd, $input);
    }
	
	static function init_contact_for(RowUpdate $upd) {
        $no_update = $upd->getRelatedData('no_b2c_update');

        if (AppConfig::is_B2C() && $upd->new_record && ! $no_update) {
			$id = create_guid();
			$upd->set('primary_contact_for', $id);
			$upd->set('primary_account_id', $id);
		}
	}

	static function account_B2C_update(RowUpdate &$upd) {
		$no_update = $upd->getRelatedData('no_b2c_update');
		if (AppConfig::is_B2C() && !$no_update) {
			$primary_contact_for = $upd->getField('primary_contact_for');
            if (empty($primary_contact_for))
                $primary_contact_for = $upd->getField('primary_account_id');

			/** @var $locale Localization */
			global $locale;
			$name = $locale->getLocaleFormattedName(
				$upd->getField('first_name'),
				$upd->getField('last_name'),
				$upd->getField('salutation'),
				AppConfig::setting('company.b2c_name_format')
			);

			/** @var $result RowResult */
			$acc_result = ListQuery::quick_fetch('Account', $primary_contact_for);
			if ($acc_result) {
				$acc_upd = RowUpdate::for_result($acc_result);
			} else {
				$acc_upd = RowUpdate::blank_for_model('Account');
				$acc_upd->set('id', $primary_contact_for);
			}
			
			$acc_set = array(
				'primary_contact_id' => $upd->getPrimaryKeyValue(),
				'assigned_user_id' => $upd->getField('assigned_user_id'),
				'name' => $name,
			);

			require_once 'modules/Accounts/Account.php';
			$bean = new Account();

			foreach ($bean->getB2CContactFieldsMap() as $cfield => $afield) {
				$field = $upd->getField($cfield);
				if (!empty($field)) {
					$acc_set[$afield] = $field;
				}
			}
			$acc_upd->set($acc_set);
			$acc_upd->save();
		}
		
		$primary_acc = $upd->getField('primary_account_id');
		if($primary_acc)
			$upd->addUpdateLink('accounts', $primary_acc);

		$primary_acc = $upd->getField('primary_contact_for');
		if($primary_acc)
			$upd->addUpdateLink('accounts', $primary_acc);
	}

    static function set_related(RowUpdate $upd) {
        if (array_get_default($_REQUEST, 'return_module') == 'Emails')
            self::set_email_parent($upd);
    }

    static function getNameById($id) {
        $sql = "SELECT first_name, last_name FROM contacts WHERE id='{$id}'";

        $db = & PearDatabase::getInstance();
        $resultSet = $db->query($sql, true);
        if($resultSet === false) {
            return false;
        }
        $row = $db->fetchByAssoc($resultSet);
        if($row === false) {
            return false;
        }

        global $locale;
        return $locale->getLocaleFormattedName($row['first_name'], $row['last_name']);

    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'CONTACT_NAME' => array('field' => 'name', 'in_subject' => true),
            'CONTACT_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'ContactAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }
    
	static function add_linked_account(RowUpdate $upd, $link_name) {
    	if($link_name == 'accounts' && $upd->getFieldRetrieved('primary_account_id')) {
			if(! $upd->getField('primary_account_id')) {
				$upd->set('primary_account_id', $upd->getLinkTargetId());
				$upd->save();
			}
		}
    }

}
?>
