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

require_once 'modules/Workflow/Workflow.php';
require_once 'modules/Emails/Email.php';
require_once 'modules/Calls/Call.php';
require_once 'modules/Meetings/Meeting.php';
require_once 'include/database/ListQuery.php';
require_once 'include/database/RowResult.php';
require_once 'include/database/RowUpdate.php';

class WorkflowProcessor {
	
	function process(&$rowUpdate, $event, $workflow = null, $owner=null) {
		global $db;
		if (defined('IAH_IN_INSTALLER')) return false;
		$module = $rowUpdate->getModuleDir();
		if (AppConfig::module_primary_bean($module) != $rowUpdate->getModelName())
			return;
		if (!empty($rowUpdate->prohibit_workflow) || $module == 'Workflow') {
			return true;
		}

		$isUpdate = (int)!$rowUpdate->new_record;
		$rowId = $rowUpdate->getPrimaryKeyValue();
		$temp = explode('_', $event);
		$timing = $temp[0];
		$action = $temp[1];
		if ($action == 'save') {
			if (!empty($rowId)) {
				$action = "('saved', 'save', 'changedTo', 'changedFrom')";
			} else {
				$action = "('saved', 'save')";				
			}
		} else {
			$action = "('deleted')";	
		}

		if (empty($workflow)) {
			$sql = "select id,name from workflow where workflow.trigger_module = '"
				.$module."' and workflow.trigger_action in "
				.$action." and workflow.deleted = 0 and workflow.status = 'active'";
			$sql .= " AND occurs_when != 'time' ";
			$res = $db->query($sql);
			// Initialize the workflow flag
			$bWorkflowApplied = false;
			while ($row = $db->fetchByAssoc($res)) {
				$GLOBALS['log']->debug('workflow found: '.$row['name']);
				
				$workflow = new Workflow();
				$workflow->retrieve($row['id']);
				$workflow->lazy_load();

				$applicable_operations = $workflow->is_applicable($rowUpdate, $timing, $workflow->trigger_action, $isUpdate);

				if ($applicable_operations) {
					self::executeOperations($workflow, $rowUpdate, $event, $applicable_operations);
					// Successfully applied a workflow
					$bWorkflowApplied = true;
				} else {
					$GLOBALS['log']->info('No workflow applied.');
				}
				$workflow->cleanup();
			}
		} else {
			self::executeOperations($workflow, $rowUpdate, $event, $workflow->operations);
			$bWorkflowApplied = true;
		}
		// Return the result
		return $bWorkflowApplied;
	}
	
	function sendEmail(&$rowUpdate, &$opt) {
		$send_from_system = ! AppConfig::setting('notify.send_from_assigning_user');
		$system_from_addr = AppConfig::setting('notify.from_address', '');
		$system_from_name = AppConfig::setting('notify_from_name', '');
		
		$module = $rowUpdate->getModuleDir();
		$rowId = $rowUpdate->getPrimaryKeyValue();

		require_once 'modules/Contacts/Contact.php';
		$ct_tpl = new Contact;

		$ids = preg_split('/\s*;\s*/', $opt->notification_invitee_ids);
		$types = preg_split('/\s*;\s*/', $opt->notification_invitee_types);

		$initiator = new User;
		$initiator->retrieve($opt->_owner);

		if ($opt->include_initiator) {
			array_unshift($ids, $initiator->id);
			array_unshift($types, 'Users');
		}

		if ($opt->include_primary_email) {
			if ($module == 'Accounts' || $module == 'Contacts' || $module == 'Leads') {
				array_unshift($ids, $rowId);
				array_unshift($types, $module);
			} elseif ($module == 'SubContracts') {
				require_once 'modules/Service/Contract.php';
				$con = new Contract;
				$con->retrieve($rowUpdate->getField('main_contract_id'));
				array_unshift($ids, $con->account_id);
				array_unshift($types, 'Accounts');
			} elseif ($module == 'Invoice' || $module == 'Quotes' || $module == 'SalesOrders') {
				array_unshift($ids, $rowUpdate->getField('billing_account_id'));
				array_unshift($types, 'Accounts');
			} elseif ($module == 'Bills') {
				array_unshift($ids, $rowUpdate->getField('supplier_id'));
				array_unshift($types, 'Accounts');
			} elseif ($module == 'Shipping') {
				array_unshift($ids, $rowUpdate->getField('shipping_account_id'));
				array_unshift($types, 'Accounts');
			} elseif ($module == 'Receiving') {
				array_unshift($ids, $rowUpdate->getField('supplier_id'));
				array_unshift($types, 'Accounts');
			} elseif ($module == 'Cases') {
				require_once 'modules/Service/Contract.php';
				$cust_contact_id = $rowUpdate->getField('cust_contact_id');
				if (!empty($cust_contact_id)) {
					array_unshift($ids, $cust_contact_id);
					array_unshift($types, 'Contacts');
				}
				$lq = new ListQuery('aCase', null, array('link_name' => 'contacts'));
				$lq->addFields(array('id'));
				$lq->setParentKey($rowId);
				$result = $lq->runQuery();
				foreach ($lq->getResultIds($result) as $cid) {
					array_unshift($ids, $cid);
					array_unshift($types, 'Contacts');
				}
			}
		}

		if ($opt->include_secondary_email) {
			if ($module == 'Invoice' || $module == 'Quotes' || $module == 'SalesOrders') {
				array_unshift($ids, $rowUpdate->getField('billing_contact_id'));
				array_unshift($types, 'Contacts');
			} elseif ($module == 'Bills') {
				array_unshift($ids, $rowUpdate->getField('supplier_contact_id'));
				array_unshift($types, 'Contacts');
			} elseif ($module == 'Shipping') {
				array_unshift($ids, $rowUpdate->getField('shipping_contact_id'));
				array_unshift($types, 'Contacts');
			} elseif ($module == 'Receiving') {
				array_unshift($ids, $rowUpdate->getField('supplier_contact_id'));
				array_unshift($types, 'Contacts');
			}
		}

		foreach ($ids as $i => $id) {
			if (empty($types[$i])) continue;
			$type = $types[$i];
			$contact_model = AppConfig::module_primary_bean($type);
			if (empty($id) || !$contact_model) {
				continue;
			}
			$contact = ListQuery::quick_fetch_row($contact_model, $id);
			if (!$contact) continue;
			if (empty($contact['email1'])) continue;

			$email = new Email();

			if (empty($opt->notification_from_email)) {
				if($send_from_system || empty($initiator->email1)) {
					$from_name = $system_from_name;
					$from_addr = $system_from_addr;
				}
				else {
					$from_name = $initiator->name;
					$from_addr = $initiator->email1;
				}
			} else {
				$from_name = $opt->notification_from_name;
				$from_addr = $opt->notification_from_email;
			}

			$email->from_name = $from_name;
			$email->from_addr = $from_addr;
			
			$addr_arr = array(
				array('email' => $contact['email1']),
			);
		
			$email->to_addrs_arr = $addr_arr;

			$cc_addrs = array();
			if (!empty($opt->notification_cc_mailbox)) {
				require_once 'modules/EmailPOP3/EmailPOP3.php';
				$box = new EmailPOP3;
				if ($box->retrieve($opt->notification_cc_mailbox) && !$box->deleted) {
					$cc_addrs[] = array('email' => $box->email);
				}
			}
			
			$email->type = 'sent';
			$email->isread = 'read';
			$email->bcc_addrs_arr = array();
			$email->cc_addrs_arr = $cc_addrs;
			$email->description_html = from_html($opt->notification_content);
			$email->name = $opt->notification_subject;	
			$email->parent_type = $module;
			$email->parent_id = $rowId;
			$email->assigned_user_id = $initiator->id;
			$email->set_created_by = $email->update_modified_by = false;
			$email->created_by = $email->assigned_user_id;
			$email->modified_user_id = $email->assigned_user_id;

			$objects = array(
				$module => $rowId,
				$type => $contact['id'],
				'Contacts' => $contact['id'],
			);

			require_once 'include/utils/html_utils.php';
			require_once 'modules/EmailTemplates/TemplateParser.php';

			$params = array('disable_cache' => true);

			list ($email->name, $description_html) = TemplateParser::parse_generic(
				array(
					$email->name,
					$email->description_html
				),
				$objects,
				$params
			);

			$email->setDescriptionHTML($description_html);

			if ($email->send()) {
				// Save the email
				$email->prohibit_workflow = true;
				$email->save();
				$emlResult = ListQuery::quick_fetch('Email', $email->id);
				$emlUpdate = RowUpdate::for_result($emlResult);
				if ($type == 'Accounts') {
					$emlUpdate->addUpdateLink('accounts', $contact['id']);
				}
				if ($type == 'Leads') {
					$emlUpdate->addUpdateLink('leads', $contact['id']);
				}
				if ($type == 'Contacts') {
					$emlUpdate->addUpdateLink('contacts', $contact['id']);
				}
			}
		}
	}
	
	function addInvitees(&$rowUpdate, &$focus, &$opt) {
		$ids = preg_split('/\s*;\s*/', $opt->notification_invitee_ids);
		$types = preg_split('/\s*;\s*/', $opt->notification_invitee_types);
		
		$module = $rowUpdate->getModuleDir();
		$rowId = $rowUpdate->getPrimaryKeyValue();
		
		$invitees = array();
		foreach($ids as $idx => $cid) {
			if(! isset($types[$idx])) break;
			$invitees[$types[$idx]][] = $cid;
		}

		if ($opt->include_contact && $module == 'Contacts') {
			$invitees['Contacts'][] = $rowId;
		}

		if(isset($invitees['Contacts'])) {
			foreach($invitees['Contacts'] as $contact_id) {
				$focus->addUpdateLink('contacts', $contact_id);
			}
		}
		if(isset($invitees['Users'])) {
			foreach($invitees['Users'] as $user_id) {
				$focus->addUpdateLink('users', $user_id);
			}
		}
		return $invitees;
	}
	
	function scheduleCall(&$rowUpdate, &$opt, $owner) {
		$user_id = $owner ? $owner : AppConfig::current_user_id();
		$module = $rowUpdate->getModuleDir();
		$rowId = $rowUpdate->getPrimaryKeyValue();

		$call = RowUpdate::blank_for_model('Call');
		$call_values = array(
			'description' => $opt->notification_content,
			'name' => $opt->notification_subject,
			'duration_hours' => $opt->notification_duration_hour,
			'duration_minutes' => $opt->notification_duration_min,
			'assigned_user_id' => $user_id,
			'created_by' => $user_id,
			'modified_user_id' => $user_id,
			'parent_type' => $module,
			'parent_id' => $rowId,
			'status' => 'Planned',
			'direction' => 'Outbound',
		);
		
		switch ($opt->notification_start_date_choice) {
			case 'C1':
				$call_values['date_start'] = self::create_date_time(0, $opt->notification_start_time_hour, $opt->notification_start_time_min);
				break;
				
			case 'C2':
				$call_values['date_start'] = self::create_date_time(1, $opt->notification_start_time_hour, $opt->notification_start_time_min);
				break;
				
			case 'C3':
				$call_values['date_start'] = self::create_date_time($opt->notification_start_date, $opt->notification_start_time_hour, $opt->notification_start_time_min);
		}

		$call->set($call_values);
		$return_id = null;
		$call->prohibit_workflow = true;
		if ($call->save())
			$return_id = $call->getPrimaryKeyValue();
		
		if($return_id)
			self::addInvitees($rowUpdate, $call, $opt);

		return $return_id;
	}
	
	function scheduleMeeting(&$rowUpdate, &$opt, $owner) {
		$meeting = RowUpdate::blank_for_model('Meeting');
		$user_id = $owner ? $owner : AppConfig::current_user_id();
		$module = $rowUpdate->getModuleDir();
		$rowId = $rowUpdate->getPrimaryKeyValue();

		$mtg_values = array(
			'description' => $opt->notification_content,
			'name' => $opt->notification_subject,
			'duration_hours' => $opt->notification_duration_hour,
			'duration_minutes' => $opt->notification_duration_min,
			'assigned_user_id' => $user_id,
			'created_by' => $user_id,
			'modified_user_id' => $user_id,
			'parent_type' => $module,
			'parent_id' => $rowId,
			'status' => 'Planned',
		);
	
		switch ($opt->notification_start_date_choice) {
			case 'C1':
				$mtg_values['date_start'] = self::create_date_time(0, $opt->notification_start_time_hour, $opt->notification_start_time_min, $user_id);
				break;
				
			case 'C2':
				$mtg_values['date_start'] = self::create_date_time(1, $opt->notification_start_time_hour, $opt->notification_start_time_min, $user_id);
				break;
				
			case 'C3':
				$mtg_values['date_start'] = self::create_date_time($opt->notification_start_date, $opt->notification_start_time_hour, $opt->notification_start_time_min, $user_id);
		}


		$meeting->set($mtg_values);
		$return_id = null;
		$meeting->prohibit_workflow = true;
		if ($meeting->save())
			$return_id = $meeting->getPrimaryKeyValue();
		
		if($return_id)
			self::addInvitees($rowUpdate, $meeting, $opt);

		return $return_id;
	}

	function createTask(&$rowUpdate, &$opt, $owner) {
		$task = RowUpdate::blank_for_model('Task');
		$user_id = $owner ? $owner : AppConfig::current_user_id();
		$module = $rowUpdate->getModuleDir();
		$rowId = $rowUpdate->getPrimaryKeyValue();
		
		$task_values = array(	
			'name' => $opt->notification_subject,
			'description' => $opt->notification_content,
			'status' => $opt->task_status,
			'priority' => $opt->task_priority,
			'contact_id' => $opt->task_contact_id,
			'contact_name' => $opt->task_contact_name,
			'date_due_flag' => $opt->task_due_date_flag,
			'date_start_flag' => $opt->task_start_date_flag,
			'effort_estim' => $opt->task_est_effort,
			'effort_estim_unit' => $opt->task_est_effort_unit,
			'parent_type' => $module,
			'parent_id' => $rowId,
			'assigned_user_id' => $user_id,
			'created_by' => $user_id,
			'modified_user_id' => $user_id,
		);
		if (!$opt->task_due_date_flag || $opt->task_due_date_flag == 'off') {
			switch ($opt->task_due_date_choice) {
				case 'C1':
					$task_values['date_due'] = self::create_date_time(0, $opt->task_due_time_hour, $opt->task_due_time_min, $user_id);
					break;
					
				case 'C2':
					$task_values['date_due'] = self::create_date_time(1, $opt->task_due_time_hour, $opt->notification_start_time_min, $user_id);
					break;
					
				case 'C3':
					$task_values['date_due'] = self::create_date_time($opt->task_due_date, $opt->task_due_time_hour, $opt->task_due_time_min, $user_id);
			}
		}


		if (!$opt->task_start_date_flag || $opt->task_start_date_flag == 'off')  {
			switch ($opt->notification_start_date_choice) {
				case 'C1':
					$task_values['date_start'] = self::create_date_time(0, $opt->notification_start_time_hour, $opt->notification_start_time_min, $user_id);
					break;
					
				case 'C2':
					$task_values['date_start'] = self::create_date_time(1, $opt->notification_start_time_hour, $opt->notification_start_time_min, $user_id);
					break;
					
				case 'C3':
					$task_values['date_start'] = self::create_date_time($opt->notification_start_date, $opt->notification_start_time_hour, $opt->notification_start_time_min, $user_id);
					break;
			}
		}


		$task->set($task_values);	
		$task->prohibit_workflow = true;
		$task->save();
	}

	function create_date_time($days, $hours, $minutes, $as_user = null)
	{
		global $timedate;
		$timestamp = time();
		$offset = $timedate->get_seconds_offset($timestamp, true, $as_user);
		$date = gmdate('Y-m-d 00:00:00', $timestamp + $offset);
		$dateStr = $date . ' GMT +' . (int)$days . ' DAYS ' . (int)$hours . ' HOURS +' . (int)$minutes . ' MINUTES';
		$date = gmdate('Y-m-d H:i', strtotime($dateStr) - $offset);
		return $date;
	}

	static function after_delete(&$rowUpdate)
	{
		self::process($rowUpdate, 'after_delete');
	}
	
	static function before_save(&$rowUpdate)
	{
		self::process($rowUpdate, 'before_save');
	}
	
	static function after_save(&$rowUpdate)
	{
		self::process($rowUpdate, 'after_save');
		self::audit_data($rowUpdate);
	}

	static function audit_data(&$rowUpdate)
	{
		if (defined('IAH_IN_INSTALLER')) return;
		if (!empty($rowUpdate->prohibit_workflow))
			return;
		global $current_user, $db;
		$module = $rowUpdate->model->module_dir;
		if (AppConfig::module_primary_bean($module) != $rowUpdate->model->name)
			return;
		static $criteria = array();
		if (!isset($criteria[$module])) {
			$criteria[$module] = array();
			$query = "SELECT c.* FROM workflow_criteria c LEFT JOIN workflow w ON w.id=c.workflow_id WHERE w.occurs_when = 'time' AND w.trigger_module = '{$module}' AND w.deleted = 0 AND c.deleted = 0 AND w.status='active'";
			$res = $db->query($query, true);
			while ($row = $db->fetchByAssoc($res)) {
				$criteria[$module][] = $row;
			}
		}
		$isUpdate = (int)!$rowUpdate->new_record;
		foreach ($criteria[$module] as $row) {
			$fname = $row['field_name'];
			$value = $db->quote($rowUpdate->getField($fname));
			if (empty($value)) {
				$val = "''";
				$cond = " = '' ";
			} else {
				$val = "'{$value}'";
				$cond = " = '{$value}'";
			}
			$id = $rowUpdate->getPrimaryKeyValue();
			$query = "SELECT related_type FROM workflow_data_audit WHERE related_type = '{$module}' AND related_id = '{$id}' AND field_name = '$fname' AND deleted = 0 AND field_value $cond ";
			$res1 = $db->query($query, true);
			$row1 = $db->fetchByAssoc($res1);
			if (!$row1) {
				$query = "REPLACE INTO workflow_data_audit SET related_type='{$module}', related_id = '{$id}', field_name = '$fname', field_value= $val, date_modified = '" . gmdate('Y-m-d H:i:s') . "', deleted = 0, is_update = $isUpdate, modified_user_id='{$current_user->id}'";
				$db->query($query, true);
				$query = "DELETE FROM workflow_data_audit_link WHERE related_id = '{$id}' AND workflow_id='{$row['workflow_id']}'";
				$db->query($query, true);
			}
		}
	}

	function getTimeCondition($coded, $now, $sql_op)
	{
		$m = array();
		if (preg_match('/^(\d+)([hdwmy])$/', $coded, $m)) {
			switch ($m[2]) {
				case 'h':
					$interval = 'HOUR';
					break;
				case 'd':
					$interval = 'DAY';
					break;
				case 'w':
					$interval = 'WEEK';
					break;
				case 'm':
					$interval = 'MONTH';
					break;
				case 'y':
					$interval = 'YEAR';
					break;
			}
			$condition = $sql_op . "('$now', INTERVAL " . $m[1] . ' ' . $interval . ')';
			return $condition;
		} else {
			return 'NULL';
		}
	}


	function executeOperations($workflow, $rowUpdate, $event, $operations, $owner = null) 
	{
		$user = new User;
		$user->retrieveAdminUser();
		foreach ($operations as $idx=>$opt) {
			if ($workflow->operation_owner == 'trigger') {
				$user_id = $owner ? $owner : AppConfig::current_user_id();
			} elseif ($workflow->operation_owner == 'owner') {
				$user_id = $rowUpdate->getField('assigned_user_id');
				if (!$user_id)
					$user_id = $rowUpdate->getField('created_by');
				if (!$user_id)
					$user_id = AppConfig::current_user_id();
			} else {
				$user_id = $user->id;
			}	

			$GLOBALS['log']->debug('operation type: '.$opt->operation_type);
			$opt->_owner = $user_id;
		
			switch ($opt->operation_type) {
				case 'sendEmail':
					self::sendEmail($rowUpdate, $opt);
					break;
					
				case 'showAlert':
					if ($workflow->occurs_when != 'time') {
						add_flash_message($opt->notification_content, 'info');
					}
					break;
					
				case 'scheduleMeeting':
					self::scheduleMeeting($rowUpdate, $opt, $user_id);
					break;
					
				case 'scheduleCall':
					self::scheduleCall($rowUpdate, $opt, $user_id);
					break;
					
				case 'updateCurrentData':
					$GLOBALS['log']->debug('dm_field_name: '.$opt->dm_field_name);
					$GLOBALS['log']->debug('dm_field_value: '.$opt->dm_field_value);
					
					$fname = $opt->dm_field_name;
					$rowUpdate->set($fname, $opt->dm_field_value);
					if ($user_id) {
						$rowUpdate->set('modified_user_id', $user_id);
					}
					// Save the updated data
					if($event != 'before_save') {
						$rowUpdate->prohibit_workflow = true;
						$rowUpdate->save();
					}
					break;
					
				case 'updateRelatedData':
					$lname = $opt->dm_link_name;
					$mname = $opt->dm_module_name;
					$fname = $opt->dm_field_name;


					$def = $rowUpdate->getFieldDefinition($lname);
					if (!$def)
						break;
					$id_name = array_get_default($def, 'id_name', $lname . '_id');
					$linkedID = $rowUpdate->getField($id_name);

	
					if (empty($linkedID))
						break;
					$model = AppConfig::module_primary_bean($mname);
					if (!$model) 
						break;
					$relatedResult = ListQuery::quick_fetch($model, $linkedID);
					if (!$relatedResult)
						break;

					$relatedUpdate = RowUpdate::for_result($relatedResult);
					$relatedUpdate->set($fname, $opt->dm_field_value);
					if ($user_id) {
						$relatedUpdate->set('modified_user_id', $user_id);
					}
					$relatedUpdate->prohibit_workflow = true;
					$relatedUpdate->save();
					break;
					
				case 'createTask':
					self::createTask($rowUpdate, $opt, $user_id);
					break;
				default:
					$GLOBALS['log']->error("Unknown operation type: ".$opt->operation_type);
			}
		}
	}
}


?>
