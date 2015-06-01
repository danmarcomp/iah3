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
require_once 'modules/Workflow/WFException.php';

class WFOperation extends SugarBean {

	var $id;
	// parent workflow id
	var $workflow_id;
	var $assigned_user_id;

	var $operation_type;
	var $performed_before_event;

	var $notification_invitee_ids;
	var $notification_invitee_types;
	var $notification_invitee_names;
	var $notification_subject;
	var $notification_content;
	var $notification_status;
	var $notification_direction;
	var $notification_start_date;
	var $notification_start_date_choice;
	var $notification_start_time_hour;
	var $notification_start_time_min;
	var $notification_duration_hour;
	var $notification_duration_min;
	var $include_initiator;
	var $include_contact;
	var $include_primary_email;
	var $include_secondary_email;
	var $notification_cc_mailbox;

	// task
	var $task_due_date;
	var $task_due_date_choice;
	var $task_due_time_hour;
	var $task_due_time_min;
	var $task_due_date_flag; //on or off
	var $task_start_date_flag; //on or off
	var $task_status;
	var $task_priority;
	var $task_contact_id;
	var $task_contact_name;
	var $task_est_effort;
	var $task_est_effort_unit;

	// data manipualtion
	var $dm_module_name;
	var $dm_field_name;
	var $dm_field_value;

	var $module_dir = 'Workflow';
	var $object_name = 'WFOperation';
	var $table_name = 'workflow_operation';

	function validate($trigger_action) {
		if (empty($this->operation_type) || ($this->performed_before_event != '0' && $this->performed_before_event != 1)) {
			throw new WFException(WFException::ERR_INVALID_TYPE);
		}

		switch ($this->operation_type) {
			case 'sendEmail':
				if (empty($this->notification_invitee_ids) && empty($this->include_initiator) && empty($this->include_primary_email) && empty($this->include_secondary_email)) {
					throw new WFException(WFException::ERR_EMPTY_RECIPIENTS);
				}
				if (empty($this->notification_subject)) {
					throw new WFException(WFException::ERR_EMPTY_SUBJECT);
				}
				if (empty($this->notification_content)) {
					throw new WFException(WFException::ERR_EMPTY_MESSAGE);
				}
				break;

			case 'scheduleMeeting':
			case 'scheduleCall':
				if (empty($this->notification_invitee_ids) && !$this->include_contact) {
					throw new WFException(WFException::ERR_EMPTY_INVITEES);
				}
				if (empty($this->notification_subject)) {
					throw new WFException(WFException::ERR_EMPTY_SUBJECT);
				}

				if (empty($this->notification_start_date_choice)) {
					throw new WFException(WFException::ERR_INVALID_DATE);
				}

				if ($this->notification_start_date_choice == 'C3' && empty($this->notification_start_date)) {
					throw new WFException(WFException::ERR_INVALID_DATE);
				}

				break;

			case 'updateCurrentData':
				if (empty($this->dm_field_name)) {
					throw new WFException(WFException::ERR_NO_UPDATE_FIELD);
				}
				if ($trigger_action == 'deleted') {
					throw new WFException(WFException::ERR_NO_UPDATE_DELETED);
				}
				break;

			case 'updateRelatedData':
				if (empty($this->dm_module_name)) {
					throw new WFException(WFException::ERR_NO_UPDATE_MODULE);
				}
				if (empty($this->dm_field_name)) {
					throw new WFException(WFException::ERR_NO_UPDATE_FIELD);
				}
				if ($trigger_action == 'deleted') {
					throw new WFException(WFException::ERR_NO_UPDATE_DELETED);
				}
				break;

			case 'createTask':
				if (empty($this->notification_subject)) {
					throw new WFException(WFException::ERR_EMPTY_SUBJECT);
				}
				if (empty($this->notification_content)) {
					throw new WFException(WFException::ERR_EMPTY_MESSAGE);
				}
				if (empty($this->task_priority)) {
					throw new WFException(WFException::ERR_EMPTY_PRIORITY);
				}
				if (empty($this->task_status)) {
					throw new WFException(WFException::ERR_EMPTY_STATUS);
				}
				break;

			case 'showAlert':
				if (empty($this->notification_content)) {
					throw new WFException(WFException::ERR_EMPTY_MESSAGE);
				}
				break;

			default:
				throw new WFException(WFException::ERR_INVALID_TYPE);
		}

		return true;
	}

}

?>
