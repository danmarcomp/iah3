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
 * $Id: Case.php,v 1.114 2006/03/23 02:37:38 chris Exp $
 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('data/SugarBean.php');

// Case is used to store customer information.
class aCase extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;



	var $case_number;
	var $resolution;
	var $description;
	var $name;
	var $status;
	var $priority;



	// longreach - start added - new fields for tracking time usage
	var $effort_actual;
	var $effort_actual_unit;
	var $travel_time;
	var $travel_time_unit;
	var $activities_minutes;
	var $activities_hours;
	// longreach - added - other new fields
	var $arrival_time;
	var $cust_req_no;
	var $cust_contact_id;
	var $cust_phone_no;
	var $date_closed;
	var $date_billed;
	var $vendor_rma_no;
	var $vendor_svcreq_no;
	var $category;
	var $type;
	// longreach - added - link to service and product modules
	var $contract_id;
	var $asset_id;
	var $asset_serial_no;
	// looked up, not stored here
	var $age;
	var $cust_contact_name;
	var $contract_name;
	var $asset_name;

	var $account_name1_owner;
	var $contact_name_owner;


	var $notify_is_update = false;

	// longreach - end added



	var $created_by;
	var $created_by_name;
	var $modified_by_name;

	// These are related
	var $bug_id;
	var $account_name;
	var $account_id;
	var $contact_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
	var $assigned_user_name;
	var $account_name1;
	var $product_id;




	var $table_name = "cases";
	var $rel_account_table = "accounts_cases";
	var $rel_contact_table = "contacts_cases";
	var $module_dir = 'Cases';
	var $object_name = "aCase";

	var $new_schema = true;


	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_contacts()
	{
		$this->load_relationship('contacts');
		$query_array=$this->contacts->getQuery(true);

		//update the select clause in the retruned query.
		$query_array['select']="SELECT contacts.id, contacts.first_name, contacts.last_name, contacts.title, contacts.email1, contacts.phone_work, contacts_cases.contact_role as case_role ";

		$query='';
		foreach ($query_array as $qstring) {
			$query.=' '.$qstring;
		}
		$temp = Array('id', 'first_name', 'last_name', 'title', 'email1', 'phone_work', 'case_role');
		// longreach - modified - add cust_contact_id
		$ret = $this->build_related_list2($query, new Contact(), $temp);
		if (!empty($this->cust_contact_id)) {
			$contact = new Contact;
			$contact->retrieve($this->cust_contact_id);
			$ret[] = $contact;
		}
		return $ret;
	}

	// longreach - added
	function get_age() {
		global $timedate, $app_strings;
		if(empty($this->date_entered)) return '';
		$hourlen = 3600;
		$daylen = $hourlen*24;
		$now = strtotime($timedate->get_gmt_db_datetime());
		$entered = $this->format_relative_dates_times ? $this->raw_date_entered : $timedate->to_db($this->date_entered);
		$age = $now - strtotime($entered);
		$days = floor($age / $daylen);
		$age -= $days * $daylen;
		$hours = floor($age / $hourlen);
		$age -= $hours * $hourlen;
		$lbl = $app_strings['LBL_TIME_PERIOD_DAYS_HOURS'];
		$lbl = str_replace(array('%1', '%2'), array((int)$days, (int)$hours), $lbl);
		return $lbl;
	}
	

	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('case_number_sequence');
	}

	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('case_number_sequence');
	}
	// longreach - end added


	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "(substring(cases.status, 1, 8) = 'Active -' OR cases.status = 'Pending')" : '1';
	}

	function getDefaultListWhereClause()
	{
		return "(substring(cases.status, 1, 8) = 'Active -' OR cases.status = 'Pending')";
	}

	static function escalate(RowResult $case, $skills)
	{
		global $db;
		$id = $case->getPrimaryKeyValue();
		$user_id = $case->getField('assigned_user_id');
		$queue_user_id = AppConfig::setting('company.case_queue_user');
		$case_skills = $user_skills = array();
		$query = "SELECT skill_id, rating FROM cases_skills WHERE case_id='{$id}' AND deleted = 0";
		$res = $db->query($query, true);
		while ($row = $db->fetchByAssoc($res)) {
			$case_skills[$row['skill_id']] = (int)$row['rating'];
		}

		$query = "SELECT skill_id, rating FROM users_skills WHERE user_id='{$user_id}' AND deleted = 0";
		$res = $db->query($query, true);
		while ($row = $db->fetchByAssoc($res)) {
			$user_skills[$row['skill_id']] = (int)$row['rating'];
		}
		foreach ($case_skills as $sid => $rating) {
			if (!isset($user_skills[$sid])) $user_skills[$sid] = $rating;
		}

		foreach ($skills as $skill_id) {
			if (isset($case_skills[$skill_id])) {
				$db->query("UPDATE cases_skills SET rating = " . (max($case_skills[$skill_id], $user_skills[$skill_id]) + 1) . " WHERE case_id='{$id}' AND skill_id='$skill_id' AND deleted = 0", true);
			} else {
				$skill = isset($user_skills[$skill_id]) ? $user_skills[$skill_id] + 1 : 1;
				$db->query("INSERT INTO cases_skills SET rating = $skill, case_id='{$id}', skill_id='$skill_id', date_modified=NOW()", true);
			}
		}
		
		if($queue_user_id) {
			$upd = RowUpdate::for_result($case);
			$upd->set(array(
				'assigned_user_id' => $queue_user_id,
				'status' => 'Active - New',
			));
			return $upd->save();
		}
	}

	function getCase($userId)
	{
		$queue_user_id = AppConfig::setting('company.case_queue_user');

		$query = "SELECT
			c.id
			FROM cases c
			WHERE c.deleted=0 AND c.assigned_user_id = '$queue_user_id'
				AND c.status = 'Active - New'
				AND NOT (SELECT COUNT(*) FROM cases_skills cs
					LEFT JOIN users_skills us
						ON us.deleted=0 AND us.user_id='$userId'
						AND cs.skill_id=us.skill_id
					WHERE cs.case_id=c.id AND NOT cs.deleted
					AND (us.skill_id IS NULL OR IFNULL(cs.rating, 0) > IFNULL(us.rating, 0))
				)
			ORDER BY c.priority, c.case_number
			LIMIT 1
			";

		$res = $this->db->query($query, true);
		if ($row = $this->db->fetchByAssoc($res)) {
			$this->retrieve($row['id']);
			$this->assigned_user_id = $userId;
			$this->status = "Active - Assigned";
			return $this;
		}
		return false;
	}

	function calculateCosts($currency_id=null) {
		require_bean('Currency');
		$currency = new Currency();
		$currency->retrieve($currency_id);
		$query = "SELECT status, quantity, billing_total, billing_total_usd, paid_total, paid_total_usd, ".
			"billing_currency_id, billing_exchange_rate, paid_currency_id, paid_exchange_rate FROM `booked_hours` booked ".
			"WHERE related_type='Cases' AND related_id='".$this->db->quote($this->id)."' AND NOT deleted";
		$r = $this->db->query($query, true);
		$tot = array('row_count'=>0, 'quantity'=>0, 'cost_usd'=>0, 'bill_usd'=>0, 'cost'=>0, 'bill'=>0);
		$ret = array('currency_id' => $currency_id, 'hours' => array('approved' => $tot, 'unapproved' => $tot));
		while($row = $this->db->fetchByAssoc($r)) {
			$stat = ($row['status'] == 'approved') ? 'approved' : 'unapproved';
			$ret['hours'][$stat]['row_count'] ++;
			$ret['hours'][$stat]['quantity'] += $row['quantity'];
			$ret['hours'][$stat]['cost_usd'] += $row['paid_total_usd'];
			$ret['hours'][$stat]['bill_usd'] += $row['billing_total_usd'];
			if($currency->id == $row['billing_currency_id'])
				$ret['hours'][$stat]['bill'] += $row['billing_total'];
			else
				$ret['hours'][$stat]['bill'] += $currency->convertFromDollar($row['billing_total_usd'], $currency->decimal_places);
			if($currency->id == $row['paid_currency_id'])
				$ret['hours'][$stat]['cost'] += $row['paid_total'];
			else
				$ret['hours'][$stat]['cost'] += $currency->convertFromDollar($row['paid_total_usd'], $currency->decimal_places);
		}
		$currency->cleanup();
		return $ret;
	}

	function getBookedHours() {
		$query = "SELECT bh.`name`, bh.`status`, bh.`quantity`, bh.`related_type`, bh.`date_start`,
			u.`user_name` AS assigned_user_name
			FROM `booked_hours` AS bh
			LEFT OUTER JOIN `users` AS u ON (u.`id` = bh.`assigned_user_id`)
			WHERE `related_id` = '" .$this->id. "'";

		$result = $this->db->query($query, true, "  Error executing query: ");
		$bokedHours = array();

		while ($row =  $this->db->fetchByAssoc($result)) {
			array_push($bokedHours, $row);
		}

		return $bokedHours;
	}

	function getServiceParts() {
		$query = "SELECT pr.`name`, pr.`is_available`, pr.`cost`, pr.`list_price`, pr.`purchase_price`,
			pr.`currency_id`, pc.`name` AS category_name, pt.`name` AS type_name
			FROM `products` AS pr
			LEFT OUTER JOIN `product_categories` AS pc ON (pc.`id` = pr.`product_category_id`)
			LEFT OUTER JOIN `product_types` AS pt ON (pt.`id` = pr.`product_type_id`
			AND pt.`category_id` = pr.`product_category_id`)
			WHERE pr.`id` IN (
				SELECT `product_id` FROM `products_cases` WHERE `case_id` = '" .$this->id. "')";

		$result = $this->db->query($query, true, "  Error executing query: ");
		$parts = array();

		while ($row =  $this->db->fetchByAssoc($result)) {
			array_push($parts, $row);
		}

		return $parts;
	}

	function getContactInfo() {
		$ret = null;
		if($this->cust_contact_id) {
			$contact = new Contact;
			if($contact->retrieve($this->cust_contact_id))
				$ret = array(
					'name' => trim($contact->first_name . ' ' . $contact->last_name),
					'email' => $contact->email1,
				);
		} else {
			$acct = new Account;
			$contact = new Contact;
			if($acct->retrieve($this->account_id)) {
				if($acct->primary_contact_id && $contact->retrieve($acct->primary_contact_id))
					$ret = array(
						'name' => trim($contact->first_name . ' ' . $contact->last_name),
						'email' => $contact->email1,
					);
				else if($acct->email1)
					$ret = array(
						'name' => $acct->name,
						'email' => $acct->email1,
					);
			}
		}
		return $ret;
	}

    static function get_effort_actual($effort = -1, $unit = -1) {
        global $app_list_strings;

        if(isset($effort) && $effort > 0) {
            if($unit == 'hours') {
                $hours = floor($effort);
                $ret = sprintf('%d %s', $hours, $app_list_strings['task_effort_unit_dom']['hours']);
                if(($minutes = round(($effort - $hours) * 60)) > 0)
                    $ret .= sprintf(' %02d %s', $minutes, $app_list_strings['task_effort_unit_dom']['minutes']);
                return $ret;
            }
            else if($unit == 'minutes') {
                $minutes = $effort % 60;
                $hours = floor(($effort - $minutes) / 60);
                $ret = '';
                if($hours)
                    $ret = sprintf('%d %s ', $hours, $app_list_strings['task_effort_unit_dom']['hours']);
                $ret .= sprintf('%02d %s', $minutes, $app_list_strings['task_effort_unit_dom']['minutes']);
                return $ret;
            }
            $unit = $app_list_strings['task_effort_unit_dom'][$unit];
            return format_number($effort) . ' ' . $unit;
        }

        return '';
    }
	
	static function format_effort_actual($spec)
	{
        $effort_actual = array_get_default($spec['raw_values'], 'effort_actual');
        $effort_actual_unit = array_get_default($spec['raw_values'], 'effort_actual_unit', 'hours');
		return self::get_effort_actual($effort_actual, $effort_actual_unit);
	}

    static function calc_activity_time($spec) {
        $effort_actual = array_get_default($spec['raw_values'], 'effort_actual');
        $effort_actual_unit = array_get_default($spec['raw_values'], 'effort_actual_unit', 'hours');
        $travel_time = array_get_default($spec['raw_values'], 'travel_time');
		$travel_time_unit = 'minutes';
        $minutes_in_hour = 60;
        $hours_in_day  = 24;

        if ($effort_actual_unit == 'hours') {
            $hours = floor($effort_actual);
            $minutes = round(($effort_actual - $hours) * $minutes_in_hour);
            $effort_actual = ($hours * $minutes_in_hour) + $minutes;
        }

        if ($travel_time_unit == 'hours') {
            $hours = floor($travel_time);
            $minutes = round(($travel_time - $hours) * $minutes_in_hour);
            $travel_time = ($hours * $minutes_in_hour) + $minutes;
        } elseif ($travel_time_unit == 'days') {
            $travel_time = ($travel_time * $hours_in_day * $minutes_in_hour);
        }

        $activity_time = $effort_actual + $travel_time;
        $activity_time = self::get_effort_actual($activity_time, 'minutes');

        return $activity_time;
    }

    static function add_view_popups(DetailManager $mgr) {
        require_bean('Account');
        Account::add_account_popup($mgr->getRecord(), 'account_id', 'service');
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $request_fields = array('asset_id', 'asset_name');
        foreach($request_fields as $f) {
            if(isset($input[$f]))
                $update[$f] = $input[$f];
        }
        
        if(! empty($input['contact_id']))
        	$update['cust_contact_id'] = $input['contact_id'];

        if (! empty($input['asset_id'])) {
            $lq = new ListQuery('SerialNumber', array('id', 'serial_no'));
            $lq->addSimpleFilter('asset_id', $input['asset_id']);
            $result = $lq->fetchAll();
            if($result) {
                foreach($result->rows as &$r) {
                    if (! empty($r['serial_no'])) {
                        $update['asset_serial_no'] = $r['serial_no'];
                        break;
                    }
                }
            }
        }

        $subc_to_case_fields = array(
            'id' => 'contract_id',
            'account_id' => 'account_id',
            'customer_contact_id' => 'cust_contact_id',
            'customer_contact_phone' => 'cust_phone_no',
        );

        $contract_id = null;
        if(isset($input['subcontract_id'])) {
            $contract_id = $input['subcontract_id'];
        } elseif (isset($input['contract_id'])) {
            $contract_id = $input['contract_id'];
        }

        if(! empty($contract_id)) {
            $subcontract = ListQuery::quick_fetch_row('SubContract', $contract_id);
            if($subcontract != null) {
                foreach($subc_to_case_fields as $scf => $cf) {
                    if (isset($subcontract[$scf]))
                        $update[$cf] = $subcontract[$scf];
                }
            }
        }

        if (! empty($subcontract['main_contract_id'])) {
            $main_contract = ListQuery::quick_fetch_row('Contract', $subcontract['main_contract_id'], array('account_id'));

            if (isset($main_contract['account_id']))
                $update['account_id'] = $main_contract['account_id'];
        }

        if (! empty($update['cust_contact_id'])) {
            $contact = ListQuery::quick_fetch_row('Contact', $update['cust_contact_id'], array('phone_work', 'primary_account_id'));

            if (isset($contact['phone_work']))
                $update['cust_phone_no'] = $contact['phone_work'];
			$update['account_id'] = $contact['primary_account_id'];
        }

        $update['effort_actual_unit'] = 'hours';
        $update['travel_time_unit'] = 'hours';

        $user_id = AppConfig::setting('company.case_queue_user');
	      $user_name = get_assigned_user_name($user_id);
        if (!empty($user_name) && !empty($user_id)) $update['assigned_user_id'] = $user_id;

        $fields = array('priority', 'name', 'description', 'account_id', 'status');
        $field = null;

        foreach($fields as $field) {
            if (isset($input[$field]))
                $update[$field] = $input[$field];
        }

        $upd->set($update);
    }

    static function init_from_email(RowUpdate &$upd, $input) {
        if (array_get_default($input, 'return_module') == 'Emails') {
            $email_id = array_get_default($input, 'return_record');
            $email = ListQuery::quick_fetch('Email', $email_id, array('name', 'description', 'contact'));

            if ($email) {
                $update = array('name' => $email->getField('name'), 'description' => $email->getField('description'),
                    'cust_contact_id' => $email->getField('contact_id'));

                if (! empty($update['cust_contact_id'])) {
                    $contact = ListQuery::quick_fetch_row('Contact', $update['cust_contact_id'], array('phone_work'));

                    if (isset($contact['phone_work']))
                        $update['cust_phone_no'] = $contact['phone_work'];
                }

                $upd->set($update);
            }
        }
    }

    static function before_save(RowUpdate $upd) {
    	$status = $upd->getField('status');
		if(substr($status, 0, 8) == 'Closed -') {
			if(! $upd->getField('date_closed'))
				$upd->set('date_closed', date('Y-m-d'));
		} else
			$upd->set('date_closed', null);
		if (!$upd->getField('travel_time_unit'))
			$upd->set('travel_time_unit', 'minutes');
    }
    
    static function after_save(RowUpdate $upd) {
    	$acc_id = $upd->getField('account_id');
    	if($acc_id && $upd->getFieldUpdated('account_id')) {
    		$upd->addUpdateLink('accounts', $acc_id);
    	}

    	$ctc_id = $upd->getField('cust_contact_id');
    	if($ctc_id && $upd->getFieldUpdated('cust_contact_id')) {
    		$upd->addUpdateLink('contacts', $ctc_id);
    	}

        if (array_get_default($_REQUEST, 'return_module') == 'Emails')
            self::set_email_parent($upd);
    }

    static function get_contacts_list($id) {
        global $db;

        $query = "SELECT contacts.id, contacts.first_name, contacts.last_name, contacts.email1, contacts.email2 FROM contacts
            INNER JOIN contacts_cases ON (contacts.id = contacts_cases.contact_id AND contacts_cases.case_id='$id') WHERE contacts_cases.deleted=0 AND contacts.deleted=0";

        $result = $db->query($query, true);
        $list = array();

        while($row = $db->fetchByAssoc($result)) {
            $contact = array(
                'id' => $row['id'],
                'full_name' => $row['first_name'] .' '. $row['last_name'],
                'receive_notifications' => 1,
                'email1' => $row['email1'],
                'email2' => $row['email2'],
                'user_name' => '',
            );

            $list[] = $contact;
        }

        return $list;
    }

    static function get_notification_recipients(NotificationManager $manager) {
        $users = array();
        $contacts = array();
        $load_users = false;
        $load_contacts = false;
        $is_update = ! $manager->updated_bean->new_record;

        if ($is_update && AppConfig::setting('notify.cases_user_update')) {
            $load_users = true;
        } elseif (! $is_update && AppConfig::setting('notify.cases_user_create')) {
            $load_users = true;
        }

        if ($is_update && AppConfig::setting('notify.cases_contact_update')) {
            $load_contacts = true;
        } elseif (! $is_update && AppConfig::setting('notify.cases_contact_create')) {
            $load_contacts = true;
        }

        if ($load_users)
            $users = $manager->getRecipients(true);

        if ($load_contacts) {
            $case_id = $manager->updated_bean->getField('id');
            $contacts = aCase::get_contacts_list($case_id);
            $cust_contact_id = $manager->updated_bean->getField('cust_contact_id');

            if ($cust_contact_id) {
                $contact_fields = array('email1', 'email2', 'first_name', 'last_name');
                $cust_contact = ListQuery::quick_fetch_row('Contact', $cust_contact_id, $contact_fields);

                if ($cust_contact != null) {
                    $cust_contact['receive_notifications'] = 1;
                    $cust_contact['full_name'] = $cust_contact['first_name'] .' '. $cust_contact['last_name'];
                    $cust_contact['user_name'] = '';

                    $contacts[] = $cust_contact;
                }
            }
        }

        $list = array_merge($contacts, $users);

        return $list;
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'CASE_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'CASE_NUMBER' => array('field' => 'case_number'),
            'CASE_PRIORITY' => array('field' => 'priority'),
            'CASE_STATUS' => array('field' => 'status'),
            'CASE_DESCRIPTION' => array('field' => 'description'),
            'CASE_RESOLUTION' => array('field' => 'resolution'),
        );

        if ($upd->new_record) {
            $template_name = 'CaseCreate';
        } else {
            $template_name = 'CaseUpdate';
        }

        $manager = new NotificationManager($upd, $template_name, $vars);
        $manager->sendMails();
    }
	static function update_time_used($id)
	{
		$caseResult = ListQuery::quick_fetch('aCase', $id);
		if (!$caseResult)
			return;
		
		$total = 0;

		$lq = new ListQuery('aCase', null, array('link_name' => 'booked_hours'));
		$lq->setParentKey($id);
		$lq->addSimpleFilter('status', 'approved');
		$result = $lq->fetchAll();
		
        if($result)
			foreach($result->rows as &$r)
				$total += $r['quantity'];

		$caseUpdate = RowUpdate::for_result($caseResult);
		$caseUpdate->set('effort_actual', $total);
		$caseUpdate->set('effort_actual_unit', 'minutes');
		$caseUpdate->save();

	}

    static function get_activity_status(RowUpdate $upd) {
        $status = null;
        $new_case_status = $upd->getField('status');
        $orig_case_status = $upd->getField('status', null, true);

        if (substr($new_case_status, 0, 8) == 'Closed -' && substr($orig_case_status, 0, 8) != 'Closed -') {
            $status = 'closed';
        } elseif (substr($new_case_status, 0, 8) == 'Active -' && substr($orig_case_status, 0, 8) == 'Closed -') {
            $status = 'reopened';
        }

        return $status;
    }
}
?>
